<?php
/**
 *
 * ideas. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2025
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
	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\ideas\factory\idea */
	protected $idea;

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
	 * Set the Idea helper
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
	 * Get notification type name
	 *
	 * @return string
	 */
	public function get_type()
	{
		return 'phpbb.ideas.notification.type.status';
	}

	/**
	 * Notification option data (for outputting to the user)
	 *
	 * @var bool|array False if the service should use its default data
	 * 					Array of data (including keys 'id', 'lang', and 'group')
	 */
	public static $notification_option = [
		'lang'	=> 'NOTIFICATION_TYPE_IDEAS',
	];

	/**
	 * Is this type available to the current user (defines whether it will be shown in the UCP Edit notification options)
	 *
	 * @return bool True/False whether this is available to the user
	 */
	public function is_available()
	{
		return true; //(bool) $this->config['ideas_forum_id'];
	}

	/**
	 * Get the id of the notification
	 *
	 * @param array $type_data The type-specific data
	 *
	 * @return int Id of the notification
	 */
	public static function get_item_id($type_data)
	{
		return $type_data['status'];
	}

	/**
	 * Get the id of the parent
	 *
	 * @param array $type_data The type-specific data
	 *
	 * @return int Id of the parent
	 */
	public static function get_item_parent_id($type_data)
	{
		return $type_data['idea_id'];
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
		$users = [];

		$idea = $this->idea->get_idea($type_data['idea_id']);

		if ($idea !== false)
		{
			$users[$idea['idea_author']] = $this->notification_manager->get_default_methods();
		}

		return $users;
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
		return $this->language->lang('PHPBB_IDEAS_NOTIFICATION', $this->get_data('idea_title'));
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
		return false;
	}

	/**
	 * Get email template variables
	 *
	 * @return array
	 */
	public function get_email_template_variables()
	{
		return [];
	}

	public function pre_create_insert_array($type_data, $notify_users)
	{
		$pre_create_data = [];

		$idea = $this->idea->get_idea($type_data['idea_id']);
		if ($idea !== false)
		{
			$pre_create_data['idea_title'] = $idea['idea_title'];
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

		parent::create_insert_array($type_data, $pre_create_data);
	}
}
