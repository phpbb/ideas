<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\controller;

class controller_base extends \phpbb_test_case
{
	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\controller\helper */
	protected $controller_helper;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\ideas\factory\base */
	protected $entity;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\ideas\factory\linkhelper */
	protected $link_helper;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\pagination */
	protected $pagination;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\request\request */
	protected $request;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	protected function setUp(): void
	{
		parent::setUp();

		// Globals required during execution
		global $config, $phpbb_dispatcher, $request, $user, $phpbb_root_path, $phpEx;
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		// Constructor arguments
		$this->auth = $this->getMockBuilder('\phpbb\auth\auth')
			->disableOriginalConstructor()
			->getMock();
		$this->config = $config = new \phpbb\config\config(array('ideas_forum_id' => 2));
		$this->controller_helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();
		$this->controller_helper->expects(self::atMost(1))
			->method('render')
			->willReturnCallback(function ($template_file, $page_title = '', $status_code = 200, $display_online_list = false) {
				return new \Symfony\Component\HttpFoundation\Response($template_file, $status_code);
			});
		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$this->lang = new \phpbb\language\language($lang_loader);
		$this->link_helper = $this->getMockBuilder('\phpbb\ideas\factory\linkhelper')
			->disableOriginalConstructor()
			->getMock();
		$this->pagination = $this->getMockBuilder('\phpbb\pagination')
			->disableOriginalConstructor()
			->getMock();
		$this->request = $request = $this->getMockBuilder('\phpbb\request\request')
			->disableOriginalConstructor()
			->getMock();
		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();
		$this->user = $user = $this->getMockBuilder('\phpbb\user')
			->setConstructorArgs(array(
				$this->lang,
				'\phpbb\datetime'
			))
			->getMock();
		$user->data['user_form_salt'] = $user->browser = $user->referer = $user->forwarded_for = $user->host = $user->page = '';
		$this->root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;
	}

	public function get_controller($name, $entity = null)
	{
		$controller = "\\phpbb\\ideas\\controller\\$name";
		$controller = new $controller(
			$this->auth,
			$this->config,
			$this->controller_helper,
			$this->lang,
			$this->link_helper,
			$this->pagination,
			$this->request,
			$this->template,
			$this->user,
			$this->root_path,
			$this->php_ext
		);

		if ($entity !== null)
		{
			$this->entity = $this->getMockBuilder("\\phpbb\\ideas\\factory\\$entity")
				->disableOriginalConstructor()
				->getMock();

			$controller->get_entity($this->entity);
		}

		return $controller;
	}

	/**
	 * Return an initialized area of idea data
	 *
	 * @return array
	 */
	public function initialized_idea_array()
	{
		return [
			'idea_id' => 0,
			'idea_title' => '',
			'idea_author' => '',
			'idea_date' => 0,
			'idea_votes_up' => 0,
			'idea_votes_down' => 0,
			'idea_status' => 0,
			'u_voted' => 0,
			'topic_id' => 0,
			'topic_status' => 0,
			'topic_visibility' => 0,
			'read' => 0,
		];

	}
}
