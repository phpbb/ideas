<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\cron;

class cron_test extends \phpbb_test_case
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\ideas\cron\prune_orphaned_ideas */
	protected $cron_task;

	/** @var \phpbb\ideas\factory\ideas|\PHPUnit\Framework\MockObject\MockObject */
	protected $ideas;

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = new \phpbb\config\config(['ideas_cron_last_run' => 0]);

		$this->ideas = $this->getMockBuilder('\phpbb\ideas\factory\ideas')
			->disableOriginalConstructor()
			->getMock();

		$this->cron_task = new \phpbb\ideas\cron\prune_orphaned_ideas($this->config, $this->ideas);
	}

	/**
	 * Test the cron task runs correctly
	 */
	public function test_run()
	{
		// Get last run time stored for cron
		$cron_last_run = $this->config['ideas_cron_last_run'];

		// Expect ideas method delete_orphans is called once
		$this->ideas->expects(self::once())
			->method('delete_orphans');

		// Run the cron task
		$this->cron_task->run();

		// Now the last run time should be greater than before the test
		self::assertGreaterThan($cron_last_run, $this->config['ideas_cron_last_run']);
	}

	/**
	 * Data set for test_should_run
	 *
	 * @return array Array of test data
	 */
	public function should_run_data()
	{
		return array(
			array(time(), false),
			array(strtotime('1 day ago'), false),
			array(strtotime('8 days ago'), true),
			array('', true),
			array(0, true),
			array(null, true),
		);
	}

	/**
	 * Test cron task should run after 1 week (7 days)
	 *
	 * @dataProvider should_run_data
	 */
	public function test_should_run($time, $expected)
	{
		$this->config['ideas_cron_last_run'] = $time;

		self::assertSame($expected, $this->cron_task->should_run());
	}
}
