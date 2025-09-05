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

use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\ideas\ext;
use phpbb\user_loader;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Ideas status change notification class.
 */
class status extends \phpbb\notification\type\base
{
	/** @var config */
	protected $config;

	/** @var helper */
	protected $helper;

	/** @var user_loader */
	protected $user_loader;

	/** @var int */
	protected $ideas_forum_id;

	/**
	 * Set additional services and properties
	 *
	 * @param config $config
	 * @param helper $helper
	 * @param user_loader $user_loader
	 * @return void
	 */
	public function set_additional_services(config $config, helper $helper, user_loader $user_loader)
	{
		$this->helper = $helper;
		$this->user_loader = $user_loader;
		$this->ideas_forum_id = (int) $config['ideas_forum_id'];
	}

	/**
	 * Email template to use to send notifications
	 *
	 * @var string
	 */
	protected $email_template = '@phpbb_ideas/status_notification';

	/**
	 * Language key used to output the text
	 *
	 * @var string
	 */
	protected $language_key = 'IDEA_STATUS_CHANGE';

	/**
	 * {@inheritDoc}
	 */
	public static $notification_option = [
		'lang'	=> 'NOTIFICATION_TYPE_IDEAS',
		'group'	=> 'NOTIFICATION_GROUP_MISCELLANEOUS',
	];

	/**
	 * {@inheritDoc}
	 */
	public function get_type()
	{
		return ext::NOTIFICATION_TYPE_STATUS;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_item_id($type_data)
	{
		return (int) $type_data['idea_id'];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_item_parent_id($type_data)
	{
		return 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_available()
	{
		return (bool) $this->auth->acl_get('f_read', $this->ideas_forum_id);
	}

	/**
	 * {@inheritDoc}
	 */
	public function find_users_for_notification($type_data, $options = [])
	{
		$options = array_merge([
			'ignore_users'		=> [],
		], $options);

		$users = [$type_data['idea_author']];

		return $this->get_authorised_recipients($users, $this->ideas_forum_id, $options);
	}

	/**
	 * {@inheritDoc}
	 */
	public function users_to_query()
	{
		return [$this->get_data('updater_id')];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_title()
	{
		if (!$this->language->is_set($this->language_key))
		{
			$this->language->add_lang('common', 'phpbb/ideas');
		}

		$username = $this->user_loader->get_username($this->get_data('updater_id'), 'no_profile');

		return $this->language->lang($this->language_key, $username);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_reference()
	{
		return $this->language->lang(
			'NOTIFICATION_REFERENCE',
			censor_text($this->get_data('idea_title'))
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_reason()
	{
		return $this->language->lang(
			'NOTIFICATION_STATUS',
			$this->language->lang(ext::status_name($this->get_data('status')))
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_url($reference_type = UrlGeneratorInterface::ABSOLUTE_PATH)
	{
		$params = ['idea_id' => $this->get_data('idea_id')];

		return $this->helper->route('phpbb_ideas_idea_controller', $params, true, false, $reference_type);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_avatar()
	{
		return $this->user_loader->get_avatar($this->get_data('updater_id'), false, true);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_email_template()
	{
		return $this->email_template;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_email_template_variables()
	{
		return [
			'IDEA_TITLE'	=> html_entity_decode(censor_text($this->get_data('idea_title')), ENT_COMPAT),
			'STATUS'		=> html_entity_decode($this->language->lang(ext::status_name($this->get_data('status'))), ENT_COMPAT),
			'UPDATED_BY'	=> html_entity_decode($this->user_loader->get_username($this->get_data('updater_id'), 'username'), ENT_COMPAT),
			'U_VIEW_IDEA'	=> $this->get_url(UrlGeneratorInterface::ABSOLUTE_URL),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function create_insert_array($type_data, $pre_create_data = [])
	{
		$this->set_data('idea_id', (int) $type_data['idea_id']);
		$this->set_data('status', (int) $type_data['status']);
		$this->set_data('updater_id', (int) $type_data['user_id']);
		$this->set_data('idea_title', $type_data['idea_title']);

		parent::create_insert_array($type_data, $pre_create_data);
	}
}
