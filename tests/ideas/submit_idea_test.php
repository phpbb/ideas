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

class submit_idea_test extends \phpbb\ideas\tests\ideas\ideas_base
{
	protected function setUp(): void
	{
		parent::setUp();

		global $phpbb_container;

		// This is needed to set up the s9e text formatter services
		// This can lead to a test failure if PCRE is old.
		$this->get_test_case_helpers()->set_s9e_services($phpbb_container);

		$this->config['max_post_chars'] = 100;
		$this->config['min_post_chars'] = 3;
	}

	/**
	 * Test submit() data
	 *
	 * @return array
	 */
	public static function submit_test_data()
	{
		return array(
			array(4, 'New Idea #1'),
		);
	}

	/**
	 * Test submit()
	 *
	 * @dataProvider submit_test_data
	 */
	public function test_submit($user_id, $title)
	{
		$data = [
			'topic_title'	=> $title,
			'poster_id'		=> $user_id,
			'post_time'		=> time(),
			'topic_id'		=> 100,
		];

		$object = $this->get_idea_object();

		$idea_id = $object->submit($data);

		self::assertGreaterThan(7, $idea_id);

		$idea = $object->get_idea($idea_id);

		self::assertEquals($title, $idea['idea_title']);
		self::assertEquals($user_id, $idea['idea_author']);
		self::assertEquals(1, $idea['idea_votes_up']);
	}
}
