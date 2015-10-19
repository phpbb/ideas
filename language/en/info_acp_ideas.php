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
	'ACP_PHPBB_IDEAS_EXPLAIN'	=> 'Here you can configure phpBB Ideas extension.<br />phpBB Ideas is an ideas centre for phpBB. It is based on <a href="http://wordpress.org/extend/ideas/">WordPress ideas</a>, and allows users to suggest and vote on "ideas" that would help improve and enhance phpBB.',
	'ACP_PHPBB_IDEAS_SETTINGS'	=> 'Ideas settings',

	// ACP Logs
	'ACP_IDEA_TITLE_EDITED_LOG'			=> '<strong>Idea title edited</strong><br />» Idea ID #%s',
	'ACP_PHPBB_IDEAS_SETTINGS_LOG'		=> '<strong>phpBB Ideas settings changed</strong>',
));
