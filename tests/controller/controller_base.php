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

require_once dirname(__FILE__) . '/../../../../../includes/functions.php';

class controller_base extends \phpbb_test_case
{
	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\controller\helper */
	protected $controller_helper;

	/** @var \PHPUnit_Framework_MockObject_MockObject|\phpbb\ideas\factory\ideas */
	protected $ideas;

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

	public function setUp()
	{
		parent::setUp();

		// Globals required during execution
		global $phpbb_dispatcher, $request, $phpbb_root_path, $phpEx;
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		// Constructor arguments
		$this->auth = $this->getMock('\phpbb\auth\auth');
		$this->config = new \phpbb\config\config(array('ideas_forum_id' => 2, 'ideas_poster_id' => 2));
		$this->controller_helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();
		$this->controller_helper->expects($this->any())
			->method('render')
			->willReturnCallback(function ($template_file, $page_title = '', $status_code = 200, $display_online_list = false) {
				return new \Symfony\Component\HttpFoundation\Response($template_file, $status_code);
			});
		$this->ideas = $this->getMockBuilder('\phpbb\ideas\factory\ideas')
			->disableOriginalConstructor()
			->getMock();
		$this->ideas->expects($this->any())
			->method('get_ideas')
			->will($this->returnValue(array(array())));
		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$this->lang = new \phpbb\language\language($lang_loader);
		$this->link_helper = $this->getMockBuilder('\phpbb\ideas\factory\linkhelper')
			->disableOriginalConstructor()
			->getMock();
		$this->link_helper->expects($this->any())
			->method('get_list_link')
			->will($this->returnValue(''));
		$this->pagination = $this->getMockBuilder('\phpbb\pagination')
			->disableOriginalConstructor()
			->getMock();
		$this->request = $request = $this->getMock('\phpbb\request\request');
		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();
		$this->user = $this->getMock('\phpbb\user', array(), array($this->lang, '\phpbb\datetime'));
		$this->root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;
	}

	public function get_controller($name)
	{
		$controller = "\\phpbb\\ideas\\controller\\$name";
		return new $controller(
			$this->auth,
			$this->config,
			$this->controller_helper,
			$this->ideas,
			$this->lang,
			$this->link_helper,
			$this->pagination,
			$this->request,
			$this->template,
			$this->user,
			$this->root_path,
			$this->php_ext
		);
	}
}
