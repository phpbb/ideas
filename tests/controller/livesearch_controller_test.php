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

class livesearch_controller_test extends controller_base
{
	/**
	 * Test data for the test_controller test
	 *
	 * @return array Array of test data
	 */
	public function controller_test_data()
	{
		return [
			[200, ['keyword' => 'foo', 'results' => '$matches']],
			[200, ['keyword' => 'bar', 'results' => '']],
		];
	}

	/**
	 * Live search controller test
	 *
	 * @dataProvider controller_test_data
	 */
	public function test_controller($status_code, $content)
	{
		/** @var \phpbb\ideas\controller\livesearch_controller $controller */
		$controller = $this->get_controller('livesearch_controller', 'livesearch');
		$this->assertInstanceOf('phpbb\ideas\controller\livesearch_controller', $controller);

		$this->request->expects($this->once())
			->method('variable')
			->with('duplicateeditinput', '', true)
			->willReturn($content['keyword']);

		$this->entity->expects($this->once())
			->method('title_search')
			->with($content['keyword'], 10)
			->willReturn($content['results']);

		$response = $controller->title_search();
		$this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $response);
		$this->assertEquals($status_code, $response->getStatusCode());
		$this->assertEquals(json_encode($content), $response->getContent());
	}
}
