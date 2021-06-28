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

class m6_migrate_old_tables extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'ideas_ideas', 'duplicate_id');
	}

	public static function depends_on()
	{
		return array(
			'\phpbb\ideas\migrations\m1_initial_schema',
			'\phpbb\ideas\migrations\m4_update_statuses',
		);
	}

	public function update_schema()
	{
		return array(
			'add_columns'		=> array(
				$this->table_prefix . 'ideas_ideas'			=> array(
					'duplicate_id'		=> array('UINT', 0),
					'ticket_id'			=> array('UINT', 0),
					'rfc_link'			=> array('VCHAR', ''),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'        => array(
				$this->table_prefix . 'ideas_ideas'        => array(
					'duplicate_id',
					'ticket_id',
					'rfc_link',
				),
			),
		);
	}

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'migrate_tables'))),
		);
	}

	public function migrate_tables()
	{
		$this->move_table_data('ideas_rfcs', 'rfc_link');
		$this->move_table_data('ideas_duplicates', 'duplicate_id');
		$this->move_table_data('ideas_tickets', 'ticket_id');
	}

	/**
	 * Move data into the ideas table
	 *
	 * @param string $table  The name of the old table to get data from
	 * @param string $column The name of the column to get data from
	 */
	public function move_table_data($table, $column)
	{
		$data = array();

		$sql = 'SELECT *
			FROM ' . $this->table_prefix . $table;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$data[$row['idea_id']] = $row[$column];
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_transaction('begin');

		if (sizeof($data))
		{
			foreach ($data as $idea_id => $value)
			{
				$sql = 'UPDATE ' . $this->table_prefix . "ideas_ideas
					SET $column = '" . $this->db->sql_escape($value) . "'
					WHERE idea_id = " . (int) $idea_id;
				$this->db->sql_query($sql);
			}
		}

		$this->db->sql_transaction('commit');
	}
}
