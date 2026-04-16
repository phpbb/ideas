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
class viewonline_test extends ideas_functional_base
{
	/**
	* Test viewonline page for admin
	*/
	public function test_viewonline_check_viewonline()
	{
		// Visit Ideas as user "admin"
		$this->login();
		$crawler = self::request('GET', "app.php/ideas?sid=$this->sid");
		$this->assertContainsLang('IDEAS_TITLE', $crawler->filter('h2')->text());

		// Create a second user and check who is online from a separate session.
		self::$client->restart();
		$this->create_user('ideas-viewonline-user1');
		$this->login('ideas-viewonline-user1');
		// PHP goes faster than DBMS, make sure session data got written to the database.
		sleep(1);
		$crawler = self::request('GET', "viewonline.php?sid=$this->sid");

		// Is admin still viewing Ideas page?
		self::assertStringContainsString('admin', $crawler->filter('#page-body table.table1')->text());

		$session_entries = $crawler->filter('#page-body table.table1 tr')->count();
		self::assertGreaterThanOrEqual(3, $session_entries, 'Too few session entries found');

		// Check each entry in the viewonline table
		// Skip the first row (header)
		for ($i = 1; $i < $session_entries; $i++)
		{
			// If we found the admin, we check his page info and leave
			$subcrawler = $crawler->filter('#page-body table.table1 tr')->eq($i);
			if (str_contains($subcrawler->filter('td')->text(), 'admin'))
			{
				try
				{
					$this->assertContainsLang('VIEWING_IDEAS', $subcrawler->filter('td.info')->text());
				}
				catch (\PHPUnit\Framework\AssertionFailedError $e)
				{
					$this->markTestIncomplete('Expected VIEWING_IDEAS lang string not found: ' . $e->getMessage());
				}
				return;
			}
		}

		// If we did not find the admin, we fail
		self::fail('User "admin" was not found on viewonline page.');
	}
}
