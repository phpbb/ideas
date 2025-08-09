<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\notification\type;

use phpbb\ideas\ext;

/**
 * Ideas status change notification class.
 */
class status extends \phpbb\notification\type\base
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\ideas\factory\idea */
	protected $idea;

	/** @var \phpbb\user_loader */
	protected $user_loader;

	/**
	 * Set the controller helper
	 *
	 * @param \phpbb\controller\helper $helper
	 *
	 * @return void
	 */
	public function set_controller_helper(\phpbb\controller\helper $helper)
	{
		$this->helper = $helper;
	}

	/**
	 * Set the Idea object
	 *
	 * @param \phpbb\ideas\factory\idea $idea
	 *
	 * @return void
	 */
	public function set_idea_factory(\phpbb\ideas\factory\idea $idea)
	{
		$this->idea = $idea;
	}

	/**
	 * Set the config object
	 *
	 * @param \phpbb\config\config $config
	 *
	 * @return void
	 */
	public function set_config(\phpbb\config\config $config)
	{
		$this->config = $config;
	}

	public function set_user_loader(\phpbb\user_loader $user_loader)
	{
		$this->user_loader = $user_loader;
	}

	/**
	 * Get notification type name
	 *
	 * @return string
	 */
	public function get_type()
	{
		return 'phpbb.ideas.notification.type.status';
	}

	/**
	 * Email template to use to send notifications
	 *
	 * @var string
	 */
	public $email_template = '@phpbb_ideas/status_notification';

	/**
	 * Language key used to output the text
	 *
	 * @var string
	 */
	protected $language_key = 'IDEA_STATUS_CHANGE';

	/**
	 * Notification option data (for outputting to the user)
	 *
	 * @var bool|array False if the service should use its default data
	 * 					Array of data (including keys 'id', 'lang', and 'group')
	 */
	public static $notification_option = [
		'lang'	=> 'NOTIFICATION_TYPE_IDEAS',
		'group'	=> 'NOTIFICATION_GROUP_MISCELLANEOUS',
	];

	/**
	 * Is this type available to the current user (defines whether it will be shown in the UCP Edit notification options)
	 *
	 * @return bool True/False whether this is available to the user
	 */
	public function is_available()
	{
		return (bool) $this->auth->acl_get('f_read', (int) $this->config['ideas_forum_id']);
	}

	/**
	 * Get the id of the notification
	 *
	 * @param array $type_data The type-specific data
	 *
	 * @return int ID of the notification
	 */
	public static function get_item_id($type_data)
	{
		return (int) $type_data['item_id'];
	}

	/**
	 * Get the id of the parent
	 *
	 * @param array $type_data The type-specific data
	 *
	 * @return int ID of the parent
	 */
	public static function get_item_parent_id($type_data)
	{
		return 0;
	}

	/**
	 * Find the users who want to receive notifications
	 *
	 * @param array $type_data The type-specific data
	 * @param array $options Options for finding users for notification
	 * 		ignore_users => array of users and user types that should not receive notifications from this type because they've already been notified
	 * 						e.g.: [2 => [''], 3 => ['', 'email'], ...]
	 *
	 * @return array
	 */
	public function find_users_for_notification($type_data, $options = [])
	{
		$options = array_merge([
			'ignore_users'		=> [],
		], $options);

		$idea = $this->idea->get_idea($type_data['idea_id']);

		$users = $idea ? [$idea['idea_author']] : [];

		return $this->check_user_notification_options($users, $options);
	}

	/**
	 * Get the user's avatar
	 */
	public function get_avatar()
	{
		$author = (int) $this->get_data('idea_author');
		return $author ? $this->user_loader->get_avatar($author, true) : '';
	}

	/**
	 * Users needed to query before this notification can be displayed
	 *
	 * @return array Array of user_ids
	 */
	public function users_to_query()
	{
		return [];
	}

	/**
	 * Get the HTML-formatted title of this notification
	 *
	 * @return string
	 */
	public function get_title()
	{
		if (!$this->language->is_set($this->language_key))
		{
			$this->language->add_lang('common', 'phpbb/ideas');
		}
		return $this->language->lang($this->language_key, $this->get_data('idea_title'));
	}

	/**
	 * Get the HTML-formatted reference of the notification
	 *
	 * @return string
	 */
	public function get_reference()
	{
		return  $this->language->lang(ext::status_name($this->get_data('status')));
	}

	/**
	 * Get the url to this item
	 *
	 * @return string URL
	 */
	public function get_url()
	{
		$params = ['idea_id' => $this->get_data('idea_id')];

		return $this->helper->route('phpbb_ideas_idea_controller', $params);
	}

	/**
	 * Get email template
	 *
	 * @return string|bool
	 */
	public function get_email_template()
	{
		return $this->email_template;
	}

	/**
	 * Get email template variables
	 *
	 * @return array
	 */
	public function get_email_template_variables()
	{
		return [
			'IDEA_TITLE'	=> html_entity_decode(censor_text($this->get_data('idea_title')), ENT_COMPAT),
			'STATUS'		=> html_entity_decode($this->language->lang(ext::status_name($this->get_data('status'))), ENT_COMPAT),
			'U_VIEW_IDEA'	=> $this->get_url(),
		];
	}

	/**
	 * Pre create insert array function
	 * This allows you to perform certain actions, like run a query
	 * and load data, before create_insert_array() is run. The data
	 * returned from this function will be sent to create_insert_array().
	 *
	 * @param array $type_data The type-specific data
	 * @param array $notify_users Notify users list
	 * 		Formatted from find_users_for_notification()
	 * @return array Whatever you want to send to create_insert_array().
	 */
	public function pre_create_insert_array($type_data, $notify_users)
	{
		$pre_create_data = [];

		$idea = $this->idea->get_idea($type_data['idea_id']);
		if ($idea !== false)
		{
			$pre_create_data['idea_title'] = $idea['idea_title'];
			$pre_create_data['idea_author'] = $idea['idea_author'];
		}

		return $pre_create_data;
	}

	/**
	 * Function for preparing the data for insertion in an SQL query
	 * (The service handles insertion)
	 *
	 * @param array $type_data The type-specific data
	 * @param array $pre_create_data Data from pre_create_insert_array()
	 */
	public function create_insert_array($type_data, $pre_create_data = [])
	{
		$this->set_data('idea_id', $type_data['idea_id']);
		$this->set_data('status', $type_data['status']);
		$this->set_data('idea_title', $pre_create_data['idea_title']);
		$this->set_data('idea_author', $pre_create_data['idea_author']);

		parent::create_insert_array($type_data, $pre_create_data);
	}
}
