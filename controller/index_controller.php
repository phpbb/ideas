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

use \phpbb\exception\http_exception;

class index_controller extends base
{
	const IDEAS = 5;

	/**
	 * Controller for /ideas
	 *
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 * @throws http_exception
	 */
	public function index()
	{
		if (!$this->is_available())
		{
			throw new http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}

		// Generate latest ideas
		$ideas = $this->ideas->get_ideas(self::IDEAS, 'date', 'DESC');
		$this->assign_template_block_vars('latest_ideas', $ideas);

		// Generate top ideas
		$ideas = $this->ideas->get_ideas(self::IDEAS, 'top', 'DESC');
		$this->assign_template_block_vars('top_ideas', $ideas);

		// Generate recently implemented
		$ideas = $this->ideas->get_ideas(self::IDEAS, 'date', 'DESC', 'idea_status = 3');
		$this->assign_template_block_vars('implemented_ideas', $ideas);

		$this->template->assign_vars(array(
			'U_VIEW_TOP'		=> $this->link_helper->get_list_link('top'),
			'U_VIEW_LATEST'		=> $this->link_helper->get_list_link('new'),
			'U_VIEW_IMPLEMENTED'=> $this->link_helper->get_list_link('implemented'),
			'U_POST_ACTION'		=> $this->helper->route('ideas_post_controller'),
		));

		// Assign breadcrumb template vars
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'		=> $this->helper->route('ideas_index_controller'),
			'FORUM_NAME'		=> $this->user->lang('IDEAS'),
		));

		return $this->helper->render('index_body.html', $this->user->lang('IDEAS_HOME'));
	}
}
