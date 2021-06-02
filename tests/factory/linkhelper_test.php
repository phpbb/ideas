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

class linkhelper_test extends \phpbb_database_test_case
{
	protected static function setup_extensions()
	{
		return array('phpbb/ideas');
	}

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\user_loader */
	protected $user_loader;

	public function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/../fixtures/ideas.xml');
	}

	protected function setUp(): void
	{
		parent::setUp();

		global $auth, $phpbb_dispatcher, $phpbb_root_path, $phpEx, $user;

		$this->db = $this->new_dbal();
		$this->helper = $this->getMockBuilder('\phpbb\controller\helper')
			->disableOriginalConstructor()
			->getMock();
		$this->helper->expects(self::atMost(3))
			->method('route')
			->willReturnCallback(function ($route, array $params = array()) {
				return $route . '#' . json_encode($params);
			});
		$this->user_loader = new \phpbb\user_loader($this->db, $phpbb_root_path, $phpEx, 'phpbb_users');

		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();
		$auth = $this->getMockBuilder('\phpbb\auth\auth')
			->disableOriginalConstructor()
			->getMock();
		$auth
			->method('acl_get')
			->with(self::stringContains('_'), self::anything())
			->willReturnMap(array(
				array('u_viewprofile', true),
			));

		$this->set_global_user();
	}

	public function set_global_user()
	{
		global $user;
		$user = $this->getMockBuilder('\phpbb\user')
			->disableOriginalConstructor()
			->getMock();
		$user->data['user_id'] = ANONYMOUS;
		$user->data['user_form_salt'] = '';
	}

	public function get_linkhelper()
	{
		return new \phpbb\ideas\factory\linkhelper($this->helper, $this->user_loader);
	}

	public function get_idea_link_test_data()
	{
		$this->set_global_user();

		return array(
			array(1, '', false, 'phpbb_ideas_idea_controller#{"idea_id":1}'),
			array(2, 'vote', false, 'phpbb_ideas_idea_controller#{"idea_id":2,"mode":"vote"}'),
			array(3, 'delete', true, 'phpbb_ideas_idea_controller#{"idea_id":3,"mode":"delete","hash":"' . generate_link_hash('delete_3') . '"}'),
		);
	}

	/**
	 * @dataProvider get_idea_link_test_data
	 */
	public function test_get_idea_link($idea_id, $mode, $hash, $expected)
	{
		$linkhelper = $this->get_linkhelper();

		self::assertEquals($expected, $linkhelper->get_idea_link($idea_id, $mode, $hash));
	}

	public function get_user_link_test_data()
	{
		return array(
			array(2, '<a href="phpBB/memberlist.php?mode=viewprofile&amp;u=2" class="username">ideabot</a>'),
			array(3, '<a href="phpBB/memberlist.php?mode=viewprofile&amp;u=3" class="username">admin</a>'),
			array(4, '<a href="phpBB/memberlist.php?mode=viewprofile&amp;u=4" class="username">poster</a>'),
		);
	}

	/**
	 * @dataProvider get_user_link_test_data
	 */
	public function test_get_user_link($user, $expected)
	{
		$linkhelper = $this->get_linkhelper();

		self::assertEquals($expected, $linkhelper->get_user_link($user));
	}
}
