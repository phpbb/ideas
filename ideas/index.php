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

$rows = $ideas->get_ideas(10, 'idea_date DESC', 'idea_status != 5 ');
foreach ($rows as $row)
{
	$template->assign_block_vars('latest_ideas', array(
		'ID'		=> $row['idea_id'],
		'LINK'		=> append_sid('./idea.php', 'id=' . $row['idea_id']),
		'TITLE'		=> $row['idea_title'],
		'AUTHOR'	=> ideas_get_user_link($row['idea_author']),
		'DATE'		=> $user->format_date($row['idea_date']),
		'RATING'	=> round($row['idea_rating'] * 10, 0) / 10,
		'READ'      => $row['read'],
		'VOTES'		=> $row['idea_votes'],
	));
}

$rows = $ideas->get_ideas(10, 'idea_rating DESC, idea_votes DESC', 'idea_status != 5 && idea_status != 3 && idea_status != 4 && idea_votes != 0');
foreach ($rows as $row)
{
	$template->assign_block_vars('top_ideas', array(
		'ID'		=> $row['idea_id'],
		'LINK'		=> append_sid('./idea.php', 'id=' . $row['idea_id']),
		'TITLE'		=> $row['idea_title'],
		'AUTHOR'	=> ideas_get_user_link($row['idea_author']),
		'DATE'		=> $user->format_date($row['idea_date']),
		'RATING'	=> round($row['idea_rating'] * 10, 0) / 10,
		'READ'      => $row['read'],
		'VOTES'		=> $row['idea_votes'],
	));
}

$template->assign_vars(array(
	'U_VIEW_TOP'		=> append_sid('./list.php'),
	'U_VIEW_LATEST'		=> append_sid('./list.php', 'sort=date'),
	'S_POST_ACTION'		=> append_sid('./posting.php'),
));

$template->set_filenames(array(
    'body' => 'index_body.html'
));

$template->display('body');
