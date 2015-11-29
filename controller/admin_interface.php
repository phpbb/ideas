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

/**
* Interface for our admin controller
*
* This describes all of the methods we'll use for the admin front-end of this extension
*/
interface admin_interface
{
	/**
	* Display the options a user can configure for this extension
	*
	* @param int $id		ACP module ID
	* @param string $mode	ACP module mode
	* @return null
	* @access public
	*/
	public function display_options($id, $mode);

	/**
	 * Generate ideas forum select options
	 *
	 * @return string Select menu HTML code
	 * @access public
	 */
	public function select_ideas_forum();

	/**
	 * Get ideas poster bot username
	 *
	 * @return string Ideas bot username
	 * @access public
	 */
	public function get_ideas_topics_poster();

	/**
	* Set page url
	*
	* @param string $u_action Custom form action
	* @return null
	* @access public
	*/
	public function set_page_url($u_action);
}
