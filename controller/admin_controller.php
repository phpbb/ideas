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

	/** @var string */
	public $u_action;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config                $config               Config object
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
	 * @return void
	 * @access public
	 */
	public function display_options()
	{
		$this->template->assign_vars(array(
			'S_FORUM_SELECT_BOX'	=> $this->select_ideas_forum(),
			'S_IDEAS_FORUM_ID'		=> !empty($this->config['ideas_forum_id']),

			'U_ACTION'			=> $this->u_action,
		));
	}

	/**
	 * Set configuration options
	 *
	 * @return void
	 * @access public
	 */
	public function set_config_options()
	{
		$errors = array();

		// Check the form for validity
		if (!check_form_key('acp_phpbb_ideas_settings'))
		{
			$errors[] = $this->language->lang('FORM_INVALID');
		}

		// Don't save settings if errors have occurred
		if (count($errors))
		{
			$this->template->assign_vars(array(
				'S_ERROR'	=> true,
				'ERROR_MSG'	=> implode('<br />', $errors),
			));

			return;
		}

		$cfg_array = $this->request->variable('config', array('' => ''));

		// Configuration options to list through
		$display_vars = array(
			'ideas_forum_id',
		);

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to
		foreach ($display_vars as $config_name)
		{
			if (!isset($cfg_array[$config_name]))
			{
				continue;
			}

			$this->config->set($config_name, $cfg_array[$config_name]);
		}

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'ACP_PHPBB_IDEAS_SETTINGS_LOG');
		trigger_error($this->language->lang('ACP_IDEAS_SETTINGS_UPDATED') . adm_back_link($this->u_action));
	}

	/**
	 * Set ideas forum options
	 *
	 * @return void
	 * @access public
	 */
	public function set_ideas_forum_options()
	{
		// Check if Ideas forum is selected and apply relevant settings if it is
		// But display the confirmation box first
		if (confirm_box(true))
		{
			if (empty($this->config['ideas_forum_id']))
			{
				trigger_error($this->language->lang('ACP_IDEAS_NO_FORUM') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$forum_id = (int) $this->config['ideas_forum_id'];

			$permission_helper = new \phpbb\ideas\factory\permission_helper($this->db, $this->phpbb_root_path, $this->php_ext);
			$permission_helper->set_ideas_forum_permissions($forum_id);

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
	 * @return void
	 * @access public
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
