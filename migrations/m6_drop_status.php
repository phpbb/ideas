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

class m6_drop_status extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return !$this->db_tools->sql_table_exists($this->table_prefix . 'ideas_statuses');
	}

	static public function depends_on()
	{
		return array('\phpbb\ideas\migrations\m4_update_statuses');
	}

	public function update_schema()
	{
		return array(
			'drop_tables'	=> array(
				$this->table_prefix . 'ideas_statuses',
			),
		);
	}
}
