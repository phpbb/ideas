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

/**
 * Class for handling votes on ideas
 */
class vote extends base
{
	/** @var string */
	protected $profile_url;

	/**
	 * Submits a vote on an idea.
	 *
	 * @param array $idea    The idea returned by get_idea().
	 * @param int   $user_id The ID of the user voting.
	 * @param int   $value   Up (1) or down (0)?
	 *
	 * @return array|string Array of information or string on error.
	 */
	public function submit(&$idea, $user_id, $value)
	{
		// Validate $vote - must be 0 or 1
		if ($value !== 0 && $value !== 1)
		{
			return 'INVALID_VOTE';
		}

		// Check whether user has already voted - update if they have
		$sql = 'SELECT idea_id, vote_value
			FROM ' . $this->table_votes . '
			WHERE idea_id = ' . (int) $idea['idea_id'] . '
				AND user_id = ' . (int) $user_id;
		$this->db->sql_query_limit($sql, 1);
		if ($row = $this->db->sql_fetchrow())
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
	public function remove(&$idea, $user_id)
	{
		// Only change something if user has already voted
		$sql = 'SELECT idea_id, vote_value
			FROM ' . $this->table_votes . '
			WHERE idea_id = ' . (int) $idea['idea_id'] . '
				AND user_id = ' . (int) $user_id;
		$this->db->sql_query_limit($sql, 1);
		if ($row = $this->db->sql_fetchrow())
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
		foreach ($rows as &$row)
		{
			$row['user'] = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour'], false, $this->profile_url());
		}

		return $rows;
	}

	/**
	 * Helper to generate the user profile URL with an
	 * absolute URL, which helps avoid problems when
	 * used in AJAX requests.
	 *
	 * @return string User profile URL
	 */
	protected function profile_url()
	{
		if (!isset($this->profile_url))
		{
			$this->profile_url = append_sid(generate_board_url() . "/memberlist.{$this->php_ext}", array('mode' => 'viewprofile'));
		}

		return $this->profile_url;
	}
}
