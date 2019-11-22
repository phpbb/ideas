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

class preview_idea_test extends \phpbb\ideas\tests\ideas\ideas_base
{
	public function setUp(): void
	{
		parent::setUp();

		global $phpbb_container;

		// This is needed to set up the s9e text formatter services
		// This can lead to a test failure if PCRE is old.
		$this->get_test_case_helpers()->set_s9e_services($phpbb_container);
	}

	/**
	 * Test preview() data
	 *
	 * @return array
	 */
	public function preview_test_data()
	{
		return array(
			array('New idea 1 posted by the test framework.'),
			array('New idea 2 [u]posted[/u] by the test [b]framework[/b].'),
		);
	}

	/**
	 * Test preview()
	 *
	 * @dataProvider preview_test_data
	 */
	public function test_preview($message)
	{
		// Get the ideas object
		$ideas = $this->get_ideas_object();

		// store the message in a temp variable, since it will forever altered
		$test_message = $message;

		// Prepare the test message for storage
		$uid = $bitfield = $flags = '';
		generate_text_for_storage($test_message, $uid, $bitfield, $flags, true, true, true);

		// Prepare the test message for display
		$expected = generate_text_for_display($test_message, $uid, $bitfield, $flags);

		// Actually run the original test message through preview method
		$preview = $ideas->preview($message);

		// Assert preview message was parsed and rendered as expected
		$this->assertEquals($expected, $preview);
	}
}
