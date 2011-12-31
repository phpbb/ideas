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
	public function get_ideas($number = 10, $sortby = 'idea_date DESC', $where = 'idea_status != 5 && idea_status != 4')
	{
		global $db;
		$sql = 'SELECT idea_id, idea_author, idea_title, idea_date, idea_rating, idea_votes, idea_status
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

	public function vote($idea, $user_id, $value)
	{
		global $db;

		// Validate $vote - must be a whole number between 1 and 5.
		if (!is_int($value) || $value > 5 || $value < 1)
		{
			return 'INVALID_VOTE';
		}

		// Check whether user has already voted - error if they have
		// @todo: Should it update vote instead?
		$sql = 'SELECT idea_id, value
			FROM ' . IDEA_VOTES_TABLE . "
			WHERE idea_id = {$idea['idea_id']}
				AND user_id = $user_id";
		$result = $db->sql_query_limit($sql, 1);
		if ($db->sql_fetchrow())
		{
			return 'ALREADY_VOTED';
		}

		// Insert vote into votes table.
		$sql_ary = array(
			'idea_id'		=> $idea['idea_id'],
			'user_id'		=> $user_id,
			'value'			=> $value,
		);

		$sql = 'INSERT INTO ' . IDEA_VOTES_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
		$db->sql_query($sql);


		// Update rating in IDEAS_TABLE and $idea
		$idea['idea_rating'] = ($idea['idea_rating'] * $idea['idea_votes'] + $value) / ++$idea['idea_votes'];

		$sql_ary = array(
			'idea_rating'	=> $idea['idea_rating'],
			'idea_votes'	=> $idea['idea_votes'],
		);

		$sql = 'UPDATE ' . IDEAS_TABLE . '
			SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE idea_id = ' . $idea['idea_id'];
		$db->sql_query($sql);

		return 'VOTE_SUCCESS';
	}

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
		else
		{
			$uid = $bitfield = $options = '';
			generate_text_for_storage($desc, $uid, $bitfield, $options, true, true, true);

			$sql_ary = array(
				'idea_title'			=> $db->sql_escape($title),
				'idea_desc'			=> $desc,
				'idea_author'		=> $user_id,
				'idea_date'			=> time(),
				'bbcode_uid'		=> $uid,
				'bbcode_bitfield'	=> $bitfield,
				'bbcode_options'	=> $options,
			);

			$sql = 'INSERT INTO ' . IDEAS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
			return $db->sql_nextid();
		}
	}
}
