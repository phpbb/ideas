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

class list_controller extends base
{
	/**
	 * Controller for /list/{sort}
	 *
	 * @param $sort string The direction to sort in.
	 * @throws http_exception
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function ideas_list($sort)
	{
		if (!$this->is_available())
		{
			throw new http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}

		// Build the breadcrumb off the $sort parameter
		$breadcrumb = (in_array($sort, array(ideas::SORT_NEW, ideas::SORT_TOP, ideas::SORT_IMPLEMENTED)) ? $sort : array());

		if ($sort === ideas::SORT_NEW)
		{
			$sort = ideas::SORT_DATE;
		}

		$sort_direction = ($this->request->variable('sd', 'd')) === 'd' ? 'DESC' : 'ASC';
		$status = $this->request->variable('status', 0);
		$author = $this->request->variable(ideas::SORT_AUTHOR, 0);
		$start = $this->request->variable('start', 0);

		if ($sort === ideas::SORT_IMPLEMENTED)
		{
			$status = ideas::STATUS_IMPLEMENTED;
			$sort = ideas::SORT_DATE;
		}

		$where = ($author) ? 'idea_author = ' (int) $author : '';

		if ($sort == ideas::SORT_TOP)
		{
			$status_name = $this->language->lang('TOP_IDEAS');
		}
		else
		{
			$status_name = $this->ideas->get_status_from_id($status);
		}

		// Generate ideas
		$ideas = $this->ideas->get_ideas($this->config['posts_per_page'], $sort, $sort_direction, $status, $where, $start);
		$this->assign_template_block_vars('ideas', $ideas);

		$statuses = $this->ideas->get_statuses();
		foreach ($statuses as $status_row)
		{
			$this->template->assign_block_vars('status', array(
				'VALUE'		=> $status_row['status_id'],
				'TEXT'		=> $this->language->lang($status_row['status_name']),
				'SELECTED'	=> $status == $status_row['status_id'],
			));
		}

		$sorts = array(ideas::SORT_AUTHOR, ideas::SORT_DATE, ideas::SORT_ID, ideas::SORT_SCORE, ideas::SORT_TITLE, ideas::SORT_TOP, ideas::SORT_VOTES);
		foreach ($sorts as $sortBy)
		{
			$this->template->assign_block_vars('sortby', array(
				'VALUE'		=> $sortBy,
				'TEXT'		=> $this->language->lang(strtoupper($sortBy)),
				'SELECTED'	=> $sortBy == $sort,
			));
		}

		$this->template->assign_vars(array(
			'U_POST_ACTION'		=> $this->helper->route('phpbb_ideas_list_controller'),
			'U_NEW_IDEA_ACTION'	=> $this->helper->route('phpbb_ideas_post_controller'),
			'SORT_DIRECTION'	=> $sort_direction,
			'STATUS_NAME'       => $status_name ?: $this->language->lang('ALL_IDEAS'),
			'TOTAL_IDEAS'       => $this->language->lang('TOTAL_IDEAS', $this->ideas->get_idea_count()),
		));

		// Assign breadcrumb template vars
		$breadcrumb_params = ($breadcrumb) ? array('sort' => $breadcrumb) : array();
		$breadcrumb_params = array_merge($breadcrumb_params, (($status) ? array('status' => $status) : array()));
		$this->template->assign_block_vars_array('navlinks', array(
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('phpbb_ideas_index_controller'),
				'FORUM_NAME'	=> $this->language->lang('IDEAS'),
			),
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('phpbb_ideas_list_controller', $breadcrumb_params),
				'FORUM_NAME'	=> $status_name ?: $this->language->lang('ALL_IDEAS'),
			),
		));

		// Generate template pagination
		$this->pagination->generate_template_pagination(
			$this->helper->route('phpbb_ideas_list_controller', $breadcrumb_params),
			'pagination',
			'start',
			$this->ideas->get_idea_count(),
			$this->config['posts_per_page'],
			$start
		);

		return $this->helper->render('list_body.html', $this->language->lang('IDEA_LIST'));
	}
}
