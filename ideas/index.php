<?php

/**
 *
 * @package phpBB3 Ideas
 * @author Callum Macrae (callumacrae) <callum@lynxphp.com>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

define('IN_IDEAS', true);
$ideas_root_path = __DIR__ . '/';
include($ideas_root_path . 'common.php');

page_header($user->lang['IDEAS_HOME'], false);

$rows = $ideas->get_ideas(10, 'date', 'DESC');
foreach ($rows as $row)
{
	$template->assign_block_vars('latest_ideas', array(
		'ID'		=> $row['idea_id'],
		'LINK'		=> append_sid('./idea.php', 'id=' . $row['idea_id']),
		'TITLE'		=> $row['idea_title'],
		'AUTHOR'	=> ideas_get_user_link($row['idea_author']),
		'DATE'		=> $user->format_date($row['idea_date']),
		'READ'      => $row['read'],
		'VOTES_UP'	=> $row['idea_votes_up'],
		'VOTES_DOWN'=> $row['idea_votes_down'],
		'POINTS'    => $row['idea_votes_up'] - $row['idea_votes_down'],
	));
}

$rows = $ideas->get_ideas(10, 'top', 'DESC');
foreach ($rows as $row)
{
	$template->assign_block_vars('top_ideas', array(
		'ID'		=> $row['idea_id'],
		'LINK'		=> append_sid('./idea.php', 'id=' . $row['idea_id']),
		'TITLE'		=> $row['idea_title'],
		'AUTHOR'	=> ideas_get_user_link($row['idea_author']),
		'DATE'		=> $user->format_date($row['idea_date']),
		'READ'      => $row['read'],
		'VOTES_UP'	=> $row['idea_votes_up'],
		'VOTES_DOWN'=> $row['idea_votes_down'],
		'POINTS'    => $row['idea_votes_up'] - $row['idea_votes_down'],
	));
}

$rows = $ideas->get_ideas(5, 'date', 'DESC', 'idea_status = 3');
foreach ($rows as $row)
{
	$template->assign_block_vars('implemented_ideas', array(
		'ID'		=> $row['idea_id'],
		'LINK'		=> append_sid('./idea.php', 'id=' . $row['idea_id']),
		'TITLE'		=> $row['idea_title'],
		'AUTHOR'	=> ideas_get_user_link($row['idea_author']),
		'DATE'		=> $user->format_date($row['idea_date']),
		'READ'      => $row['read'],
		'VOTES_UP'	=> $row['idea_votes_up'],
		'VOTES_DOWN'=> $row['idea_votes_down'],
		'POINTS'    => $row['idea_votes_up'] - $row['idea_votes_down'],
	));
}

$template->assign_vars(array(
	'U_VIEW_TOP'		=> append_sid('./list.php', 'sort=top'),
	'U_VIEW_LATEST'		=> append_sid('./list.php', 'sort=date'),
	'U_VIEW_IMPLEMENTED'=> append_sid('./list.php', 'status=3'),
	'S_POST_ACTION'		=> append_sid('./posting.php'),
));

$template->set_filenames(array(
    'body' => 'ideas/index_body.html'
));

$template->display('body');
