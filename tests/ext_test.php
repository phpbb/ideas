<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests;

class ext_test extends \phpbb_test_case
{
	public function test_ext()
	{
		/** @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\DependencyInjection\ContainerInterface $container */
		$container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
			->disableOriginalConstructor()
			->getMock();

		/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\finder\finder $extension_finder */
		$extension_finder = $this->getMockBuilder('\phpbb\finder\finder')
			->disableOriginalConstructor()
			->getMock();

		/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\migrator $migrator */
		$migrator = $this->getMockBuilder('\phpbb\db\migrator')
			->disableOriginalConstructor()
			->getMock();

		$ext = new \phpbb\ideas\ext(
			$container,
			$extension_finder,
			$migrator,
			'phpbb/ideas',
			''
		);

		self::assertTrue($ext->is_enableable());
	}
}
