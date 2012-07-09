<?php

define('IN_IDEAS', true);
$ideas_root_path = (defined('IDEAS_ROOT_PATH')) ? IDEAS_ROOT_PATH : __DIR__ . '/';
include($ideas_root_path . 'common.php');


// Add permissions (we use SQL to insert so that we can get the row ID without another query.)

$sql = 'INSERT INTO ' . ACL_OPTIONS_TABLE . ' (auth_option, is_global, is_local, founder_only) VALUES (\'m_mod_ideas\', 1, 0, 0)';
$db->sql_query($sql);

$sql_ary = array(
	'group_id'			=> 4,
	'auth_option_id'	=> $db->sql_nextid(),
	'auth_setting'		=> ACL_YES,
);

$sql = 'INSERT INTO ' . ACL_GROUPS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
$db->sql_query($sql);

$cache->destroy('acl_options');
$auth->acl_clear_prefetch();


// Create and populate database tables

$db->sql_query('CREATE TABLE  ' . $table_prefix . 'ideas_statuses (
status_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
status_name VARCHAR( 200 ) NOT NULL
) ENGINE = MYISAM ;');

$db->sql_query('CREATE TABLE  ' . $table_prefix . 'ideas_ideas (
idea_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
idea_author INT NOT NULL ,
idea_title VARCHAR( 200 ) NOT NULL ,
idea_date INT NOT NULL ,
idea_comments INT NOT NULL DEFAULT 0 ,
idea_rating FLOAT NOT NULL DEFAULT 0 ,
idea_votes INT NOT NULL DEFAULT 0 ,
idea_status INT NOT NULL DEFAULT 1 ,
topic_id INT NOT NULL
) ENGINE = MYISAM ;');

$db->sql_query('CREATE TABLE  ' . $table_prefix . 'ideas_votes (
idea_id INT NOT NULL ,
user_id INT NOT NULL ,
value INT NOT NULL ,
UNIQUE (
idea_id , user_id
)
) ENGINE = MYISAM ;');

$db->sql_query("INSERT INTO {$table_prefix}ideas_statuses (status_id, status_name) VALUES
(1, 'New'),
(2, 'Accepted'),
(3, 'Rejected'),
(4, 'Merged'),
(5, 'Duplicate');");

echo 'Successfully set up permissions and database. To complete installation, please copy permissions_ideas.php (found in the files directory) into language/en/mods, and delete this file.';
