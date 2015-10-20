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

		$rows = $this->ideas->get_ideas(self::IDEAS, 'date', 'DESC');
		foreach ($rows as $row)
		{
			$this->template->assign_block_vars('latest_ideas', array(
				'ID'		=> $row['idea_id'],
				'LINK'		=> $this->link_helper->get_idea_link($row['idea_id']),
				'TITLE'		=> $row['idea_title'],
				'AUTHOR'	=> $this->link_helper->get_user_link($row['idea_author']),
				'DATE'		=> $this->user->format_date($row['idea_date']),
				'READ'      => $row['read'],
				'VOTES_UP'	=> $row['idea_votes_up'],
				'VOTES_DOWN'=> $row['idea_votes_down'],
				'POINTS'    => $row['idea_votes_up'] - $row['idea_votes_down'],
			));
		}

		$rows = $this->ideas->get_ideas(self::IDEAS, 'top', 'DESC');
		foreach ($rows as $row)
		{
			$this->template->assign_block_vars('top_ideas', array(
				'ID'		=> $row['idea_id'],
				'LINK'		=> $this->link_helper->get_idea_link($row['idea_id']),
				'TITLE'		=> $row['idea_title'],
				'AUTHOR'	=> $this->link_helper->get_user_link($row['idea_author']),
				'DATE'		=> $this->user->format_date($row['idea_date']),
				'READ'      => $row['read'],
				'VOTES_UP'	=> $row['idea_votes_up'],
				'VOTES_DOWN'=> $row['idea_votes_down'],
				'POINTS'    => $row['idea_votes_up'] - $row['idea_votes_down'],
			));
		}

		$rows = $this->ideas->get_ideas(self::IDEAS, 'date', 'DESC', 'idea_status = 3');
		foreach ($rows as $row)
		{
			$this->template->assign_block_vars('implemented_ideas', array(
				'ID'		=> $row['idea_id'],
				'LINK'		=> $this->link_helper->get_idea_link($row['idea_id']),
				'TITLE'		=> $row['idea_title'],
				'AUTHOR'	=> $this->link_helper->get_user_link($row['idea_author']),
				'DATE'		=> $this->user->format_date($row['idea_date']),
				'READ'      => $row['read'],
				'VOTES_UP'	=> $row['idea_votes_up'],
				'VOTES_DOWN'=> $row['idea_votes_down'],
				'POINTS'    => $row['idea_votes_up'] - $row['idea_votes_down'],
			));
		}

		$this->template->assign_vars(array(
			'U_VIEW_TOP'		=> $this->link_helper->get_list_link('top'),
			'U_VIEW_LATEST'		=> $this->link_helper->get_list_link('new'),
			'U_VIEW_IMPLEMENTED'=> $this->link_helper->get_list_link('implemented'),
			'U_POST_ACTION'		=> $this->helper->route('phpbb_ideas_post_controller'),
		));

		// Assign breadcrumb template vars
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'		=> $this->helper->route('phpbb_ideas_index_controller'),
			'FORUM_NAME'		=> $this->user->lang('IDEAS'),
		));

		return $this->helper->render('index_body.html', $this->user->lang('IDEAS_HOME'));
	}
}
