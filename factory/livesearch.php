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
 * Class for handling multiple ideas
 */
class livesearch extends base
{
	/** @var array */
	protected $sql;

	/**
	 * Do a live search on idea titles. Return any matches based on a given search query.
	 *
	 * @param string $search The string of characters to search using LIKE
	 * @param int    $limit  The number of results to return
	 *
	 * @return array An array of matching idea id/key and title/values
	 */
	public function title_search($search, $limit = 10)
	{
		$results = [];
		$sql = 'SELECT idea_title, idea_id
			FROM ' . $this->table_ideas . '
			WHERE idea_title ' . $this->db->sql_like_expression($this->db->get_any_char() . $search . $this->db->get_any_char());
		$result = $this->db->sql_query_limit($sql, $limit);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$results[] = [
				'idea_id'     => $row['idea_id'],
				'result'      => $row['idea_id'],
				'clean_title' => $row['idea_title'],
				'display'     => "<span>{$row['idea_title']}</span>", // spans are expected in phpBB's live search JS
			];
		}
		$this->db->sql_freeresult($result);

		return $results;
	}
}
