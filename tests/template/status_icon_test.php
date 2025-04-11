<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\template;

require_once __DIR__ . '/../../../../../../tests/template/template_test_case.php';

class status_icon_test extends \phpbb_template_template_test_case
{
	protected $test_path = __DIR__;

	protected function setup_engine(array $new_config = array())
	{
		global $phpbb_root_path, $phpEx;

		$defaults = $this->config_defaults();
		$config = new \phpbb\config\config(array_merge($defaults, $new_config));
		$lang_loader = new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx);
		$this->lang = $lang = new \phpbb\language\language($lang_loader);
		$user = new \phpbb\user($lang, '\phpbb\datetime');
		$this->user = $user;

		$filesystem = new \phpbb\filesystem\filesystem();

		$path_helper = new \phpbb\path_helper(
			new \phpbb\symfony_request(
				new \phpbb_mock_request()
			),
			$this->createMock('\phpbb\request\request'),
			$phpbb_root_path,
			$phpEx
		);

		$this->template_path = $this->test_path . '/templates';

		$cache_path = $phpbb_root_path . 'cache/twig';
		$context = new \phpbb\template\context();
		$loader = new \phpbb\template\twig\loader();
		$assets_bag = new \phpbb\template\assets_bag();
		$twig = new \phpbb\template\twig\environment(
			$assets_bag,
			$config,
			$filesystem,
			$path_helper,
			$cache_path,
			null,
			$loader,
			new \phpbb\event\dispatcher(),
			[
				'cache'			=> false,
				'debug'			=> false,
				'auto_reload'	=> true,
				'autoescape'	=> false,
			]
		);
		$this->template = new \phpbb\template\twig\twig(
			$path_helper,
			$config,
			$context,
			$twig,
			$cache_path,
			$this->user,
			[
				new \phpbb\template\twig\extension($context, $twig, $this->lang),
				new \phpbb\ideas\template\twig\extension\ideas_status_icon(),
			]
		);
		$twig->setLexer(new \phpbb\template\twig\lexer($twig));
		$this->template->set_custom_style('tests', $this->template_path);
	}

	public function data_template_status_icons()
	{
		return [
			[0, ''],
			[1, 'lightbulb'],
			[2, 'code-fork'],
			[3, 'check'],
			[4, 'copy'],
			[5, 'ban'],
		];
	}

	/**
	 * @dataProvider data_template_status_icons
	 */
	public function test_ideas_status_icon($var, $expected)
	{
		$this->run_template('status_icon.html', ['id' => $var], [], [], $expected, []);
	}
}
