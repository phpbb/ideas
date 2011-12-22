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


define('IDEAS_TABLE', $table_prefix . 'ideas_ideas');
include($ideas_root_path . '/includes/ideas.php');
$ideas = new Ideas();
