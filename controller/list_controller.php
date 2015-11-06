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
	 * @param $sort string The type of list to show (new|top|implemented)
	 * @throws http_exception
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function ideas_list($sort)
	{
		if (!$this->is_available())
		{
			throw new http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}

		// Overwrite the $sort parameter if the url contains a sort query.
		// This is needed with the sort by options form at the footer of the list.
		$sort = ($this->request->is_set('sort')) ? $this->request->variable('sort', ideas::SORT_NEW) : $sort;

		// Get additional query values the url may contain
		$sort_direction = $this->request->variable('sd', 'd');
		$status = $this->request->variable('status', 0);
		$start = $this->request->variable('start', 0);

		// Store original query params for use in breadcrumbs & pagination
		$u_sort = $sort;
		$u_status = $status;
		$u_sort_direction = $sort_direction;

		// convert the sort direction to ASC or DESC
		$sort_direction = ($sort_direction === 'd') ? 'DESC' : 'ASC';

		// If sort by "new" we really use date
		if ($sort === ideas::SORT_NEW)
		{
			$sort = ideas::SORT_DATE;
		}

		// if sort by "implemented", sort ideas with status implemented by date
		if ($sort === ideas::SORT_IMPLEMENTED)
		{
			$status = ideas::STATUS_IMPLEMENTED;
			$sort = ideas::SORT_DATE;
		}

		// Set the status name for displaying in the template
		$status_name = (!$status && $sort == ideas::SORT_TOP) ? $this->language->lang('TOP_IDEAS') : $this->ideas->get_status_from_id($status);

		// For special case where we want to request ALL ideas,
		// including the statuses normally hidden from lists.
		if ($status === -1)
		{
			$status = array(
				ideas::STATUS_NEW,
				ideas::STATUS_PROGRESS,
				ideas::STATUS_IMPLEMENTED,
				ideas::STATUS_INVALID,
				ideas::STATUS_DUPLICATE,
			);

			$status_name = $this->language->lang('ALL_IDEAS');
		}

		// Generate ideas
		$ideas = $this->ideas->get_ideas($this->config['posts_per_page'], $sort, $sort_direction, $status, $start);
		$this->assign_template_block_vars('ideas', $ideas);

		// Build the status form menu
		$statuses = $this->ideas->get_statuses();
		foreach ($statuses as $status_row)
		{
			$this->template->assign_block_vars('status', array(
				'VALUE'		=> $status_row['status_id'],
				'TEXT'		=> $this->language->lang($status_row['status_name']),
				'SELECTED'	=> $u_status == $status_row['status_id'],
			));
		}

		// Build the sort by menu
		$sortables = array(ideas::SORT_AUTHOR, ideas::SORT_DATE, ideas::SORT_SCORE, ideas::SORT_TITLE, ideas::SORT_TOP, ideas::SORT_VOTES);
		foreach ($sortables as $sortable)
		{
			$this->template->assign_block_vars('sortby', array(
				'VALUE'		=> $sortable,
				'TEXT'		=> $this->language->lang(strtoupper($sortable)),
				'SELECTED'	=> $sortable == $sort,
			));
		}

		// Build general list page template output
		$this->template->assign_vars(array(
			'U_POST_ACTION'		=> $this->helper->route('phpbb_ideas_list_controller'),
			'U_NEW_IDEA_ACTION'	=> $this->helper->route('phpbb_ideas_post_controller'),
			'SORT_DIRECTION'	=> $sort_direction,
			'STATUS_NAME'       => $status_name ?: $this->language->lang('OPEN_IDEAS'),
			'TOTAL_IDEAS'       => $this->language->lang('TOTAL_IDEAS', $this->ideas->get_idea_count()),
		));

		// Recreate the url parameters for the current list
		$params = array(
			'sort'		=> $u_sort ?: null,
			'status'	=> $u_status ?: null,
			'sd'		=> $u_sort_direction ?: null,
		);

		// Assign breadcrumb template vars
		$this->template->assign_block_vars_array('navlinks', array(
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('phpbb_ideas_index_controller'),
				'FORUM_NAME'	=> $this->language->lang('IDEAS'),
			),
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('phpbb_ideas_list_controller', $params),
				'FORUM_NAME'	=> $status_name ?: $this->language->lang('OPEN_IDEAS'),
			),
		));

		// Generate template pagination
		$this->pagination->generate_template_pagination(
			$this->helper->route('phpbb_ideas_list_controller', $params),
			'pagination',
			'start',
			$this->ideas->get_idea_count(),
			$this->config['posts_per_page'],
			$start
		);

		return $this->helper->render('list_body.html', $this->language->lang('IDEA_LIST'));
	}
}
