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

use phpbb\exception\runtime_exception;
use phpbb\ideas\ext;

/**
 * Class for handling multiple ideas
 */
class ideas extends base
{
	/** @var int */
	protected $idea_count;

	/** @var array */
	protected $sql;

	/**
	 * Returns an array of ideas. Defaults to ten ideas ordered by date
	 * excluding implemented, duplicate or invalid ideas.
	 *
	 * @param int       $number    The number of ideas to return
	 * @param string    $sort      A sorting option/collection
	 * @param string    $direction Should either be ASC or DESC
	 * @param array|int $status    The id of the status(es) to load
	 * @param int       $start     Start value for pagination
	 *
	 * @return array Array of row data
	 */
	public function get_ideas($number = 10, $sort = 'date', $direction = 'DESC', $status = [], $start = 0)
	{
		// Initialize a query to request ideas
		$sql = $this->query_ideas()
			->query_sort($sort, $direction)
			->query_status($status);

		// For pagination, get a count of the total ideas being requested
		if ($number >= $this->config['posts_per_page'])
		{
			$this->idea_count = $sql->query_count();
		}

		$ideas = $sql->query_get($number, $start);

		if (count($ideas))
		{
			$topic_ids = array_column($ideas, 'topic_id');
			$idea_ids = array_column($ideas, 'idea_id');

			$topic_tracking_info = get_complete_topic_tracking((int) $this->config['ideas_forum_id'], $topic_ids);
			$user_voting_info = $this->get_users_votes($this->user->data['user_id'], $idea_ids);

			foreach ($ideas as &$idea)
			{
				$idea['read'] = !(isset($topic_tracking_info[$idea['topic_id']]) && $idea['topic_last_post_time'] > $topic_tracking_info[$idea['topic_id']]);
				$idea['u_voted'] = isset($user_voting_info[$idea['idea_id']]) ? (int) $user_voting_info[$idea['idea_id']] : '';
			}
			unset ($idea);
		}

		return $ideas;
	}

	/**
	 * Initialize the $sql property with necessary SQL statements.
	 *
	 * @return \phpbb\ideas\factory\ideas $this For chaining calls
	 */
	protected function query_ideas()
	{
		$this->sql = [];

		$this->sql['SELECT'][] = 't.topic_last_post_time, t.topic_status, t.topic_visibility, i.*';
		$this->sql['FROM'] = "$this->table_ideas i";
		$this->sql['JOIN'] = "$this->table_topics t ON i.topic_id = t.topic_id";
		$this->sql['WHERE'][] = 't.forum_id = ' . (int) $this->config['ideas_forum_id'];

		// Only get approved topics for regular users, Moderators will see unapproved topics
		if (!$this->auth->acl_get('m_', $this->config['ideas_forum_id']))
		{
			$this->sql['WHERE'][] = 't.topic_visibility = ' . ITEM_APPROVED;
		}

		return $this;
	}

	/**
	 * Update the $sql property with ORDER BY statements to obtain
	 * the requested collection of Ideas. Some instances may add
	 * additional WHERE or SELECT statements to refine the collection.
	 *
	 * @param string $sort      A sorting option/collection
	 * @param string $direction Will either be ASC or DESC
	 *
	 * @return \phpbb\ideas\factory\ideas $this For chaining calls
	 */
	protected function query_sort($sort, $direction)
	{
		$sort = strtolower($sort);
		$direction = $direction === 'DESC' ? 'DESC' : 'ASC';

		// Most sorting relies on simple ORDER BY statements, but some may use a WHERE statement
		$statements = [
			ext::SORT_DATE    => ['ORDER_BY' => 'i.idea_date'],
			ext::SORT_TITLE   => ['ORDER_BY' => 'i.idea_title'],
			ext::SORT_AUTHOR  => ['ORDER_BY' => 'i.idea_author'],
			ext::SORT_SCORE   => ['ORDER_BY' => 'CAST(i.idea_votes_up AS decimal) - CAST(i.idea_votes_down AS decimal)'],
			ext::SORT_VOTES   => ['ORDER_BY' => 'i.idea_votes_up + i.idea_votes_down'],
			ext::SORT_TOP     => ['WHERE' => 'i.idea_votes_up > i.idea_votes_down'],
			ext::SORT_MYIDEAS => ['ORDER_BY' => 'i.idea_date', 'WHERE' => 'i.idea_author = ' . (int) $this->user->data['user_id']],
		];

		// Append a new WHERE statement if the sort has one
		if (isset($statements[$sort]['WHERE']))
		{
			$this->sql['WHERE'][] = $statements[$sort]['WHERE'];
		}

		// If we have an ORDER BY we use that. The absence of an ORDER BY
		// means we will default to sorting ideas by their calculated score.
		if (isset($statements[$sort]['ORDER_BY']))
		{
			$this->sql['ORDER_BY'] = "{$statements[$sort]['ORDER_BY']} $direction";
		}
		else
		{
			// https://www.evanmiller.org/how-not-to-sort-by-average-rating.html
			$this->sql['SELECT'][] = '((i.idea_votes_up + 1.9208) / (i.idea_votes_up + i.idea_votes_down) -
				1.96 * SQRT((i.idea_votes_up * i.idea_votes_down) / (i.idea_votes_up + i.idea_votes_down) + 0.9604) /
				(i.idea_votes_up + i.idea_votes_down)) / (1 + 3.8416 / (i.idea_votes_up + i.idea_votes_down))
				AS ci_lower_bound';

			$this->sql['ORDER_BY'] = "ci_lower_bound $direction";
		}

