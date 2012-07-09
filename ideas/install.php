<?php

define('IN_IDEAS', true);
$ideas_root_path = (defined('IDEAS_ROOT_PATH')) ? IDEAS_ROOT_PATH : __DIR__ . '/';
include($ideas_root_path . 'common.php');

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

echo 'Successfully set up database. Modify config.php.';
