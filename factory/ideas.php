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
use phpbb\language\language;
use phpbb\user;

class ideas
{
	const SORT_AUTHOR = 'author';
	const SORT_DATE = 'date';
	const SORT_IMPLEMENTED = 'implemented';
	const SORT_NEW = 'new';
	const SORT_SCORE = 'score';
	const SORT_TITLE = 'title';
	const SORT_TOP = 'top';
	const SORT_VOTES = 'votes';
	const SUBJECT_LENGTH = 120;

	/** @var array Idea status names and IDs */
	public static $statuses = array(
		'NEW'			=> 1,
		'IN_PROGRESS'	=> 2,
		'IMPLEMENTED'	=> 3,
		'DUPLICATE'		=> 4,
		'INVALID'		=> 5,
	);

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

	/** @var int */
	protected $idea_count;

	/** @var string */
	protected $php_ext;

	/** @var string */
	protected $profile_url;

	/**
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
	 * Returns an array of ideas. Defaults to ten ideas ordered by date
	 * excluding implemented, duplicate or invalid ideas.
	 *
	 * @param int       $number         The number of ideas to return.
	 * @param string    $sort           Thing to sort by.
	 * @param string    $sort_direction ASC / DESC.
	 * @param array|int $status         The id of the status(es) to load
	 * @param int       $start          Start value for pagination
	 *
	 * @return array Array of row data
	 */
	public function get_ideas($number = 10, $sort = 'date', $sort_direction = 'DESC', $status = array(), $start = 0)
	{
		switch (strtolower($sort))
		{
			case self::SORT_AUTHOR:
				$sortby = 'i.idea_author ' . $sort_direction;
			break;

			case self::SORT_DATE:
				$sortby = 'i.idea_date ' . $sort_direction;
			break;

			case self::SORT_SCORE:
				$sortby = 'CAST(i.idea_votes_up AS decimal) - CAST(i.idea_votes_down AS decimal) ' . $sort_direction;
			break;

			case self::SORT_TITLE:
				$sortby = 'i.idea_title ' . $sort_direction;
			break;

			case self::SORT_VOTES:
				$sortby = 'i.idea_votes_up + i.idea_votes_down ' . $sort_direction;
			break;

			case self::SORT_TOP:
				// Special case!
				$sortby = 'TOP';
			break;

			default:
				// Special case!
				$sortby = 'ALL';
			break;
		}

		// If we have a $status value or array lets use it,
		// otherwise lets exclude implemented, invalid and duplicate by default
		$where = (!empty($status)) ? $this->db->sql_in_set('i.idea_status', $status) : $this->db->sql_in_set(
			'i.idea_status', array(self::$statuses['IMPLEMENTED'], self::$statuses['DUPLICATE'], self::$statuses['INVALID'],
		), true);

		if ($sortby === 'TOP')
		{
			$where .= ' AND i.idea_votes_up > i.idea_votes_down';
		}

		// Only get approved topics for regular users, Moderators can see unapproved topics
		if (!$this->auth->acl_get('m_', $this->config['ideas_forum_id']))
		{
			$where .= ' AND t.topic_visibility = ' . ITEM_APPROVED;
		}

		// Only get ideas that are actually in the ideas forum (not ones that have been moved)
		$where .= ' AND t.forum_id = ' . (int) $this->config['ideas_forum_id'];

		// Count the total number of ideas for pagination
		if ($number >= $this->config['posts_per_page'])
		{
			$sql = 'SELECT COUNT(i.idea_id) as num_ideas
				FROM ' . $this->table_ideas . ' i
       			INNER JOIN ' . $this->table_topics . " t 
       				ON i.topic_id = t.topic_id
				WHERE $where";
			$result = $this->db->sql_query($sql);
			$num_ideas = (int) $this->db->sql_fetchfield('num_ideas');
			$this->db->sql_freeresult($result);

			// Set the total number of ideas for pagination
			$this->idea_count = $num_ideas;
		}

		if ($sortby !== 'TOP' && $sortby !== 'ALL')
		{
			$sql = 'SELECT t.topic_last_post_time, t.topic_status, t.topic_visibility, i.*
				FROM ' . $this->table_ideas . ' i
				INNER JOIN ' . $this->table_topics . " t 
					ON i.topic_id = t.topic_id
				WHERE $where
				ORDER BY " . $this->db->sql_escape($sortby);
		}
		else
		{
			// YEEEEEEEEAAAAAAAAAAAAAHHHHHHH
			// From http://evanmiller.org/how-not-to-sort-by-average-rating.html
			$sql = 'SELECT t.topic_last_post_time, t.topic_status, t.topic_visibility, i.*,
				((i.idea_votes_up + 1.9208) / (i.idea_votes_up + i.idea_votes_down) -
	            1.96 * SQRT((i.idea_votes_up * i.idea_votes_down) / (i.idea_votes_up + i.idea_votes_down) + 0.9604) /
	            (i.idea_votes_up + i.idea_votes_down)) / (1 + 3.8416 / (i.idea_votes_up + i.idea_votes_down))
	            AS ci_lower_bound
       				FROM ' . $this->table_ideas . ' i
       				INNER JOIN ' . $this->table_topics . " t 
       					ON i.topic_id = t.topic_id
       				WHERE $where
       			ORDER BY ci_lower_bound " . $this->db->sql_escape($sort_direction);
		}

		$result = $this->db->sql_query_limit($sql, $number, $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if (count($rows))
		{
			$topic_ids = array_map(function($row) {
				return $row['topic_id'];
			}, $rows);

			$topic_tracking_info = get_complete_topic_tracking((int) $this->config['ideas_forum_id'], $topic_ids);

			foreach ($rows as &$row)
			{
				$row['read'] = !(isset($topic_tracking_info[$row['topic_id']]) && $row['topic_last_post_time'] > $topic_tracking_info[$row['topic_id']]);
			}
		}

		return $rows;
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
		return $this->language->lang(array_search($id, self::$statuses));
	}

	/**
	 * Updates the status of an idea.
	 *
	 * @param int $idea_id The ID of the idea.
	 * @param int $status  The ID of the status.
	 *
	 * @return void
	 */
	public function change_status($idea_id, $status)
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
			'idea_title' => truncate_string($title, self::SUBJECT_LENGTH),
		);

