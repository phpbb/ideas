<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\controller;

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\ideas\factory\ideas;
use phpbb\ideas\factory\linkhelper;
use phpbb\language\language;
use phpbb\pagination;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;

abstract class base
{
	/** @var auth */
	protected $auth;

	/* @var config */
	protected $config;

	/* @var helper */
	protected $helper;

	/* @var ideas */
	protected $ideas;

	/** @var language  */
	protected $language;

	/* @var linkhelper */
	protected $link_helper;

	/** @var pagination */
	protected $pagination;

	/* @var request */
	protected $request;

	/* @var template */
	protected $template;

	/* @var user */
	protected $user;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/**
	 * @param auth       $auth
	 * @param config     $config
	 * @param helper     $helper
	 * @param ideas      $ideas
	 * @param language   $language
	 * @param linkhelper $link_helper
	 * @param pagination $pagination
	 * @param request    $request
	 * @param template   $template
	 * @param user       $user
	 * @param string     $root_path
	 * @param string     $php_ext
	 */
	public function __construct(auth $auth, config $config, helper $helper, ideas $ideas, language $language, linkhelper $link_helper, pagination $pagination, request $request, template $template, user $user, $root_path, $php_ext)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;
		$this->ideas = $ideas;
		$this->language = $language;
		$this->link_helper = $link_helper;
		$this->pagination = $pagination;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		$this->language->add_lang('common', 'phpbb/ideas');
	}

	/**
	 * Check if Ideas is properly configured after installation
	 * Ideas is available only after forum and poster settings have been set in ACP
	 *
	 * @return bool Depending on whether or not the extension is properly configured
	 */
	public function is_available()
	{
		return (bool) $this->config['ideas_forum_id'] && (bool) $this->config['ideas_poster_id'];
	}

	/**
	 * Assign idea lists template variables
	 *
	 * @param string $block The template block var name
	 * @param array  $rows  The Idea row data
	 */
	protected function assign_template_block_vars($block, $rows)
	{
		foreach ($rows as $row)
		{
			$this->template->assign_block_vars($block, array(
				'ID'         => $row['idea_id'],
				'LINK'       => $this->link_helper->get_idea_link($row['idea_id']),
				'TITLE'      => $row['idea_title'],
				'AUTHOR'     => $this->link_helper->get_user_link($row['idea_author']),
				'DATE'       => $this->user->format_date($row['idea_date']),
				'READ'       => $row['read'],
				'VOTES_UP'   => $row['idea_votes_up'],
				'VOTES_DOWN' => $row['idea_votes_down'],
				'POINTS'     => $row['idea_votes_up'] - $row['idea_votes_down'],
				'STATUS'     => $row['idea_status'], // for status icons (not currently implemented)
			));
		}
	}

	/**
	 * Assign template variables for a search ideas field
	 *
	 * @return null
	 */
	protected function display_search_ideas()
	{
		$this->template->assign_vars(array(
			'S_DISPLAY_SEARCHBOX'	=> (bool) $this->auth->acl_get('u_search') && $this->auth->acl_get('f_search', $this->config['ideas_forum_id']) && $this->config['load_search'],
			'S_SEARCHBOX_ACTION'	=> append_sid("{$this->root_path}search.{$this->php_ext}"),
			'S_SEARCH_IDEAS_HIDDEN_FIELDS'	=> build_hidden_fields(array('fid' => array($this->config['ideas_forum_id']))),

		));
	}
}
