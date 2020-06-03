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
 * Class for handling a single idea
 */
class idea extends base
{
	/** @var \phpbb\ideas\factory\vote */
	protected $vote;

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
	 * @param vote             $vote
	 */
	public function __construct(auth $auth, config $config, driver_interface $db, language $language, user $user, $table_ideas, $table_votes, $table_topics, $phpEx, vote $vote)
	{
		parent::__construct($auth, $config, $db, $language, $user, $table_ideas, $table_votes, $table_topics, $phpEx);
		$this->vote = $vote;
	}

	/**
	 * Returns the specified idea.
	 *
	 * @param int $id The ID of the idea to return.
	 *
	 * @return array|false The idea row set, or false if not found.
	 */
	public function get_idea($id)
	{
		$sql = 'SELECT *
			FROM ' . $this->table_ideas . '
			WHERE idea_id = ' . (int) $id;
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row;
	}

	/**
	 * Returns an idea specified by its topic ID.
	 *
	 * @param int $id The ID of the idea to return.
	 *
	 * @return array|false The idea row set, or false if not found.
	 */
	public function get_idea_by_topic_id($id)
	{
		$sql = 'SELECT idea_id
			FROM ' . $this->table_ideas . '
			WHERE topic_id = ' . (int) $id;
		$result = $this->db->sql_query_limit($sql, 1);
		$idea_id = (int) $this->db->sql_fetchfield('idea_id');
		$this->db->sql_freeresult($result);

		return $this->get_idea($idea_id);
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
	 * Updates the status of an idea.
	 *
	 * @param int $idea_id The ID of the idea.
	 * @param int $status  The ID of the status.
	 *
	 * @return void
	 */
	public function set_status($idea_id, $status)
	{
		$sql_ary = array(
			'idea_status' => (int) $status,
		);

		$this->update_idea_data($sql_ary, $idea_id, $this->table_ideas);
	}

	/**
	 * Sets the ID of the duplicate for an idea.
	 *
	 * @param int    $idea_id   ID of the idea to be updated.
	 * @param string $duplicate Idea ID of duplicate.
	 *
	 * @return bool True if set, false if invalid.
	 */
	public function set_duplicate($idea_id, $duplicate)
	{
		if ($duplicate && !is_numeric($duplicate))
		{
			return false;
		}

		$sql_ary = array(
			'duplicate_id'	=> (int) $duplicate,
		);

		$this->update_idea_data($sql_ary, $idea_id, $this->table_ideas);

		return true;
	}

	/**
	 * Sets the RFC link of an idea.
	 *
	 * @param int    $idea_id ID of the idea to be updated.
	 * @param string $rfc     Link to the RFC.
	 *
	 * @return bool True if set, false if invalid.
	 */
	public function set_rfc($idea_id, $rfc)
	{
		$match = '/^https?:\/\/area51\.phpbb\.com\/phpBB\/viewtopic\.php/';
		if ($rfc && !preg_match($match, $rfc))
		{
			return false;
		}

		$sql_ary = array(
			'rfc_link'	=> $rfc, // string is escaped by build_array()
		);

		$this->update_idea_data($sql_ary, $idea_id, $this->table_ideas);

		return true;
	}

	/**
	 * Sets the ticket ID of an idea.
	 *
	 * @param int    $idea_id ID of the idea to be updated.
	 * @param string $ticket  Ticket ID.
	 *
	 * @return bool True if set, false if invalid.
	 */
	public function set_ticket($idea_id, $ticket)
	{
		if ($ticket && !is_numeric($ticket))
		{
			return false;
		}

		$sql_ary = array(
			'ticket_id'	=> (int) $ticket,
		);

		$this->update_idea_data($sql_ary, $idea_id, $this->table_ideas);

		return true;
	}

	/**
	 * Sets the implemented version of an idea.
	 *
	 * @param int    $idea_id ID of the idea to be updated.
	 * @param string $version Version of phpBB the idea was implemented in.
	 *
	 * @return bool True if set, false if invalid.
	 */
	public function set_implemented($idea_id, $version)
	{
		$match = '/^\d\.\d\.\d+(\-\w+)?$/';
		if ($version && !preg_match($match, $version))
		{
			return false;
		}

		$sql_ary = array(
			'implemented_version'	=> $version, // string is escaped by build_array()
		);

		$this->update_idea_data($sql_ary, $idea_id, $this->table_ideas);

		return true;
	}

	/**
	 * Sets the title of an idea.
	 *
	 * @param int    $idea_id ID of the idea to be updated.
	 * @param string $title   New title.
	 *
	 * @return boolean True if updated, false if invalid length.
	 */
	public function set_title($idea_id, $title)
	{
		if (utf8_clean_string($title) === '')
		{
			return false;
		}

		$sql_ary = array(
			'idea_title' => truncate_string($title, ext::SUBJECT_LENGTH),
		);

		$this->update_idea_data($sql_ary, $idea_id, $this->table_ideas);

		return true;
	}

	/**
	 * Get the title of an idea.
	 *
	 * @param int $id ID of an idea
	 *
	 * @return string The idea's title, empty string if not found
	 */
	public function get_title($id)
	{
		$sql = 'SELECT idea_title
			FROM ' . $this->table_ideas . '
			WHERE idea_id = ' . (int) $id;
		$result = $this->db->sql_query_limit($sql, 1);
		$idea_title = $this->db->sql_fetchfield('idea_title');
		$this->db->sql_freeresult($result);

		return $idea_title ?: '';
	}

	/**
	 * Submit new idea data to the ideas table
	 *
	 * @param array $data An array of post data from a newly posted idea
	 *
	 * @return int The ID of the new idea.
	 */
	public function submit($data)
	{
		$sql_ary = [
			'idea_title'	=> $data['topic_title'],
			'idea_author'	=> $data['poster_id'],
			'idea_date'		=> $data['post_time'],
			'topic_id'		=> $data['topic_id'],
		];

		$idea_id = $this->insert_idea_data($sql_ary, $this->table_ideas);

		// Initial vote
		if (($idea = $this->get_idea($idea_id)) !== false)
		{
			$this->vote->submit($idea, $data['poster_id'], 1);
		}

		return $idea_id;
	}

	/**
	 * Deletes an idea and the topic to go with it.
	 *
	 * @param int $id       The ID of the idea to be deleted.
	 * @param int $topic_id The ID of the idea topic. Optional, but preferred.
	 *
	 * @return boolean Whether the idea was deleted or not.
	 */
	public function delete($id, $topic_id = 0)
	{
		if (!$topic_id)
		{
			$idea = $this->get_idea($id);
			$topic_id = $idea['topic_id'];
		}

		// Delete topic
		delete_posts('topic_id', $topic_id);

		// Delete idea
		$deleted = $this->delete_idea_data($id, $this->table_ideas);

		// Delete votes
		$this->delete_idea_data($id, $this->table_votes);

		return $deleted;
	}
}
