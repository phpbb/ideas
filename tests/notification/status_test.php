<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\notification\type;

use phpbb\ideas\notification\type\status;

class status_test extends \phpbb_test_case
{
	/** @var status */
	protected $notification_type;

	/** @var \phpbb\config\config|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var \phpbb\controller\helper|\PHPUnit\Framework\MockObject\MockObject */
	protected $helper;

	/** @var \phpbb\ideas\factory\idea|\PHPUnit\Framework\MockObject\MockObject */
	protected $idea_factory;

	/** @var \phpbb\user_loader|\PHPUnit\Framework\MockObject\MockObject */
	protected $user_loader;

	/** @var \phpbb\auth\auth|\PHPUnit\Framework\MockObject\MockObject */
	protected $auth;

	/** @var \phpbb\language\language|\PHPUnit\Framework\MockObject\MockObject */
	protected $language;

	/** @var \phpbb\notification\manager|\PHPUnit\Framework\MockObject\MockObject */
	protected $notification_manager;

	protected function setUp(): void
	{
		parent::setUp();

		global $cache, $user, $phpbb_root_path, $phpEx;

		$this->config = $this->createMock(\phpbb\config\config::class);
		$this->helper = $this->createMock(\phpbb\controller\helper::class);
		$this->idea_factory = $this->createMock(\phpbb\ideas\factory\idea::class);
		$this->user_loader = $this->createMock(\phpbb\user_loader::class);
		$this->auth = $this->createMock(\phpbb\auth\auth::class);
		$this->language = $this->createMock(\phpbb\language\language::class);
		$this->notification_manager = $this->createMock(\phpbb\notification\manager::class);
		$db = $this->createMock('\phpbb\db\driver\driver_interface');
		$user = new \phpbb\user($this->language, '\phpbb\datetime');
		$user->data['user_options'] = 230271;
		$cache = new \phpbb_mock_cache();

		$this->forum_id = 5;
		$this->config->expects($this->once())
			->method('offsetGet')
			->with('ideas_forum_id')
			->willReturn($this->forum_id);

		$this->notification_type = new status($db, $this->language, $user, $this->auth, $phpbb_root_path, $phpEx, 'phpbb_user_notifications');
		$this->notification_type->set_additional_services($this->config, $this->helper, $this->idea_factory, $this->user_loader);

		// Set protected properties using reflection
		$reflection = new \ReflectionClass($this->notification_type);
		$notification_manager_property = $reflection->getProperty('notification_manager');
		$notification_manager_property->setAccessible(true);
		$notification_manager_property->setValue($this->notification_type, $this->notification_manager);
	}

	/**
	 * Helper method to set notification data using reflection
	 */
	protected function setNotificationData(array $data)
	{
		$reflection = new \ReflectionClass($this->notification_type);
		$method = $reflection->getMethod('set_data');
		$method->setAccessible(true);

		foreach ($data as $key => $value)
		{
			$method->invoke($this->notification_type, $key, $value);
		}
	}

	public function test_get_type()
	{
		$this->assertEquals('phpbb.ideas.notification.type.status', $this->notification_type->get_type());
	}

	public function test_is_available_with_permission()
	{
		$this->auth->expects($this->once())
			->method('acl_get')
			->with('f_read', $this->forum_id)
			->willReturn(true);

		$this->assertTrue($this->notification_type->is_available());
	}

	public function test_is_available_without_permission()
	{
		$this->auth->expects($this->once())
			->method('acl_get')
			->with('f_read', $this->forum_id)
			->willReturn(false);

		$this->assertFalse($this->notification_type->is_available());
	}

	public function test_get_item_id()
	{
		$type_data = ['item_id' => 123];
		$this->assertEquals(123, status::get_item_id($type_data));
	}

	public function test_get_item_parent_id()
	{
		$type_data = ['parent_id' => 456];
		$this->assertEquals(0, status::get_item_parent_id($type_data));
	}

	public function test_find_users_for_notification()
	{
		$idea_id = 1;
		$idea_author = 2;

		$type_data = ['idea_id' => $idea_id];
		$idea_data = ['idea_author' => $idea_author];
		$default_methods = ['board', 'email'];

		$this->auth->expects($this->once())
			->method('acl_get_list')
			->with([$idea_author], 'f_read', $this->forum_id)
			->willReturn([$this->forum_id => ['f_read' => [$idea_author]]]);

		$this->idea_factory->expects($this->once())
			->method('get_idea')
			->with($idea_id)
			->willReturn($idea_data);

		$this->notification_manager->expects($this->once())
			->method('get_default_methods')
			->willReturn($default_methods);

		$result = $this->notification_type->find_users_for_notification($type_data);
		$this->assertEquals([$idea_author => $default_methods], $result);
	}

	public function test_find_users_for_notification_idea_not_found()
	{
		$type_data = ['idea_id' => 999];

		$this->idea_factory->expects($this->once())
			->method('get_idea')
			->with(999)
			->willReturn(false);

		$result = $this->notification_type->find_users_for_notification($type_data);
		$this->assertEquals([], $result);
	}

