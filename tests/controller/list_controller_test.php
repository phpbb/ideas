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

class list_controller_test extends controller_base
{
	/**
	 * Test data for the test_controller test
	 *
	 * @return array Array of test data
	 */
	public function controller_test_data()
	{
		return array(
			// DEFAULT LIST VIEWS //
			// Call top ideas list
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_TOP,
					'status' => 0,
				),
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_TOP,
					'status' => 0,
				),
			),
			// Call latest ideas list
			array(
				200,
				'list_body.html',
				array(
					'sort' => '',
					'status' => 0,
				),
				array(
					'sort' => '',
					'status' => 0,
				),
			),
			// Call implemented ideas list
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_IMPLEMENTED,
					'status' => 0,
				),
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_DATE,
					'status' => \phpbb\ideas\factory\ideas::$statuses['IMPLEMENTED'],
				),
			),

			// OPTIONAL DISPLAY AND SORT LISTS
			// New ideas sorted by date
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_NEW,
					'status' => \phpbb\ideas\factory\ideas::$statuses['NEW'],
				),
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_DATE,
					'status' => \phpbb\ideas\factory\ideas::$statuses['NEW'],
				),
			),
			// In progress ideas sorted by score
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_SCORE,
					'status' => \phpbb\ideas\factory\ideas::$statuses['IN_PROGRESS'],
				),
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_SCORE,
					'status' => \phpbb\ideas\factory\ideas::$statuses['IN_PROGRESS'],
				),
			),
			// Duplicate ideas sorted by author
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_AUTHOR,
					'status' => \phpbb\ideas\factory\ideas::$statuses['DUPLICATE'],
				),
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_AUTHOR,
					'status' => \phpbb\ideas\factory\ideas::$statuses['DUPLICATE'],
				),
			),
			// Invalid ideas sorted by date
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_DATE,
					'status' => \phpbb\ideas\factory\ideas::$statuses['INVALID'],
				),
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_DATE,
					'status' => \phpbb\ideas\factory\ideas::$statuses['INVALID'],
				),
			),
			// Implemented ideas sorted by top
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_TOP,
					'status' => \phpbb\ideas\factory\ideas::$statuses['IMPLEMENTED'],
				),
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_TOP,
					'status' => \phpbb\ideas\factory\ideas::$statuses['IMPLEMENTED'],
				),
			),
			// All ideas sorted by date
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_NEW,
					'status' => -1,
				),
				array(
					'sort' => \phpbb\ideas\factory\ideas::SORT_DATE,
					'status' => \phpbb\ideas\factory\ideas::$statuses,
				),
			),
		);
	}

	/**
	 * Basic controller test
	 *
	 * @dataProvider controller_test_data
	 */
	public function test_controller($status_code, $page_content, $params, $expected)
	{
		$this->request->expects($this->any())
			->method('variable')
			->will($this->returnValueMap(array(
				array('sd', 'd', false, \phpbb\request\request_interface::REQUEST, ''),
				array('status', 0, false, \phpbb\request\request_interface::REQUEST, $params['status']),
				array('start', 0, false, \phpbb\request\request_interface::REQUEST, 0),
			)));

		$this->ideas->expects($this->any())
			->method('get_ideas')
			->with('', $expected['sort'], 'ASC', $expected['status'], 0);

		/** @var \phpbb\ideas\controller\list_controller $controller */
		$controller = $this->get_controller('list_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\list_controller', $controller);

		$response = $controller->ideas_list($params['sort']);
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

		/** @var \phpbb\ideas\controller\list_controller $controller */
		$controller = $this->get_controller('list_controller');
		$this->assertInstanceOf('phpbb\ideas\controller\list_controller', $controller);

		$controller->ideas_list('');
	}
}
