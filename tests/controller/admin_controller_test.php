<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\controller;

class admin_controller_test extends \phpbb_test_case
{
	/** @var bool */
	public static $valid_form = false;

	/** @var bool */
	public static $confirm = true;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\log\log */
	protected $log;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\request\request */
	protected $request;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\template\template */
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
		global $cache, $db, $phpbb_dispatcher, $phpbb_root_path, $phpEx;
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		// Constructor arguments
		$this->config = new \phpbb\config\config(['ideas_forum_id' => 2]);
		$cache = new \phpbb_mock_cache();
		$cache->put('_acl_options', [
			'local' => [
				'f_' => 1,
				'f_post' => 1,
				'f_announce' => 0,
				'f_announce_global' => 0,
				'f_sticky' => 0,
				'f_poll' => 0,
				'f_icons' => 0,
				'f_user_lock' => 0,
			],
			'id' => [
				'f_' => 1,
				'f_post' => 1,
				'f_announce' => 0,
				'f_announce_global' => 0,
				'f_sticky' => 0,
				'f_poll' => 0,
				'f_icons' => 0,
				'f_user_lock' => 0,
			],
			'option' => [
				[],
			],
		]);

		$this->db = $db = $this->getMockBuilder('\phpbb\db\driver\driver_interface')
			->disableOriginalConstructor()
			->getMock();
		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$this->language = new \phpbb\language\language($lang_loader);
		$this->log = $this->getMockBuilder('\phpbb\log\log')
			->disableOriginalConstructor()
			->getMock();
		$this->request = $this->getMockBuilder('\phpbb\request\request')
			->disableOriginalConstructor()
			->getMock();
		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();
		$this->user = $this->getMockBuilder('\phpbb\user')
			->setConstructorArgs(array(
				$this->language,
				'\phpbb\datetime'
			))
			->getMock();
		$this->user->data['user_id'] = 2;
		$this->user->ip = '1.1.1.1';
		$this->root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;
	}

	public function get_controller()
	{
		return new \phpbb\ideas\controller\admin_controller(
			$this->config,
			$this->db,
			$this->language,
			$this->log,
			$this->request,
			$this->template,
			$this->user,
			$this->root_path,
			$this->php_ext
		);
	}

	public function test_display_options()
	{
		$admin = $this->get_controller();

		$this->template->expects(self::once())
			->method('assign_vars')
			->with([
				'S_FORUM_SELECT_BOX'	=> '<select id="ideas_forum_id" name="config[ideas_forum_id]"><option value="0">ACP_IDEAS_NO_FORUM</option><option value="2" selected="selected">ACP_IDEAS_NO_FORUM</option></select>',
				'S_IDEAS_FORUM_ID'		=> true,
				'U_ACTION'				=> 'FOO',
			]);

		$admin->set_page_url('FOO');
		$admin->display_options();
	}

	public static function set_config_options_data()
	{
		return [
			[2, 2, true],
			[5, 5, true],
			[null, 0, true],
		];
	}

	/**
	 * @dataProvider set_config_options_data
	 * @param int $forum_id
	 * @param int $expected
	 * @param bool $valid_form
	 * @return void
	 */
	public function test_set_config_options($forum_id, $expected, $valid_form)
	{
		self::$valid_form = $valid_form;

		$this->setExpectedTriggerError(E_USER_NOTICE);

		$admin = $this->get_controller();

		$this->request->expects(self::once())
			->method('variable')
			->with('config')
			->willReturn(['ideas_forum_id' => $forum_id]);

		$this->log->expects(self::once())
			->method('add');

		$admin->set_config_options();

		$this->assertSame($expected, $this->config['ideas_forum_id']);
	}

	public static function set_config_options_errors_data()
	{
		return [
			[2, false],
			[5, false],
			[null, false],
		];
	}

	/**
	 * @dataProvider set_config_options_errors_data
	 * @param int $forum_id
	 * @param bool $valid_form
	 * @return void
	 */
	public function test_set_config_options_errors($forum_id, $valid_form)
	{
		self::$valid_form = $valid_form;

		$admin = $this->get_controller();

		$this->request->expects(self::never())
			->method('variable');

		$this->log->expects(self::never())
			->method('add');

		$this->template->expects(self::once())
			->method('assign_vars')
			->with([
				'S_ERROR'	=> true,
				'ERROR_MSG' => $this->language->lang('FORM_INVALID'),
			]);

		$admin->set_config_options();
	}

	public static function set_ideas_forum_options_data()
	{
		return [
			[2, true],
			[0, true],
			[2, false],
			[0, false],
		];
	}

	/**
	 * @dataProvider set_ideas_forum_options_data
	 * @param $forum_id
	 * @param $confirm
	 * @return void
	 */
	public function test_set_ideas_forum_options($forum_id, $confirm)
	{
		\phpbb\ideas\controller\idea_controller_test::$confirm = $confirm;
		$this->config['ideas_forum_id'] = $forum_id;

		$admin = $this->get_controller();

		if ($confirm)
		{
			if (empty($forum_id))
			{
				$this->setExpectedTriggerError(E_USER_WARNING, 'ACP_IDEAS_NO_FORUM');
			}
			else
			{
				$this->setExpectedTriggerError(E_USER_NOTICE, 'ACP_IDEAS_FORUM_SETUP_UPDATED');
				$this->log->expects(self::once())
					->method('add');
			}
		}
		else
		{
			$this->request->expects(self::once())
				->method('is_set_post')
				->with('ideas_forum_setup');
		}

		$admin->set_ideas_forum_options();
	}
}

/**
 * Mock make_forum_select()
 *
 * @return string
 */
function make_forum_select($ideas_forum_id)
{
	return '<option value="' . $ideas_forum_id . '" selected="selected">ACP_IDEAS_NO_FORUM</option>';
}

/**
 * Mock check_form_key()
 * Note: use the same namespace as the admin_input
 *
 * @return bool
 */
function check_form_key()
{
	return \phpbb\ideas\controller\admin_controller_test::$valid_form;
}

/**
 * Mock adm_back_link()
 * Note: use the same namespace as the acp_controller
 */
function adm_back_link()
{
}
