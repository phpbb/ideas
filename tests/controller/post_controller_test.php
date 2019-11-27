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
	public function setUp()
	{
		parent::setUp();

		global $db, $phpbb_container;
		$db = $this->getMockBuilder('\phpbb\db\driver\driver_interface')
			->disableOriginalConstructor()
			->getMock();
		$phpbb_container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * Test data for the test_controller test
	 *
	 * @return array Array of test data
	 */
	public function controller_test_data()
	{
		return array(
			array(200, '@phpbb_ideas/idea_new.html'),
		);
	}

	/**
	 * Basic controller test
	 *
	 * @dataProvider controller_test_data
	 */
	public function test_controller($status_code, $page_content)
	{
		/** @var \phpbb\ideas\controller\post_controller $controller */
		$controller = $this->get_controller('post_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\post_controller', $controller);

		$response = $controller->post();
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		$this->assertEquals($status_code, $response->getStatusCode());
		$this->assertEquals($page_content, $response->getContent());
	}

	/**
	 * Test data for the test_controller_exception test
	 *
	 * @return array Array of test data
	 */
	public function controller_exception_test_data()
	{
		return array(
			array(0, 2, 404, 'IDEAS_NOT_AVAILABLE'), // forum id is bad
			array('', 2, 404, 'IDEAS_NOT_AVAILABLE'), // forum id is bad
			array(2, 1, 404, 'LOGGED_OUT'), // user id is anonymous
		);
	}

	/**
	 * Basic controller exception test
	 *
	 * @dataProvider controller_exception_test_data
	 */
	public function test_controller_exception($forum, $user_id, $status_code, $page_content)
	{
		$this->config['ideas_forum_id'] = $forum;
		$this->user->data['user_id'] = $user_id;

		/** @var \phpbb\ideas\controller\post_controller $controller */
		$controller = $this->get_controller('post_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\post_controller', $controller);

		try
		{
			$controller->post();
			$this->fail('The expected \phpbb\exception\http_exception was not thrown');
		}
		catch (\phpbb\exception\http_exception $exception)
		{
			$this->assertEquals($status_code, $exception->getStatusCode());
			$this->assertEquals($page_content, $exception->getMessage());
		}
	}

	/**
	 * Test data for the test_post_success test
	 *
	 * @return array Array of test data
	 */
	public function post_success_data()
	{
		return array(
			array(true),
			array(false),
		);
	}

	/**
	 * Test post
	 *
	 * @dataProvider post_success_data
	 */
	public function test_post_success($is_newly_registered_user)
	{
		/** @var \phpbb\ideas\controller\post_controller $controller */
		$controller = $this->get_controller('post_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\post_controller', $controller);

		$this->controller_helper->expects($this->once())
			->method('route')
			->willReturn('phpbb_ideas_idea_controller');

		$this->request->expects($this->once())
			->method('is_set_post')
			->willReturnMap(array(
				array('post', true),
			));

		$this->auth->expects($this->once())
			->method('acl_get')
			->with('f_noapprove', $this->config['ideas_forum_id'])
			->willReturn(!$is_newly_registered_user);

		if ($is_newly_registered_user)
		{
			$this->expectException('\phpbb\exception\http_exception');
			$this->expectExceptionMessage('IDEA_STORED_MOD');
		}

		// ideas->submit() will return an idea id on successful submit
		$this->ideas->expects($this->once())
			->method('submit')
			->willReturn(1);

		$response = $controller->post();
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
	}

	/**
	 * Test preview
	 */
	public function test_preview()
	{
		/** @var \phpbb\ideas\controller\post_controller $controller */
		$controller = $this->get_controller('post_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\post_controller', $controller);

		$this->request->expects($this->atLeastOnce())
			->method('is_set_post')
			->willReturnMap(array(
				array('post', false),
				array('preview', true),
			));

		$this->request->expects($this->atLeastOnce())
			->method('variable')
			->willReturnMap(array(
				array('title', '', true, \phpbb\request\request_interface::REQUEST, 'test title'),
				array('message', '', true, \phpbb\request\request_interface::REQUEST, 'test message'),
			));

		$this->ideas->expects($this->never())
			->method('submit');

		$this->ideas->expects($this->once())
			->method('preview')
			->willReturn('test message');

		$this->template->expects($this->at(0))
			->method('assign_vars')
			->with(array(
				'S_DISPLAY_PREVIEW' => true,
				'PREVIEW_SUBJECT'   => 'test title',
				'PREVIEW_MESSAGE'   => 'test message'
			));

		$controller->post();
	}

	/**
	 * Test submit errors
	 */
	public function test_submit_errors()
	{
		/** @var \phpbb\ideas\controller\post_controller $controller */
		$controller = $this->get_controller('post_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\post_controller', $controller);

		$this->request->expects($this->atLeastOnce())
			->method('is_set_post')
			->willReturnMap(array(
				array('post', true),
				array('preview', false),
			));

		$this->request->expects($this->atLeastOnce())
			->method('variable')
			->willReturnMap(array(
				array('title', '', true, \phpbb\request\request_interface::REQUEST, 'test title'),
				array('message', '', true, \phpbb\request\request_interface::REQUEST, 'test message'),
			));

		// ideas->submit() will return an array of error messages on submit error
		$this->ideas->expects($this->once())
			->method('submit')
			->willReturn(array('error1', 'error2'));

		$this->template->expects($this->at(0))
			->method('assign_vars')
			->with(array(
				'ERROR' => 'error1<br />error2',
				'MESSAGE' => 'test message',
			));

		$response = $controller->post();
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
	}
}
