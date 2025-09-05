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

use PHPUnit\Framework\MockObject\MockObject;
use phpbb\notification\manager;
use phpbb\finder;
use phpbb\db\migrator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use phpbb\ideas\ext;

class ext_test extends \phpbb_test_case
{
	/** @var ext */
	private $ext;

	/** @var MockObject|manager */
	private $notification_manager;

	/** @var MockObject|ContainerInterface */
	private $container;

	/** @var MockObject|finder */
	private $extension_finder;

	/** @var MockObject|migrator */
	private $migrator;

	protected function setUp(): void
	{
		parent::setUp();
		$this->initialize_mocks();
		$this->create_extension();
	}

	private function initialize_mocks(): void
	{
		$this->notification_manager = $this->createMock(manager::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->extension_finder = $this->createMock(finder::class);
		$this->migrator = $this->createMock(migrator::class);
	}

	private function create_extension(): void
	{
		$this->ext = new ext(
			$this->container,
			$this->extension_finder,
			$this->migrator,
			'phpbb/ideas',
			''
		);
	}

	private function setup_notification_manager(string $method): void
	{
		$this->container->expects($this->once())
			->method('get')
			->with('notification_manager')
			->willReturn($this->notification_manager);

		$this->notification_manager->expects($this->once())
			->method($method)
			->with(ext::NOTIFICATION_TYPE_STATUS);
	}

	public function test_is_enableable(): void
	{
		$this->assertTrue($this->ext->is_enableable());
	}

	/**
	 * @dataProvider notification_step_provider
	 */
	public function test_notification_steps(string $method, string $step): void
	{
		$this->setup_notification_manager($method);

		$state = $this->ext->$step(false);
		$this->assertEquals('notification', $state);
	}

	public function notification_step_provider(): array
	{
		return [
			'enable step'  => ['enable_notifications', 'enable_step'],
			'disable step' => ['disable_notifications', 'disable_step'],
			'purge step'   => ['purge_notifications', 'purge_step']
		];
	}

	/**
	 * @dataProvider parent_step_provider
	 */
	public function test_parent_steps(string $step, $expected_result): void
	{
		$this->setup_parent_step_expectations($step, $expected_result);

		$state = $this->ext->$step('notification');
		$this->assertEquals($expected_result, $state);
	}

	private function setup_parent_step_expectations(string $step, $expected_result): void
	{
		if ($step === 'enable_step')
		{
			$this->extracted();

			$this->migrator->expects($this->once())
				->method('update');

			$this->migrator->expects($this->once())
				->method('finished')
				->willReturn(!$expected_result);
		}
		else if ($step === 'purge_step')
		{
			$this->extracted();
		}
	}

	public function parent_step_provider(): array
	{
		return [
			'enable parent step' => ['enable_step', false],
			'disable parent step' => ['disable_step', false],
			'purge parent step' => ['purge_step', false]
		];
	}

	/**
	 * @return void
	 */
	private function extracted(): void
	{
		$this->extension_finder->expects($this->once())
			->method('extension_directory')
			->with('/migrations')
			->willReturnSelf();

		$this->extension_finder->expects($this->once())
			->method('find_from_extension')
			->with('phpbb/ideas', '')
			->willReturn([]);

		$this->extension_finder->expects($this->once())
			->method('get_classes_from_files')
			->with([])
			->willReturn([]);

		$this->migrator->expects($this->once())
			->method('set_migrations')
			->with([]);

		$this->migrator->expects($this->once())
			->method('get_migrations')
			->willReturn([]);
	}
}
