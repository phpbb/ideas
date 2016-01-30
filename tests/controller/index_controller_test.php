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

class index_controller_test extends controller_base
{
	/**
	 * Test data for the test_controller test
	 *
	 * @return array Array of test data
	 */
	public function controller_test_data()
	{
		return array(
			array(200, 'index_body.html'),
		);
	}

	/**
	 * Basic controller test
	 *
	 * @dataProvider controller_test_data
	 */
	public function test_controller($status_code, $page_content)
	{
		/** @var \phpbb\ideas\controller\index_controller $controller */
		$controller = $this->get_controller('index_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\index_controller', $controller);

		$response = $controller->index();
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
			array(2, 0),
			array(0, 2),
			array('', ''),
		);
	}

	/**
	 * Basic controller exception test
	 *
	 * @dataProvider controller_exception_test_data
	 * @expectedException \phpbb\exception\http_exception
	 */
	public function test_controller_exception($idea_bot, $forum)
	{
		$this->config['ideas_forum_id'] = $forum;
		$this->config['ideas_poster_id'] = $idea_bot;

		/** @var \phpbb\ideas\controller\index_controller $controller */
		$controller = $this->get_controller('index_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\index_controller', $controller);

		$controller->index();
	}
}
