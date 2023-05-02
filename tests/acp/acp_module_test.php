<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

require_once __DIR__ . '/../../../../../includes/functions_module.php';

class acp_module_test extends \phpbb_test_case
{
	/** @var \phpbb_mock_extension_manager */
	protected $extension_manager;

	/** @var \phpbb\module\module_manager */
	protected $module_manager;

	protected function setUp(): void
	{
		global $phpbb_dispatcher, $phpbb_extension_manager, $phpbb_root_path, $phpEx;

		$this->extension_manager = new \phpbb_mock_extension_manager(
			$phpbb_root_path,
			[
				'phpbb/ideas' => [
					'ext_name' => 'phpbb/ideas',
					'ext_active' => '1',
					'ext_path' => 'ext/phpbb/ideas/',
				],
			]);
		$phpbb_extension_manager = $this->extension_manager;

		$this->module_manager = new \phpbb\module\module_manager(
			new \phpbb\cache\driver\dummy(),
			$this->getMockBuilder('\phpbb\db\driver\driver_interface')->getMock(),
			$this->extension_manager,
			MODULES_TABLE,
			$phpbb_root_path,
			$phpEx
		);

		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();
	}

	public function test_module_info()
	{
		self::assertEquals([
			'\\phpbb\\ideas\\acp\\ideas_module' => [
				'filename'	=> '\\phpbb\\ideas\\acp\\ideas_module',
				'title'		=> 'ACP_PHPBB_IDEAS',
				'modes'		=> [
					'settings'	=> [
						'title'	=> 'ACP_PHPBB_IDEAS_SETTINGS',
						'auth'	=> 'ext_phpbb/ideas && acl_a_board',
						'cat'	=> ['ACP_PHPBB_IDEAS']
					],
				],
			],
		], $this->module_manager->get_module_infos('acp', 'acp_ideas_module'));
	}

	public function module_auth_test_data()
	{
		return [
			// module_auth, expected result
			['ext_foo/bar', false],
			['ext_phpbb/ideas', true],
		];
	}

	/**
	 * @dataProvider module_auth_test_data
	 */
	public function test_module_auth($module_auth, $expected)
	{
		self::assertEquals($expected, p_master::module_auth($module_auth, 0));
	}

	public function main_module_test_data()
	{
		return [
			['submit'],
			['ideas_forum_setup'],
			['']
		];
	}

	/**
	 * @dataProvider main_module_test_data
	 * @param string $post
	 */
	public function test_main_module($post)
	{
		global $phpbb_container, $request, $template;

		if (!defined('IN_ADMIN'))
		{
			define('IN_ADMIN', true);
		}

		if ($post)
		{
			$this->expectException('\Exception');
		}

		$request = $this->getMockBuilder('\phpbb\request\request')
			->disableOriginalConstructor()
			->getMock();
		$language = $this->getMockBuilder('\phpbb\language\language')
			->disableOriginalConstructor()
			->getMock();
		$template = $this->getMockBuilder('\phpbb\template\template')
			->disableOriginalConstructor()
			->getMock();
		$phpbb_container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
			->disableOriginalConstructor()
			->getMock();
		$admin_controller = $this->getMockBuilder('\phpbb\ideas\controller\admin_controller')
			->disableOriginalConstructor()
			->getMock();

		$phpbb_container
			->expects(self::exactly(3))
			->method('get')
			->withConsecutive(['language'], ['request'], ['phpbb.ideas.admin.controller'])
			->willReturnOnConsecutiveCalls($language, $request, $admin_controller);

		// Add the phpBB Ideas ACP lang file
		$language->expects(self::once())
			->method('add_lang')
			->with('phpbb_ideas_acp', 'phpbb/ideas');

		// Make the $u_action url available in the admin controller
		$admin_controller
			->expects(self::once())
			->method('set_page_url');

		if ($post === 'submit')
		{
			$request->expects(self::once())
				->method('is_set_post')
				->with('submit')
				->willReturn(true);
		}

		if ($post === 'ideas_forum_setup')
		{
			$request->expects(self::exactly(2))
				->method('is_set_post')
				->withConsecutive(['submit'], ['ideas_forum_setup'])
				->willReturnOnConsecutiveCalls(false, true);
		}

		// Apply Ideas configuration settings
		$admin_controller->expects($post === 'submit' ? self::once() : self::never())
			->method('set_config_options')
			->willThrowException(new \Exception());

		// Set Ideas forum  options and registered user group forum permissions
		$admin_controller->expects($post === 'ideas_forum_setup' ? self::once() : self::never())
			->method('set_ideas_forum_options')
			->willThrowException(new \Exception());

		// Display/set ACP configuration settings
		$admin_controller->expects(empty($post) ? self::once() : self::never())
			->method('display_options');

		$p_master = new p_master();
		$p_master->module_ary[0]['is_duplicate'] = 0;
		$p_master->module_ary[0]['url_extra'] = '';
		$p_master->load('acp', '\phpbb\ideas\acp\ideas_module');
	}
}