		return $this;
	}

	/**
	 * Update $sql property with additional SQL statements to filter ideas
	 * by status. If $status is given we'll get those ideas. If no $status
	 * is given, the default is to get all ideas excluding Duplicates, Invalid
	 * and Implemented statuses (because they are considered done & dusted,
	 * if they were gases they'd be inert).
	 *
	 * @param array|int $status The id(s) of the status(es) to load
	 *
	 * @return \phpbb\ideas\factory\ideas $this For chaining calls
	 */
	protected function query_status($status = [])
	{
		$this->sql['WHERE'][] = !empty($status) ? $this->db->sql_in_set('i.idea_status', $status) : $this->db->sql_in_set(
			'i.idea_status', [ext::$statuses['IMPLEMENTED'], ext::$statuses['DUPLICATE'], ext::$statuses['INVALID'],
		], true);

		return $this;
	}

	/**
	 * Run a query using the $sql property to get a collection of ideas.
	 *
	 * @param int $number The number of ideas to return
	 * @param int $start  Start value for pagination
	 *
	 * @return mixed      Nested array if the query had rows, false otherwise
	 * @throws runtime_exception
	 */
	protected function query_get($number, $start)
	{
		if (empty($this->sql))
		{
			throw new runtime_exception('INVALID_IDEA_QUERY');
		}

		$sql = 'SELECT ' . implode(', ', $this->sql['SELECT']) . '
			FROM ' . $this->sql['FROM'] . '
			INNER JOIN ' . $this->sql['JOIN'] . '
			WHERE ' . implode(' AND ', $this->sql['WHERE']) . '
			ORDER BY ' . $this->sql['ORDER_BY'];

		$result = $this->db->sql_query_limit($sql, $number, $start);
		$rows = $this->db->sql_fetchrowset($result);
		$this->db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * Run a query using the $sql property to get a count of ideas.
	 *
	 * @return int The number of ideas
	 * @throws runtime_exception
	 */
	protected function query_count()
	{
		if (empty($this->sql))
		{
			throw new runtime_exception('INVALID_IDEA_QUERY');
		}

		$sql = 'SELECT COUNT(i.idea_id) as count
			FROM ' . $this->sql['FROM'] . '
       		INNER JOIN ' . $this->sql['JOIN'] . '
			WHERE ' . implode(' AND ', $this->sql['WHERE']);

		$result = $this->db->sql_query($sql);
		$count = (int) $this->db->sql_fetchfield('count');
		$this->db->sql_freeresult($result);

		return $count;
	}

	/**
	 * Get a user's votes from a group of ideas
	 *
	 * @param int $user_id The user's id
	 * @param array $ids An array of idea ids
	 *
	 * @return array An array of ideas the user voted on and their vote result, or empty otherwise.
	 *               example: [idea_id => vote_result]
	 *                         1 => 1, idea 1, voted up by the user
	 *                         2 => 0, idea 2, voted down by the user
	 */
	public function get_users_votes($user_id, array $ids)
	{
		$results = [];
		$sql = 'SELECT idea_id, vote_value
			FROM ' . $this->table_votes . '
			WHERE user_id = ' . (int) $user_id . '
			AND ' . $this->db->sql_in_set('idea_id', $ids, false, true);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$results[$row['idea_id']] = $row['vote_value'];
		}
		$this->db->sql_freeresult($result);

		return $results;
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
	 * Get the stored idea count
	 * Note: this should only be called after get_ideas()
	 *
	 * @return int Count of ideas
	 */
	public function get_idea_count()
	{
		return $this->idea_count ?? 0;
	}
}
