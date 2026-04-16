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

class get_statistics_test extends ideas_base
{
	public function test_get_statistics()
	{
		$object = $this->get_ideas_object();

		$stats = $object->get_statistics();

		self::assertEquals(7, $stats['total']);
		self::assertEquals(1, $stats['implemented']);
		self::assertEquals(1, $stats['in_progress']);
		self::assertEquals(0, $stats['duplicate']);
		self::assertEquals(0, $stats['invalid']);
		self::assertEquals(5, $stats['new']);
	}
}
