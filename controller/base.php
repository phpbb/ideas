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

use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\ideas\factory\ideas;
use phpbb\ideas\factory\linkhelper;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;

abstract class base
{
	/* @var config */
	protected $config;

	/* @var helper */
	protected $helper;

	/* @var ideas */
	protected $ideas;

	/* @var linkhelper */
	protected $link_helper;

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
	 * @param config     $config
	 * @param helper     $helper
	 * @param ideas      $ideas
	 * @param linkhelper $link_helper
	 * @param request    $request
	 * @param template   $template
	 * @param user       $user
	 * @param string     $root_path
	 * @param string     $php_ext
	 */
	public function __construct(config $config, helper $helper, ideas $ideas, linkhelper $link_helper, request $request, template $template, user $user, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->ideas = $ideas;
		$this->link_helper = $link_helper;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		$this->user->add_lang_ext('phpbb/ideas', 'common');
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
}
