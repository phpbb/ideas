<?php

/**
 *
 * @package phpBB3 Ideas
 * @author Callum Macrae (callumacrae) <callum@lynxphp.com>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbb\ideas\controller;

// @todo: refactor out
define('IDEAS_FORUM_ID', 1);
define('IDEAS_POSTER_ID', 1);

class main
{
	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var \phpbb\ideas\factory\LinkHelper */
	protected $link_helper;

	/* @var \phpbb\ideas\factory\Ideas */
	protected $ideas;

	/* @var \phpbb\request\request */
	protected $request;

	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\ideas\factory\LinkHelper $link_helper, \phpbb\ideas\factory\Ideas $ideas, \phpbb\request\request $request)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->link_helper = $link_helper;
		$this->ideas = $ideas;
		$this->request = $request;

		$this->user->add_lang_ext('phpbb/ideas', 'common');
	}

	/**
	 * Controller for /ideas
	 *
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function index()
	{
		$rows = $this->ideas->get_ideas(10, 'date', 'DESC');
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

		$rows = $this->ideas->get_ideas(10, 'top', 'DESC');
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

		$rows = $this->ideas->get_ideas(5, 'date', 'DESC', 'idea_status = 3');
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
//			'S_POST_ACTION'		=> append_sid('./posting.php'),
		));

		return $this->helper->render('index_body.html', $this->user->lang['IDEAS_HOME']);
	}

	/**
	 * Controller for /idea/{idea_id}
	 *
	 * @param $idea_id int The ID of the requested idea, maybe?
	 */
	public function idea($idea_id)
	{
		$idea = $this->ideas->get_idea($idea_id);
		var_dump($idea);
	}

	/**
	 * Controller for /all/{sort}
	 *
	 * @param $sort string The direction to sort in.
	 */
	public function all($sort)
	{
		if ($sort === 'new') {
			$sort = 'date';
		}

		$req = $this->request;

		$sort_direction = ($req->variable('sd', 'd') === 'd') ? 'DESC' : 'ASC';
		$status = $req->variable('status', 0);
		$author = $req->variable('author', 0);

		if ($sort === 'implemented') {
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
//			'U_POST_ACTION'		=> append_sid('./list.php'),
			'SORT_DIRECTION'	=> $sort_direction,
			'STATUS_NAME'       => $status_name ?: $this->user->lang('ALL_IDEAS'),
		));

		return $this->helper->render('list_body.html', $this->user->lang['IDEA_LIST']);
	}
}
