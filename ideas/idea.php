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
define('IN_IDEAS', true);
$ideas_root_path = (defined('IDEAS_ROOT_PATH')) ? IDEAS_ROOT_PATH : __DIR__ . '/';
include($ideas_root_path . 'common.php');

$mode = request_var('mode', '');
$id = request_var('id', (int) 0);
$vote = request_var('v', 0);
$idea = $ideas->get_idea($id);
if (!$idea)
{
	trigger_error('IDEA_NOT_FOUND');
}

if ($mode === 'vote' && $user->data['user_id'] !== ANONYMOUS)
{
	$message = $ideas->vote($idea, $user->data['user_id'], $vote);
	trigger_error($message);
}

page_header($user->lang['VIEW_IDEA'] . ' - ' . $idea['idea_title'], false);

$template->assign_vars(array(
	'IDEA_TITLE'				=> $idea['idea_title'],
	'IDEA_DESC'				=> generate_text_for_display($idea['idea_desc'], $idea['bbcode_uid'], $idea['bbcode_bitfield'], $idea['bbcode_options']),
	'IDEA_AUTHOR'		=> get_user_link($idea['idea_author']),
	'IDEA_DATE'				=> $user->format_date($idea['idea_date']),
	'IDEA_RATING'			=> round($idea['idea_rating'] * 2, 0) / 2,
	'IDEA_VOTES'			=> $idea['idea_votes'],
	'IDEA_STATUS'			=> $ideas->get_status_from_id($idea['idea_status']),
	'IDEA_STATUS_LINK'=> append_sid('./list.php?status=' . $idea['idea_status']),

	'U_VOTE_1'				=> append_sid("./idea.php?mode=vote&id=$id&v=1"),
	'U_VOTE_2'				=> append_sid("./idea.php?mode=vote&id=$id&v=2"),
	'U_VOTE_3'				=> append_sid("./idea.php?mode=vote&id=$id&v=3"),
	'U_VOTE_4'				=> append_sid("./idea.php?mode=vote&id=$id&v=4"),
	'U_VOTE_5'				=> append_sid("./idea.php?mode=vote&id=$id&v=5"),
));

$template->set_filenames(array(
    'body' => 'idea_body.html'
));

$template->display('body');
