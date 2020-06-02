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

class get_title_test extends \phpbb\ideas\tests\ideas\ideas_base
{
	public function get_title_data()
	{
		return [
			[1, 'Foo Idea #1 New with 3 up votes'],
			[2, 'Bar Idea #2 New with 1 down vote'],
			[3, 'Foo Idea #3 In Progress with 1 up and 1 down vote'],
			[4, 'Bar Idea #4 Implemented with 0 votes'],
			[5, 'Unapproved Idea #5 New with 0 votes'],
			[6, 'Orphaned Idea #6 New with 2 votes'],
			[7, 'Orphaned Idea #7 New with no votes'],
			[8, ''],
		];
	}

	/**
	 * @dataProvider get_title_data
	 */
	public function test_get_title($id, $expected)
	{
		$ideas = $this->get_ideas_object();

		$this->assertSame($expected, $ideas->get_title($id));
	}
}
