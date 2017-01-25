<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\event;

class listener_test extends \phpbb_test_case
{
	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\controller\helper */
	protected $helper;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\ideas\factory\ideas */
	protected $ideas;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\ideas\factory\linkhelper */
	protected $link_helper;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $php_ext;

	/**
	 * Setup test environment
	 */
	public function setUp()
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;

		// Load/Mock classes required by the event listener class
		$this->auth = $this->getMock('\phpbb\auth\auth');
		$this->config = new \phpbb\config\config(array('ideas_forum_id' => 2, 'ideas_poster_id' => 2));
		$this->helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();
		$this->helper->expects($this->any())
			->method('route')
			->willReturnCallback(function ($route, array $params = array()) {
				return $route . '#' . serialize($params);
			});
		$this->ideas = $this->getMockBuilder('\phpbb\ideas\factory\ideas')
			->disableOriginalConstructor()
			->getMock();
		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$this->lang = new \phpbb\language\language($lang_loader);
		$this->link_helper = $this->getMockBuilder('\phpbb\ideas\factory\linkhelper')
			->disableOriginalConstructor()
			->getMock();
		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();
		$this->user = new \phpbb\user($this->lang, '\phpbb\datetime');
		$this->php_ext = $phpEx;
	}

	/**
	 * Get the event listener
	 *
	 * @return \phpbb\ideas\event\listener
	 */
	protected function get_listener()
	{
		return new \phpbb\ideas\event\listener(
			$this->auth,
			$this->config,
			$this->helper,
			$this->ideas,
			$this->lang,
			$this->link_helper,
			$this->template,
			$this->user,
			$this->php_ext
		);
	}

	/**
	 * Test the event listener is constructed correctly
	 */
	public function test_construct()
	{
		$this->assertInstanceOf('\Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->get_listener());
	}

	/**
	 * Test the event listener is subscribing events
	 */
	public function test_getSubscribedEvents()
	{
		$this->assertEquals(array(
			'core.viewforum_get_topic_data',
			'core.viewtopic_modify_post_row',
			'core.viewtopic_modify_page_title',
			'core.viewtopic_add_quickmod_option_before',
			'core.viewonline_overwrite_location',
		), array_keys(\phpbb\ideas\event\listener::getSubscribedEvents()));
	}

	/**
	 * Data set for test_clean_message
	 *
	 * @return array Array of test data
	 */
	public function clean_message_data()
	{
		return array(
			array(1, 1, 1, 'Foo Bar', 'Foo Bar'), // Invalid forum, nothing cleaned
			array(2, 1, 2, 'Foo Bar', 'Foo Bar'), // Invalid post, nothing clean
			array(2, 1, 1, 'Foo Bar', 'Foo Bar'), // Valid post, nothing to clean
			array(2, 1, 1, 'Foo Bar<br />----------<br>BarFoo<br>', 'Foo Bar'), // Valid post, requires cleaning
			array(2, 1, 1, 'Foo Bar<br />\n\n----------<br>\nBarFoo<br>', 'Foo Bar'), // Valid post, requires cleaning
			array(2, 1, 1, 'Foo Bar<br />--------<br>BarFoo<br>', 'Foo Bar<br />--------<br>BarFoo<br>'), // Valid post, nothing to clean
		);
	}

	/**
	 * Test the clean_message event
	 *
	 * @dataProvider clean_message_data
	 */
	public function test_clean_message($forum_id, $post_id, $first_post_id, $message, $expected)
	{
		$listener = $this->get_listener();

		$event = new \phpbb\event\data(array(
			'row' 			=> array(
				'forum_id'	=> $forum_id,
				'post_id'	=> $post_id,
			),
			'post_row'		=> array('MESSAGE' => $message),
			'topic_data'	=> array('topic_first_post_id' => $first_post_id),
		));

		$listener->clean_message($event);

		$this->assertContains($expected, $event['post_row']['MESSAGE']);
	}

	/**
	 * Data set for test_viewonline
	 *
	 * @return array Array of test data
	 */
	public function viewonline_data()
	{
		global $phpEx;

		return array(
			// test when on_page is index
			array(
				array(
					1 => 'index',
				),
				array(),
				'$location_url',
				'$location',
				'$location_url',
				'$location',
			),
			// test when on_page is app and session_page is NOT for ideas
			array(
				array(
					1 => 'app',
				),
				array(
					'session_page' => 'app.' . $phpEx . '/foobar'
				),
				'$location_url',
				'$location',
				'$location_url',
				'$location',
			),
			// test when on_page is app and session_page is for ideas
			array(
				array(
					1 => 'app',
				),
				array(
					'session_page' => 'app.' . $phpEx . '/ideas'
				),
				'$location_url',
				'$location',
				'phpbb_ideas_index_controller#a:0:{}',
				'VIEWING_IDEAS',
			),
			// test when on_page is app and session_page is for ideas/post
			array(
				array(
					1 => 'app',
				),
				array(
					'session_page' => 'app.' . $phpEx . '/ideas/post'
				),
				'$location_url',
				'$location',
				'phpbb_ideas_index_controller#a:0:{}',
				'POSTING_NEW_IDEA',
			),
			// test when viewing an idea topic (any topic in forum id 2)
			array(
				array(
					1 => 'viewtopic',
				),
				array(
					'session_forum_id' => 2,
				),
				'$location_url',
				'$location',
				'phpbb_ideas_index_controller#a:0:{}',
				'VIEWING_IDEAS',
			),
			// test when viewing a normal topic (not an idea, so not in forum id 2)
			array(
				array(
					1 => 'viewtopic',
				),
				array(
					'session_forum_id' => 3,
				),
				'$location_url',
				'$location',
				'$location_url',
				'$location',
			),
		);
	}

	/**
	 * Test the viewonline event
	 *
	 * @dataProvider viewonline_data
	 */
	public function test_viewonline($on_page, $row, $location_url, $location, $expected_location_url, $expected_location)
	{
		$listener = $this->get_listener();

		$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
		$dispatcher->addListener('core.viewonline_overwrite_location', array($listener, 'viewonline_ideas'));

		$event_data = array('on_page', 'row', 'location_url', 'location');
		$event = new \phpbb\event\data(compact($event_data));
		$dispatcher->dispatch('core.viewonline_overwrite_location', $event);

		$event_data_after = $event->get_data_filtered($event_data);
		foreach ($event_data as $expected)
		{
			$this->assertArrayHasKey($expected, $event_data_after);
		}
		extract($event_data_after);

		$this->assertEquals($expected_location_url, $location_url);
		$this->assertEquals($expected_location, $location);
	}
}
