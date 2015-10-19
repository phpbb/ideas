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
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var array */
	protected $new_config;

	/** @var string */
	protected $phpbb_root_path;

	/** @var string */
	protected $php_ext;

	/** @var string */
	public $u_action;

	public function __construct()
	{
		global $config, $db, $phpbb_log, $request, $template, $user, $phpbb_root_path, $phpEx;

		$this->config = $config;
		$this->db = $db;
		$this->log = $phpbb_log;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $phpEx;

		// Add the phpBB Ideas ACP lang file
		$this->user->add_lang_ext('phpbb/ideas', 'phpbb_ideas_acp');

		// Load a template from adm/style for our ACP page
		$this->tpl_name = 'acp_phpbb_ideas';

		// Set the page title for our ACP page
		$this->page_title = 'ACP_PHPBB_IDEAS_SETTINGS';
	}

	/**
	* Main ACP module
	*
	* @param int $id
	* @param string $mode
	* @access public
	*/
	public function main($id, $mode)
	{
		// Define the name of the form for use as a form key
		$form_name = 'acp_phpbb_ideas_settings';
		add_form_key($form_name);

		// Set an empty errors array
		$errors = array();

		$display_vars = array(
			'ideas_forum_id'	=> array('lang' => 'ACP_IDEAS_FORUM_ID',	'validate' => 'string',	'type' => 'custom', 'method' => 'select_ideas_forum', 'explain' => true),
			'ideas_poster_id'	=> array('lang' => 'ACP_IDEAS_POSTER_ID',	'validate' => 'string',	'type' => 'custom', 'method' => 'select_ideas_topics_poster', 'explain' => true),
		);

		$this->new_config = $this->config;
		$cfg_array = ($this->request->is_set('config')) ? $this->request->variable('config', array('' => ''), true) : $this->new_config;
		$submit = $this->request->is_set('submit');

		// We validate the complete config if wished
		validate_config_vars($display_vars, $cfg_array, $errors);

		if ($submit)
		{
			if (!check_form_key($form_name))
			{
				$errors[] = $this->user->lang['FORM_INVALID'];
			}

			// Check if selected user exists
			$sql = 'SELECT user_id
				FROM ' . USERS_TABLE . "
				WHERE username_clean = '" . $this->db->sql_escape(utf8_clean_string($cfg_array['ideas_poster_id'])) . "'";
			$result = $this->db->sql_query($sql);
			$user_id = (int) $this->db->sql_fetchfield('user_id');
			$this->db->sql_freeresult($result);

			if (!$user_id)
			{
				$errors[] = $this->user->lang['NO_USER'];
			}
			else
			{
				// If selected user does exist, reassign the config value to its ID
				$cfg_array['ideas_poster_id'] = $user_id;
			}
		}

		// Do not write values if there is an errors
		if (sizeof($errors))
		{
			$submit = false;
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to
		foreach ($display_vars as $config_name => $null)
		{
			if (!isset($cfg_array[$config_name]))
			{
				continue;
			}

			$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];

			if ($submit)
			{
				$this->config->set($config_name, $config_value);
			}
		}

		if ($submit)
		{
			// Add option settings change action to the admin log
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'ACP_PHPBB_IDEAS_SETTINGS_LOG');

			trigger_error($this->user->lang['ACP_PHPBB_IDEAS_SETTINGS_CHANGED'] . adm_back_link($this->u_action));
		}

		// Output relevant page
		foreach ($display_vars as $config_key => $vars)
		{
			$type = explode(':', $vars['type']);

			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

			if (empty($content))
			{
				continue;
			}

			$this->template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> $this->user->lang($vars['lang']),
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> ($vars['explain']) ? $this->user->lang($vars['lang'] . '_EXPLAIN') : '',
				'CONTENT'		=> $content,
			));
		}

		$this->template->assign_vars(array(
			'S_ERROR'	=> (bool) sizeof($errors),
			'ERROR_MSG'	=> (sizeof($errors)) ? implode('<br />', $errors) : '',

			'U_ACTION'	=> $this->u_action,
			'U_FIND_USERNAME'	=> append_sid("{$this->phpbb_root_path}memberlist.$this->php_ext", 'mode=searchuser&amp;form=acp_phpbb_ideas_settings&amp;field=ideas_poster_id&amp;select_single=true'),
		));
	}

	public function select_ideas_forum($value, $key)
	{
		$ideas_forum_id = (int) $this->config['ideas_forum_id'];
		$s_forums_list = '<select id="' . $key . '" name="config[' . $key . ']">';
		$s_forums_list .= '<option value="0"' . ((!$ideas_forum_id) ? ' selected="selected"' : '') . '>' . $this->user->lang('ACP_NO_FORUM_SELECTED') . '</option>';
		$forum_list = make_forum_select($ideas_forum_id, false, true, true);
		$s_forums_list .= $forum_list . '</select>';

		return $s_forums_list;
	}

	public function select_ideas_topics_poster($value, $key)
	{
		$ideas_poster_id = (int) $this->config['ideas_poster_id'];
		$sql = 'SELECT username FROM ' . USERS_TABLE . '
			WHERE user_id = ' . $ideas_poster_id;
		$this->db->sql_query($sql);
		$username = $this->db->sql_fetchfield('username');
		$username = ($username !== false) ? $username : '';

		$tpl = '<input id="' . $key . '" type="text" size="45" maxlength="255" name="config[' . $key . ']" value="' . $username . '" />';

		return $tpl;
	}
}
