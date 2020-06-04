<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\factory;

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\ideas\ext;
use phpbb\language\language;
use phpbb\user;

/**
 * Base ideas class
 */
class base
{
	/** @var auth */
	protected $auth;

	/* @var config */
	protected $config;

	/* @var driver_interface */
	protected $db;

	/** @var language */
	protected $language;

	/* @var user */
	protected $user;

	/** @var string */
	protected $table_ideas;

	/** @var string */
	protected $table_votes;

	/** @var string */
	protected $table_topics;

	/** @var string */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param auth             $auth
	 * @param config           $config
	 * @param driver_interface $db
	 * @param language         $language
	 * @param user             $user
	 * @param string           $table_ideas
	 * @param string           $table_votes
	 * @param string           $table_topics
	 * @param string           $phpEx
	 */
	public function __construct(auth $auth, config $config, driver_interface $db, language $language, user $user, $table_ideas, $table_votes, $table_topics, $phpEx)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->user = $user;

		$this->php_ext = $phpEx;

		$this->table_ideas = $table_ideas;
		$this->table_votes = $table_votes;
		$this->table_topics = $table_topics;
	}

	/**
	 * Returns the status name from the status ID specified.
	 *
	 * @param int $id ID of the status.
	 *
	 * @return string|bool The status name if it exists, false otherwise.
	 */
	public function get_status_from_id($id)
	{
		return $this->language->lang(array_search($id, ext::$statuses));
	}

	/**
	 * Helper method for inserting new idea data
	 *
	 * @param array  $data  The array of data to insert
	 * @param string $table The name of the table
	 *
	 * @return int The ID of the inserted row
	 */
	protected function insert_idea_data(array $data, $table)
	{
		$sql = 'INSERT INTO ' . $table . '
		' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);

		return (int) $this->db->sql_nextid();
	}

	/**
	 * Helper method for updating idea data
	 *
	 * @param array  $data  The array of data to insert
	 * @param int    $id    The ID of the idea
	 * @param string $table The name of the table
	 *
	 * @return void
	 */
	protected function update_idea_data(array $data, $id, $table)
	{
		$sql = 'UPDATE ' . $table . '
			SET ' . $this->db->sql_build_array('UPDATE', $data) . '
			WHERE idea_id = ' . (int) $id;
		$this->db->sql_query($sql);
	}

	/**
	 * Helper method for deleting idea data
	 *
	 * @param int    $id    The ID of the idea
	 * @param string $table The name of the table
	 *
	 * @return bool True if idea was deleted, false otherwise
	 */
	protected function delete_idea_data($id, $table)
	{
		$sql = 'DELETE FROM ' . $table . '
			WHERE idea_id = ' . (int) $id;
		$this->db->sql_query($sql);

		return (bool) $this->db->sql_affectedrows();
	}
}
