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

require_once dirname(__FILE__) . '/../../../../../includes/functions_content.php';

class get_voters_test extends ideas_base
{
	/**
	 * Test get_voters() data
	 *
	 * @return array
	 */
	public function get_voters_test_data()
	{
		return array(
			array(1, array('admin', 'ideabot', 'poster')),
			array(2, array('ideabot')),
			array(3, array('admin', 'ideabot')),
			array(4, array()),
			array(5, array()),
		);
	}

	/**
	 * Test get_voters()
	 *
	 * @dataProvider get_voters_test_data
	 */
	public function test_get_voters($idea_id, $expected)
	{
		$ideas = $this->get_ideas_object();

		$voters = $ideas->get_voters($idea_id);

		$users = array();
		foreach ($voters as $voter)
		{
			$users[] = $voter['username'];
		}

		$this->assertEquals($expected, $users);
	}
}
