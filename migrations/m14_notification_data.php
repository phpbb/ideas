<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\migrations;

class m14_notification_data extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->config->offsetExists('ideas_status_notifications_id');
	}

	public static function depends_on()
	{
		return [
			'\phpbb\ideas\migrations\m1_initial_schema',
			'\phpbb\ideas\migrations\m13_set_permissions'
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['ideas_status_notifications_id', 0, true]],
		];
	}
}
