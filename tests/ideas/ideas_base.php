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

require_once dirname(__FILE__) . '/../../../../../includes/functions.php';

class ideas_base extends \phpbb_database_test_case
{
	static protected function setup_extensions()
	{
		return array('phpbb/ideas');
	}

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $lang;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $php_ext;

	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/../fixtures/ideas.xml');
	}

	public function setUp()
	{
		parent::setUp();

		global $auth, $config, $db, $phpbb_dispatcher, $phpbb_root_path, $phpEx, $request;

		$auth = $this->getMock('\phpbb\auth\auth');
		$this->config = $config = new \phpbb\config\config(array(
			'posts_per_page' => 10,
			'ideas_forum_id' => 2,
			'ideas_poster_id' => 2,
		));
		$this->db = $db = $this->new_dbal();
		$phpbb_dispatcher = new \phpbb_mock_event_dispatcher();
		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$this->lang = new \phpbb\language\language($lang_loader);
		$this->user = $this->getMock('\phpbb\user', array(), array(
			$this->lang,
			'\phpbb\datetime'
		));
		$this->log = $this->getMockBuilder('\phpbb\log\log')
			->disableOriginalConstructor()
			->getMock();
		$this->php_ext = $phpEx;
		$request = $this->getMock('\phpbb\request\request');
	}

	/**
	 * Get an instance of the ideas object
	 *
	 * @return \phpbb\ideas\factory\ideas
	 */
	protected function get_ideas_object()
	{
		return new \phpbb\ideas\factory\ideas(
			$this->config,
			$this->db,
			$this->lang,
			$this->log,
			$this->user,
			'phpbb_ideas_ideas',
			'phpbb_ideas_votes',
			$this->php_ext
		);
	}
}
