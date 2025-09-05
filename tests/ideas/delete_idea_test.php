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

class delete_idea_test extends \phpbb\ideas\tests\ideas\ideas_base
{
	/**
	 * Test delete() data
	 *
	 * @return array
	 */
	public function delete_test_data()
	{
		return array(
			array(1),
			array(2),
		);
	}

	/**
	 * Test delete()
	 *
	 * @dataProvider delete_test_data
	 */
	public function test_delete($idea_id)
	{
		$this->notification_manager->expects($this->once())
			->method('delete_notifications');

		$object = $this->get_idea_object();

		// delete idea
		self::assertTrue($object->delete($idea_id));

		// check idea no longer exists
		self::assertFalse($object->get_idea($idea_id));

		// check that all votes are removed
		$sql = 'SELECT * FROM phpbb_ideas_votes WHERE idea_id = ' . $idea_id;
		$result = $this->db->sql_query($sql);
		$rows = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		self::assertFalse($rows);
	}

	/**
	 * Test delete() fails data
	 *
	 * @return array
	 */
	public function delete_fails_test_data()
	{
		return array(
			array(10),
			array(''),
			array(null),
		);
	}

	/**
	 * Test delete() fails
	 *
	 * @dataProvider delete_fails_test_data
	 */
	public function test_delete_fails($idea_id)
	{
		$this->notification_manager->expects($this->never())
			->method('delete_notifications');

		$object = $this->get_idea_object();

		self::assertFalse($object->delete($idea_id));
	}
}

/**
 * Mock delete_posts()
 * This function has too much overhead to deal with in this test.
 * We will trust delete_posts() is working as expected.
 *
 * Note: for this to work this file should use the same
 * namespace as the class being tested where this is used.
 */
function delete_posts()
{
}
