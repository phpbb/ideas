<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @author Callum Macrae (callumacrae) <callum@lynxphp.com>
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace phpbb\ideas\controller;

// @todo: refactor out
define('IDEAS_FORUM_ID', 2);
define('IDEAS_POSTER_ID', 2);

abstract class base
{
	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var \phpbb\ideas\factory\linkhelper */
	protected $link_helper;

	/* @var \phpbb\ideas\factory\ideas */
	protected $ideas;

	/* @var \phpbb\request\request */
	protected $request;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/**
	 * @param \phpbb\controller\helper        $helper
	 * @param \phpbb\template\template        $template
	 * @param \phpbb\user                     $user
	 * @param \phpbb\ideas\factory\linkhelper $link_helper
	 * @param \phpbb\ideas\factory\ideas      $ideas
	 * @param \phpbb\request\request          $request
	 * @param string                          $root_path
	 * @param string                          $php_ext
	 */
	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\ideas\factory\linkhelper $link_helper, \phpbb\ideas\factory\ideas $ideas, \phpbb\request\request $request, $root_path, $php_ext)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->link_helper = $link_helper;
		$this->ideas = $ideas;
		$this->request = $request;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;

		$this->user->add_lang_ext('phpbb/ideas', 'common');
	}
}
