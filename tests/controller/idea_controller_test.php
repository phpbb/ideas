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
	public static $confirm = false;

	/**
	 * Test data for the test_controller test
	 *
	 * @return array Array of test data
	 */
	public function controller_test_data()
	{
		return array(
			array(1, '', '', false, null, null, 302), // non-ajax
			array(2, 'delete', '', true, true, '{}', 200), // ajax delete success (confirm fail)
			array(2, 'delete', '', true, false, 'NO_AUTH_OPERATION', 403), // ajax delete fail
			array(2, 'delete', 'delete', true, true, 'trigger_error', 200), // ajax delete success (confirm true)
			array(3, 'duplicate', 'set_duplicate', true, true, 'true', 200), // ajax set duplicate success
			array(3, 'duplicate', '', true, false, 'false', 200), // ajax set duplicate fail
			array(4, 'removevote', 'remove_vote', true, true, 'true', 200), // ajax remove vote success
			array(4, 'removevote', 'remove_vote', true, true, 'false', 200, ['idea_status' => \phpbb\ideas\ext::$statuses['DUPLICATE']]), // ajax remove vote not allowed
			array(4, 'removevote', 'remove_vote', true, true, 'false', 200, ['idea_status' => \phpbb\ideas\ext::$statuses['IMPLEMENTED']]), // ajax remove vote not allowed
			array(4, 'removevote', '', true, false, '"You do not have the necessary permissions to complete this operation."', 200), // ajax remove vote fail
			array(5, 'rfc', 'set_rfc', true, true, 'true', 200), // ajax set rfc success
			array(5, 'rfc', '', true, false, 'false', 200), // ajax set rfc fail
			array(6, 'status', 'set_status', true, true, 'true', 200), // ajax set status success
			array(6, 'status', '', true, false, 'false', 200), // ajax set status fail
			array(7, 'ticket', 'set_ticket', true, true, 'true', 200), // ajax set ticket success
			array(7, 'ticket', '', true, false, 'false', 200), // ajax set ticket fail
			array(9, 'vote', 'vote', true, true, 'true', 200), // ajax vote success
			array(9, 'vote', 'vote', true, true, 'false', 200, ['idea_status' => \phpbb\ideas\ext::$statuses['DUPLICATE']]), // ajax vote not allowed
			array(9, 'vote', 'vote', true, true, 'false', 200, ['idea_status' => \phpbb\ideas\ext::$statuses['IMPLEMENTED']]), // ajax vote not allowed
			array(9, 'vote', '', true, false, '"You do not have the necessary permissions to complete this operation."', 200), // ajax vote fail
			array(10, 'implemented', 'set_implemented', true, true, 'true', 200), // ajax set implemented success
			array(10, 'implemented', '', true, false, 'false', 200), // ajax set implemented fail
		);
	}

	/**
	 * Idea controller test
	 *
	 * @dataProvider controller_test_data
	 */
	public function test_controller($idea_id, $mode, $callback, $is_ajax, $authorised, $expected, $status_code, $additional_data = [])
	{
		/** @var \phpbb\ideas\controller\idea_controller $controller */
		$controller = $this->get_controller('idea_controller', 'idea');
		self::assertInstanceOf('phpbb\ideas\controller\idea_controller', $controller);

		// mock some basic idea data
		$author_user_id = 2;
		$this->entity->expects(self::once())
			->method('get_idea')
			->willReturn(array_merge(array(
					'idea_id'     => $idea_id,
					'idea_author' => $author_user_id,
					'idea_status' => \phpbb\ideas\ext::$statuses['NEW'],
					'topic_id'    => $idea_id * 10,
				), $additional_data)
			);

		$this->user->data['user_id'] = $authorised ? $author_user_id : ++$author_user_id;

		// mock a result from each method called by the idea controller
		if ($expected === 'true')
		{
			$this->entity->expects(self::once())
				->method(($callback))
				->willReturn($authorised);
		}

		// set if using ajax or not
		$this->request->expects($is_ajax ? self::once() : self::never())
			->method('is_ajax')
			->willReturn($is_ajax);

		// mock some useful variables requested by the idea controller
		$this->request->expects(self::atLeastOnce())
			->method('variable')
			->with(self::anything())
			->willReturnMap(array(
				array('mode', '', false, \phpbb\request\request_interface::REQUEST, $mode),
				array('hash', '', false, \phpbb\request\request_interface::REQUEST, generate_link_hash("{$mode}_{$idea_id}")),
				array('status', 0, false, \phpbb\request\request_interface::REQUEST, 1),
				array('v', 1, false, \phpbb\request\request_interface::REQUEST, 1),
			));

		// mock some user permissions during testing
		$this->auth
			->method('acl_get')
			->with(self::stringContains('_'), self::anything())
			->willReturnMap(array(
				array('m_', $author_user_id, $authorised),
				array('f_vote', $author_user_id, $authorised),
			));

		// special case, expect trigger_error when a confirm_box return true
		if ($expected === 'trigger_error')
		{
			self::$confirm = true;
			$this->setExpectedTriggerError(E_USER_NOTICE);
		}

		if ($status_code === 403)
		{
			$this->expectException('\phpbb\exception\http_exception');
			$this->expectExceptionMessage('NO_AUTH_OPERATION');
		}

		$response = $controller->idea($idea_id);

		if ($is_ajax)
		{
			self::assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
			self::assertEquals($expected, $response->getContent());
		}
		else
		{
			self::assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
		}

		self::assertEquals($status_code, $response->getStatusCode());
	}

	/**
	 * Test data for the test_controller_exception test
	 *
	 * @return array Array of test data
	 */
	public function display_fails_data()
	{
		return array(
			array(0, 2, 404, 'IDEA_NOT_FOUND'), // no idea data
			array(1, 0, 404, 'IDEAS_NOT_AVAILABLE'), // no ideas_forum_id
			array(1, '', 404, 'IDEAS_NOT_AVAILABLE'), // no ideas_forum_id
		);
	}

	/**
	 * Test controller display throws 404 exceptions
	 *
	 * @dataProvider display_fails_data
	 */
	public function test_controller_exception($idea_id, $forum, $status_code, $page_content)
	{
		$this->config['ideas_forum_id'] = $forum;

		/** @var \phpbb\ideas\controller\idea_controller $controller */
		$controller = $this->get_controller('idea_controller', 'idea');
		self::assertInstanceOf('phpbb\ideas\controller\idea_controller', $controller);

		try
		{
			$controller->idea($idea_id);
			self::fail('The expected \phpbb\exception\http_exception was not thrown');
		}
		catch (\phpbb\exception\http_exception $exception)
		{
			self::assertEquals($status_code, $exception->getStatusCode());
			self::assertEquals($page_content, $exception->getMessage());
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
 * @return void
 */
function meta_refresh()
{
}
