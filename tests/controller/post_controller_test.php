<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\controller;

class post_controller_test extends controller_base
{
	/**
	 * Test data for the test_controller_exception test
	 *
	 * @return array Array of test data
	 */
	public function controller_exception_test_data()
	{
		return [
			[2, 2, 200, ''], // all good
			[0, 2, 404, 'IDEAS_NOT_AVAILABLE'], // forum id is bad
			['', 2, 404, 'IDEAS_NOT_AVAILABLE'], // forum id is bad
			[2, 1, 404, 'LOGGED_OUT'], // user id is anonymous
		];
	}

	/**
	 * Basic controller exception test
	 *
	 * @dataProvider controller_exception_test_data
	 */
	public function test_controller_exception($forum, $user_id, $status_code, $message)
	{
		$this->config['ideas_forum_id'] = $forum;
		$this->user->data['user_id'] = $user_id;

		/** @var \phpbb\ideas\controller\post_controller $controller */
		$controller = $this->get_controller('post_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\post_controller', $controller);

		try
		{
			$response = $controller->post();

			if (!$response)
			{
				$this->fail('The expected \phpbb\exception\http_exception was not thrown');
			}

			$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
		}
		catch (\phpbb\exception\http_exception $exception)
		{
			$this->assertEquals($status_code, $exception->getStatusCode());
			$this->assertEquals($message, $exception->getMessage());
		}
	}
}
