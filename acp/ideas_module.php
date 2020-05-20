<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\acp;

class ideas_module
{
	/** @var string */
	public $page_title;

	/** @var string */
	public $tpl_name;

	/** @var string */
	public $u_action;

	/**
	 * Main ACP module
	 *
	 * @access public
	 * @throws \Exception
	 */
	public function main()
	{
		global $phpbb_container;

		// Load a template from adm/style for our ACP page
		$this->tpl_name = 'acp_phpbb_ideas';

		// Set the page title for our ACP page
		$this->page_title = 'ACP_PHPBB_IDEAS_SETTINGS';

		$language = $phpbb_container->get('language');
		$request = $phpbb_container->get('request');

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('phpbb.ideas.admin.controller');

		// Add the phpBB Ideas ACP lang file
		$language->add_lang('phpbb_ideas_acp', 'phpbb/ideas');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		// Create a form key for preventing CSRF attacks
		add_form_key('acp_phpbb_ideas_settings');

		// Apply Ideas configuration settings
		if ($request->is_set_post('submit'))
		{
			$admin_controller->set_config_options();
		}

		// Set Ideas forum  options and registered usergroup forum permissions
		if ($request->is_set_post('ideas_forum_setup'))
		{
			$admin_controller->set_ideas_forum_options();
		}

		// Display/set ACP configuration settings
		$admin_controller->display_options();
	}
}
