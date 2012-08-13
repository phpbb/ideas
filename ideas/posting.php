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
	$submit = $ideas->submit($title, $desc, $user->data['user_id']);

	if (is_array($submit))
	{
		$template->assign_vars(array(
			'ERROR'		=> implode('<br />', $submit),
			'DESC'		=> $desc,
		));
	}
	else
	{
		header('Location: ' . append_sid('./idea.php?id=' . $submit));
		garbage_collection();
		exit_handler();
	}
}

page_header($user->lang['NEW_IDEA'], false);

display_custom_bbcodes();
generate_smilies('inline', 0);

$template->assign_vars(array(
	'S_POST_ACTION'		=> append_sid('posting.php?mode=submit'),
	'TITLE'				=> $title,
));

$template->set_filenames(array(
	'body' => 'idea_new.html'
));

$template->display('body');
