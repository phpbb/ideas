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

use \phpbb\exception\http_exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

class post_controller extends base
{
	/**
	 * Controller for /post
	 * Redirects to the idea forum's posting page.
	 *
	 * @throws http_exception
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function post()
	{
		if (!$this->is_available())
		{
			throw new http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}

		if ($this->user->data['user_id'] == ANONYMOUS)
		{
			throw new http_exception(404, 'LOGGED_OUT');
		}

		$params = array(
			'mode'	=> 'post',
			'f'		=> $this->config['ideas_forum_id'],
		);

		$url = append_sid(generate_board_url() . "/posting.{$this->php_ext}", $params, false);

		return new RedirectResponse($url);
	}
}
