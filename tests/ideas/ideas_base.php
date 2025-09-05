<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\ideas;

class ideas_base extends \phpbb_database_test_case
{
	protected static function setup_extensions()
	{
		return array('phpbb/ideas');
	}

	/** @var \phpbb\auth\auth|\PHPUnit\Framework\MockObject\MockObject */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var \phpbb_mock_notification_manager */
	protected $notification_manager;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $php_ext;

	public function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/../fixtures/ideas.xml');
	}

	protected function setUp(): void
	{
		parent::setUp();

		global $auth, $config, $db, $user, $phpbb_dispatcher, $phpbb_root_path, $phpEx, $request;

		$this->auth = $auth = $this->getMockBuilder('\phpbb\auth\auth')
			->disableOriginalConstructor()
			->getMock();
		$this->config = $config = new \phpbb\config\config(array(
			'posts_per_page' => 10,
			'ideas_forum_id' => 2,
		));
		$this->db = $db = $this->new_dbal();
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();
		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$this->lang = new \phpbb\language\language($lang_loader);
		$this->user = $user = new \phpbb\user($this->lang, '\phpbb\datetime');
		$user->data['user_form_salt'] = $user->browser = $user->referer = $user->forwarded_for = $user->host = $user->page = '';
		$user->data = $this->user->data = [
			'user_id'		=> 2,
			'username'		=> 'user-name',
			'is_registered'	=> true,
			'user_colour'	=> '',
			'user_lastmark'	=> 0,
			'user_form_salt'=> '',
		];

		$this->php_ext = $phpEx;
		$request = $this->getMockBuilder('\phpbb\request\request')
			->disableOriginalConstructor()
			->getMock();
		$this->notification_manager = $this->getMockBuilder('\phpbb\notification\manager')
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * Get an instance of the ideas object
	 *
	 * @return \phpbb\ideas\factory\ideas
	 */
	protected function get_ideas_object()
	{
		return $this->get_factory('ideas');
	}

	/**
	 * Get an instance of the idea object
	 *
	 * @return \phpbb\ideas\factory\idea
	 */
	protected function get_idea_object()
	{
		return $this->get_factory('idea');
	}

	/**
	 * Get an instance of the livesearch object
	 *
	 * @return \phpbb\ideas\factory\livesearch
	 */
	protected function get_livesearch_object()
	{
		return $this->get_factory('livesearch');
	}

	protected function get_factory($name)
	{
		$object = "\\phpbb\\ideas\\factory\\$name";
		return new $object(
			$this->auth,
			$this->config,
			$this->db,
			$this->lang,
			$this->notification_manager,
			$this->user,
			'phpbb_ideas_ideas',
			'phpbb_ideas_votes',
			'phpbb_topics',
			$this->php_ext
		);
	}
}
