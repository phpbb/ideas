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
use phpbb\ideas\ext;
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

	/* @var \phpbb\ideas\factory\ideas|\phpbb\ideas\factory\idea */
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
	 * @param language   $language
	 * @param linkhelper $link_helper
	 * @param pagination $pagination
	 * @param request    $request
	 * @param template   $template
	 * @param user       $user
	 * @param string     $root_path
	 * @param string     $php_ext
	 */
	public function __construct(auth $auth, config $config, helper $helper, language $language, linkhelper $link_helper, pagination $pagination, request $request, template $template, user $user, $root_path, $php_ext)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;
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
	 * Set the Ideas entity
	 *
	 * @param string $entity
	 */
	public function get_entity($entity)
	{
		$this->ideas = $entity;
	}

	/**
	 * Check if Ideas is properly configured after installation
	 * Ideas is available only after forum settings have been set in ACP
	 *
	 * @return bool Depending on whether or not the extension is properly configured
	 */
	public function is_available()
	{
		return (bool) $this->config['ideas_forum_id'];
	}

	/**
	 * Assign idea lists template variables
	 *
	 * @param string $block The template block var name
	 * @param array  $rows  The Idea row data
	 *
	 * @return void
	 */
	protected function assign_template_block_vars($block, $rows)
	{
		foreach ($rows as $row)
		{
			$this->template->assign_block_vars($block, array(
				'ID'         => $row['idea_id'], // (not currently implemented)
				'LINK'       => $this->link_helper->get_idea_link($row['idea_id']),
				'TITLE'      => $row['idea_title'],
				'AUTHOR'     => $this->link_helper->get_user_link($row['idea_author']),
				'DATE'       => $this->user->format_date($row['idea_date']),
				'READ'       => $row['read'],
				'VOTES_UP'   => $row['idea_votes_up'],
				'VOTES_DOWN' => $row['idea_votes_down'],
				'USER_VOTED' => $row['u_voted'],
				'POINTS'     => $row['idea_votes_up'] - $row['idea_votes_down'], // (not currently implemented)
				'STATUS'     => $row['idea_status'], // for status icons (not currently implemented)
				'LOCKED'     => $row['topic_status'] == ITEM_LOCKED,
				'U_UNAPPROVED_IDEA'	=> (($row['topic_visibility'] == ITEM_UNAPPROVED || $row['topic_visibility'] == ITEM_REAPPROVE) && $this->auth->acl_get('m_approve', $this->config['ideas_forum_id'])) ? append_sid("{$this->root_path}mcp.{$this->php_ext}", 'i=queue&amp;mode=approve_details&amp;t=' . $row['topic_id'], true, $this->user->session_id) : '',
			));
		}
	}

	/**
	 * Assign common template variables for Ideas pages
	 *
	 * @return void
	 */
	protected function display_common_vars()
	{
		$this->template->assign_vars([
			'S_DISPLAY_SEARCHBOX'	=> (bool) $this->auth->acl_get('u_search') && $this->auth->acl_get('f_search', $this->config['ideas_forum_id']) && $this->config['load_search'],
			'S_SEARCHBOX_ACTION'	=> append_sid("{$this->root_path}search.{$this->php_ext}"),
			'S_SEARCH_IDEAS_HIDDEN_FIELDS'	=> build_hidden_fields(['fid' => [$this->config['ideas_forum_id']]]),

			'U_SEARCH_MY_IDEAS' 	=> $this->helper->route('phpbb_ideas_list_controller', ['sort' => ext::SORT_MYIDEAS, 'status' => '-1']),
		]);
	}
}
