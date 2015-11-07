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

class m7_drop_old_tables extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return !$this->db_tools->sql_table_exists($this->table_prefix . 'ideas_statuses');
	}

	static public function depends_on()
	{
		return array('\phpbb\ideas\migrations\m6_migrate_old_tables');
	}

	public function update_schema()
	{
		return array(
			'drop_tables'	=> array(
				$this->table_prefix . 'ideas_statuses',
				$this->table_prefix . 'ideas_tickets',
				$this->table_prefix . 'ideas_rfcs',
				$this->table_prefix . 'ideas_duplicates',
			),
		);
	}
}
