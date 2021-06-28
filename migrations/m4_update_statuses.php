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

class m4_update_statuses extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT status_id
			FROM ' . $this->table_prefix . "ideas_statuses
			WHERE status_name='New'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row === false;
	}

	public static function depends_on()
	{
		return array(
			'\phpbb\ideas\migrations\m1_initial_schema',
			'\phpbb\ideas\migrations\m2_initial_data',
		);
	}

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'update_statuses'))),
		);
	}

	public function update_statuses()
	{
		$status_updates = array(
			'New'			=> 'NEW',
			'In Progress'	=> 'IN_PROGRESS',
			'Implemented'	=> 'IMPLEMENTED',
			'Duplicate'		=> 'DUPLICATE',
			'Invalid'		=> 'INVALID',
		);

		$this->db->sql_transaction('begin');

		foreach ($status_updates as $old => $new)
		{
			$sql = 'UPDATE ' . $this->table_prefix . "ideas_statuses
				SET status_name='" . $this->db->sql_escape($new) . "'
				WHERE status_name='" .  $this->db->sql_escape($old) . "'";
			$this->db->sql_query($sql);
		}

		$this->db->sql_transaction('commit');
	}
}