	public function test_get_avatar_with_author()
	{
		$this->setNotificationData(['idea_author' => 5]);

		$this->user_loader->expects($this->once())
			->method('get_avatar')
			->with(5, false, true)
			->willReturn('<img src="avatar.png">');

		$this->assertEquals('<img src="avatar.png">', $this->notification_type->get_avatar());
	}

	public function test_get_avatar_without_author()
	{
		$this->setNotificationData(['idea_author' => 0]);
		$this->assertEquals('', $this->notification_type->get_avatar());
	}

	public function test_users_to_query()
	{
		$this->setNotificationData(['idea_author' => 0]);
		$this->assertEquals([0], $this->notification_type->users_to_query());
	}

	public function test_get_title()
	{
		$this->setNotificationData(['idea_title' => 'Test Idea']);

		$this->language->expects($this->once())
			->method('is_set')
			->with('IDEA_STATUS_CHANGE')
			->willReturn(true);

		$this->language->expects($this->once())
			->method('lang')
			->with('IDEA_STATUS_CHANGE', 'Test Idea')
			->willReturn('Status changed for: Test Idea');

		$this->assertEquals('Status changed for: Test Idea', $this->notification_type->get_title());
	}

	public function test_get_title_loads_language()
	{
		$this->setNotificationData(['idea_title' => 'Test Idea']);

		$this->language->expects($this->once())
			->method('is_set')
			->with('IDEA_STATUS_CHANGE')
			->willReturn(false);

		$this->language->expects($this->once())
			->method('add_lang')
			->with('common', 'phpbb/ideas');

		$this->language->expects($this->once())
			->method('lang')
			->with('IDEA_STATUS_CHANGE', 'Test Idea')
			->willReturn('Status changed for: Test Idea');

		$this->assertEquals('Status changed for: Test Idea', $this->notification_type->get_title());
	}

	public function test_get_reference()
	{
		$this->setNotificationData(['status' => 2]);

		$this->language->expects($this->once())
			->method('lang')
			->with('IN_PROGRESS')
			->willReturn('In Progress');

		$this->assertEquals('In Progress', $this->notification_type->get_reference());
	}

	public function test_get_url()
	{
		$this->setNotificationData(['idea_id' => 42]);

		$this->helper->expects($this->once())
			->method('route')
			->with('phpbb_ideas_idea_controller', ['idea_id' => 42])
			->willReturn('/ideas/42');

		$this->assertEquals('/ideas/42', $this->notification_type->get_url());
	}

	public function test_get_email_template()
	{
		$this->assertEquals('@phpbb_ideas/status_notification', $this->notification_type->get_email_template());
	}

	public function test_get_email_template_variables()
	{
		$this->setNotificationData([
			'idea_title' => 'Test & Idea',
			'status' => 3,
			'idea_id' => 10
		]);

		$this->helper->expects($this->once())
			->method('route')
			->with('phpbb_ideas_idea_controller', ['idea_id' => 10])
			->willReturn('/ideas/10');

		$this->language->expects($this->once())
			->method('lang')
			->with('IMPLEMENTED')
			->willReturn('Implemented');

		$result = $this->notification_type->get_email_template_variables();

		$expected = [
			'IDEA_TITLE' => 'Test & Idea',
			'STATUS' => 'Implemented',
			'U_VIEW_IDEA' => '/ideas/10',
		];

		$this->assertEquals($expected, $result);
	}

	public function test_pre_create_insert_array()
	{
		$type_data = ['idea_id' => 5];
		$notify_users = [];
		$idea_data = [
			'idea_title' => 'Sample Idea',
			'idea_author' => 3
		];

		$this->idea_factory->expects($this->once())
			->method('get_idea')
			->with(5)
			->willReturn($idea_data);

		$result = $this->notification_type->pre_create_insert_array($type_data, $notify_users);

		$expected = [
			'idea_title' => 'Sample Idea',
			'idea_author' => 3
		];

		$this->assertEquals($expected, $result);
	}

	public function test_pre_create_insert_array_idea_not_found()
	{
		$type_data = ['idea_id' => 999];
		$notify_users = [];

		$this->idea_factory->expects($this->once())
			->method('get_idea')
			->with(999)
			->willReturn(false);

		$result = $this->notification_type->pre_create_insert_array($type_data, $notify_users);
		$this->assertEquals([], $result);
	}

	public function test_create_insert_array()
	{
		$type_data = [
			'item_id' => 1,
			'idea_id' => 7,
			'status' => 4
		];
		$pre_create_data = [
			'idea_title' => 'Another Idea',
			'idea_author' => 8
		];

		// Use reflection to access set_data calls
		$reflection = new \ReflectionClass($this->notification_type);
		$set_data_method = $reflection->getMethod('set_data');
		$set_data_method->setAccessible(true);

		$this->notification_type->create_insert_array($type_data, $pre_create_data);

		// Verify data was set by checking get_data
		$get_data_method = $reflection->getMethod('get_data');
		$get_data_method->setAccessible(true);

		$this->assertEquals(7, $get_data_method->invoke($this->notification_type, 'idea_id'));
		$this->assertEquals(4, $get_data_method->invoke($this->notification_type, 'status'));
		$this->assertEquals('Another Idea', $get_data_method->invoke($this->notification_type, 'idea_title'));
		$this->assertEquals(8, $get_data_method->invoke($this->notification_type, 'idea_author'));
	}
}
