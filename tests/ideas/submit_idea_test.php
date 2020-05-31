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

class submit_idea_test extends \phpbb\ideas\tests\ideas\ideas_base
{
	public function setUp(): void
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
	public function submit_test_data()
	{
		return array(
			array(4, 'New Idea #1', 'New idea posted by the test framework'),
		);
	}

	/**
	 * Test submit()
	 *
	 * @dataProvider submit_test_data
	 */
	public function test_submit($user_id, $title, $message)
	{
		$ideas = $this->get_ideas_object();

		$idea_id = $ideas->submit($title, $message, $user_id);

		$this->assertGreaterThan(4, $idea_id);

		$idea = $ideas->get_idea($idea_id);

		$this->assertEquals($title, $idea['idea_title']);
		$this->assertEquals($user_id, $idea['idea_author']);
		$this->assertEquals(1, $idea['idea_votes_up']);
	}

	/**
	 * Test submit() fails data
	 *
	 * @return array
	 */
	public function submit_fails_test_data()
	{
		return array(
			array(4, '', '', array('EMPTY_SUBJECT', 'TOO_FEW_CHARS')),
			array(4, str_repeat('a', (\phpbb\ideas\factory\ideas::SUBJECT_LENGTH + 1)), '', array('TITLE_TOO_LONG', 'TOO_FEW_CHARS')),
			array(4, '', str_repeat('a', 101), array('EMPTY_SUBJECT', 'TOO_MANY_CHARS')),
			array(4, 'Foo', '', array('TOO_FEW_CHARS')),
			array(4, '', 'Foo', array('EMPTY_SUBJECT')),
		);
	}

	/**
	 * Test submit() fails
	 *
	 * @dataProvider submit_fails_test_data
	 */
	public function test_submit_fails($user_id, $title, $message, $error)
	{
		$ideas = $this->get_ideas_object();

		$result = $ideas->submit($title, $message, $user_id);

		$this->assertEquals($error, $result);
	}
}

/**
 * Mock submit_post()
 * This function has too much overhead to deal with in this test.
 * We will trust submit_post() is working as expected.
 *
 * Note: for this to work this file should use the same
 * namespace as the class being tested where this is used.
 */
function submit_post()
{
}
