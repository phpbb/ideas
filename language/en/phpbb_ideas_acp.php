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
	'ACP_IDEAS_FORUM_ID_EXPLAIN'	=> 'Select the forum that Ideas topics will be posted to. Once done, apply Ideas forum setup clicking the appropriate button below.',
	'ACP_IDEAS_FORUM_SETUP'			=> 'Set up the Ideas forum',
	'ACP_IDEAS_FORUM_SETUP_CONFIRM'	=> 'Are you sure you wish to set up the Ideas forum?',
	'ACP_IDEAS_FORUM_SETUP_EXPLAIN'	=> 'Sets up the Ideas forum. Users will not be able to start new topics. Additionally, auto-pruning will be disabled. Note: you have to set the Ideas forum first.',
	'ACP_IDEAS_FORUM_SETUP_UPDATED'	=> 'phpBB Ideas forum settings successfully updated.',
	'ACP_IDEAS_NO_FORUM'			=> 'No forum selected',
	'ACP_IDEAS_POSTER_ID'			=> 'Ideas user bot',
	'ACP_IDEAS_POSTER_ID_EXPLAIN'	=> 'Enter a username for the Idea-bot that will post Ideas topics into the forum.',
	'ACP_IDEAS_SETTINGS_UPDATED'	=> 'phpBB Ideas settings updated.',
	'ACP_IDEAS_UTILITIES'			=> 'Ideas utilities',
	'ACP_IDEAS_BASE_URL'			=> 'Ideas base URL (optional)',
	'ACP_IDEAS_BASE_URL_EXPLAIN'	=> 'Enter the full URL to Ideas, e.g.: <samp>http://www.phpbb.com/ideas</samp>. This should only be set if your board has custom routing configurations that prevent Ideas from working as expected.',
	'ACP_PHPBB_IDEAS_EXPLAIN'		=> 'Here you can configure phpBB Ideas extension. phpBB Ideas is an ideas centre for phpBB. It allows users to suggest and vote on ideas that would help to improve and enhance phpBB.',
));
