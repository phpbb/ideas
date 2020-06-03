<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\cron;

/**
 * Ideas cron task.
 */
class prune_orphaned_ideas extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\ideas\factory\manager */
	protected $ideas;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config         $config Config object
	 * @param \phpbb\ideas\factory\manager $ideas  Ideas factory object
	 * @access public
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\ideas\factory\manager $ideas)
	{
		$this->config = $config;
		$this->ideas = $ideas;
	}

	/**
	 * {@inheritdoc}
	 */
	public function run()
	{
		$this->ideas->delete_orphans();
		$this->config->set('ideas_cron_last_run', time(), false);
	}

	/**
	 * {@inheritdoc}
	 */
	public function should_run()
	{
		return $this->config['ideas_cron_last_run'] < strtotime('1 week ago');
	}
}
