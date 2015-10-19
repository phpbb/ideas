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
	'ACP_IDEAS_FORUM_ID_EXPLAIN'	=> 'Select the forum that Ideas topics will be posted to.',
	'ACP_IDEAS_POSTER_ID'			=> 'Ideas user bot',
	'ACP_IDEAS_POSTER_ID_EXPLAIN'	=> 'Enter a username for the Idea-bot that will post Ideas topics into the forum.',
	'ACP_NO_FORUM_SELECTED'			=> 'No forum selected',
	'ACP_PHPBB_IDEAS_EXPLAIN'		=> 'Here you can configure phpBB Ideas extension. phpBB Ideas is an ideas centre for phpBB. It allows users to suggest and vote on ideas that would help improving and enhancing phpBB.',
	'ACP_PHPBB_IDEAS_SETTINGS_CHANGED'	=> 'phpBB Ideas settings changed.',
));
