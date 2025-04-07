<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\event;

use phpbb\ideas\ext;

class listener_test extends \phpbb_test_case
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\controller\helper */
	protected $helper;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\ideas\factory\idea */
	protected $idea;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\ideas\factory\linkhelper */
	protected $link_helper;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $php_ext;

	/**
	 * Setup test environment
	 */
	protected function setUp(): void
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;

		// Load/Mock classes required by the event listener class
		$this->auth = $this->getMockBuilder('\phpbb\auth\auth')
			->disableOriginalConstructor()
			->getMock();
		$this->config = new \phpbb\config\config(array('ideas_forum_id' => 2));
		$this->helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();
		$this->idea = $this->getMockBuilder('\phpbb\ideas\factory\idea')
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
			$this->idea,
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
		self::assertInstanceOf('\Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->get_listener());
	}

	/**
	 * Test the event listener is subscribing events
	 */
	public function test_getSubscribedEvents()
	{
		self::assertEquals(array(
			'core.page_header',
			'core.viewforum_get_topic_data',
			'core.viewtopic_modify_post_row',
			'core.viewtopic_modify_page_title',
			'core.viewtopic_add_quickmod_option_before',
			'core.viewonline_overwrite_location',
			'core.posting_modify_template_vars',
			'core.posting_modify_submit_post_before',
			'core.posting_modify_submit_post_after',
		), array_keys(\phpbb\ideas\event\listener::getSubscribedEvents()));
	}

	public function global_template_vars_data()
	{
		return [
			'registered user' => [true, false, true],
			'unregistered user' => [false, false, false],
			'is bot user' => [true, true, false],
			'is bot guest' => [false, true, false],
		];
	}

	/**
	 * @dataProvider global_template_vars_data
	 */
	public function test_global_template_vars($is_registered, $is_bot, $expected)
	{
		$this->user->data['is_registered'] = $is_registered;
		$this->user->data['is_bot'] = $is_bot;

		$this->helper->expects($expected ? $this->once() : $this->never())
			->method('route')
			->willReturn('phpbb_ideas_list_controller');

		$this->template->expects($expected ? $this->once() : $this->never())
			->method('assign_var')
			->with('U_SEARCH_MY_IDEAS', 'phpbb_ideas_list_controller');

		$listener = $this->get_listener();
		$listener->global_template_vars();
	}

	public function show_idea_data()
	{
		return [
			[2, 10, true, true, true],
			[2, 10, false, true, true],
			[2, 10, false, false, true],
			[0, 10, false, false, false],
			[2, 0, false, false, false],
		];
	}

	/**
	 * @dataProvider show_idea_data
	 */
	public function test_show_idea($forum_id, $topic_id, $has_votes, $mod_auth, $expected)
	{
		$this->user->data['user_id'] = 2;

		$listener = $this->get_listener();

		$event = new \phpbb\event\data([
			'forum_id'		=> $forum_id,
			'topic_data'	=> ['topic_id' => $topic_id],
		]);

		// Assert that get_idea_by_topic_id() is called when the forum_id is correct
		// Also will return idea data if topic_id is valid, otherwise false
		$this->idea->expects($forum_id ? self::once() : self::never())
			->method('get_idea_by_topic_id')
			->with($topic_id)
			->willReturnMap([
				[0, false],
				[10, [
					 'topic_id' => $topic_id,
					 'idea_id' => 1,
					 'idea_author' => 0,
					 'idea_title' => '',
					 'idea_date' => 0,
					 'idea_votes_up' => (int) $has_votes,
					 'idea_votes_down' => (int) $has_votes,
					 'idea_status' => 1,
					 'duplicate_id' => 0,
					 'ticket_id' => 0,
					 'rfc_link' => '',
					 'implemented_version' => '',
				 ]],
			]);

		// Assert that get_voters() gets called if the idea being shown has votes
		$this->idea->expects($has_votes ? self::once() : self::never())
			->method('get_voters')
			->willReturn([[
				'user_id' => 2,
				'user' => 'admin',
				'vote_value' => (int) $has_votes,
			]]);

		// We need to stub the acl_get, returns true when the user is a moderator
		$this->auth->method('acl_get')
			->with(self::stringContains('_'), self::anything())
			->willReturnMap([
				['m_', $forum_id, $mod_auth],
			]);

		// Assert that moderator template vars are assigned when user is a moderator
		$this->template->expects($mod_auth ? self::once() : self::never())
			->method('assign_var')
			->with('STATUS_ARY', ext::$statuses);

		// Assert that the main idea template vars are called when we have an idea to show
		$this->template->expects($expected ? self::once() : self::never())
			->method('assign_vars');

		$listener->show_idea($event);
	}

	/**
	 * Data set for show_post_buttons
	 *
	 * @return array Array of test data
	 */
	public function show_post_buttons_data()
	{
		$post_row = array(
			'U_EDIT'   => true,
			'U_DELETE' => true,
			'U_REPORT' => true,
			'U_WARN'   => true,
			'U_INFO'   => true,
			'U_QUOTE'  => true,
		);

		return array(
			array(2, 1, 1, $post_row, false), // Valid
			array(1, 1, 1, $post_row, true), // Invalid forum
			array(2, 1, 2, $post_row, true), // Invalid post
		);
	}

	/**
	 * Test the show_post_buttons event
	 *
	 * @dataProvider show_post_buttons_data
	 */
	public function test_show_post_buttons($forum_id, $post_id, $first_post_id, $post_row, $expected)
	{
		$listener = $this->get_listener();

		$event = new \phpbb\event\data(array(
			'row' 			=> array(
				'forum_id'	=> $forum_id,
				'post_id'	=> $post_id,
			),
			'post_row'		=> $post_row,
			'topic_data'	=> array('topic_first_post_id' => $first_post_id),
		));

		$listener->show_post_buttons($event);

		self::assertEquals($expected, $event['post_row']['U_DELETE']);
		self::assertEquals($expected, $event['post_row']['U_WARN']);

		// These should always be true since we're not changing them
		self::assertTrue($event['post_row']['U_QUOTE']);
		self::assertTrue($event['post_row']['U_EDIT']);
		self::assertTrue($event['post_row']['U_REPORT']);
		self::assertTrue($event['post_row']['U_INFO']);
	}

	/**
	 * Data set for adjust_quickmod_tools
	 *
	 * @return array Array of test data
	 */
	public function adjust_quickmod_tools_data()
	{
		$quickmod_array = [
			'lock'          => [1 => true],
			'unlock'        => [1 => true],
			'delete_topic'  => [1 => true],
			'restore_topic' => [1 => true],
			'move'          => [1 => true],
			'split'         => [1 => true],
			'merge'         => [1 => true],
			'merge_topic'   => [1 => true],
			'fork'          => [1 => true],
			'make_normal'   => [1 => true],
			'make_sticky'   => [1 => true],
			'make_announce' => [1 => true],
			'make_global'   => [1 => true],
		];

		return [
			[2, $quickmod_array, false], // Valid
			[1, $quickmod_array, true], // Invalid forum
		];
	}

	/**
	 * Test the adjust_quickmod_tools event
	 *
	 * @dataProvider adjust_quickmod_tools_data
	 */
	public function test_adjust_quickmod_tools($forum_id, $quickmod_array, $expected)
	{
		$listener = $this->get_listener();

		$event = new \phpbb\event\data([
			'forum_id' 			=> $forum_id,
			'quickmod_array'	=> $quickmod_array,
		]);

		$listener->adjust_quickmod_tools($event);

		self::assertEquals($expected, $event['quickmod_array']['delete_topic'][1]);
		self::assertEquals($expected, $event['quickmod_array']['restore_topic'][1]);
		self::assertEquals($expected, $event['quickmod_array']['make_normal'][1]);
		self::assertEquals($expected, $event['quickmod_array']['make_sticky'][1]);
		self::assertEquals($expected, $event['quickmod_array']['make_announce'][1]);
		self::assertEquals($expected, $event['quickmod_array']['make_global'][1]);

		// These should always be true since we're not changing them
		self::assertTrue($event['quickmod_array']['lock'][1]);
		self::assertTrue($event['quickmod_array']['unlock'][1]);
		self::assertTrue($event['quickmod_array']['move'][1]);
		self::assertTrue($event['quickmod_array']['split'][1]);
		self::assertTrue($event['quickmod_array']['merge'][1]);
		self::assertTrue($event['quickmod_array']['merge_topic'][1]);
		self::assertTrue($event['quickmod_array']['fork'][1]);
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
		$this->helper->expects(self::atMost(1))
			->method('route')
			->willReturnCallback(function ($route, array $params = array()) {
				return $route . '#' . serialize($params);
			});

		$listener = $this->get_listener();

		$dispatcher = new \phpbb\event\dispatcher();
		$dispatcher->addListener('core.viewonline_overwrite_location', array($listener, 'viewonline_ideas'));

		$event_data = array('on_page', 'row', 'location_url', 'location');
		$event_data_after = $dispatcher->trigger_event('core.viewonline_overwrite_location', compact($event_data));
		extract($event_data_after, EXTR_OVERWRITE);

		self::assertEquals($expected_location_url, $location_url);
		self::assertEquals($expected_location, $location);
	}

	/**
	 * Data set for edit_idea_title
	 *
	 * @return array Array of test data
	 */
	public function edit_idea_title_data()
	{
		return array(
			array(
				array(
					'topic_id'       => 1,
					'post_id'        => 1,
					'forum_id'       => 2,
					'mode'           => 'edit',
					'update_subject' => true,
					'post_data'      => array(
						'topic_first_post_id' => 1,
						'post_subject'        => 'Foo Bar 1',
					),
				),
				'once',
			),
			array( // invalid posting mode
				array(
					'topic_id'       => 1,
					'post_id'        => 1,
					'forum_id'       => 2,
					'mode'           => 'post',
					'update_subject' => true,
					'post_data'      => array(
						'topic_first_post_id' => 1,
						'post_subject'        => 'Foo Bar 1',
					),
				),
				'never',
			),
			array( // subject not updated
				   array(
					   'topic_id'       => 1,
					   'post_id'        => 1,
					   'forum_id'       => 2,
					   'mode'           => 'edit',
					   'update_subject' => false,
					   'post_data'      => array(
						   'topic_first_post_id' => 1,
						   'post_subject'        => 'Foo Bar 1',
					   ),
				   ),
				   'never',
			),
			array( // wrong forum
				   array(
					   'topic_id'       => 1,
					   'post_id'        => 1,
					   'forum_id'       => 1,
					   'mode'           => 'edit',
					   'update_subject' => true,
					   'post_data'      => array(
						   'topic_first_post_id' => 1,
						   'post_subject'        => 'Foo Bar 1',
					   ),
				   ),
				   'never',
			),
			array( // not first post
				   array(
					   'topic_id'       => 1,
					   'post_id'        => 2,
					   'forum_id'       => 2,
					   'mode'           => 'edit',
					   'update_subject' => true,
					   'post_data'      => array(
						   'topic_first_post_id' => 1,
						   'post_subject'        => 'Foo Bar 1',
					   ),
				   ),
				   'never',
			),
		);
	}

	/**
	 * Test the edit_idea_title event
	 *
	 * @dataProvider edit_idea_title_data
	 */
	public function test_edit_idea_title($data, $expected)
	{
		$listener = $this->get_listener();

		$event = new \phpbb\event\data($data);

		$this->idea->expects($this->$expected())
			->method('get_idea_by_topic_id')
			->with($event['topic_id'])
			->willReturn(array('idea_id' => $event['topic_id']));

		$this->idea->expects($this->$expected())
			->method('set_title')
			->with($event['topic_id'], $event['post_data']['post_subject']);

		$listener->edit_idea_title($event);
	}

	/**
	 * Test data for test_ideas_forum_redirect
	 */
	public function ideas_forum_redirect_data()
	{
		return [
			[2, '$url', true],
			[4, '$url', false],
		];
	}

	/**
	 * Test the ideas_forum_redirect() method
	 *
	 * @dataProvider ideas_forum_redirect_data
	 */
	public function test_ideas_forum_redirect($forum_id, $url, $expected)
	{
		if ($expected)
		{
			$this->setExpectedTriggerError(E_USER_NOTICE, "Redirected to $url");
		}
		$this->helper->expects($expected ? self::once() : self::never())
			->method('route')
			->willReturn($url);

		$listener = $this->get_listener();

		$event = new \phpbb\event\data([
			'forum_id' => $forum_id,
		]);

		$listener->ideas_forum_redirect($event);
	}

	public function submit_idea_data()
	{
		return [
			['post', 2, 0, true, true], // all good
			['post', 1, 0, true, false], // all good except forum id
			['post', 2, 1, true, false], // all good except topic id
			['post', 2, 0, false, true], // all good but not approved
			['edit', 2, 0, true, false], // wrong mode
			['reply', 2, 0, true, false], // wrong mode
		];
	}

	/**
	 * @dataProvider submit_idea_data
	 */
	public function test_submit_idea($mode, $forum_id, $topic_id, $approved, $success)
	{
		// Set up the listener and event data
		$listener = $this->get_listener();
		$event = new \phpbb\event\data([
			'mode'     => $mode,
			'forum_id' => $forum_id,
			'data'     => [
				'forum_id' => $forum_id,
				'topic_id' => $topic_id,
			],
		]);

		// test submit_idea_template()
		$page_data = $event['page_data'];
		$page_data['U_VIEW_FORUM'] = '';
		$page_data['L_POST_A'] = '';
		$page_data['S_SAVE_ALLOWED'] = '';
		$page_data['S_HAS_DRAFTS'] = '';
		$event['page_data'] = $page_data;
		$event['page_title'] = 'NEW_POST';

		$this->helper
			->method('route')
			->willReturn('phpbb_ideas_index_controller');

		$listener->submit_idea_template($event);

		if ($mode === 'post' && $forum_id === 2)
		{
			self::assertStringContainsString('POST_IDEA', $event['page_title']);
			self::assertSame('phpbb_ideas_index_controller', $event['page_data']['U_VIEW_FORUM']);
			self::assertSame('POST_IDEA', $event['page_data']['L_POST_A']);
		}
		else
		{
			self::assertStringContainsString('NEW_POST', $event['page_title']);
			self::assertEmpty($event['page_data']['U_VIEW_FORUM']);
			self::assertEmpty($event['page_data']['L_POST_A']);
		}

		// Test submit_idea_before()
		$listener->submit_idea_before($event);
		if ($success)
		{
			self::assertArrayHasKey('post_time', $event['data']);
			self::assertGreaterThan(0, $event['data']['post_time']);
			$topic_id++;
		}
		else
		{
			self::assertArrayNotHasKey('post_time', $event['data']);
			$topic_id = 0;
		}

		// Update the topic_id like it would be after posting
		$data = $event['data'];
		$data['topic_id'] = $topic_id;
		$event['data'] = $data;

		// Test submit_idea_after()
		$this->auth->expects($success ? self::once() : self::never())
			->method('acl_get')
			->willReturn($approved);

		$this->idea->expects($success ? self::once() : self::never())
			->method('submit')
			->with($data)
			->willReturn(self::greaterThan(0));

		if (!$approved)
		{
			$this->setExpectedTriggerError(E_USER_NOTICE,'IDEA_STORED_MOD');
		}

		$listener->submit_idea_after($event);
	}
}

/**
 * Mock redirect()
 * Note: use the same namespace as the ideas
 *
 * @return void
 */
function redirect($url)
{
	trigger_error("Redirected to $url", E_USER_NOTICE);
}

/**
 * Mock meta_refresh()
 * Note: use the same namespace as the ideas
 *
 * @return void
 */
function meta_refresh()
{
}
