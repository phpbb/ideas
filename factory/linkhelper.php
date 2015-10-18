<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\factory;

use phpbb\controller\helper;
use phpbb\user_loader;

class linkhelper
{
	/* @var helper */
	protected $helper;

	/* @var user_loader */
	protected $user_loader;

	/**
	 * @param \phpbb\controller\helper $helper
	 * @param \phpbb\user_loader       $user_loader
	 */
	public function __construct(helper $helper, user_loader $user_loader)
	{
		$this->helper = $helper;
		$this->user_loader = $user_loader;
	}

	/**
	 * Shortcut method to get the link to a specified idea.
	 * Optionally add mode and hash URL arguments.
	 *
	 * @param int    $idea_id int The ID of the idea.
	 * @param string $mode        The mode argument (vote, delete, etc.)
	 * @param bool   $hash        Add a link hash
	 * @return string The route
	 */
	public function get_idea_link($idea_id, $mode = '', $hash = false)
	{
		$params = array('idea_id' => $idea_id);
		$params = ($mode) ? array_merge($params, array('mode' => $mode)) : $params;
		$params = ($hash) ? array_merge($params, array('hash' => generate_link_hash("{$mode}_{$idea_id}"))) : $params;

		return $this->helper->route('ideas_idea_controller', $params);
	}

	public function get_list_link($sort = 'date')
	{
		return $this->helper->route('ideas_list_controller', array(
			'sort'	=> $sort
		));
	}

	/**
	 * Returns a link to the users profile, complete with colour.
	 *
	 * Is there a function that already does this? This seems fairly database heavy.
	 *
	 * @param int $id The ID of the user.
	 * @return string An HTML link to the users profile.
	 */
	public function get_user_link($id)
	{
		$this->user_loader->load_users(array($id));
		return $this->user_loader->get_username($id, 'full');
	}
}
