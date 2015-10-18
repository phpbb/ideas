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

use phpbb\controller\helper;
use phpbb\ideas\factory\ideas;
use phpbb\ideas\factory\linkhelper;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;

abstract class base
{
	/* @var \phpbb\config\config */
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
	public function __construct(\phpbb\config\config $config, helper $helper, ideas $ideas, linkhelper $link_helper, request $request, template $template, user $user, $root_path, $php_ext)
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

		// Don't let using Ideas if it's not been properly configured
		if (!$this->is_available())
		{
			throw new \phpbb\exception\http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}
	}

	// Check if Ideas is properly configured after installation
	public function is_available()
	{
		return $this->config['ideas_forum_id'] && $this->config['ideas_poster_id'];
	}
}
