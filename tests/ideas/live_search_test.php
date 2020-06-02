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

class live_search_test extends \phpbb\ideas\tests\ideas\ideas_base
{
	public function live_search_data()
	{
		return [
			['Foo', [
				0    => [
					'idea_id'     => 1,
					'result'      => 1,
					'clean_title' => 'Foo Idea #1 New with 3 up votes',
					'display'     => '<span>Foo Idea #1 New with 3 up votes</span>',
				], 1 => [
					'idea_id'     => 3,
					'result'      => 3,
					'clean_title' => 'Foo Idea #3 In Progress with 1 up and 1 down vote',
					'display'     => '<span>Foo Idea #3 In Progress with 1 up and 1 down vote</span>',
				],
			]],
			['bar', [
				0    => [
					'idea_id'     => 2,
					'result'      => 2,
					'clean_title' => 'Bar Idea #2 New with 1 down vote',
					'display'     => '<span>Bar Idea #2 New with 1 down vote</span>',
				], 1 => [
					'idea_id'     => 4,
					'result'      => 4,
					'clean_title' => 'Bar Idea #4 Implemented with 0 votes',
					'display'     => '<span>Bar Idea #4 Implemented with 0 votes</span>',
				],
			]],
			['roved', [
				0    => [
					'idea_id'     => 5,
					'result'      => 5,
					'clean_title' => 'Unapproved Idea #5 New with 0 votes',
					'display'     => '<span>Unapproved Idea #5 New with 0 votes</span>',
				],
			]],
			['xxx', []],
		];
	}

	/**
	 * @dataProvider live_search_data
	 */
	public function test_live_search($input, $expected)
	{
		$ideas = $this->get_ideas_object();

		$this->assertEquals($expected, $ideas->ideas_title_livesearch($input));
	}
}
