<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas;

/**
* This ext class is optional and can be omitted if left empty.
* However, you can add special (un)installation commands in the
* methods enable_step(), disable_step() and purge_step(). As it is,
* these methods are defined in \phpbb\extension\base, which this
* class extends, but you can overwrite them to give special
* instructions for those cases.
*/
class ext extends \phpbb\extension\base
{
	public const SORT_AUTHOR = 'author';
	public const SORT_DATE = 'date';
	public const SORT_NEW = 'new';
	public const SORT_SCORE = 'score';
	public const SORT_TITLE = 'title';
	public const SORT_TOP = 'top';
	public const SORT_VOTES = 'votes';
	public const SORT_MYIDEAS = 'egosearch';
	public const SUBJECT_LENGTH = 120;
	public const NUM_IDEAS = 5;

	/** @var array Idea status names and IDs */
	public static $statuses = array(
		'NEW'			=> 1,
		'IN_PROGRESS'	=> 2,
		'IMPLEMENTED'	=> 3,
		'DUPLICATE'		=> 4,
		'INVALID'		=> 5,
	);

	/**
	 * Return the status name from the status ID.
	 *
	 * @param int $id ID of the status.
	 *
	 * @return string The status name.
	 * @static
	 * @access public
	 */
	public static function status_name($id)
	{
		return array_flip(self::$statuses)[$id];
	}

	/**
	 * Check whether the extension can be enabled.
	 *
	 * Requires phpBB >= 3.2.3 due to removal of deprecated Twig functions (ie Twig_SimpleFunction)
	 * Requires phpBB >= 3.3.0 due to use of PHP 7 features
	 * Requires PHP >= 7.1.0
	 *
	 * @return bool
	 * @access public
	 */
	public function is_enableable()
	{
		return !(PHP_VERSION_ID < 70100 ||
			phpbb_version_compare(PHPBB_VERSION, '3.3.0', '<') ||
			phpbb_version_compare(PHPBB_VERSION, '4.0.0-dev', '>='));
	}

	/**
	 * Enable notifications for the extension
	 *
	 * @param mixed $old_state
	 * @return bool|string
	 */
	public function enable_step($old_state)
	{
		if ($old_state === false)
		{
			$this->container->get('notification_manager')
				->enable_notifications('phpbb.ideas.notification.type.status');

			return 'notification';
		}

		return parent::enable_step($old_state);
	}

	/**
	 * Disable notifications for the extension
	 *
	 * @param mixed $old_state
	 * @return bool|string
	 */
	public function disable_step($old_state)
	{
		if ($old_state === false)
		{
			$this->container->get('notification_manager')
				->disable_notifications('phpbb.ideas.notification.type.status');

			return 'notification';
		}

		return parent::disable_step($old_state);
	}

	/**
	 * Purge notifications for the extension
	 *
	 * @param mixed	$old_state
	 * @return bool|string
	 */
	public function purge_step($old_state)
	{
		if ($old_state === false)
		{
			$this->container->get('notification_manager')
				->purge_notifications('phpbb.ideas.notification.type.status');

			return 'notification';
		}

		return parent::purge_step($old_state);
	}
}
