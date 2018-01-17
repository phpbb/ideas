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

class m8_implemented_version extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'ideas_ideas', 'implemented_version');
	}

	static public function depends_on()
	{
		return array(
			'\phpbb\ideas\migrations\m1_initial_schema',
			'\phpbb\ideas\migrations\m4_update_statuses',
			'\phpbb\ideas\migrations\m6_migrate_old_tables',
			'\phpbb\ideas\migrations\m7_drop_old_tables',
		);
	}

	public function update_schema()
	{
		return array(
			'add_columns'		=> array(
				$this->table_prefix . 'ideas_ideas'			=> array(
					'implemented_version'		=> array('VCHAR', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'        => array(
				$this->table_prefix . 'ideas_ideas'        => array(
					'implemented_version',
				),
			),
		);
	}
}
