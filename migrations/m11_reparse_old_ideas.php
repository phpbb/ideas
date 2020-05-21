<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\migrations;

class m11_reparse_old_ideas extends \phpbb\db\migration\container_aware_migration
{
	/**
	 * {@inheritDoc}
	 */
	public function effectively_installed()
	{
		return $this->config->offsetExists('phpbb.ideas.text_reparser.clean_old_ideas');
	}

	/**
	 * {@inheritDoc}
	 */
	public static function depends_on()
	{
		return [
			'\phpbb\ideas\migrations\m1_initial_schema',
			'\phpbb\ideas\migrations\m6_migrate_old_tables',
			'\phpbb\ideas\migrations\m7_drop_old_tables',
			'\phpbb\ideas\migrations\m8_implemented_version',
			'\phpbb\ideas\migrations\m10_update_idea_schema',
		];
	}

	/**
	 * @inheritDoc
	 */
	public function update_data()
	{
		return [
			['custom', [[$this, 'reparse']]],
		];
	}

	/**
	 * Run the clean old ideas reparser
	 *
	 * @param int $current An idea identifier
	 * @return bool|int An idea identifier or true if finished
	 */
	public function reparse($current = 0)
	{
		/** @var \phpbb\textreparser\manager $reparser_manager */
		$reparser_manager = $this->container->get('text_reparser.manager');

		/** @var \phpbb\textformatter\s9e\utils $text_formatter_utils */
		$text_formatter_utils = $this->container->get('text_formatter.utils');

		$reparser = new \phpbb\ideas\textreparser\plugins\clean_old_ideas(
			$this->db,
			$text_formatter_utils,
			$this->container->getParameter('tables.posts'),
			$this->container->getParameter('tables.topics'),
			$this->container->getParameter('core.table_prefix') . 'ideas_ideas'
		);

		if (empty($current))
		{
			$current = $reparser->get_max_id();
		}

		$limit = 50; // lets keep the reparsing conservative
		$start = max(1, $current + 1 - $limit);
		$end   = max(1, $current);

		$reparser->reparse_range($start, $end);

		$current = $start - 1;

		if ($current === 0)
		{
			// Prevent CLI command from running this reparser again
			$reparser_manager->update_resume_data('phpbb.ideas.text_reparser.clean_old_ideas', 1, 0, $limit);

			return true;
		}

		return $current;
	}
}
