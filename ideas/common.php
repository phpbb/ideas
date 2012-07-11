<?php

/**
*
* @package phpBB3 Ideas
* @author Callum Macrae (callumacrae) <callum@lynxphp.com>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
include($ideas_root_path . '/config.php');
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();
include($ideas_root_path . 'lang/en/common.php');

// Set up custom template
$template->set_custom_template($ideas_root_path . 'template', 'default');
$template->assign_var('U_IDEAS_HOME', append_sid('./index.php'));

// We are not modifying constants.php - define IDEAS_TABLE here.
define('IDEAS_TABLE', $table_prefix . 'ideas_ideas');
define('IDEA_STATUS_TABLE', $table_prefix . 'ideas_statuses');
define('IDEA_VOTES_TABLE', $table_prefix . 'ideas_votes');
include($ideas_root_path . '/includes/ideas.php');
$ideas = new Ideas();

include($ideas_root_path . '/includes/functions.php');
