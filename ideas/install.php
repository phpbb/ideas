<?php
/**
 *
 * @package phpBB3 Ideas
 * @author callumacrae (Callum Macrae) callum@phpbb.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * @ignore
 */
define('UMIL_AUTO', true);
define('IN_PHPBB', true);
include('config.php'); // Defines PHPBB_ROOT_PATH
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);

include($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();


if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
	trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}

// The name of the mod to be displayed during installation.
$mod_name = 'phpbb-ideas';

/*
* The name of the config variable which will hold the currently installed version
* UMIL will handle checking, setting, and updating the version itself.
*/
$version_config_name = 'phpbb_ideas_version';

/*
* Optionally we may specify our own logo image to show in the upper corner instead of the default logo.
* $phpbb_root_path will get prepended to the path specified
* Image height should be 50px to prevent cut-off or stretching.
*/
//$logo_img = 'styles/prosilver/imageset/site_logo.gif';

/*
* The array of versions and actions within each.
* You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
*
* You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
* The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
*/
$versions = array(
	'1.0.0-RC3' => array(
		'table_add' => array(
			array($table_prefix . 'ideas_ideas', array(
				'COLUMNS' => array(
					'idea_id' => array('UINT', NULL, 'auto_increment'),
					'idea_author' => array('UINT', 0),
					'idea_title' => array('VCHAR', ''),
					'idea_date' => array('TIMESTAMP', 0),
					'idea_votes_up' => array('UINT', 0),
					'idea_votes_down' => array('UINT', 0),
					'idea_status' => array('UINT', 1),
					'topic_id' => array('UINT', 0),
				),

				'PRIMARY_KEY'	=> 'idea_id',

				'KEYS'		=> array(
					'idea_id' => array('INDEX', array('idea_id')),
				),
			)),

			array($table_prefix . 'ideas_statuses', array(
				'COLUMNS' => array(
					'status_id' => array('UINT', 0),
					'status_name' => array('VCHAR', ''),
				),

				'PRIMARY_KEY'	=> 'status_id',

				'KEYS'		=> array(
					'status_id' => array('INDEX', array('status_id')),
				),
			)),

			array($table_prefix . 'ideas_tickets', array(
				'COLUMNS' => array(
					'idea_id' => array('UINT', 0),
					'ticket_id' => array('UINT', 0),
				),

				'KEYS'		=> array(
					'ticket_key' => array('INDEX', array('idea_id', 'ticket_id')),
				),
			)),

			array($table_prefix . 'ideas_rfcs', array(
				'COLUMNS' => array(
					'idea_id' => array('UINT', 0),
					'rfc_link' => array('VCHAR', ''),
				),

				'KEYS'		=> array(
					'rfc_key' => array('INDEX', array('idea_id', 'rfc_link')),
				),
			)),

			array($table_prefix . 'ideas_votes', array(
				'COLUMNS' => array(
					'idea_id' => array('UINT', 0),
					'user_id' => array('UINT', 0),
					'vote_value' => array('BOOL', 0),
				),

				'KEYS'		=> array(
					'idea_id' => array('INDEX', array('idea_id', 'user_id')),
				),
			)),

		),

		'table_row_insert'	=> array(
			array($table_prefix . 'ideas_statuses', array(
				array(
					'status_id'		=> 1,
					'status_name'	=> 'New'
				),
				array(
					'status_id'		=> 2,
					'status_name'	=> 'In Progress'
				),
				array(
					'status_id'		=> 3,
					'status_name'	=> 'Implemented'
				),
				array(
					'status_id'		=> 4,
					'status_name'	=> 'Duplicate'
				),
			)),
		),

	),

	'1.0.0-RC4' => array(
		'custom'    => array('add_ideas_bbcode'),

		'table_add' => array(

			array($table_prefix . 'ideas_duplicates', array(
				'COLUMNS' => array(
					'idea_id' => array('UINT', 0),
					'duplicate_id' => array('UINT', 0),
				),

				'KEYS'		=> array(
					'dupe_key' => array('INDEX', array('idea_id', 'duplicate_id')),
				),
			)),

		),
	),

	'1.0.1' => array(
		'table_row_insert'	=> array(
			array($table_prefix . 'ideas_statuses', array(
				array(
					'status_id'		=> 5,
					'status_name'	=> 'Invalid'
				),
			),),
		),
	),
);

// Include the UMIL Auto file, it handles the rest
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);

function add_ideas_bbcode()
{
	// @todo
}
