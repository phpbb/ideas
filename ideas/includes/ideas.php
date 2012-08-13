<?php

/**
*
* @package phpBB3 Ideas
* @author Callum Macrae (callumacrae) <callum@lynxphp.com>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* phpBB Ideas class
* @package phpBB Ideas
*/
class Ideas
{
	/**
	 * Returns an array of ideas. Defaults to ten ideas ordered by date excluding
	 * duplicate or rejected ideas.
	 *
	 * @param int $number The number of ideas to return.
	 * @param string $sortby SQL ORDER BY query.
	 * @param string $where SQL WHERE query.
	 */
	public function get_ideas($number = 10, $sortby = 'idea_date DESC', $where = 'idea_status != 5 && idea_status != 4')
	{
		global $db;
		$sql = 'SELECT *
			FROM ' . IDEAS_TABLE . "
			WHERE $where
			ORDER BY $sortby";
		$result = $db->sql_query_limit($sql, $number);
		$rows = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Returns the specified idea.
	 *
	 * @param int $id The ID of the idea to return.
	 */
	public function get_idea($id)
	{
		global $db;
		$sql = 'SELECT *
			FROM ' . IDEAS_TABLE . "
			WHERE idea_id = $id";
		$result = $db->sql_query_limit($sql, 1);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		return $row;
	}

	/**
	 * Returns the status name from the status ID specified.
	 *
	 * @param int $id ID of the status.
	 * @return string The status name.
	 */
	public function get_status_from_id($id)
	{
		global $db;
		$sql = 'SELECT status_name
			FROM ' . IDEA_STATUS_TABLE . "
			WHERE status_id = $id";
		$result = $db->sql_query_limit($sql, 1);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		return $row['status_name'];
	}

	/**
	 * Returns all statuses.
	 *
	 * @return Array of statuses.
	 */
	public function get_statuses()
	{
		global $db;

		$sql = 'SELECT * FROM ' . IDEA_STATUS_TABLE;
		$result = $db->sql_query($sql);
		$rows = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Updates the status of an idea.
	 *
	 * @param int $idea_id The ID of the idea.
	 * @param int $status The ID of the status.
	 */
	public function change_status($idea_id, $status)
	{
		global $db;

		$sql = 'UPDATE ' . IDEAS_TABLE . '
			SET idea_status = ' . (int) $status . '
			WHERE idea_id = ' . (int) $idea_id;
		$db->sql_query($sql);
	}

	/**
	 * Sets the RFC link of an idea.
	 *
	 * @param int $idea_id ID of the idea to be updated.
	 * @param string $rfc Link to the RFC.
	 */
	public function set_rfc($idea_id, $rfc)
	{
		global $db;

		if ($rfc && !filter_var($rfc, FILTER_VALIDATE_URL))
		{
			return; // Don't bother informing user, probably an attempted hacker
		}

		$sql = 'UPDATE ' . IDEAS_TABLE . '
			SET rfc_link = \'' . $db->sql_escape($rfc) . '\'
			WHERE idea_id = ' . (int) $idea_id;
		$db->sql_query($sql);
	}

	/**
	 * Sets the ticket ID of an idea.
	 *
	 * @param int $idea_id ID of the idea to be updated.
	 * @param string $ticket Ticket ID.
	 */
	public function set_ticket($idea_id, $ticket)
	{
		global $db;

		if ($ticket && !is_numeric($ticket))
		{
			return; // Don't bother informing user, probably an attempted hacker
		}

		$sql = 'UPDATE ' . IDEAS_TABLE . '
			SET ticket_id = ' . (int) $ticket . '
			WHERE idea_id = ' . (int) $idea_id;
		$db->sql_query($sql);
	}

	/**
	 * Submits a vote on an idea.
	 *
	 * @param array $idea The idea returned by get_idea().
	 * @param int $user_id The ID of the user voting.
	 * @param int $value The value to vote for (int, 1-5).
	 *
	 * @return array Array of information.
	 */
	public function vote(&$idea, $user_id, $value)
	{
		global $db, $user;

		// Validate $vote - must be a whole number between 1 and 5.
		if (!is_int($value) || $value > 5 || $value < 1)
		{
			return 'INVALID_VOTE';
		}

		// Check whether user has already voted - update if they have
		$sql = 'SELECT idea_id, vote_value
			FROM ' . IDEA_VOTES_TABLE . "
			WHERE idea_id = {$idea['idea_id']}
				AND user_id = $user_id";
		$result = $db->sql_query_limit($sql, 1);
		if ($db->sql_fetchrow())
		{
			// Get old vote so that we can be mathematical
			$sql = 'SELECT vote_value FROM ' . IDEA_VOTES_TABLE . '
				WHERE user_id = ' . (int) $user_id . '
					AND idea_id = ' . (int) $idea['idea_id'];
			$db->sql_query($sql);
			$old_value = $db->sql_fetchfield('vote_value');

			$sql = 'UPDATE ' . IDEA_VOTES_TABLE . '
				SET vote_value = ' . $value . '
				WHERE user_id = ' . (int) $user_id . '
					AND idea_id = ' . (int) $idea['idea_id'];
			$db->sql_query($sql);

			$idea['idea_rating'] = ($idea['idea_rating'] * $idea['idea_votes'] - $old_value + $value) / $idea['idea_votes'];

			$sql = 'UPDATE ' . IDEAS_TABLE . '
				SET idea_rating = ' . $idea['idea_rating'] . '
				WHERE idea_id = ' . $idea['idea_id'];
			$db->sql_query($sql);

			return array(
				'message'	=> $user->lang('UPDATED_VOTE'),
				'rating'	=> $idea['idea_rating'],
				'votes'		=> $idea['idea_votes'],
			);
		}

		// Insert vote into votes table.
		$sql_ary = array(
			'idea_id'		=> $idea['idea_id'],
			'user_id'		=> $user_id,
			'vote_value'	=> $value,
		);

		$sql = 'INSERT INTO ' . IDEA_VOTES_TABLE . ' ' .
			$db->sql_build_array('INSERT', $sql_ary);
		$db->sql_query($sql);


		// Update rating in IDEAS_TABLE and $idea
		$idea['idea_rating'] = ($idea['idea_rating'] * $idea['idea_votes'] + $value)
			/ ++$idea['idea_votes'];

		$sql_ary = array(
			'idea_rating'	=> $idea['idea_rating'],
			'idea_votes'	=> $idea['idea_votes'],
		);

		$sql = 'UPDATE ' . IDEAS_TABLE . '
			SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE idea_id = ' . $idea['idea_id'];
		$db->sql_query($sql);

		return array(
			'message'	=> $user->lang('VOTE_SUCCESS'),
			'rating'	=> $idea['idea_rating'],
			'votes'		=> $idea['idea_votes'],
		);
	}

	/**
	 * Returns voter info on an idea.
	 *
	 * @param int $id ID of the idea.
	 */
	public function get_voters($id)
	{
		global $db;

		$sql = 'SELECT user_id, vote_value
			FROM ' . IDEA_VOTES_TABLE . '
			WHERE idea_id = ' . (int) $id . '
			ORDER BY vote_value DESC';
		$result = $db->sql_query($sql);
		$rows = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Submits a new idea.
	 *
	 * @param string $title The title of the idea.
	 * @param string $desc The description of the idea.
	 * @param int $user_id The ID of the author.
	 */
	public function submit($title, $desc, $user_id)
	{
		global $db, $user;

		$error = array();
		if (strlen($title) < 6)
		{
			$error[] = $user->lang['TITLE_TOO_SHORT'];
		}
		if (strlen($desc) < 5)
		{
			$error[] = $user->lang['DESC_TOO_SHORT'];
		}
		if (strlen($title) > 64)
		{
			$error[] = $user->lang['TITLE_TOO_LONG'];
		}
		if (strlen($desc) > 10000)
		{
			$error[] = $user->lang['DESC_TOO_LONG'];
		}

		if (count($error))
		{
			return $error;
		}

		$uid = $bitfield = $options = '';
		generate_text_for_storage($desc, $uid, $bitfield, $options, true, true, true);

		$data = array(
			'forum_id'			=> IDEAS_FORUM_ID,
			'topic_id'			=> 0,
			'icon_id'			=> false,
			'poster_id'			=> IDEAS_POSTER_ID,

			'enable_bbcode'		=> true,
			'enable_smilies'	=> true,
			'enable_urls'		=> true,
			'enable_sig'		=> true,

			'message'			=> $desc,
			'message_md5'		=> md5($desc),

			'bbcode_bitfield'	=> $bitfield,
			'bbcode_uid'		=> $uid,

			'post_edit_locked'	=> 0,
			'topic_title'		=> $title,

			'notify_set'		=> false,
			'notify'			=> false,
			'post_time'			=> 0,
			'forum_name'		=> 'Ideas forum',

			'enable_indexing'	=> true,

			'force_approved_state'	=> true
		);

		ideas_submit_post($title, POST_NORMAL, $data);

		$sql_ary = array(
			'idea_title'		=> $db->sql_escape($title),
			'idea_author'		=> $user_id,
			'idea_date'			=> time(),
			'topic_id'			=> $data['topic_id']
		);

		$sql = 'INSERT INTO ' . IDEAS_TABLE . ' ' .
			$db->sql_build_array('INSERT', $sql_ary);
		$db->sql_query($sql);
		return $db->sql_nextid();
	}

	/**
	 * Deletes an idea and the topic to go with it.
	 *
	 * @param int $id The ID of the idea to be deleted.
	 * @param int $topic_id The ID of the idea topic. Optional, but preferred.
	 *
	 * @return boolean Whether the idea was deleted or not.
	 */
	public function delete($id, $topic_id = 0)
	{
		global $db;

		if (!$topic_id) {
			$idea = $this->get_idea($id);
			$topic_id = $idea['topic_id'];
		}

		delete_posts('topic_id', $topic_id);

		$sql = 'DELETE FROM ' . IDEAS_TABLE . '
			WHERE idea_id = ' . (int) $id;
		$db->sql_query($sql);
		return (bool) $db->sql_affectedrows();
	}
}
