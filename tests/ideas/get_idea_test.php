<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\ideas;

class get_idea_test extends ideas_base
{
	/**
	 * Test data array
	 *
	 * @return array
	 */
	public static function get_idea_test_data()
	{
		return array(
			array(1, 'Idea #1'),
			array(2, 'Idea #2'),
			array(3, 'Idea #3'),
			array(4, 'Idea #4'),
		);
	}

	/**
	 * Test fails data array
	 *
	 * @return array
	 */
	public static function get_idea_fails_test_data()
	{
		return array(
			array(50),
			array(''),
			array(null),
			array(0),
		);
	}

	/**
	 * Test get_idea()
	 *
	 * @dataProvider get_idea_test_data
	 */
	public function test_get_idea($idea_id, $expected)
	{
		$object = $this->get_idea_object();

		$rows = $object->get_idea($idea_id);

		self::assertNotFalse(strpos($rows['idea_title'], $expected));
	}

	/**
	 * Test get_idea() fails
	 *
	 * @dataProvider get_idea_fails_test_data
	 */
	public function test_get_idea_fails($idea_id)
	{
		$object = $this->get_idea_object();

		self::assertFalse($object->get_idea($idea_id));
	}

	/**
	 * Test get_idea_by_topic_id()
	 *
	 * @dataProvider get_idea_test_data
	 */
	public function test_get_idea_by_topic_id($topic_id, $expected)
	{
		$object = $this->get_idea_object();

		$rows = $object->get_idea_by_topic_id($topic_id);

		self::assertNotFalse(strpos($rows['idea_title'], $expected));
	}

	/**
	 * Test get_idea_by_topic_id() fails
	 *
	 * @dataProvider get_idea_fails_test_data
	 */
	public function test_get_idea_by_topic_id_fails($topic_id)
	{
		$object = $this->get_idea_object();

		self::assertFalse($object->get_idea_by_topic_id($topic_id));
	}
}
