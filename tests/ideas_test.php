<?php
/**
*
* Board Rules extension for the phpBB Forum Software package.
*
* @copyright (c) 2014 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace phpbb\ideas\tests;

require_once dirname(__FILE__) . '/../../../../includes/functions.php';

class ideas_test extends \phpbb_database_test_case
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

	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/fixtures/ideas.xml');
	}

	public function setUp()
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;

		$this->db = $this->new_dbal();
		$this->config = new \phpbb\config\config(array(
			'posts_per_page' => 10,
			'ideas_forum_id' => 2,
		));
		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$this->lang = new \phpbb\language\language($lang_loader);
		$this->user = $this->getMock('\phpbb\user', array(), array(
			new \phpbb\language\language(new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx)),
			'\phpbb\datetime'
		));
		$this->log = $this->getMockBuilder('\phpbb\log\log')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function get_ideas()
	{
		return new \phpbb\ideas\factory\ideas(
			$this->config,
			$this->db,
			$this->lang,
			$this->log,
			$this->user,
			'phpbb_ideas_ideas',
			'phpbb_ideas_duplicates',
			'phpbb_ideas_rfcs',
			'phpbb_ideas_statuses',
			'phpbb_ideas_tickets',
			'phpbb_ideas_votes'
		);
	}

	/**
	 * @dataProvider get_ideas_data
	 */
	public function test_get_ideas($number, $sort, $sort_direction, $status, $where, $start, $expected)
	{
		$ideas = $this->get_ideas();

		$result = $ideas->get_ideas($number, $sort, $sort_direction, $status, $where, $start);

		$this->assertEquals($expected, $result);
	}

	public function get_ideas_data()
	{
		return array(
			array(10, 'score', 'DESC',  array(), '',  0, array(
				array(
					'idea_id' => 1,
					'idea_author' => 2,
					'idea_title' => 'Idea test title #1',
					'idea_date' => 1446267172,
					'idea_votes_up' => 0,
					'idea_votes_down' => 1,
					'idea_status' => 1,
					'topic_id' => 1,
					'read' => true,
				),
			)),
		);
	}
}
