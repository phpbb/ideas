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
		return array(
			'\phpbb\ideas\migrations\m1_initial_schema',
			'\phpbb\ideas\migrations\m6_migrate_old_tables',
		);
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

	public function revert_schema()
	{
		return array(
			'add_tables'	=> array(
				$this->table_prefix . 'ideas_statuses' => array(
					'COLUMNS'	=> array(
						'status_id'			=> array('UINT', 0),
						'status_name'		=> array('VCHAR', ''),
					),
					'PRIMARY_KEY'			=> 'status_id',
				),
				$this->table_prefix . 'ideas_tickets' => array(
					'COLUMNS'	=> array(
						'idea_id'			=> array('UINT', 0),
						'ticket_id'			=> array('UINT', 0),
					),
					'KEYS'					=> array(
						'ticket_key' 		=> array('INDEX', array('idea_id', 'ticket_id')),
					),
				),
				$this->table_prefix . 'ideas_rfcs' => array(
					'COLUMNS'	=> array(
						'idea_id'			=> array('UINT', 0),
						'rfc_link'			=> array('VCHAR', ''),
					),
					'KEYS'					=> array(
						'rfc_key'			=> array('INDEX', array('idea_id', 'rfc_link')),
					),
				),
				$this->table_prefix . 'ideas_duplicates' => array(
					'COLUMNS'	=> array(
						'idea_id'			=> array('UINT', 0),
						'duplicate_id'		=> array('UINT', 0),
					),
					'KEYS'					=> array(
						'dupe_key'			=> array('INDEX', array('idea_id', 'duplicate_id')),
					),
				),
			),
		);
	}
}
