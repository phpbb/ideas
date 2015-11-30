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
	 * Check if Ideas bot user exists
	 *
	 * @return null
	 * @access public
	 */
	public function check_ideas_topics_poster();

	/**
	 * Get ideas poster bot username
	 *
	 * @return string Ideas bot username
	 * @access public
	 */
	public function get_ideas_topics_poster();

	/**
	 * Set configuration options
	 *
	 * @param array $display_vars  Array of config options to display
	 * @param bool  $update_config Flag indicating if database should be updated
	 * @return null
	 * @access public
	 */
	public function set_config_options($display_vars = array(), $update_config = false);

	/**
	 * Set ideas forum options
	 *
	 * @param int $id		ACP module ID
	 * @param string $mode	ACP module mode
	 * @return  null
	 * @access public
	 */
	public function set_ideas_forum_options($id, $mode);

	/**
	 * Generate ideas forum select options
	 *
	 * @return string Select menu HTML code
	 * @access public
	 */
	public function select_ideas_forum();

	/**
	* Set page url
	*
	* @param string $u_action Custom form action
	* @return null
	* @access public
	*/
	public function set_page_url($u_action);
}
