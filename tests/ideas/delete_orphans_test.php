<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\factory;

class delete_orphans_test extends \phpbb\ideas\tests\ideas\ideas_base
{
	public function test_delete_orphans()
	{
		$idea_id = 6;

		$ideas = $this->get_ideas_object();

		// First, check the idea exists
		$this->assertNotEmpty($ideas->get_idea($idea_id));

		// Delete orphans
		$this->assertEquals(1, $ideas->delete_orphans());

		// Confirm idea no longer exists
		$this->assertFalse($ideas->get_idea($idea_id));

		// check that all votes are removed
		$sql = 'SELECT * FROM phpbb_ideas_votes WHERE idea_id = ' . $idea_id;
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$this->assertFalse($rows);
	}
}
