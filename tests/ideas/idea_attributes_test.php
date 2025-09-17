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

use phpbb\ideas\ext;

class idea_attributes_test extends ideas_base
{
	/**
	 * Test get_status_from_id() data
	 *
	 * @return array
	 */
	public static function get_status_from_id_test_data()
	{
		return array(
			array(1, 'NEW'),
			array(2, 'IN_PROGRESS'),
			array(3, 'IMPLEMENTED'),
			array(4, 'DUPLICATE'),
			array(5, 'INVALID'),
		);
	}

	/**
	 * Test get_status_from_id()
	 *
	 * @dataProvider get_status_from_id_test_data
	 */
	public function test_get_status_from_id($id, $expected)
	{
		self::assertEquals($expected, \phpbb\ideas\ext::status_name($id));
	}

	/**
	 * Test set_status() data
	 *
	 * @return array
	 */
	public static function set_status_test_data()
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
	 * Test set_status()
	 *
	 * @dataProvider set_status_test_data
	 */
	public function test_set_status($idea_id, $status)
	{
		$object = $this->get_idea_object();

		$object->set_status($idea_id, $status);

		$idea = $object->get_idea($idea_id);

		self::assertEquals($status, $idea['idea_status']);
	}

	public static function set_status_notification_data()
	{
		return [
			[1, 1, [], 'add_notifications'],
			[1, 2, [2], 'update_notifications'],
			[2, 3, [], 'add_notifications'],
			[2, 4, [3], 'update_notifications'],
		];
	}

	/**
	 * @dataProvider set_status_notification_data
	 */
	public function test_set_status_notification($idea_id, $status, $notified_users, $expected)
	{
		$this->notification_manager->expects($this->once())
			->method('get_notified_users')
			->with(ext::NOTIFICATION_TYPE_STATUS, ['item_id' => $idea_id])
			->willReturn($notified_users);

		$this->notification_manager->expects($this->once())
			->method($expected);

		$object = $this->get_idea_object();

		$object->set_status($idea_id, $status);
	}

	/**
	 * Data for idea attribute tests
	 *
	 * @return array
	 */
	public static function idea_attribute_test_data()
	{
		return array(
			array(
				array(
					'idea_id'		=> 1,
					'duplicate_id'	=> 2,
					'rfc_link'		=> 'https://area51.phpbb.com/phpBB/viewtopic.php?foo&bar',
					'implemented_version' => '3.1.0',
					'ticket_id'		=> 1111,
					'idea_title'	=> 'Foo Idea 1',
				), true
			),
			array(
				array(
					'idea_id'		=> 2,
					'duplicate_id'	=> 1,
					'rfc_link'		=> 'https://area51.phpbb.com/phpBB/viewtopic.php?bar&foo',
					'implemented_version' => '3.2.0',
					'ticket_id'		=> 2222,
					'idea_title'	=> 'Foo Idea 2',
				), true
			),
			array(
				array(
					'idea_id'		=> 3,
					'duplicate_id'	=> '5',
					'rfc_link'		=> '',
					'implemented_version' => '',
					'ticket_id'		=> '3333',
					'idea_title'	=> 'Føó Îdéå',
				), true
			),
			array(
				array(
					'idea_id'		=> 4,
					'duplicate_id'	=> 'foo',
					'rfc_link'		=> 'https://www.phpbb.com/phpBB/viewtopic.php?foo',
					'implemented_version' => 'foo',
					'ticket_id'		=> 'foo',
					'idea_title'	=> '',
				), false
			),
			array(
				array(
					'idea_id'		=> 5,
					'duplicate_id'	=> array(1),
					'rfc_link'		=> 'foobar',
					'implemented_version' => '1',
					'ticket_id'		=> array(1),
					'idea_title'	=> '',
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
	 * Test set_implemented()
	 *
	 * @dataProvider idea_attribute_test_data
	 */
	public function test_set_implemented($data, $expected)
	{
		$this->set_attribute_test('set_implemented', 'implemented_version', $data, $expected);
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
		$this->set_attribute_test('set_title', 'idea_title', $data, $expected);
	}

	/**
	 * Set attribute test runner
	 *
	 * @param string $call      The name of the ideas method to call
	 * @param string $attribute The name of the attribute
	 * @param array  $data      The test data array
	 * @param bool   $expected  The expected result returned by method
	 * @return array|false The idea data array, or false if error
	 */
	public function set_attribute_test($call, $attribute, $data, $expected)
	{
		$object = $this->get_idea_object();

		$result = $object->$call($data['idea_id'], $data[$attribute]);

		self::assertEquals($expected, $result);

		if ($result)
		{
			$idea = $object->get_idea($data['idea_id']);

			self::assertEquals($data[$attribute], $idea[$attribute]);

			return $idea;
		}

		return false;
	}
}
