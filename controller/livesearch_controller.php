<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\controller;

/**
 * Ideas live search controller
 */
class livesearch_controller extends base
{
	public function title_search()
	{
		$title_chars = $this->request->variable('duplicateeditinput', '', true);

		$matches = $this->ideas->ideas_title_livesearch($title_chars, 10);

		$json_response = new \phpbb\json_response();
		$json_response->send([
			'keyword' => $title_chars,
			'results' => $matches,
		]);
	}
}
