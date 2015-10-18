<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2015 phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\migrations;

class m3_acp_module extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\ideas\migrations\m2_initial_data');
	}

	public function update_data()
	{
		return array(
			array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_PHPBB_IDEAS')),
			array('module.add', array(
				'acp', 'ACP_PHPBB_IDEAS', array(
					'module_basename'	=> '\phpbb\ideas\acp\phpbb_ideas_module',
					'modes'				=> array('settings'),
				),
			)),

			array('config.add', array('ideas_forum_id', '0')),
			array('config.add', array('ideas_poster_id', '0')),
		);
	}
}
