<?php
/**
*
* common [English]
*
* @package phpBB Ideas
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_IDEAS'))
{
	exit;
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$user->lang = array_merge($user->lang, array(
	'ACCEPTED'				=> 'Accepted',
	'ALL_IDEAS'				=> 'All ideas',
	'ALREADY_VOTED'	=> 'You have already voted on this idea.',

	'CREATE_IDEA'			=> 'Create new idea',

	'DATE'						=> 'Date',
	'DESC'						=> 'Description',
	'DESC_TOO_LONG'	=> 'Description must be under 10,000 characters long.',
	'DESC_TOO_SHORT'	=> 'Description must be at least 5 characters long.',
	'DUPLICATE'				=> 'Duplicate',

	'ID'							=> 'ID',
	'IDEAS'						=> 'Ideas',
	'IDEA_LIST'				=> 'Idea List',
	'IDEA_NOT_FOUND'	=> 'Idea not found',
	'IDEAS_HOME'			=> 'Ideas Home',
	'INVALID_VOTE'		=> 'Invalid vote; the number you entered was invalid.',

	'LATEST_IDEAS'		=> 'Latest Ideas',
	'LOGGED_OUT'			=> 'You must be logged in to post ideas',

	'MERGED'					=> 'Merged',

	'NEW'						=> 'New',
	'NEW_IDEA'				=> 'New Idea',
	'NO_IDEAS'				=> 'No ideas',

	'POST_IDEA'				=> 'Post idea',

	'RATING'					=> 'Rating',
	'REJECTED'				=> 'Rejected',

	'SHOW_W_STATUS'	=> 'Display ideas with status:',
	'STATUS'					=> 'Status',

	'TITLE'						=> 'Title',
	'TITLE_TOO_LONG'	=> 'Title must be under 64 characters long.',
	'TITLE_TOO_SHORT'	=> 'Title must be 6 characters long.',
	'TOP_IDEAS'				=> 'Top Ideas',

	'VIEW_ALL'				=> 'View All',
	'VIEW_IDEA'				=> 'View Idea',
	'VIEWING_IDEA'		=> 'Viewing Idea',
	'VOTE'						=> 'Vote',
	'VOTE_SUCCESS'		=> 'Successfully voted on this idea.',
	'VOTES'					=> 'votes',
));
