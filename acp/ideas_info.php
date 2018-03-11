<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\acp;

class ideas_info
{
	public function module()
	{
		return array(
			'filename' => '\phpbb\ideas\acp\ideas_module',
			'title'    => 'ACP_PHPBB_IDEAS',
			'modes'    => array(
				'settings' => array(
					'title' => 'ACP_PHPBB_IDEAS_SETTINGS',
					'auth'  => 'ext_phpbb/ideas && acl_a_board',
					'cat'   => array('ACP_PHPBB_IDEAS'),
				),
			),
		);
	}
}
