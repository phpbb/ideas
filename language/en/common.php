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
	'ADD'					=> 'Add',
	'ALL_IDEAS'				=> 'All ideas',
	'ALREADY_VOTED'			=> 'You have already voted on this idea.',

	'CHANGE_STATUS'			=> 'Change status',
	'CLICK_TO_VIEW'			=> 'Click to view votes.',
	'CONFIRM_DELETE'		=> 'Are you sure you want to delete this idea?',

	'DATE'					=> 'Date',
	'DELETE_IDEA'			=> 'Delete idea',
	'DUPLICATE'				=> 'Duplicate',
	'DUPLICATE_PLACEHOLDER'	=> 'Start typing a title',

	'EDIT'					=> 'Edit',
	'ENABLE_JS'             => 'Please enable JavaScript in your browser to use phpBB Ideas effectively.',

	'IDEAS'					=> 'Ideas',
	'IDEA_DELETED'			=> 'Idea successfully deleted.',
	'IDEA_LIST'				=> 'Idea List',
	'IDEA_NOT_FOUND'		=> 'Idea not found',
	'IDEA_STATUS_CHANGE'	=> '<strong>Idea status changed</strong> by %s:',
	'IDEA_STORED_MOD'		=> 'Your idea has been submitted successfully, but it will need to be approved by a moderator before it is publicly viewable. You will be notified when your idea has been approved.<br /><br /><a href="%s">Return to Ideas</a>.',
	'IDEAS_TITLE'			=> 'phpBB Ideas',
	'IDEAS_NOT_AVAILABLE'	=> 'Ideas is not available at this time.',
	'IMPLEMENTED'           => 'Implemented',
	'IMPLEMENTED_ERROR'		=> 'Must be a valid phpBB version number.',
	'IMPLEMENTED_IDEAS'		=> 'Recently Implemented Ideas',
	'IMPLEMENTED_VERSION'	=> 'phpBB version',
	'IN_PROGRESS'           => 'In Progress',
	'IN_PROGRESS_IDEAS'     => 'Ideas In Progress',
	'INVALID'				=> 'Invalid',
	'INVALID_IDEA_QUERY'	=> 'Invalid SQL query. Ideas failed to load.',
	'INVALID_VOTE'			=> 'Invalid vote; the number you entered was invalid.',

	'JS_DISABLED'           => 'JavaScript is disabled',

	'LATEST_IDEAS'			=> 'Latest Ideas',
	'LIST_DUPLICATE'		=> 'Duplicate ideas',
	'LIST_EGOSEARCH'		=> 'My Ideas',
	'LIST_IMPLEMENTED'		=> 'Implemented ideas',
	'LIST_IN_PROGRESS'		=> 'In Progress ideas',
	'LIST_INVALID'			=> 'Invalid ideas',
	'LIST_NEW'				=> 'New ideas',
	'LIST_TOP'				=> 'Top ideas',

	'LOGGED_OUT'			=> 'You must be logged in to do this.',

	'NEW'					=> 'New',
	'NEW_IDEA'				=> 'New Idea',
	'NO_IDEAS_DISPLAY'		=> 'There are no ideas to display.',
	'NOTIFICATION_STATUS'	=> '<em>Status: <strong>%s</strong></em>',

	'OPEN_IDEAS'			=> 'Open ideas',

	'POST_IDEA'				=> 'Post a new idea',
	'POSTING_NEW_IDEA'		=> 'Posting a new idea',

	'REMOVE_VOTE'			=> 'Remove my vote',
	'RETURN_IDEAS'			=> '%sReturn to Ideas%s',
	'RFC'					=> 'RFC',
	'RFC_ERROR'				=> 'RFC must be a topic on Area51.',
	'RFC_LINK_TEXT'			=> 'View RFC discussion on Area51',

	'SEARCH_IDEAS'			=> 'Search ideas...',
	'SCORE'                 => 'Score',
	'SHOW_W_STATUS'			=> 'Display ideas with status',
	'STATUS'				=> 'Status',

	'TICKET'				=> 'Ticket',
	'TICKET_ERROR'			=> 'Ticket ID must be of the format “PHPBB-#####” or “PHPBB3-#####”.',
	'TICKET_ERROR_DUP'		=> 'You must click on an idea title from the live search results. To delete the duplicate, clear the field and press ENTER. To exit this field press ESC.',
	'TITLE'					=> 'Title',
	'TOP'					=> 'Top',
	'TOP_IDEAS'				=> 'Top Ideas',
	'TOTAL_IDEAS'			=> [
		1	=> '%d idea',
		2	=> '%d ideas',
	],
	'TOTAL_POINTS'			=> [
		1	=> '%s point.',
		2	=> '%s points.',
	],

	'UPDATED_VOTE'			=> 'Successfully updated vote!',

	'USER_ALREADY_VOTED'	=> [
		0 => 'You voted against this idea',
		1 => 'You voted for this idea',
	],

	'VIEW_IDEA'				=> 'View Idea',
	'VIEW_IMPLEMENTED'		=> 'View all implemented ideas',
	'VIEW_IN_PROGRESS'		=> 'View all in progress ideas',
	'VIEW_LATEST'			=> 'View all open ideas',
	'VIEW_TOP'				=> 'View all top voted ideas',
	'VIEWING_IDEAS'			=> 'Viewing Ideas',
	'VOTE'					=> 'Vote',
	'VOTE_DOWN'				=> 'Vote Down',
	'VOTE_ERROR'			=> 'An error occurred',
	'VOTE_FAIL'				=> 'Failed to vote; check your connection.',
	'VOTE_SUCCESS'			=> 'Successfully voted on this idea.',
	'VOTE_UP'				=> 'Vote Up',
	'VOTES'					=> 'Votes',
));
