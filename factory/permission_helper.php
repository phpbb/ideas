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

class permission_helper
{
	/**
	 * @var \phpbb\db\driver\driver_interface
	 */
	protected $db;

	/**
	 * @var string
	 */
	protected $phpbb_root_path;

	/**
	 * @var string
	 */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface $db              Database object
	 * @param string                            $phpbb_root_path phpBB root path
	 * @param string                            $php_ext         php_ext
	 * @access public
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, $phpbb_root_path, $php_ext)
	{
		$this->db = $db;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Set the best permissions for an Ideas forum.
	 *
	 * @param int $forum_id A forum id
	 */
	public function set_ideas_forum_permissions($forum_id)
	{
		if (!class_exists('auth_admin'))
		{
			include $this->phpbb_root_path . 'includes/acp/auth.' . $this->php_ext;
		}
		$auth_admin = new \auth_admin();

		// Get the REGISTERED usergroup ID
		$sql = 'SELECT group_id
			FROM ' . GROUPS_TABLE . "
			WHERE group_name = '" . $this->db->sql_escape('REGISTERED') . "'";
		$result = $this->db->sql_query($sql);
		$group_id = (int) $this->db->sql_fetchfield('group_id');
		$this->db->sql_freeresult($result);

		// Get 'f_' local REGISTERED users group permissions array for the ideas forum
		// Default undefined permissions to ACL_NO
		$hold_ary = $auth_admin->get_mask('set', false, $group_id, $forum_id, 'f_', 'local', ACL_NO);
		$auth_settings = $hold_ary[$group_id][$forum_id];

		// Set 'Can start new topics' permissions to 'Yes' for the ideas forum
		$auth_settings['f_post'] = ACL_YES;

		// Can not post announcement or stickies, polls, use topic icons or lock own topic
		$auth_settings['f_announce'] = ACL_NEVER;
		$auth_settings['f_announce_global'] = ACL_NEVER;
		$auth_settings['f_sticky'] = ACL_NEVER;
		$auth_settings['f_poll'] = ACL_NEVER;
		$auth_settings['f_icons'] = ACL_NEVER;
		$auth_settings['f_user_lock'] = ACL_NEVER;

		// Update the registered usergroup permissions for selected Ideas forum...
		$auth_admin->acl_set('group', $forum_id, $group_id, $auth_settings);
	}
}
