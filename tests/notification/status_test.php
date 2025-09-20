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

use phpbb\ideas\ext;
use phpbb\ideas\notification\type\status;

class status_test extends \phpbb_test_case
{
	/** @var status */
	protected $notification_type;

	/** @var \phpbb\config\config|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var \phpbb\controller\helper|\PHPUnit\Framework\MockObject\MockObject */
	protected $helper;

	/** @var \phpbb\user_loader|\PHPUnit\Framework\MockObject\MockObject */
	protected $user_loader;

	/** @var \phpbb\auth\auth|\PHPUnit\Framework\MockObject\MockObject */
	protected $auth;

	/** @var \phpbb\language\language|\PHPUnit\Framework\MockObject\MockObject */
	protected $language;

	/** @var \phpbb\notification\manager|\PHPUnit\Framework\MockObject\MockObject */
	protected $notification_manager;

	protected int $forum_id;

	protected function setUp(): void
	{
		parent::setUp();

		global $cache, $user, $phpbb_root_path, $phpEx;

		$this->config = $this->createMock(\phpbb\config\config::class);
		$this->helper = $this->createMock(\phpbb\controller\helper::class);
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
		$this->notification_type->set_additional_services($this->config, $this->helper, $this->user_loader);

		// Set protected properties using reflection
		$reflection = new \ReflectionClass($this->notification_type);
		$notification_manager_property = $reflection->getProperty('notification_manager');
		$notification_manager_property->setValue($this->notification_type, $this->notification_manager);
	}

	/**
	 * Helper method to set notification data using reflection
	 */
	protected function setNotificationData(array $data)
	{
		$reflection = new \ReflectionClass($this->notification_type);
		$method = $reflection->getMethod('set_data');

		foreach ($data as $key => $value)
		{
			$method->invoke($this->notification_type, $key, $value);
		}
	}

	public function test_get_type()
	{
		$this->assertEquals(ext::NOTIFICATION_TYPE_STATUS, $this->notification_type->get_type());
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
		$type_data = ['idea_id' => 123];
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

		$type_data = ['idea_id' => $idea_id, 'idea_author' => $idea_author];
		$default_methods = ['board', 'email'];

		$this->auth->expects($this->once())
			->method('acl_get_list')
			->with([$idea_author], 'f_read', $this->forum_id)
			->willReturn([$this->forum_id => ['f_read' => [$idea_author]]]);

		$this->notification_manager->expects($this->once())
			->method('get_default_methods')
			->willReturn($default_methods);

		$result = $this->notification_type->find_users_for_notification($type_data);
		$this->assertEquals([$idea_author => $default_methods], $result);
	}

	public function test_get_avatar_with_author()
	{
		$this->setNotificationData(['updater_id' => 5]);

		$this->user_loader->expects($this->once())
			->method('get_avatar')
			->with(5, false, true)
			->willReturn('<img src="avatar.png">');

		$this->assertEquals('<img src="avatar.png">', $this->notification_type->get_avatar());
	}

	public function test_get_avatar_without_author()
	{
		$this->setNotificationData(['updater_id' => 0]);
		$this->assertEquals('', $this->notification_type->get_avatar());
	}

	public function test_users_to_query()
	{
		$this->setNotificationData(['updater_id' => 0]);
		$this->assertEquals([0], $this->notification_type->users_to_query());
	}

	public function test_get_title()
	{
		$this->setNotificationData([
			'updater_id' => 123
		]);

		$this->language->expects($this->once())
			->method('is_set')
			->with('IDEA_STATUS_CHANGE')
			->willReturn(true);

		$this->language->expects($this->once())
			->method('lang')
			->with('IDEA_STATUS_CHANGE', 'TestUser')
			->willReturn('Idea status changed by TestUser');

		$this->user_loader->expects($this->once())
			->method('get_username')
			->with(123, 'no_profile')
			->willReturn('TestUser');

		$result = $this->notification_type->get_title();
		$this->assertEquals('Idea status changed by TestUser', $result);
	}

	public function test_get_title_loads_language()
	{
		$this->setNotificationData([
			'updater_id' => 456,
		]);

		$this->language->expects($this->once())
			->method('is_set')
			->with('IDEA_STATUS_CHANGE')
			->willReturn(false);

		$this->language->expects($this->once())
			->method('add_lang')
			->with('common', 'phpbb/ideas');

		$this->language->expects($this->once())
			->method('lang')
			->with('IDEA_STATUS_CHANGE', 'AdminUser')
			->willReturn('Idea status changed by AdminUser');

		$this->user_loader->expects($this->once())
			->method('get_username')
			->with(456, 'no_profile')
			->willReturn('AdminUser');

		$result = $this->notification_type->get_title();
		$this->assertEquals('Idea status changed by AdminUser', $result);
	}

	public function test_get_reference()
	{
		$this->setNotificationData(['idea_title' => 'Test Idea']);

		$this->language->expects($this->once())
			->method('lang')
			->with('NOTIFICATION_REFERENCE', 'Test Idea')
			->willReturn('“Test Idea”');

		$this->assertEquals('“Test Idea”', $this->notification_type->get_reference());
	}

	public function test_get_reason()
	{
		$this->setNotificationData([
			'status' => ext::$statuses['IN_PROGRESS'],
		]);

		$this->language->expects($this->exactly(2))
			->method('lang')
			->willReturnCallback(function($key, ...$args) {
				if ($key === 'IN_PROGRESS')
				{
					return 'In Progress';
				}
				if ($key === 'NOTIFICATION_STATUS' && $args[0] === 'In Progress')
				{
					return 'Status: In Progress';
				}
				return '';
			});

		$this->assertEquals('Status: In Progress', $this->notification_type->get_reason());
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
			'idea_id' => 10,
			'updater_id' => 123,
		]);

		$this->helper->expects($this->once())
			->method('route')
			->with('phpbb_ideas_idea_controller', ['idea_id' => 10])
			->willReturn('/ideas/10');

		$this->language->expects($this->once())
			->method('lang')
			->with('IMPLEMENTED')
			->willReturn('Implemented');

		$this->user_loader->expects($this->once())
			->method('get_username')
			->with(123, 'username')
			->willReturn('TestUser');

		$result = $this->notification_type->get_email_template_variables();

		$expected = [
			'IDEA_TITLE' => 'Test & Idea',
			'STATUS' => 'Implemented',
			'UPDATED_BY' => 'TestUser',
			'U_VIEW_IDEA' => '/ideas/10',
		];

		$this->assertEquals($expected, $result);
	}

	public function test_create_insert_array()
	{
		$type_data = [
			'idea_id' => 7,
			'status' => 4,
			'user_id' => 3,
			'idea_title' => 'Sample Idea'
		];

		$this->notification_type->create_insert_array($type_data);

		// Verify data was set by checking get_data
		$reflection = new \ReflectionClass($this->notification_type);
		$get_data_method = $reflection->getMethod('get_data');

		$this->assertEquals(7, $get_data_method->invoke($this->notification_type, 'idea_id'));
		$this->assertEquals(4, $get_data_method->invoke($this->notification_type, 'status'));
		$this->assertEquals(3, $get_data_method->invoke($this->notification_type, 'updater_id'));
		$this->assertEquals('Sample Idea', $get_data_method->invoke($this->notification_type, 'idea_title'));
	}
}
