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

if ($mode === 'vote' && $user->data['user_id'] != ANONYMOUS)
{
	$message = $ideas->vote($idea, $user->data['user_id'], $vote);

	if ($request->is_ajax())
	{
		header('Content-Type: application/json');
		echo json_encode($user->lang[$message]);
		garbage_collection();
		exit_handler();
	}
	else
	{
		trigger_error($message);
	}
}
else if ($mode === 'delete' && $auth->acl_get('m_mod_ideas'))
{
	$ideas->delete($id);
	$message = $user->lang['IDEA_DELETED'] . '<br /><br />';
	$message .= sprintf($user->lang['RETURN_INDEX'], '<a href="' . append_sid('./index.php') . '">', '</a>');
	trigger_error($message);
}

if ($request->is_ajax())
{
	header('Content-Type: application/json');
	echo json_encode($user->lang['LOGGED_OUT']);
	garbage_collection();
	exit_handler();
}

page_header($user->lang['VIEW_IDEA'] . ' - ' . $idea['idea_title'], false);

$template->assign_vars(array(
	'IDEA_ID'					=> $idea['idea_id'],
	'IDEA_TITLE'				=> $idea['idea_title'],
	'IDEA_DESC'				=> generate_text_for_display($idea['idea_desc'], $idea['bbcode_uid'], $idea['bbcode_bitfield'], $idea['bbcode_options']),
	'IDEA_AUTHOR'		=> get_user_link($idea['idea_author']),
	'IDEA_DATE'				=> $user->format_date($idea['idea_date']),
	'IDEA_RATING'			=> round($idea['idea_rating'] * 10, 0) / 10,
	'IDEA_VOTES'			=> $idea['idea_votes'],
	'IDEA_STATUS'			=> $ideas->get_status_from_id($idea['idea_status']),
	'IDEA_STATUS_LINK'=> append_sid('./list.php?status=' . $idea['idea_status']),

	'U_DELETE_IDEA'		=> $auth->acl_get('m_mod_ideas'),
	'U_IDEA_VOTE'			=> append_sid('./idea.php?mode=vote&id=' . $id),
	'U_IDEA_MOD'			=> append_sid('./idea.php'),
));

$template->set_filenames(array(
    'body' => 'idea_body.html'
));

$template->display('body');
