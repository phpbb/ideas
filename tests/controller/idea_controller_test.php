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

class idea_controller_test extends \phpbb\ideas\tests\controller\controller_base
{
	static $confirm = false;

	/**
	 * Test data for the test_controller test
	 *
	 * @return array Array of test data
	 */
	public function controller_test_data()
	{
		return array(
			array(1, '', '', false, null, null, 302), // non-ajax
			array(2, 'delete', 'delete', true, true, '{}', 200), // ajax delete success (confirm fail)
			array(2, 'delete', 'delete', true, false, '{}', 200), // ajax delete fail
			array(2, 'delete', 'delete', true, true, 'trigger_error', 200), // ajax delete success (confirm true)
			array(3, 'duplicate', 'set_duplicate', true, true, 'true', 200), // ajax set duplicate success
			array(3, 'duplicate', 'set_duplicate', true, false, 'false', 200), // ajax set duplicate fail
			array(4, 'removevote', 'remove_vote', true, true, 'true', 200), // ajax set title success
			array(4, 'removevote', 'remove_vote', true, false, '"You do not have the necessary permissions to complete this operation."', 200), // ajax set title fail
			array(5, 'rfc', 'set_rfc', true, true, 'true', 200), // ajax set rfc success
			array(5, 'rfc', 'set_rfc', true, false, 'false', 200), // ajax set rfc fail
			array(6, 'status', 'set_status', true, true, 'true', 200), // ajax set status success
			array(6, 'status', 'set_status', true, false, 'false', 200), // ajax set status fail
			array(7, 'ticket', 'set_ticket', true, true, 'true', 200), // ajax set ticket success
			array(7, 'ticket', 'set_ticket', true, false, 'false', 200), // ajax set ticket fail
			array(8, 'title', 'set_title', true, true, 'true', 200), // ajax set title success
			array(8, 'title', 'set_title', true, false, 'false', 200), // ajax set title fail
			array(9, 'vote', 'vote', true, true, 'true', 200), // ajax set title success
			array(9, 'vote', 'vote', true, false, '"You do not have the necessary permissions to complete this operation."', 200), // ajax set title fail
		);
	}

	/**
	 * Idea controller test
	 *
	 * @dataProvider controller_test_data
	 */
	public function test_controller($idea_id, $mode, $callback, $is_ajax, $authorised, $expected, $status_code)
	{
		// mock some basic idea data
		$this->ideas->expects($this->any())
			->method('get_idea')
			->will($this->returnValue(array('idea_id' => $idea_id, 'idea_author' => 2)));

		// mock a result from each method called by the idea controller
		$this->ideas->expects($this->any())
			->method($callback)
			->will($this->returnValue($authorised));

		// set if using ajax or not
		$this->request->expects($this->any())
			->method('is_ajax')
			->will($this->returnValue($is_ajax));

		// mock some useful variables requested by the idea controller
		$this->request->expects($this->any())
			->method('variable')
			->with($this->anything())
			->will($this->returnValueMap(array(
				array('mode', '', false, \phpbb\request\request_interface::REQUEST, $mode),
				array('hash', '', false, \phpbb\request\request_interface::REQUEST, generate_link_hash("{$mode}_{$idea_id}")),
				array('status', 0, false, \phpbb\request\request_interface::REQUEST, 1),
				array('v', 1, false, \phpbb\request\request_interface::REQUEST, 1),
			)));

		// mock some user permissions during testing
		$this->auth->expects($this->any())
			->method('acl_get')
			->with($this->stringContains('_'), $this->anything())
			->will($this->returnValueMap(array(
				array('m_', 2, $authorised),
				array('f_vote', 2, $authorised),
			)));

		// special case, expect trigger_error when a confirm_box return true
		if ($expected === 'trigger_error')
		{
			self::$confirm = true;
			$this->setExpectedTriggerError(E_USER_NOTICE);
		}

		/** @var \phpbb\ideas\controller\idea_controller $controller */
		$controller = $this->get_controller('idea_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\idea_controller', $controller);

		$response = $controller->idea($idea_id);

		if ($is_ajax)
		{
			$this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
			$this->assertEquals($expected, $response->getContent());
		}
		else
		{
			$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
		}

		$this->assertEquals($status_code, $response->getStatusCode());
	}

	/**
	 * Test data for the test_controller_exception test
	 *
	 * @return array Array of test data
	 */
	public function display_fails_data()
	{
		return array(
			array(1, 2, 0, 404, 'IDEAS_NOT_AVAILABLE'), // no ideas_poster_id
			array(1, 0, 2, 404, 'IDEAS_NOT_AVAILABLE'), // no ideas_forum_id
			array(1, '', '', 404, 'IDEAS_NOT_AVAILABLE'), // no ideas_poster_id or ideas_forum_id
			array(0, 2, 2, 404, 'IDEA_NOT_FOUND'), // no idea data
		);
	}

	/**
	 * Test controller display throws 404 exceptions
	 *
	 * @dataProvider display_fails_data
	 */
	public function test_controller_exception($idea_id, $idea_bot, $forum, $status_code, $page_content)
	{
		$this->config['ideas_forum_id'] = $forum;
		$this->config['ideas_poster_id'] = $idea_bot;

		/** @var \phpbb\ideas\controller\idea_controller $controller */
		$controller = $this->get_controller('idea_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\idea_controller', $controller);

		try
		{
			$controller->idea($idea_id);
			$this->fail('The expected \phpbb\exception\http_exception was not thrown');
		}
		catch (\phpbb\exception\http_exception $exception)
		{
			$this->assertEquals($status_code, $exception->getStatusCode());
			$this->assertEquals($page_content, $exception->getMessage());
		}
	}
}

/**
 * Mock confirm_box()
 * Note: use the same namespace as the idea_controller
 *
 * @return bool
 */
function confirm_box()
{
	return \phpbb\ideas\controller\idea_controller_test::$confirm;
}

/**
 * Mock meta_refresh()
 * Note: use the same namespace as the idea_controller
 *
 * @return null
 */
function meta_refresh()
{
}
