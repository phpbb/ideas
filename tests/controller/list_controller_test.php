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
					'sort' => \phpbb\ideas\ext::SORT_TOP,
					'status' => 0,
				),
				array(
					'sort' => \phpbb\ideas\ext::SORT_TOP,
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
					'sort' => \phpbb\ideas\ext::SORT_DATE,
					'status' => \phpbb\ideas\ext::$statuses['IMPLEMENTED'],
				),
				array(
					'sort' => \phpbb\ideas\ext::SORT_DATE,
					'status' => \phpbb\ideas\ext::$statuses['IMPLEMENTED'],
				),
			),

			// OPTIONAL DISPLAY AND SORT LISTS
			// New ideas sorted by date
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\ext::SORT_NEW,
					'status' => \phpbb\ideas\ext::$statuses['NEW'],
				),
				array(
					'sort' => \phpbb\ideas\ext::SORT_DATE,
					'status' => \phpbb\ideas\ext::$statuses['NEW'],
				),
			),
			// In progress ideas sorted by score
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\ext::SORT_SCORE,
					'status' => \phpbb\ideas\ext::$statuses['IN_PROGRESS'],
				),
				array(
					'sort' => \phpbb\ideas\ext::SORT_SCORE,
					'status' => \phpbb\ideas\ext::$statuses['IN_PROGRESS'],
				),
			),
			// Duplicate ideas sorted by author
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\ext::SORT_AUTHOR,
					'status' => \phpbb\ideas\ext::$statuses['DUPLICATE'],
				),
				array(
					'sort' => \phpbb\ideas\ext::SORT_AUTHOR,
					'status' => \phpbb\ideas\ext::$statuses['DUPLICATE'],
				),
			),
			// Invalid ideas sorted by date
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\ext::SORT_DATE,
					'status' => \phpbb\ideas\ext::$statuses['INVALID'],
				),
				array(
					'sort' => \phpbb\ideas\ext::SORT_DATE,
					'status' => \phpbb\ideas\ext::$statuses['INVALID'],
				),
			),
			// Implemented ideas sorted by top
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\ext::SORT_TOP,
					'status' => \phpbb\ideas\ext::$statuses['IMPLEMENTED'],
				),
				array(
					'sort' => \phpbb\ideas\ext::SORT_TOP,
					'status' => \phpbb\ideas\ext::$statuses['IMPLEMENTED'],
				),
			),
			// All ideas sorted by date
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\ext::SORT_NEW,
					'status' => -1,
				),
				array(
					'sort' => \phpbb\ideas\ext::SORT_DATE,
					'status' => \phpbb\ideas\ext::$statuses,
				),
			),
			// My ideas list
			array(
				200,
				'list_body.html',
				array(
					'sort' => \phpbb\ideas\ext::SORT_MYIDEAS,
					'status' => -1,
				),
				array(
					'sort' => \phpbb\ideas\ext::SORT_MYIDEAS,
					'status' => \phpbb\ideas\ext::$statuses,
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
		/** @var \phpbb\ideas\controller\list_controller $controller */
		$controller = $this->get_controller('list_controller', 'ideas');
		self::assertInstanceOf('phpbb\ideas\controller\list_controller', $controller);

		$this->request->expects(self::atMost(3))
			->method('variable')
			->willReturnMap(array(
				array('sd', 'd', false, \phpbb\request\request_interface::REQUEST, 'd'),
				array('status', 0, false, \phpbb\request\request_interface::REQUEST, $params['status']),
				array('start', 0, false, \phpbb\request\request_interface::REQUEST, 0),
			));

		$this->entity->expects(self::once())
			->method('get_ideas')
			->with('', $expected['sort'], 'DESC', $expected['status'], 0)
			->willReturn([[]]);

		$response = $controller->ideas_list($params['sort']);
		self::assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
		self::assertEquals($status_code, $response->getStatusCode());
		self::assertEquals($page_content, $response->getContent());
	}

	/**
	 * Test data for the test_controller_exception test
	 *
	 * @return array Array of test data
	 */
	public function controller_exception_test_data()
	{
		return array(
			array(0),
			array(''),
		);
	}

	/**
	 * Basic controller exception test
	 *
	 * @dataProvider controller_exception_test_data
	 */
	public function test_controller_exception($forum)
	{
		$this->expectException(\phpbb\exception\http_exception::class);
		$this->config['ideas_forum_id'] = $forum;

		/** @var \phpbb\ideas\controller\list_controller $controller */
		$controller = $this->get_controller('list_controller', 'ideas');
		self::assertInstanceOf('phpbb\ideas\controller\list_controller', $controller);

		$controller->ideas_list('');
	}
}