		$this->update_idea_data($sql_ary, $idea_id, $this->table_ideas);

		return true;
	}

	/**
	 * Submits a vote on an idea.
	 *
	 * @param array $idea    The idea returned by get_idea().
	 * @param int   $user_id The ID of the user voting.
	 * @param int   $value   Up (1) or down (0)?
	 *
	 * @return array Array of information.
	 */
	public function vote(&$idea, $user_id, $value)
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
	public function remove_vote(&$idea, $user_id)
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
	 * Submits a new idea.
	 *
	 * @param string $title   The title of the idea.
	 * @param string $message The description of the idea.
	 * @param int    $user_id The ID of the author.
	 *
	 * @return array|int Either an array of errors, or the ID of the new idea.
	 */
	public function submit($title, $message, $user_id)
	{
		$error = array();
		if (utf8_clean_string($title) === '')
		{
			$error[] = $this->language->lang('TITLE_TOO_SHORT');
		}
		if (utf8_strlen($title) > self::SUBJECT_LENGTH)
		{
			$error[] = $this->language->lang('TITLE_TOO_LONG', self::SUBJECT_LENGTH);
		}
		if (utf8_strlen($message) < $this->config['min_post_chars'])
		{
			$error[] = $this->language->lang('TOO_FEW_CHARS');
		}
		if ($this->config['max_post_chars'] != 0 && utf8_strlen($message) > $this->config['max_post_chars'])
		{
			$error[] = $this->language->lang('TOO_MANY_CHARS');
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
			'topic_id'			=> 0,
		);

		$idea_id = $this->insert_idea_data($sql_ary, $this->table_ideas);

		// Initial vote
		$idea = $this->get_idea($idea_id);
		$this->vote($idea, $this->user->data['user_id'], 1);

		$uid = $bitfield = $options = '';
		generate_text_for_storage($message, $uid, $bitfield, $options, true, true, true);

		$data = array(
			'forum_id'			=> (int) $this->config['ideas_forum_id'],
			'topic_id'			=> 0,
			'icon_id'			=> false,
			'poster_id'			=> (int) $this->config['ideas_poster_id'],

			'enable_bbcode'		=> true,
			'enable_smilies'	=> true,
			'enable_urls'		=> true,
			'enable_sig'		=> true,

			'message'			=> $message,
			'message_md5'		=> md5($message),

			'bbcode_bitfield'	=> $bitfield,
			'bbcode_uid'		=> $uid,

			'post_edit_locked'	=> 0,
			'topic_title'		=> $title,

			'notify_set'		=> false,
			'notify'			=> false,
			'post_time'			=> 0,
			'forum_name'		=> 'Ideas forum',

			'enable_indexing'	=> true,

			'force_approved_state'	=> (!$this->auth->acl_get('f_noapprove', $this->config['ideas_forum_id'])) ? ITEM_UNAPPROVED : true,
		);

		// Get Ideas Bot info
		$sql = 'SELECT *
			FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $this->config['ideas_poster_id'];
		$result = $this->db->sql_query_limit($sql, 1);
		$poster_bot = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$poster_bot['is_registered'] = true;

		$tmpdata = $this->user->data;
		$this->user->data = $poster_bot;

		$poll = array();
		submit_post('post', $title, $this->user->data['username'], POST_NORMAL, $poll, $data);

		$this->user->data = $tmpdata;

		// Edit topic ID into idea; both should link to each other
		$sql_ary = array(
			'topic_id' => $data['topic_id'],
		);

		$this->update_idea_data($sql_ary, $idea_id, $this->table_ideas);

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

	/**
	 * Delete orphaned ideas. Orphaned ideas may exist after a
	 * topic has been deleted or moved to another forum.
	 *
	 * @return int Number of rows affected
	 */
	public function delete_orphans()
	{
		// Find any orphans
		$sql = 'SELECT idea_id FROM ' . $this->table_ideas . '
 			WHERE topic_id NOT IN (SELECT t.topic_id 
 			FROM ' . $this->table_topics . ' t
 				WHERE t.forum_id = ' . (int) $this->config['ideas_forum_id'] . ')';
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		if (empty($rows))
		{
			return 0;
		}

		$this->db->sql_transaction('begin');

		foreach ($rows as $row)
		{
			// Delete idea
			$this->delete_idea_data($row['idea_id'], $this->table_ideas);

			// Delete votes
			$this->delete_idea_data($row['idea_id'], $this->table_votes);
		}

		$this->db->sql_transaction('commit');

		return count($rows);
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

	/**
	 * Get the stored idea count
	 * Note: this should only be called after get_ideas()
	 *
	 * @return int Count of ideas
	 */
	public function get_idea_count()
	{
		return isset($this->idea_count) ? $this->idea_count : 0;
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
