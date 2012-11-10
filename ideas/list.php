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

$sort = request_var('sort', '');
$sort_direction = (request_var('sd', 'd')) === 'd' ? 'DESC' : 'ASC';
$status = request_var('status', 0);
$author = request_var('author', 0);

$where = $status ? "idea_status = $status" : 'idea_status != 5';
if ($author)
{
	$where .= " && idea_author = $author";
}

switch (strtolower($sort))
{
	case 'author':
		$sort = 'idea_author ' . $sort_direction;
		break;

	case 'date':
		$sort = 'idea_date ' . $sort_direction;
		break;

	case 'id':
		$sort = 'idea_id ' . $sort_direction;
		break;

	case 'title':
		$sort = 'idea_title ' . $sort_direction;
		break;

	case 'votes':
		$sort = 'idea_votes ' . $sort_direction;
		break;

	case 'rating':
	default:
		$sort = 'idea_rating ' . $sort_direction . ', idea_votes ' . $sort_direction;
		break;
}

$ideas = $ideas->get_ideas(0, $sort, $where);

foreach ($ideas as $idea)
{
	$template->assign_block_vars('ideas', array(
		'ID'			=> $idea['idea_id'],
		'LINK'			=> append_sid('./idea.php', 'id=' . $idea['idea_id']),
		'TITLE'			=> $idea['idea_title'],
		'AUTHOR'		=> ideas_get_user_link($idea['idea_author']),
		'DATE'			=> $user->format_date($idea['idea_date']),
		'RATING'		=> round($idea['idea_rating'] * 10, 0) / 10,
		'READ'          => $idea['read'],
		'VOTES'			=> $idea['idea_votes'],
		'STATUS'		=> $idea['idea_status'], // For icons
	));
}

page_header($user->lang['IDEA_LIST'], false);

$statuses = array('new', 'accepted', 'rejected', 'duplicate', 'merged');
$prevstatus = request_var('status', '');
foreach ($statuses as $key => $status)
{
	$template->assign_block_vars('status', array(
		'VALUE'		=> $key + 1,
		'TEXT'		=> $user->lang[strtoupper($status)],
		'SELECTED'	=> $prevstatus == $key + 1,
	));
}

$sorts = array('author', 'date', 'id', 'title', 'votes', 'rating');
$sorted = request_var('sort', 'rating');
foreach ($sorts as $sort)
{
	$template->assign_block_vars('sortby', array(
		'VALUE'		=> $sort,
		'TEXT'		=> $user->lang[strtoupper($sort)],
		'SELECTED'	=> $sort == $sorted,
	));
}

$template->assign_vars(array(
	'U_POST_ACTION'		=> append_sid('./list.php'),
	'SORT_DIRECTION'	=> $sort_direction,
));

$template->set_filenames(array(
    'body' => 'list_body.html'
));

$template->display('body');
