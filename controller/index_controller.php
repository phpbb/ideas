<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\controller;

use phpbb\exception\http_exception;
use phpbb\ideas\factory\ideas;

class index_controller extends base
{
	const NUM_IDEAS = 5;

	/**
	 * Controller for /ideas
	 *
	 * @throws http_exception
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function index()
	{
		if (!$this->is_available())
		{
			throw new http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}

		// Generate latest ideas
		$ideas = $this->ideas->get_ideas(self::NUM_IDEAS, ideas::SORT_DATE, 'DESC');
		$this->assign_template_block_vars('latest_ideas', $ideas);

		// Generate top ideas
		$ideas = $this->ideas->get_ideas(self::NUM_IDEAS, ideas::SORT_TOP, 'DESC');
		$this->assign_template_block_vars('top_ideas', $ideas);

		// Generate recently implemented
		$ideas = $this->ideas->get_ideas(self::NUM_IDEAS, ideas::SORT_DATE, 'DESC', ideas::$statuses['IMPLEMENTED']);
		$this->assign_template_block_vars('implemented_ideas', $ideas);

		$this->template->assign_vars(array(
			'U_VIEW_TOP'		=> $this->link_helper->get_list_link(ideas::SORT_TOP),
			'U_VIEW_LATEST'		=> $this->link_helper->get_list_link(ideas::SORT_NEW),
			'U_VIEW_IMPLEMENTED'=> $this->link_helper->get_list_link(ideas::SORT_IMPLEMENTED),
			'U_POST_ACTION'		=> $this->helper->route('phpbb_ideas_post_controller'),
		));

		// Assign breadcrumb template vars
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'		=> $this->helper->route('phpbb_ideas_index_controller'),
			'FORUM_NAME'		=> $this->language->lang('IDEAS'),
		));

		// Display the search ideas field
		$this->display_search_ideas();

		return $this->helper->render('index_body.html', $this->language->lang('IDEAS_TITLE'));
	}
}
