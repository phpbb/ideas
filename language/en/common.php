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

	'DATE'					=> 'Date',
	'DELETE_IDEA'			=> 'Delete idea',
	'DUPLICATE'				=> 'Duplicate',

	'EDIT'					=> 'Edit',
	'ENABLE_JS'             => 'Please enable JavaScript in your browser to use phpBB Ideas effectively.',

	'ID'					=> 'ID',
	'IDEAS'					=> 'Ideas',
	'IDEA_DELETED'			=> 'Idea successfully deleted.',
	'IDEA_LIST'				=> 'Idea List',
	'IDEA_NOT_FOUND'		=> 'Idea not found',
	'IDEAS_TITLE'			=> 'phpBB Ideas',
	'IDEAS_NOT_AVAILABLE'	=> 'Ideas is not available at this time.',
	'IMPLEMENTED'           => 'Implemented',
	'IMPLEMENTED_IDEAS'		=> 'Recently Implemented Ideas',
	'IN_PROGRESS'           => 'In Progress',
	'INVALID'				=> 'Invalid',
	'INVALID_VOTE'			=> 'Invalid vote; the number you entered was invalid.',

	'JS_DISABLED'           => 'JavaScript is disabled',

	'LATEST_IDEAS'			=> 'Latest Ideas',
	'LOGGED_OUT'			=> 'You must be logged in to do this.',

	'NEW'					=> 'New',
	'NEW_IDEA'				=> 'New Idea',
	'NO_IDEAS_DISPLAY'		=> 'There are no ideas to display.',

	'OPEN_IDEAS'			=> 'Open ideas',

	'POST_IDEA'				=> 'Post a new idea',
	'POSTING_NEW_IDEA'		=> 'Posting a new idea',

	'RATING'                => 'Rating',
	'REMOVE_VOTE'			=> 'Remove my vote',
	'RETURN_IDEAS'			=> '%sReturn to Ideas%s',
	'RFC'					=> 'RFC',
	'RFC_ERROR'				=> 'RFC must be a topic on Area51.',

	'SEARCH_IDEAS'			=> 'Search ideas...',
	'SET'                   => 'Set',
	'SCORE'                 => 'Score',
	'SHOW_W_STATUS'			=> 'Display ideas with status',
	'STATUS'				=> 'Status',

	'TICKET'				=> 'Ticket',
	'TICKET_ERROR'			=> 'Ticket ID must be of the format “PHPBB3-#####”.',
	'TICKET_ERROR_DUP'		=> 'Please post the ID of the ticket.',
	'TITLE'					=> 'Title',
	'TITLE_EDIT'            => 'Edit title',
	'TITLE_EDIT_ERROR'		=> 'Subject must be between 1 and 64 characters.',
	'TITLE_TOO_LONG'		=> 'Subject must be under 64 characters long.',
	'TITLE_TOO_SHORT'		=> 'You must specify a subject when posting a new idea.',
	'TOP'                   => 'Top',
	'TOP_IDEAS'				=> 'Top Ideas',
	'TOTAL_IDEAS'			=> array(
		1	=> '%d idea',
		2	=> '%d ideas',
	),
	'TOTAL_POINTS'			=> array(
		1	=> '%s point.',
		2	=> '%s points.',
	),

	'UPDATED_VOTE'			=> 'Successfully updated vote!',

	'VIEW_ALL'				=> 'View All',
	'VIEW_DUPLICATE'		=> 'View duplicate',
	'VIEW_IDEA'				=> 'View Idea',
	'VIEW_IMPLEMENTED'		=> 'View all implemented ideas',
	'VIEW_LATEST'			=> 'View all open ideas',
	'VIEW_TOP'				=> 'View all top rated ideas',
	'VIEWING_IDEAS'			=> 'Viewing Ideas',
	'VOTE'					=> 'Vote',
	'VOTE_DOWN'				=> 'Vote Down',
	'VOTE_ERROR'			=> 'An error occurred',
	'VOTE_FAIL'				=> 'Failed to vote; check your connection.',
	'VOTE_SUCCESS'			=> 'Successfully voted on this idea.',
	'VOTE_UP'				=> 'Vote Up',
	'VOTES'					=> 'Votes',
));
