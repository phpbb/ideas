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
	 * Returns an array of ideas. Defaults to ten ideas ordered by date
	 * excluding duplicate or rejected ideas.
	 *
	 * @param int $number The number of ideas to return.
	 * @param string $sort Thing to sort by.
	 * @param string $sort_direction ASC / DESC.
	 * @param string $where SQL WHERE query.
	 */
	public function get_ideas($number = 10, $sort = 'date', $sort_direction = 'DESC', $where = 'idea_status != 4')
	{
		global $db;

		switch (strtolower($sort))
		{
			case 'author':
				$sortby = 'idea_author ' . $sort_direction;
				break;

			case 'date':
				$sortby = 'idea_date ' . $sort_direction;
				break;

			case 'id':
				$sortby = 'idea_id ' . $sort_direction;
				break;

			case 'title':
				$sortby = 'idea_title ' . $sort_direction;
				break;

			case 'votes':
				$sortby = 'idea_votes_up + idea_votes_down ' . $sort_direction;
				break;

			case 'top':
			default:
				// Special case!
				$sortby = 'TOP';
				break;
		}

		if ($sortby !== 'TOP')
		{
			$sql = 'SELECT *
				FROM ' . IDEAS_TABLE . "
				WHERE $where
				ORDER BY $sortby";
		}
		else
		{
			// YEEEEEEEEAAAAAAAAAAAAAHHHHHHH
			// From http://evanmiller.org/how-not-to-sort-by-average-rating.html
			$sql = 'SELECT *,
					((idea_votes_up + 1.9208) / (idea_votes_up + idea_votes_down) -
	                1.96 * SQRT((idea_votes_up * idea_votes_down) / (idea_votes_up + idea_votes_down) + 0.9604) /
	                (idea_votes_up + idea_votes_down)) / (1 + 3.8416 / (idea_votes_up + idea_votes_down))
	                AS ci_lower_bound
       			FROM ' . IDEAS_TABLE . "
       			WHERE idea_votes_up > idea_votes_down
       			    AND $where
       			ORDER BY ci_lower_bound " . $sort_direction;
		}

		$result = $db->sql_query_limit($sql, $number);
		$rows = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		if (count($rows))
		{
			$topic_ids = array();
			foreach ($rows as $row)
			{
				$topic_ids[] = $row['topic_id'];
			}
			$topic_tracking_info = get_complete_topic_tracking(IDEAS_FORUM_ID, $topic_ids);

			$last_times = array();
			$sql = 'SELECT topic_id, topic_last_post_time
				FROM ' . TOPICS_TABLE . '
				WHERE ' . $db->sql_in_set('topic_id', $topic_ids);
			$result = $db->sql_query($sql);
			while (($last_time = $db->sql_fetchrow($result)))
			{
				$last_times[$last_time['topic_id']] = $last_time['topic_last_post_time'];
			}
			$db->sql_freeresult($result);

			foreach ($rows as &$row)
			{
				$topic_id = $row['topic_id'];
				$row['read'] = !(isset($topic_tracking_info[$topic_id]) && $last_times[$topic_id] > $topic_tracking_info[$topic_id]);
			}
		}

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

		if ($row === false) {
			return null;
		}

		$sql = 'SELECT duplicate_id
			FROM ' . IDEA_DUPLICATES_TABLE . "
			WHERE idea_id = $id";
		$result = $db->sql_query_limit($sql, 1);
		$row['duplicate_id'] = $db->sql_fetchfield('duplicate_id');

		$sql = 'SELECT ticket_id
			FROM ' . IDEA_TICKETS_TABLE . "
			WHERE idea_id = $id";
		$result = $db->sql_query_limit($sql, 1);
		$row['ticket_id'] = $db->sql_fetchfield('ticket_id');

		$sql = 'SELECT rfc_link
			FROM ' . IDEA_RFCS_TABLE . "
			WHERE idea_id = $id";
		$result = $db->sql_query_limit($sql, 1);
		$row['rfc_link'] = $db->sql_fetchfield('rfc_link');

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
	 * Sets the ID of the duplicate for an idea.
	 *
	 * @param int $idea_id ID of the idea to be updated.
	 * @param string $duplicate Idea ID of duplicate.
	 */
	public function set_duplicate($idea_id, $duplicate)
	{
		global $db;

		if ($duplicate && !is_numeric($duplicate))
		{
			return; // Don't bother informing user, probably an attempted hacker
		}

		$sql = 'DELETE FROM ' . IDEA_DUPLICATES_TABLE . '
			WHERE idea_id = ' . (int) $idea_id;
		$db->sql_query($sql);

		$sql = 'INSERT INTO ' . IDEA_DUPLICATES_TABLE . ' (idea_id, duplicate_id)
			VALUES (' . (int) $idea_id . ', ' . (int) $duplicate . ')';
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

		$match = '/^https?:\/\/area51\.phpbb\.com\/phpBB\/viewtopic\.php/';
		if ($rfc && !preg_match($match, $rfc))
		{
			return; // Don't bother informing user, probably an attempted hacker
		}

		$sql = 'DELETE FROM ' . IDEA_RFCS_TABLE . '
			WHERE idea_id = ' . (int) $idea_id;
		$db->sql_query($sql);

		$sql = 'INSERT INTO ' . IDEA_RFCS_TABLE . ' (idea_id, rfc_link)
			VALUES (' . (int) $idea_id . ', \'' . $db->sql_escape($rfc) . '\')';
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

		$sql = 'DELETE FROM ' . IDEA_TICKETS_TABLE . '
			WHERE idea_id = ' . (int) $idea_id;
		$db->sql_query($sql);

		$sql = 'INSERT INTO ' . IDEA_TICKETS_TABLE . ' (idea_id, ticket_id)
			VALUES (' . (int) $idea_id . ', ' . (int) $ticket . ')';
		$db->sql_query($sql);
	}

	/**
	 * Sets the title of an idea.
	 *
	 * @param int $idea_id ID of the idea to be updated.
	 * @param string $title New title.
	 *
	 * @return boolean False if invalid length.
	 */
	public function set_title($idea_id, $title)
	{
		global $db;

		if (strlen($title) < 6 || strlen($title) > 64)
		{
			return false;
		}

		$sql_ary = array(
			'idea_title'    => $title
		);

		$sql = 'UPDATE ' . IDEAS_TABLE . '
			SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE idea_id = ' . $idea_id;
		$db->sql_query($sql);

		return true;
	}

	/**
	 * Submits a vote on an idea.
	 *
	 * @param array $idea The idea returned by get_idea().
	 * @param int $user_id The ID of the user voting.
	 * @param boolean $value Up or down?
	 *
	 * @return array Array of information.
	 */
	public function vote(&$idea, $user_id, $value)
	{
		/*return array(
			'message' => 'yeah',
			'votes_up' => 4,
			'votes_down' => 2,
			'points'    => 999,
		);*/

		global $db, $user;

		// Validate $vote - must be 0 or 1
		if ($value !== 0 && $value !== 1)
		{
			return 'INVALID_VOTE';
		}

		// Check whether user has already voted - update if they have
		$sql = 'SELECT idea_id, vote_value
			FROM ' . IDEA_VOTES_TABLE . "
			WHERE idea_id = {$idea['idea_id']}
				AND user_id = $user_id";
		$db->sql_query_limit($sql, 1);
		if ($db->sql_fetchrow())
		{
			$sql = 'SELECT vote_value
				FROM ' . IDEA_VOTES_TABLE . '
				WHERE user_id = ' . (int) $user_id . '
					AND idea_id = ' . (int) $idea['idea_id'];
			$db->sql_query($sql);
			$old_value = $db->sql_fetchfield('vote_value');

			if ($old_value != $value)
			{
				$sql = 'UPDATE ' . IDEA_VOTES_TABLE . '
					SET vote_value = ' . $value . '
					WHERE user_id = ' . (int) $user_id . '
						AND idea_id = ' . (int) $idea['idea_id'];
				$db->sql_query($sql);

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

				$sql = 'UPDATE ' . IDEAS_TABLE . '
					SET idea_votes_up = ' . $idea['idea_votes_up'] . ',
						idea_votes_down = ' . $idea['idea_votes_down'] . '
					WHERE idea_id = ' . $idea['idea_id'];
				$db->sql_query($sql);
			}

			return array(
				'message'	    => $user->lang('UPDATED_VOTE'),
				'votes_up'	    => $idea['idea_votes_up'],
				'votes_down'	=> $idea['idea_votes_down'],
				'points'        => $idea['idea_votes_up'] - $idea['idea_votes_down']
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


		// Update number of votes in ideas table
		$idea['idea_votes_' . ($value ? 'up' : 'down')]++;

		$sql_ary = array(
			'idea_votes_up'	    => $idea['idea_votes_up'],
			'idea_votes_down'	=> $idea['idea_votes_down'],
		);

		$sql = 'UPDATE ' . IDEAS_TABLE . '
			SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE idea_id = ' . $idea['idea_id'];
		$db->sql_query($sql);

		return array(
			'message'	    => $user->lang('VOTE_SUCCESS'),
			'votes_up'	    => $idea['idea_votes_up'],
			'votes_down'	=> $idea['idea_votes_down'],
			'points'        => $idea['idea_votes_up'] - $idea['idea_votes_down']
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
		if (strlen($desc) > 9900)
		{
			$error[] = $user->lang['DESC_TOO_LONG'];
		}

		if (count($error))
		{
			return $error;
		}

		// Submit idea
		$sql_ary = array(
			'idea_title'		=> $title,
			'idea_author'		=> $user_id,
			'idea_date'			=> time(),
			'topic_id'			=> 0
		);

		$sql = 'INSERT INTO ' . IDEAS_TABLE . ' ' .
			$db->sql_build_array('INSERT', $sql_ary);
		$db->sql_query($sql);
		$idea_id = $db->sql_nextid();

		$sql = 'SELECT username
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . $user_id;
		$result = $db->sql_query_limit($sql, 1);
		$username = $db->sql_fetchfield('username');
		$db->sql_freeresult($result);

		// Submit topic
		$bbcode = "[idea={$idea_id}]{$title}[/idea]";
		$desc .= "\n\n----------\n\n" . $user->lang('VIEW_IDEA_AT', $bbcode);
		$bbcode = "[user={$user_id}]{$username}[/user]";
		$desc .= "\n\n" . $user->lang('IDEA_POSTER', $bbcode);

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

			'force_approved_state'	=> true,
		);

		ideas_submit_post($title, POST_NORMAL, $data);

		// Edit topic ID into idea; both should link to each other
		$sql = 'UPDATE ' . IDEAS_TABLE . '
			SET topic_id = ' . $data['topic_id'] . '
			WHERE idea_id = ' . $idea_id;
		$db->sql_query($sql);

		return $idea_id;
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

		if (!$topic_id)
		{
			$idea = $this->get_idea($id);
			$topic_id = $idea['topic_id'];
		}

		// Delete topic
		delete_posts('topic_id', $topic_id);

		// Delete idea
		$sql = 'DELETE FROM ' . IDEAS_TABLE . '
			WHERE idea_id = ' . (int) $id;
		$db->sql_query($sql);
		$deleted = (bool) $db->sql_affectedrows();

		// Delete votes
		$sql = 'DELETE FROM ' . IDEA_VOTES_TABLE . '
			WHERE idea_id = ' . (int) $id;
		$db->sql_query($sql);

		// Delete RFCS
		$sql = 'DELETE FROM ' . IDEA_RFCS_TABLE . '
			WHERE idea_id = ' . (int) $id;
		$db->sql_query($sql);

		// Delete tickets
		$sql = 'DELETE FROM ' . IDEA_TICKETS_TABLE . '
			WHERE idea_id = ' . (int) $id;
		$db->sql_query($sql);

		return $deleted;
	}
}
