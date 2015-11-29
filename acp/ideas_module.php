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
	public $u_action;

	/**
	* Main ACP module
	*
	* @param int $id
	* @param string $mode
	* @access public
	*/
	public function main($id, $mode)
	{
		global $phpbb_container;

		// Load a template from adm/style for our ACP page
		$this->tpl_name = 'acp_phpbb_ideas';

		// Set the page title for our ACP page
		$this->page_title = 'ACP_PHPBB_IDEAS_SETTINGS';

		$language = $phpbb_container->get('language');

		// Add the phpBB Ideas ACP lang file
		$language->add_lang('phpbb_ideas_acp', 'phpbb/ideas');

		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('phpbb.ideas.admin.controller');

		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);

		$admin_controller->display_options($id, $mode);
	}
}
