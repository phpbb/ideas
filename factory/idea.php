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

use phpbb\ideas\ext;

/**
 * Class for handling a single idea
 */
class idea extends base
{
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
		$match = '/^\d\.\d\.\d+(-\w+)?$/';
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
			$this->vote($idea, $data['poster_id'], 1);
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
			$topic_id = $idea ? $idea['topic_id'] : 0;
		}

		// Delete topic
		delete_posts('topic_id', $topic_id);

		// Delete idea
		$deleted = $this->delete_idea_data($id, $this->table_ideas);

		// Delete votes
		$this->delete_idea_data($id, $this->table_votes);

		return $deleted;
	}

	/**
	 * Submits a vote on an idea.
	 *
	 * @param array $idea    The idea returned by get_idea().
	 * @param int   $user_id The ID of the user voting.
	 * @param int   $value   Up (1) or down (0)?
	 *
	 * @return array|string Array of information or string on error.
	 */
	public function vote(&$idea, $user_id, $value)
	{
		// Validate $vote - must be 0 or 1
		if ($value !== 0 && $value !== 1)
		{
			return 'INVALID_VOTE';
		}

		// Check whether user has already voted - update if they have
		if ($row = $this->get_users_vote($idea['idea_id'], $user_id))
		{
			if ($row['vote_value'] != $value)
			{
				$sql = 'UPDATE ' . $this->table_votes . '
					SET vote_value = ' . $value . '
					WHERE user_id = ' . (int) $user_id . '
						AND idea_id = ' . (int) $idea['idea_id'];
				$this->db->sql_query($sql);

				if ($value == 1)
				{
					// Change to upvote
					$idea['idea_votes_up']++;
					$idea['idea_votes_down']--;
				}
				else
				{
					// Change to downvote
					$idea['idea_votes_up']--;
					$idea['idea_votes_down']++;
				}

				$sql_ary = array(
					'idea_votes_up'	    => $idea['idea_votes_up'],
					'idea_votes_down'	=> $idea['idea_votes_down'],
				);

				$this->update_idea_data($sql_ary, $idea['idea_id'], $this->table_ideas);
			}

			return array(
				'message'	    => $this->language->lang('UPDATED_VOTE'),
				'votes_up'	    => $idea['idea_votes_up'],
				'votes_down'	=> $idea['idea_votes_down'],
				'points'        => $this->language->lang('TOTAL_POINTS', $idea['idea_votes_up'] - $idea['idea_votes_down']),
				'voters'		=> $this->get_voters($idea['idea_id']),
			);
		}

		// Insert vote into votes table.
		$sql_ary = array(
			'idea_id'		=> (int) $idea['idea_id'],
			'user_id'		=> (int) $user_id,
			'vote_value'	=> (int) $value,
		);

		$this->insert_idea_data($sql_ary, $this->table_votes);

		// Update number of votes in ideas table
		$idea['idea_votes_' . ($value ? 'up' : 'down')]++;

		$sql_ary = array(
			'idea_votes_up'	    => $idea['idea_votes_up'],
			'idea_votes_down'	=> $idea['idea_votes_down'],
		);

		$this->update_idea_data($sql_ary, $idea['idea_id'], $this->table_ideas);

		return array(
			'message'	    => $this->language->lang('VOTE_SUCCESS'),
			'votes_up'	    => $idea['idea_votes_up'],
			'votes_down'	=> $idea['idea_votes_down'],
			'points'        => $this->language->lang('TOTAL_POINTS', $idea['idea_votes_up'] - $idea['idea_votes_down']),
			'voters'		=> $this->get_voters($idea['idea_id']),
		);
	}

	/**
	 * Remove a user's vote from an idea
	 *
	 * @param array   $idea    The idea returned by get_idea().
	 * @param int     $user_id The ID of the user voting.
	 *
	 * @return array Array of information.
	 */
	public function remove_vote(&$idea, $user_id)
	{
		// Only change something if user has already voted
		if ($row = $this->get_users_vote($idea['idea_id'], $user_id))
		{
			$sql = 'DELETE FROM ' . $this->table_votes . '
				WHERE idea_id = ' . (int) $idea['idea_id'] . '
					AND user_id = ' . (int) $user_id;
			$this->db->sql_query($sql);

			$idea['idea_votes_' . ($row['vote_value'] == 1 ? 'up' : 'down')]--;

			$sql_ary = array(
				'idea_votes_up'	    => $idea['idea_votes_up'],
				'idea_votes_down'	=> $idea['idea_votes_down'],
			);

			$this->update_idea_data($sql_ary, $idea['idea_id'], $this->table_ideas);
		}

		return array(
			'message'	    => $this->language->lang('UPDATED_VOTE'),
			'votes_up'	    => $idea['idea_votes_up'],
			'votes_down'	=> $idea['idea_votes_down'],
			'points'        => $this->language->lang('TOTAL_POINTS', $idea['idea_votes_up'] - $idea['idea_votes_down']),
			'voters'		=> $this->get_voters($idea['idea_id']),
		);
	}

	/**
	 * Returns voter info on an idea.
	 *
	 * @param int $id ID of the idea.
	 *
	 * @return array Array of row data
	 */
	public function get_voters($id)
	{
		$sql = 'SELECT iv.user_id, iv.vote_value, u.username, u.user_colour
			FROM ' . $this->table_votes . ' as iv,
				' . USERS_TABLE . ' as u
			WHERE iv.idea_id = ' . (int) $id . '
				AND iv.user_id = u.user_id
			ORDER BY u.username ASC';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		// Process the username for the template now, so it is
		// ready to use in AJAX responses and DOM injections.
		$profile_url = append_sid(generate_board_url() . "/memberlist.{$this->php_ext}", array('mode' => 'viewprofile'));
		foreach ($rows as &$row)
		{
			$row['user'] = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour'], false, $profile_url);
		}

		return $rows;
	}

	/**
	 * Get a user's stored vote value for a given idea
	 *
	 * @param int $idea_id The idea id
	 * @param int $user_id The user id
	 * @return mixed Array with the row data, false if the row does not exist
	 */
	protected function get_users_vote($idea_id, $user_id)
	{
		$sql = 'SELECT idea_id, vote_value
			FROM ' . $this->table_votes . '
			WHERE idea_id = ' . (int) $idea_id . '
				AND user_id = ' . (int) $user_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow();
		$this->db->sql_freeresult($result);

		return $row;
	}
}
