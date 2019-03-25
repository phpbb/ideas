<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\functional;

/**
* @group functional
*/
class ideas_functional_base extends \phpbb_functional_test_case
{
	/**
	* Define the extensions to be tested
	*
	* @return array vendor/name of extension(s) to test
	*/
	static protected function setup_extensions()
	{
		return array('phpbb/ideas');
	}

	public function setUp()
	{
		parent::setUp();
		$this->enable_ideas();
		$this->add_lang_ext('phpbb/ideas', array(
			'common',
			'info_acp_ideas',
			'phpbb_ideas_acp',
		));
	}

	/**
	 * Set up Ideas settings.
	 */
	public function enable_ideas()
	{
		$this->get_db();

		$sql = "UPDATE phpbb_config
			SET config_value = '2'
			WHERE config_name = 'ideas_forum_id'";

		$this->db->sql_query($sql);

		$this->purge_cache();
	}

	/**
	 * Disable Ideas settings.
	 */
	public function disable_ideas()
	{
		$this->get_db();

		$sql = "UPDATE phpbb_config
			SET config_value = ''
			WHERE config_name = 'ideas_forum_id'";

		$this->db->sql_query($sql);

		$this->purge_cache();
	}
}
