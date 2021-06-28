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

class m8_cron_data extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->config->offsetExists('ideas_cron_last_run');
	}

	public static function depends_on()
	{
		return array('\phpbb\ideas\migrations\m1_initial_schema');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('ideas_cron_last_run', 0, true)),
		);
	}
}
