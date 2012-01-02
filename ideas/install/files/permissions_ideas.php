<?php
/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = array();
}

// Adding the permissions
$lang = array_merge($lang, array(
    'acl_m_mod_ideas'    => array('lang' => 'Can moderate the ideas.', 'cat' => 'misc'),
));
