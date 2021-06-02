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

class m2_initial_data extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT * FROM ' . $this->table_prefix . 'ideas_statuses';
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row !== false;
	}

	public static function depends_on()
	{
		return array('\phpbb\ideas\migrations\m1_initial_schema');
	}

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'statuses_data'))),
		);
	}

	public function statuses_data()
	{
		$statuses_data = array(
			array(
				'status_id'		=> 1,
				'status_name'	=> 'NEW',
			),
			array(
				'status_id'		=> 2,
				'status_name'	=> 'IN_PROGRESS',
			),
			array(
				'status_id'		=> 3,
				'status_name'	=> 'IMPLEMENTED',
			),
			array(
				'status_id'		=> 4,
				'status_name'	=> 'DUPLICATE',
			),
			array(
				'status_id'		=> 5,
				'status_name'	=> 'INVALID',
			),
		);

		$insert_buffer = new \phpbb\db\sql_insert_buffer($this->db, $this->table_prefix . 'ideas_statuses');

		foreach ($statuses_data as $row)
		{
			$insert_buffer->insert($row);
		}

		$insert_buffer->flush();
	}
}
