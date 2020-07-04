<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\factory;

class permissions_helper_test extends \phpbb_database_test_case
{
	protected static function setup_extensions()
	{
		return array('phpbb/ideas');
	}

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	public function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/../fixtures/permissions.xml');
	}

	public function setUp(): void
	{
		parent::setUp();

		global $cache, $db, $phpbb_dispatcher;

		$cache = $this->createMock('\phpbb\cache\driver\driver_interface');
		$cache->method('get')->willReturn(false);
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();

		$this->auth = new \phpbb\auth\auth;
		$this->config = new \phpbb\config\config(['ideas_forum_id' => 2]);
		$this->db = $db = $this->new_dbal();
	}

	public function test_set_ideas_forum_permissions()
	{
		global $phpbb_root_path, $phpEx;

		$permission_helper = new \phpbb\ideas\factory\permission_helper($this->db, $phpbb_root_path, $phpEx);

		$permission_helper->set_ideas_forum_permissions($this->config['ideas_forum_id']);

		$sql = 'SELECT a.auth_setting, b.auth_option
			FROM phpbb_acl_groups a
			JOIN phpbb_acl_options b
				ON a.auth_option_id = b.auth_option_id
			WHERE forum_id = ' . $this->config['ideas_forum_id'];

		$expected = [
			[
				'auth_setting' => ACL_YES,
				'auth_option'  => 'f_post',
			],
			[
				'auth_setting' => ACL_NEVER,
				'auth_option'  => 'f_announce',
			],
			[
				'auth_setting' => ACL_NEVER,
				'auth_option'  => 'f_announce_global',
			],
			[
				'auth_setting' => ACL_NEVER,
				'auth_option'  => 'f_sticky',
			],
			[
				'auth_setting' => ACL_NEVER,
				'auth_option'  => 'f_poll',
			],
			[
				'auth_setting' => ACL_NEVER,
				'auth_option'  => 'f_icons',
			],
			[
				'auth_setting' => ACL_NEVER,
				'auth_option'  => 'f_user_lock',
			],
		];

		$this->assertSqlResultEquals($expected, $sql);
	}
}
