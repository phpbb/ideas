<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\ui;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;

/**
 * @group ui
 */
class ideas_test extends \phpbb_ui_test_case
{
	/**
	 * {@inheritdoc}
	 */
	public static function setUpBeforeClass()
	{
		// Set these properties to true so we can use the same board created for functional tests
		// instead of having to create and set up a whole new board.
		self::$already_installed = true;
		self::$install_success = true;
		parent::setUpBeforeClass();
	}

	/**
	 * Test JavaScript user interactions
	 *
	 * @throws \Facebook\WebDriver\Exception\NoSuchElementException
	 * @throws \Facebook\WebDriver\Exception\TimeOutException
	 * @throws \Facebook\WebDriver\Exception\WebDriverCurlException
	 */
	public function test_js_actions()
	{
		$this->login();
		$this->admin_login();

		$this->visit('app.php/idea/1');

		// test showing the list of voters
		$votes = $this->find_element('className', 'voteslist');
		$this->assertEquals('none', $votes->getCSSValue('display'));
		$this->find_element('className', 'votes')->click();
		$this->assertEquals('block', $votes->getCSSValue('display'));

		// test voting
		$votedown = $this->find_element('className', 'vote-down');
		$this->assertEquals('0', $votedown->getText());
		$votedown->click();
		$this->waitForAjax();
		$this->assertEquals('1', $this->find_element('className', 'vote-down')->getText());

		// test changing the status
		$this->assertEquals('New', $this->find_element('className', 'status-badge')->getText());
		$dropdown_container = $this->find_element('className', 'status-dropdown-container');
		$dropdown = $dropdown_container->findElement(WebDriverBy::className('dropdown'));
		$this->assertEmpty($dropdown->getText());
		$dropdown_container->findElement(WebDriverBy::className('dropdown-toggle'))->click();
		$this->assertNotNull($dropdown->getText());
		$statuses = $dropdown->findElements(WebDriverBy::className('change-status'));
		foreach ($statuses as $status)
		{
			if ($status->getText() === 'In Progress')
			{
				$status->click();
				break;
			}
		}
		$this->waitForAjax();
		$this->assertEquals('In Progress', $this->find_element('className', 'status-badge')->getText());

		// test showing the edit ticket input and entering text into it
		$test = 'PHPBB3-123';
		$input = $this->find_element('cssSelector', '#ticketeditinput');
		$this->assertFalse($input->isDisplayed());
		$this->find_element('cssSelector', '#ticketedit')->click();
		$this->assertTrue($input->isDisplayed());
		$input->sendKeys([$test, WebDriverKeys::ENTER]);
		$this->waitForAjax();
		$this->assertEquals($test, $this->find_element('cssSelector', '#ticketlink')->getText());
	}
}
