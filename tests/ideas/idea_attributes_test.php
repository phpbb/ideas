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

class idea_attributes_test extends ideas_base
{
	/**
	 * Test get_status_from_id() data
	 *
	 * @return array
	 */
	public function get_status_from_id_test_data()
	{
		return array(
			array(1, 'NEW'),
			array(2, 'IN_PROGRESS'),
			array(3, 'IMPLEMENTED'),
			array(4, 'DUPLICATE'),
			array(5, 'INVALID'),
			array(6, ''), // this value doesn't exist
		);
	}

	/**
	 * Test get_status_from_id()
	 *
	 * @dataProvider get_status_from_id_test_data
	 */
	public function test_get_status_from_id($id, $expected)
	{
		$ideas = $this->get_ideas_object();

		$this->assertEquals($expected, $ideas->get_status_from_id($id));
	}

	/**
	 * Test change_status() data
	 *
	 * @return array
	 */
	public function change_status_test_data()
	{
		return array(
			array(1, 1),
			array(1, 2),
			array(2, 3),
			array(2, 4),
			array(3, 5),
		);
	}

	/**
	 * Test change_status()
	 *
	 * @dataProvider change_status_test_data
	 */
	public function test_change_status($idea_id, $status)
	{
		$ideas = $this->get_ideas_object();

		$ideas->change_status($idea_id, $status);

		$idea = $ideas->get_idea($idea_id);

		$this->assertEquals($status, $idea['idea_status']);
	}

	/**
	 * Data for idea attribute tests
	 *
	 * @return array
	 */
	public function idea_attribute_test_data()
	{
		return array(
			array(
				array(
					'idea_id'		=> 1,
					'duplicate_id'	=> 2,
					'rfc_link'		=> 'http://area51.phpbb.com/phpBB/viewtopic.php?foo&bar',
					'ticket_id'		=> 1111,
					'idea_title'	=> 'Foo Idea 1',
				), true
			),
			array(
				array(
					'idea_id'		=> 2,
					'duplicate_id'	=> 1,
					'rfc_link'		=> 'https://area51.phpbb.com/phpBB/viewtopic.php?bar&foo',
					'ticket_id'		=> 2222,
					'idea_title'	=> 'Foo Idea 2',
				), true
			),
			array(
				array(
					'idea_id'		=> 3,
					'duplicate_id'	=> '5',
					'rfc_link'		=> '',
					'ticket_id'		=> '3333',
					'idea_title'	=> 'Føó Îdéå',
				), true
			),
			array(
				array(
					'idea_id'		=> 4,
					'duplicate_id'	=> 'foo',
					'rfc_link'		=> 'https://www.phpbb.com/phpBB/viewtopic.php?foo',
					'ticket_id'		=> 'foo',
					'idea_title'	=> '',
				), false
			),
			array(
				array(
					'idea_id'		=> 5,
					'duplicate_id'	=> array(1),
					'rfc_link'		=> 'foobar',
					'ticket_id'		=> array(1),
					'idea_title'	=> str_repeat('a', 65),
				), false
			),
		);
	}

	/**
	 * Test set_duplicate()
	 *
	 * @dataProvider idea_attribute_test_data
	 */
	public function test_set_duplicate($data, $expected)
	{
		$this->set_attribute_test('set_duplicate', 'duplicate_id', $data, $expected);
	}

	/**
	 * Test set_rfc()
	 *
	 * @dataProvider idea_attribute_test_data
	 */
	public function test_set_rfc($data, $expected)
	{
		$this->set_attribute_test('set_rfc', 'rfc_link', $data, $expected);
	}

	/**
	 * Test set_ticket()
	 *
	 * @dataProvider idea_attribute_test_data
	 */
	public function test_set_ticket($data, $expected)
	{
		$this->set_attribute_test('set_ticket', 'ticket_id', $data, $expected);
	}

	/**
	 * Test set_title()
	 *
	 * @dataProvider idea_attribute_test_data
	 */
	public function test_set_title($data, $expected)
	{
		$idea = $this->set_attribute_test('set_title', 'idea_title', $data, $expected);

		// Also check the topic title was updated
		if ($idea)
		{
			$sql = "SELECT topic_title
				FROM phpbb_topics
				WHERE topic_id = {$idea['topic_id']}";
			$result = $this->db->sql_query($sql);
			$topic_title = $this->db->sql_fetchfield('topic_title');
			$this->db->sql_freeresult($result);

			$this->assertEquals($data['idea_title'], $topic_title);
		}
	}

	/**
	 * Set attribute test runner
	 *
	 * @param string $call      The name of the ideas method to call
	 * @param string $attribute The name of the attribute
	 * @param array  $data      The test data array
	 * @param bool   $expected  The expected result returned by method
	 * @return mixed The idea data array, or false if error
	 */
	public function set_attribute_test($call, $attribute, $data, $expected)
	{
		$ideas = $this->get_ideas_object();

		$result = $ideas->$call($data['idea_id'], $data[$attribute]);

		$this->assertEquals($expected, $result);

		if ($result)
		{
			$idea = $ideas->get_idea($data['idea_id']);

			$this->assertEquals($data[$attribute], $idea[$attribute]);

			return $idea;
		}

		return false;
	}
}
