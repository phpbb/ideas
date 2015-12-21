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
 * Admin controller
 */
class admin_controller
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/** @var array */
	protected $cfg_array = array();

	/** @var string */
	public $u_action;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config              $config               Config object
	 * @param \phpbb\db\driver\driver_interface $db                   Database object
	 * @param \phpbb\language\language          $language             Language object
	 * @param \phpbb\log\log                    $log                  Log object
	 * @param \phpbb\request\request            $request              Request object
	 * @param \phpbb\template\template          $template             Template object
	 * @param \phpbb\user                       $user                 User object
	 * @param string                            $root_path            phpBB root path
	 * @param string                            $php_ext              php_ext
	 * @access public
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\log\log $log, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $root_path, $php_ext)
	{
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->log = $log;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Display the options a user can configure for this extension
	 *
	 * @return null
	 * @access public
	 */
	public function display_options()
	{
		$this->template->assign_vars(array(
			'IDEAS_POSTER'		=> $this->get_ideas_topics_poster_username(),
			'IDEAS_BASE_URL'	=> ($this->config['ideas_base_url']) ?: '',

			'S_FORUM_SELECT_BOX'	=> $this->select_ideas_forum(),
			'S_IDEAS_FORUM_ID'		=> !empty($this->config['ideas_forum_id']),

			'U_ACTION'			=> $this->u_action,
			'U_FIND_USERNAME'	=> append_sid("{$this->phpbb_root_path}memberlist.{$this->php_ext}", 'mode=searchuser&amp;form=acp_phpbb_ideas_settings&amp;field=ideas_poster_id&amp;select_single=true'),
		));
	}

	/**
	 * Set configuration options
	 *
	 * @return null
	 * @access public
	 */
	public function set_config_options()
	{
		$errors = array();

		// This method is called on submit, so set flag to true initially
		$submit = true;

		$this->cfg_array = $this->request->variable('config', array('' => ''), true);

		// Check the form for validity
		if (!check_form_key('acp_phpbb_ideas_settings'))
		{
			$errors[] = $this->language->lang('FORM_INVALID');
		}

		// Check if selected user exists
		$user_id = $this->get_ideas_topics_poster_id();
		if (!$user_id)
		{
			$errors[] = $this->language->lang('NO_USER');
		}

		// Don't save settings if errors have occurred
		if (sizeof($errors))
		{
			$submit = false;

			$this->template->assign_vars(array(
				'S_ERROR'	=> true,
				'ERROR_MSG'	=> implode('<br />', $errors),
			));
		}

		if ($submit)
		{
			// If selected user does exist, reassign the config value to its ID
			$this->cfg_array['ideas_poster_id'] = $user_id;

			// Configuration options to list through
			$display_vars = array(
				'ideas_forum_id',
				'ideas_poster_id',
				'ideas_base_url',
				'ideas_forum_setup',
			);

			// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to
			foreach ($display_vars as $config_name)
			{
				if (!isset($this->cfg_array[$config_name]))
				{
					continue;
				}

				if ($submit)
				{
					$this->config->set($config_name, $this->cfg_array[$config_name]);
				}
			}
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'ACP_PHPBB_IDEAS_SETTINGS_LOG');
			trigger_error($this->language->lang('ACP_IDEAS_SETTINGS_UPDATED') . adm_back_link($this->u_action));
		}
	}

	/**
	 * Set ideas forum options
	 *
	 * @return  null
	 * @access public
	 */
	public function set_ideas_forum_options()
	{
		// Check if Ideas forum is selected and apply relevant settings if it is
		// But display the confirm box first
		if (confirm_box(true))
		{
			if (empty($this->config['ideas_forum_id']))
			{
				trigger_error($this->language->lang('ACP_IDEAS_NO_FORUM') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if (!class_exists('auth_admin'))
			{
				include($this->phpbb_root_path . 'includes/acp/auth.' . $this->php_ext);
			}
			$auth_admin = new \auth_admin();

			$forum_id = (int) $this->config['ideas_forum_id'];

			// Get the REGISTERED usergroup ID
			$sql = 'SELECT group_id
				FROM ' . GROUPS_TABLE . "
				WHERE group_name = '" . $this->db->sql_escape('REGISTERED') . "'";
			$this->db->sql_query($sql);
			$group_id = (int) $this->db->sql_fetchfield('group_id');

			// Get 'f_' local REGISTERED users group permissions array for the ideas forum
			// Default undefined permissions to ACL_NO
			$hold_ary = $auth_admin->get_mask('set', false, $group_id, $forum_id, 'f_', 'local', ACL_NO);
			$auth_settings = $hold_ary[$group_id][$forum_id];

			// Set 'Can start new topics' permissions to 'Never' for the ideas forum
			$auth_settings['f_post'] = ACL_NEVER;

			// Update the registered usergroup permissions for selected Ideas forum...
			$auth_admin->acl_set('group', $forum_id, $group_id, $auth_settings);

			// Disable auto-pruning for ideas forum
			$sql = 'UPDATE ' . FORUMS_TABLE . '
				SET ' . $this->db->sql_build_array('UPDATE', array('enable_prune' => false)) . '
				WHERE forum_id = ' . $forum_id;
			$this->db->sql_query($sql);

			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'ACP_PHPBB_IDEAS_FORUM_SETUP_LOG');
			trigger_error($this->language->lang('ACP_IDEAS_FORUM_SETUP_UPDATED') . adm_back_link($this->u_action));
		}
		else
		{
			confirm_box(false, $this->language->lang('ACP_IDEAS_FORUM_SETUP_CONFIRM'), build_hidden_fields(array(
				'ideas_forum_setup'	=> $this->request->is_set_post('ideas_forum_setup'),
			)));
		}
	}

	/**
	 * Get Ideas poster bot user ID
	 *
	 * @return int user_id Ideas bot user ID
	 * @access protected
	 */
	protected function get_ideas_topics_poster_id()
	{
		$sql = 'SELECT user_id
			FROM ' . USERS_TABLE . "
			WHERE username_clean = '" . $this->db->sql_escape(utf8_clean_string($this->cfg_array['ideas_poster_id'])) . "'";
		$result = $this->db->sql_query($sql);
		$user_id = (int) $this->db->sql_fetchfield('user_id');
		$this->db->sql_freeresult($result);

		return $user_id;
	}

	/**
	 * Get Ideas poster bot username
	 *
	 * @return string Ideas bot username
	 * @access protected
	 */
	protected function get_ideas_topics_poster_username()
	{
		$sql = 'SELECT username FROM ' . USERS_TABLE . '
			WHERE user_id = ' . (int) $this->config['ideas_poster_id'];
		$this->db->sql_query($sql);
		$username = $this->db->sql_fetchfield('username');

		return ($username !== false) ? $username : '';
	}

	/**
	 * Generate ideas forum select options
	 *
	 * @return string Select menu HTML code
	 * @access protected
	 */
	protected function select_ideas_forum()
	{
		$ideas_forum_id = (int) $this->config['ideas_forum_id'];
		$s_forums_list = '<select id="ideas_forum_id" name="config[ideas_forum_id]">';
		$s_forums_list .= '<option value="0"' . ((!$ideas_forum_id) ? ' selected="selected"' : '') . '>' . $this->language->lang('ACP_IDEAS_NO_FORUM') . '</option>';
		$forum_list = make_forum_select($ideas_forum_id, false, true, true);
		$s_forums_list .= $forum_list . '</select>';

		return $s_forums_list;
	}

	/**
	 * Set page url
	 *
	 * @param string $u_action Custom form action
	 * @return null
	 * @access public
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
