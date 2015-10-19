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
	'ACP_IDEAS_FORUM_ID'			=> 'Ideas forum',
	'ACP_IDEAS_FORUM_ID_EXPLAIN'	=> 'Select the forum that ideas topics will be posted to.',
	'ACP_IDEAS_POSTER_ID'			=> 'Ideas poster',
	'ACP_IDEAS_POSTER_ID_EXPLAIN'	=> 'Enter username that will post ideas topics into the forums.',
	'ACP_NO_FORUM_SELECTED'			=> 'No forum selected',
	'ACP_PHPBB_IDEAS_SETTINGS_CHANGED'	=> 'phpBB Ideas settings changed.',
));
