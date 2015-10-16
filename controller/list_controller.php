<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @author Callum Macrae (callumacrae) <callum@lynxphp.com>
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace phpbb\ideas\controller;

class list_controller extends base
{
	/**
	 * Controller for /all/{sort}
	 *
	 * @param $sort string The direction to sort in.
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function all($sort)
	{
		if ($sort === 'new')
		{
			$sort = 'date';
		}

		$sort_direction = ($this->request->variable('sd', 'd')) === 'd' ? 'DESC' : 'ASC';
		$status = $this->request->variable('status', 0);
		$author = $this->request->variable('author', 0);

		if ($sort === 'implemented')
		{
			$status = 3;
			$sort = 'date';
		}

		$where = $status ? "idea_status = $status" : 'idea_status != 4 AND idea_status != 3 AND idea_status != 5';
		if ($author)
		{
			$where .= " && idea_author = $author";
		}

		if ($sort == 'top')
		{
			$status_name = $this->user->lang('TOP_IDEAS');
		}
		else
		{
			$status_name = $this->ideas->get_status_from_id($status);
		}

		$returned_ideas = $this->ideas->get_ideas(0, $sort, $sort_direction, $where);

		foreach ($returned_ideas as $idea)
		{
			$this->template->assign_block_vars('ideas', array(
				'ID'			=> $idea['idea_id'],
				'LINK'			=> $this->link_helper->get_idea_link($idea['idea_id']),
				'TITLE'			=> $idea['idea_title'],
				'AUTHOR'		=> $this->link_helper->get_user_link($idea['idea_author']),
				'DATE'			=> $this->user->format_date($idea['idea_date']),
				'READ'          => $idea['read'],
				'VOTES_UP'	    => $idea['idea_votes_up'],
				'VOTES_DOWN'    => $idea['idea_votes_down'],
				'POINTS'        => $idea['idea_votes_up'] - $idea['idea_votes_down'],
				'STATUS'		=> $idea['idea_status'], // For icons
			));
		}

		$statuses = array('new', 'in_progress', 'implemented', 'duplicate');
		foreach ($statuses as $key => $statusText)
		{
			$this->template->assign_block_vars('status', array(
				'VALUE'		=> $key + 1,
				'TEXT'		=> $this->user->lang[strtoupper($statusText)],
				'SELECTED'	=> $status == $key + 1,
			));
		}

		$sorts = array('author', 'date', 'id', 'score', 'title', 'top', 'votes');
		foreach ($sorts as $sortBy)
		{
			$this->template->assign_block_vars('sortby', array(
				'VALUE'		=> $sortBy,
				'TEXT'		=> $this->user->lang[strtoupper($sortBy)],
				'SELECTED'	=> $sortBy == $sort,
			));
		}

		$this->template->assign_vars(array(
			'U_POST_ACTION'		=> $this->helper->route('ideas_list_controller'),
			'SORT_DIRECTION'	=> $sort_direction,
			'STATUS_NAME'       => $status_name ?: $this->user->lang('ALL_IDEAS'),
		));

		// Assign breadcrumb template vars
		$this->template->assign_block_vars_array('navlinks', array(
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('ideas_index_controller'),
				'FORUM_NAME'	=> $this->user->lang('IDEAS'),
			),
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('ideas_list_controller'),
				'FORUM_NAME'	=> $status_name ?: $this->user->lang('ALL_IDEAS'),
			),
		));

		return $this->helper->render('list_body.html', $this->user->lang('IDEA_LIST'));
	}
}
