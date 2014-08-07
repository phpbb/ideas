<?php

/**
*
* @package phpBB3 Ideas
* @author Callum Macrae (callumacrae) <callum@lynxphp.com>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

define('IN_PHPBB', true);
include($ideas_root_path . '/config.php');
$phpbb_root_path = PHPBB_ROOT_PATH;
$phpEx = 'php';
include($phpbb_root_path . 'common.php');

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();
include($ideas_root_path . 'lang/en/common.php');

// Set up custom template
$template->set_custom_style('ideas', $ideas_root_path . 'template', '');
$template->assign_var('U_IDEAS_HOME', append_sid('./index.php'));

include($ideas_root_path . '/includes/constants.php');
include($ideas_root_path . '/includes/ideas.php');
$ideas = new Ideas();

include($ideas_root_path . '/includes/functions.php');
