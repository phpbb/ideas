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
include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/message_parser.' . $phpEx);

$user->add_lang('posting');

if ($user->data['user_id'] == ANONYMOUS)
{
	trigger_error('LOGGED_OUT');
}

$mode = request_var('mode', '');
$title = utf8_normalize_nfc(request_var('title', ''));
$desc = utf8_normalize_nfc(request_var('desc', '', true));

if ($mode === 'submit')
{
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
		$template->assign_vars(array(
			'ERROR'	=> implode('<br />', $error),
			'TITLE'		=> $title,
			'DESC'		=> $desc,
		));
	}
	else
	{
		$uid = $bitfield = $options = '';
		generate_text_for_storage($desc, $uid, $bitfield, $options, true, true, true);
		
		$sql_ary = array(
			'idea_title'			=> $db->sql_escape($title),
			'idea_desc'			=> $desc,
			'idea_author'		=> $user->data['user_id'],
			'idea_date'			=> time(),
			'bbcode_uid'		=> $uid,
			'bbcode_bitfield'	=> $bitfield,
			'bbcode_options'	=> $options,
		);
		
		$sql = 'INSERT INTO ' . IDEAS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
		$db->sql_query($sql);
		$id = $db->sql_nextid();
		header('Location: ' . append_sid('./idea.php?id=' . $id));
		garbage_collection();
		exit_handler();
	}
}

page_header($user->lang['NEW_IDEA'], false);

display_custom_bbcodes();
generate_smilies('inline', 0);

$template->assign_vars(array(
	'S_POST_ACTION'		=> append_sid('posting.php?mode=submit'),
	'TITLE'						=> $title,
));

$template->set_filenames(array(
	'body' => 'idea_new.html'
));

$template->display('body');