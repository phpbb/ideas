<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	// ACP module
	'ACP_PHPBB_IDEAS'			=> 'phpBB Ideas',
	'ACP_PHPBB_IDEAS_SETTINGS'	=> 'Ideas settings',

	// ACP Logs
	'ACP_PHPBB_IDEAS_SETTINGS_LOG'		=> '<strong>phpBB Ideas settings updated</strong>',
	'ACP_PHPBB_IDEAS_FORUM_SETUP_LOG'	=> '<strong>phpBB Ideas forum setup applied</strong>',
));
