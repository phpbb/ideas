<?php
/**
 *
 * @package phpBB3 Ideas
 * @author Callum Macrae (callumacrae) <callum@lynxphp.com>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */


if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACCEPTED'				=> 'Accepted',
	'ADD'					=> 'Add',
	'ALL_IDEAS'				=> 'All ideas',
	'ALREADY_VOTED'			=> 'You have already voted on this idea.',

	'CREATE_IDEA'			=> 'Create new idea',

	'DATE'					=> 'Date',
	'DESC'					=> 'Description',
	'DESC_TOO_LONG'			=> 'Description must be under 10,000 characters long.',
	'DESC_TOO_SHORT'		=> 'Description must be at least 5 characters long.',
	'DUPLICATE'				=> 'Duplicate',

	'EDIT'					=> 'Edit',
	'ENABLE_JS'             => 'Please enable JavaScript to use phpBB Ideas correctly',

	'ID'					=> 'ID',
	'IDEAS'					=> 'Ideas',
	'IDEA_DELETED'			=> 'Idea successfully deleted.',
	'IDEA_LIST'				=> 'Idea List',
	'IDEA_NOT_FOUND'		=> 'Idea not found',
	'IDEA_TOPIC'            => 'Idea topic',
	'IDEA_POSTER'           => 'Posted by %s', // Warning: submitted to db
	'IDEAS_HOME'			=> 'Ideas Home',
	'IMPLEMENTED'           => 'Implemented',
	'IMPLEMENTED_IDEAS'		=> 'Recently Implemented Ideas',
	'IN_PROGRESS'           => 'In Progress',
	'INVALID_VOTE'			=> 'Invalid vote; the number you entered was invalid.',

	'JS_DISABLED'           => 'JavaScript is disabled',

	'LATEST_IDEAS'			=> 'Latest Ideas',
	'LOGGED_OUT'			=> 'You must be logged in to do this.',

	'MERGED'				=> 'Merged',
	'MOD_IDEA'				=> 'Moderate idea',

	'NEW'						=> 'New',
	'NEW_IDEA'				=> 'New Idea',
	'NO_IDEAS'				=> 'No ideas',

	'POST_IDEA'				=> 'Post idea',

	'RATING'                => 'Rating',
	'REJECTED'				=> 'Rejected',
	'RFC'					=> 'RFC',

	'SET'                   => 'Set',
	'SCORE'                 => 'Score',
	'SHOW_W_STATUS'			=> 'Display ideas with status:',
	'STATUS'				=> 'Status',

	'TICKET'				=> 'Ticket',
	'TITLE'					=> 'Title',
	'TITLE_EDIT'            => 'Edit title',
	'TITLE_TOO_LONG'		=> 'Title must be under 64 characters long.',
	'TITLE_TOO_SHORT'		=> 'Title must be 6 characters long.',
	'TOP'                   => 'Top',
	'TOP_IDEAS'				=> 'Top Ideas',

	'UPDATED_VOTE'			=> 'Successfully updated vote',

	'VIEW_ALL'				=> 'View All',
	'VIEW_IDEA'				=> 'View Idea',
	'VIEW_IDEA_AT'          => 'View idea at: %s', // Warning: submitted to db
	'VIEW_VOTES'            => '%s points. Click to view votes',
	'VIEWING_IDEA'			=> 'Viewing Idea',
	'VOTE'					=> 'Vote',
	'VOTE_SUCCESS'			=> 'Successfully voted on this idea.',
	'VOTES'					=> 'Votes',
));
