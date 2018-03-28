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
		$sort = $this->request->is_set('sort') ? $this->request->variable('sort', ideas::SORT_NEW) : $sort;

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
			$status = ideas::$statuses['IMPLEMENTED'];
			$sort = ideas::SORT_DATE;
		}

		// Set the status name for displaying in the template
		$status_name = (!$status && $sort === ideas::SORT_TOP) ? $this->language->lang('TOP_IDEAS') : $this->ideas->get_status_from_id($status);

		// For special case where we want to request ALL ideas,
		// including the statuses normally hidden from lists.
		if ($status === -1)
		{
			$status = ideas::$statuses;
			$status_name = $this->language->lang('ALL_IDEAS');
		}

		// Generate ideas
		$ideas = $this->ideas->get_ideas($this->config['posts_per_page'], $sort, $sort_direction, $status, $start);
		$this->assign_template_block_vars('ideas', $ideas);

		// Build list page template output
		$this->template->assign_vars(array(
			'U_LIST_ACTION'		=> $this->helper->route('phpbb_ideas_list_controller'),
			'U_POST_ACTION'		=> $this->helper->route('phpbb_ideas_post_controller'),
			'IDEAS_COUNT'       => $this->ideas->get_idea_count(),
			'STATUS_NAME'       => $status_name ?: $this->language->lang('OPEN_IDEAS'),
			'STATUS_ARY'		=> ideas::$statuses,
			'STATUS'			=> $u_status,
			'SORT_ARY'			=> array(ideas::SORT_AUTHOR, ideas::SORT_DATE, ideas::SORT_SCORE, ideas::SORT_TITLE, ideas::SORT_TOP, ideas::SORT_VOTES),
			'SORT'				=> $sort,
			'SORT_DIRECTION'	=> $sort_direction,
			'U_MCP' 			=> ($this->auth->acl_get('m_', $this->config['ideas_forum_id'])) ? append_sid("{$this->root_path}mcp.{$this->php_ext}", "f={$this->config['ideas_forum_id']}&amp;i=main&amp;mode=forum_view", true, $this->user->session_id) : '',

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

		// Display the search ideas field
		$this->display_search_ideas();

		return $this->helper->render('list_body.html', $this->language->lang('IDEA_LIST'));
	}
}
