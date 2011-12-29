<?php

/**
*
* @package phpBB3 Ideas
* @author Callum Macrae (callumacrae) <callum@lynxphp.com>
* @author Mark Barnes (MarkTheDaemon)
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_IDEAS'))
{
	exit;
}

/**
* phpBB Ideas class
* @package phpBB Ideas
*/
class Ideas
{
	public function get_ideas($number = 10, $sortby = 'idea_date DESC', $where = 'idea_category != 5')
	{
		global $db;
		$sql = 'SELECT idea_id, idea_author, idea_title, idea_date, idea_rating, idea_votes, idea_category
			FROM ' . IDEAS_TABLE . "
			WHERE $where
			ORDER BY $sortby";
		$result = $db->sql_query_limit($sql, $number);
		$rows = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		return $rows;
	}

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
}
