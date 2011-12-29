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
	'DESC'						=> 'Description',
	'IDEA_NOT_FOUND'	=> 'Idea not found',
	'IDEAS_HOME'			=> 'Ideas Home',
	'LATEST_IDEAS'			=> 'Latest Ideas',
	'RATING'					=> 'Rating',
	'SCORE'						=> 'Score',
	'TOP_IDEAS'				=> 'Top Idea',
	'VIEW_ALL'					=> 'View All',
	'VIEW_IDEA'				=> 'View Idea',
	'VIEWING_IDEA'			=> 'Viewing Idea',
	'VOTES'						=> 'votes',
));
