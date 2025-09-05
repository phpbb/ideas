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
class ideas_test extends ideas_functional_base
{
	/**
	 * Visit Ideas controller
	 */
	public function test_view_ideas()
	{
		// Access /ideas directly
		$crawler = self::request('GET', "app.php/ideas");
		$this->assertContainsLang('IDEAS_TITLE', $crawler->filter('h2')->text());

		// Test forum redirect to /ideas from /viewforum.php
		$crawler = self::request('GET', "viewforum.php?f=2");
		$this->assertContainsLang('IDEAS_TITLE', $crawler->filter('h2')->text());
	}

	/**
	 * Create and visit a new idea
	 */
	public function test_new_idea()
	{
		$this->login();

		// Create a new Idea
		$idea = $this->create_idea('Test Idea #1', 'This is an idea posted by the test framework.');

		// Visit the new idea page and verify data
		$crawler = self::request('GET', "app.php/idea/{$idea['idea_id']}?sid=$this->sid");
		$this->assertContainsLang('IDEAS', $crawler->filter('#nav-breadcrumbs')->text());
		self::assertStringContainsString($idea['subject'], $crawler->filter('h2')->text());
		self::assertStringContainsString($idea['message'], $crawler->filter('.content')->text());
		self::assertStringContainsString('1', $crawler->filter('.rating > .vote-up')->text());
		self::assertStringContainsString('0', $crawler->filter('.rating > .vote-down')->text());
	}

	/**
	 * Visit Ideas List controller
	 */
	public function test_view_ideas_lists()
	{
		// Test new ideas list
		$crawler = self::request('GET', "app.php/ideas/list?sid=$this->sid");
		$this->assertContainsLang('OPEN_IDEAS', $crawler->filter('h2')->text());
		$this->assertNotContainsLang('NO_IDEAS_DISPLAY', $crawler->filter('.topiclist.forums')->text());

		// Test top ideas list
		$crawler = self::request('GET', "app.php/ideas/list/top?sid=$this->sid");
		$this->assertContainsLang('LIST_TOP', $crawler->filter('h2')->text());
		$this->assertNotContainsLang('NO_IDEAS_DISPLAY', $crawler->filter('.topiclist.forums')->text());

		// Test all ideas list
		$crawler = self::request('GET', "app.php/ideas/list/date?status=-1&sid=$this->sid");
		$this->assertContainsLang('ALL_IDEAS', $crawler->filter('h2')->text());
		$this->assertNotContainsLang('NO_IDEAS_DISPLAY', $crawler->filter('.topiclist.forums')->text());

		// Test implemented ideas list (should be empty list)
		$crawler = self::request('GET', "app.php/ideas/list/date?status=3&sid=$this->sid");
		$this->assertContainsLang('LIST_IMPLEMENTED', $crawler->filter('h2')->text());
		$this->assertContainsLang('NO_IDEAS_DISPLAY', $crawler->filter('.topiclist.forums')->text());

		// Test my ideas list is empty when logged out
		$crawler = self::request('GET', "app.php/ideas/list/egosearch?status=-1&sid=$this->sid");
		$this->assertNotContainsLang('LIST_EGOSEARCH', $crawler->filter('#nav-main')->text());
		$this->assertContainsLang('LIST_EGOSEARCH', $crawler->filter('h2')->text());
		$this->assertContainsLang('NO_IDEAS_DISPLAY', $crawler->filter('.topiclist.forums')->text());

		// Test my ideas list works when logged in
		$this->login();
		$crawler = self::request('GET', "app.php/ideas/list/egosearch?status=-1&sid=$this->sid");
		$this->assertContainsLang('LIST_EGOSEARCH', $crawler->filter('#nav-main')->text());
		$this->assertContainsLang('LIST_EGOSEARCH', $crawler->filter('h2')->text());
		$this->assertNotContainsLang('NO_IDEAS_DISPLAY', $crawler->filter('.topiclist.forums')->text());
	}

	/**
	 * Test for notification options
	 */
	public function test_notification_options()
	{
		$this->login();

		$crawler = self::request('GET', "/ucp.php?i=ucp_notifications&mode=notification_options");
		$this->assertContainsLang('NOTIFICATION_TYPE_IDEAS', $crawler->filter('#cp-main')->text());
	}

	/**
	 * Test ideas displays expected error messages
	 */
	public function test_idea_errors()
	{
		// Visit an idea that does not exist
		$this->error_check("app.php/idea/0?sid=$this->sid", 'IDEA_NOT_FOUND');

		// Try to post new idea when not logged in
		$this->error_check("app.php/ideas/post?sid=$this->sid", 'LOGGED_OUT');

		// Verify ideas controllers are no longer accessible when Ideas is unavailable
		$this->disable_ideas();
		$this->error_check("app.php/ideas?sid=$this->sid", 'IDEAS_NOT_AVAILABLE');
		$this->error_check("app.php/idea/1?sid=$this->sid", 'IDEAS_NOT_AVAILABLE');
		$this->error_check("app.php/ideas/list?sid=$this->sid", 'IDEAS_NOT_AVAILABLE');
		$this->error_check("app.php/ideas/post?sid=$this->sid", 'IDEAS_NOT_AVAILABLE');

		// While ideas is disabled, let's check that notifications are no longer available too
		$this->login();
		$crawler = self::request('GET', "/ucp.php?i=ucp_notifications&mode=notification_options");
		$this->assertNotContainsLang('NOTIFICATION_TYPE_IDEAS', $crawler->filter('#cp-main')->text());
	}

	/**
	 * Helper method to check an html page that
	 * should return an html error code and page.
	 *
	 * @param string $url      The URL to get
	 * @param string $expected The expected lang string
	 * @param int    $code     The html error code
	 */
	public function error_check($url, $expected, $code = 404)
	{
		$crawler = self::request('GET', $url, array(), false);
		self::assert_response_html($code);
		$this->assertContainsLang($expected, $crawler->filter('#page-body')->text());
	}

	/**
	 * Create a new idea
	 * Make sure to be logged in before calling
	 *
	 * @param string $subject The title of the new idea
	 * @param string $message The message of the new idea
	 * @return array Array containing the idea id, title and message
	 */
	public function create_idea($subject, $message)
	{
		// Visit Ideas post controller
		$crawler = self::request('GET', "app.php/ideas/post?sid=$this->sid");

		// Set the form field data
		$form = $crawler->selectButton($this->lang('SUBMIT'))->form();
		$data = array(
			'subject'	=> $subject,
			'message'	=> $message,
		);
		$form->setValues($data);

		// Submit and verify success
		$crawler = self::submit($form);
		self::assertStringContainsString($data['subject'], $crawler->filter('h2')->text());

		// Get the new idea's ID and add it to the data array
		$url = $crawler->selectLink($this->lang('REMOVE_VOTE'))->link()->getUri();
		preg_match('#/idea/(\d+)\?#', $url, $matches);
		$data['idea_id'] = $matches ? $matches[1] : null;

		return $data;
	}
}
