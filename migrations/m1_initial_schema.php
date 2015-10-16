<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @author Callum Macrae (callumacrae) <callum@lynxphp.com>
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace phpbb\ideas\migrations;

class m1_initial_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'ideas_ideas');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	public function update_schema()
	{
		return array(
			'add_tables'	=> array(
				$this->table_prefix . 'ideas_ideas' => array(
					'COLUMNS'	=> array(
						'idea_id'			=> array('UINT', NULL, 'auto_increment'),
						'idea_author'		=> array('UINT', 0),
						'idea_title'		=> array('VCHAR', ''),
						'idea_date'			=> array('TIMESTAMP', 0),
						'idea_votes_up'		=> array('UINT', 0),
						'idea_votes_down'	=> array('UINT', 0),
						'idea_status'		=> array('UINT', 1),
						'topic_id'			=> array('UINT', 0),
					),
					'PRIMARY_KEY'			=> 'idea_id',
				),
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
				$this->table_prefix . 'ideas_votes' => array(
					'COLUMNS'	=> array(
						'idea_id'			=> array('UINT', 0),
						'user_id'			=> array('UINT', 0),
						'vote_value'		=> array('BOOL', 0),
					),
					'KEYS'					=> array(
						'idea_id'			=> array('INDEX', array('idea_id', 'user_id')),
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

	public function revert_schema()
	{
		return array(
			'drop_tables'	=> array(
				$this->table_prefix . 'ideas_ideas',
				$this->table_prefix . 'ideas_statuses',
				$this->table_prefix . 'ideas_tickets',
				$this->table_prefix . 'ideas_rfcs',
				$this->table_prefix . 'ideas_votes',
				$this->table_prefix . 'ideas_duplicates',
			),
		);
	}
}
