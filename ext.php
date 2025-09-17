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
	public const NOTIFICATION_TYPE_STATUS = 'phpbb.ideas.notification.type.status';

	/** @var array Idea status names and IDs */
	public static $statuses = [
		'NEW'			=> 1,
		'IN_PROGRESS'	=> 2,
		'IMPLEMENTED'	=> 3,
		'DUPLICATE'		=> 4,
		'INVALID'		=> 5,
	];

	/** @var array Cached flipped statuses array */
	private static $status_names;

	/**
	 * Return the status name from the status ID.
	 *
	 * @param int $id ID of the status.
	 * @return string The status name.
	 */
	public static function status_name($id)
	{
		if (self::$status_names === null)
		{
			self::$status_names = array_flip(self::$statuses);
		}

		return self::$status_names[$id];
	}

	/**
	 * Check whether the extension can be enabled.
	 *
	 * Requires phpBB >= 4.0.0 due to use of Icon()
	 * Requires PHP >= 8.1
	 *
	 * @return bool
	 */
	public function is_enableable()
	{
		return PHP_VERSION_ID >= 80100 && phpbb_version_compare(PHPBB_VERSION, '4.0.0-dev', '>=');
	}

	/**
	 * Handle notification management for extension lifecycle
	 *
	 * @param string $method The notification manager method to call
	 * @return string
	 */
	private function handle_notifications($method)
	{
		$this->container->get('notification_manager')->$method(self::NOTIFICATION_TYPE_STATUS);
		return 'notification';
	}

	/**
	 * Enable notifications for the extension
	 *
	 * @param mixed $old_state
	 * @return bool|string
	 */
	public function enable_step($old_state)
	{
		return $old_state === false ? $this->handle_notifications('enable_notifications') : parent::enable_step($old_state);
	}

	/**
	 * Disable notifications for the extension
	 *
	 * @param mixed $old_state
	 * @return bool|string
	 */
	public function disable_step($old_state)
	{
		return $old_state === false ? $this->handle_notifications('disable_notifications') : parent::disable_step($old_state);
	}

	/**
	 * Purge notifications for the extension
	 *
	 * @param mixed $old_state
	 * @return bool|string
	 */
	public function purge_step($old_state)
	{
		return $old_state === false ? $this->handle_notifications('purge_notifications') : parent::purge_step($old_state);
	}
}
