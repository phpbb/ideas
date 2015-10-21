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

class list_controller extends base
{
	const SORT_NEW = 'new';
	const SORT_TOP = 'top';
	const SORT_IMPLEMENTED = 'implemented';
	const SORT_AUTHOR = 'author';
	const SORT_DATE = 'date';
	const SORT_ID = 'id';
	const SORT_SCORE = 'score';
	const SORT_TITLE = 'title';
	const SORT_VOTES = 'votes';

	/**
	 * Controller for /list/{sort}
	 *
	 * @param $sort string The direction to sort in.
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 * @throws http_exception
	 */
	public function ideas_list($sort)
	{
		if (!$this->is_available())
		{
			throw new http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}

		// Build the breadcrumb off the $sort parameter
		$breadcrumb = (in_array($sort, array(self::SORT_NEW, self::SORT_TOP, self::SORT_IMPLEMENTED)) ? $sort : array());

		if ($sort === self::SORT_NEW)
		{
			$sort = self::SORT_DATE;
		}

		$sort_direction = ($this->request->variable('sd', 'd')) === 'd' ? 'DESC' : 'ASC';
		$status = $this->request->variable('status', 0);
		$author = $this->request->variable(self::SORT_AUTHOR, 0);

		if ($sort === self::SORT_IMPLEMENTED)
		{
			$status = 3;
			$sort = self::SORT_DATE;
		}

		$where = $status ? "idea_status = $status" : 'idea_status != 4 AND idea_status != 3 AND idea_status != 5';
		if ($author)
		{
			$where .= " && idea_author = $author";
		}

		if ($sort == self::SORT_TOP)
		{
			$status_name = $this->user->lang('TOP_IDEAS');
		}
		else
		{
			$status_name = $this->ideas->get_status_from_id($status);
		}

		// Generate ideas
		$ideas = $this->ideas->get_ideas(0, $sort, $sort_direction, $where);
		$this->assign_template_block_vars('ideas', $ideas);

		$statuses = $this->ideas->get_statuses();
		foreach ($statuses as $status_row)
		{
			$this->template->assign_block_vars('status', array(
				'VALUE'		=> $status_row['status_id'],
				'TEXT'		=> $this->user->lang($status_row['status_name']),
				'SELECTED'	=> $status == $status_row['status_id'],
			));
		}

		$sorts = array(self::SORT_AUTHOR, self::SORT_DATE, self::SORT_ID, self::SORT_SCORE, self::SORT_TITLE, self::SORT_TOP, self::SORT_VOTES);
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
			'U_NEW_IDEA_ACTION'	=> $this->helper->route('ideas_post_controller'),
			'SORT_DIRECTION'	=> $sort_direction,
			'STATUS_NAME'       => $status_name ?: $this->user->lang('ALL_IDEAS'),
		));

		// Assign breadcrumb template vars
		$breadcrumb_params = ($breadcrumb) ? array('sort' => $breadcrumb) : array();
		$breadcrumb_params = array_merge($breadcrumb_params, (($status) ? array('status' => $status) : array()));
		$this->template->assign_block_vars_array('navlinks', array(
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('ideas_index_controller'),
				'FORUM_NAME'	=> $this->user->lang('IDEAS'),
			),
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('ideas_list_controller', $breadcrumb_params),
				'FORUM_NAME'	=> $status_name ?: $this->user->lang('ALL_IDEAS'),
			),
		));

		return $this->helper->render('list_body.html', $this->user->lang('IDEA_LIST'));
	}
}
