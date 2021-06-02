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

class m13_set_permissions extends \phpbb\db\migration\migration
{
	/**
	 * {@inheritDoc}
	 */
	public static function depends_on()
	{
		return [
			'\phpbb\ideas\migrations\m1_initial_schema',
			'\phpbb\ideas\migrations\m3_acp_data',
			'\phpbb\ideas\migrations\m12_drop_base_url_config',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function effectively_installed()
	{
		return (int) $this->config['ideas_forum_id'] === 0;
	}

	/**
	 * @inheritDoc
	 */
	public function update_data()
	{
		return [
			['custom', [[$this, 'update_permissions']]],
		];
	}

	public function update_permissions()
	{
		$permission_helper = new \phpbb\ideas\factory\permission_helper($this->db, $this->phpbb_root_path, $this->php_ext);
		$permission_helper->set_ideas_forum_permissions($this->config['ideas_forum_id']);
	}
}
