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

class m10_update_idea_schema extends \phpbb\db\migration\migration
{
	/**
	 * {@inheritDoc}
	 */
	static public function depends_on()
	{
		return [
			'\phpbb\ideas\migrations\m1_initial_schema',
			'\phpbb\ideas\migrations\m6_migrate_old_tables',
			'\phpbb\ideas\migrations\m7_drop_old_tables',
			'\phpbb\ideas\migrations\m8_implemented_version',
			'\phpbb\ideas\migrations\m9_remove_idea_bot',
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * Convert ideas title column to sortable text (same as topic titles)
	 * to allow for case-insensitive SQL LIKE searches.
	 */
	public function update_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'ideas_ideas' => [
					'idea_title' => ['STEXT_UNI', '', 'true_sort'],
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function revert_schema()
	{
	}
}
