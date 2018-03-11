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

class m5_base_url_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->config->offsetExists('ideas_base_url');
	}

	static public function depends_on()
	{
		return array('\phpbb\ideas\migrations\m1_initial_schema');
	}

	public function update_data()
	{
		return array(
			array('config.add', array('ideas_base_url', '')),
		);
	}
}
