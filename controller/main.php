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

	/* @var \phpbb\ideas\factory\Ideas */
	protected $ideas;

	public function __construct(\phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user, \phpbb\user_loader $user_loader, \phpbb\ideas\factory\Ideas $ideas)
	{
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->user_loader = $user_loader;
		$this->ideas = $ideas;

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
				'LINK'		=> $this->getIdeaLink($row['idea_id']),
				'TITLE'		=> $row['idea_title'],
				'AUTHOR'	=> $this->get_user_link($row['idea_author']),
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
				'LINK'		=> $this->getIdeaLink($row['idea_id']),
				'TITLE'		=> $row['idea_title'],
				'AUTHOR'	=> $this->get_user_link($row['idea_author']),
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
				'LINK'		=> $this->getIdeaLink($row['idea_id']),
				'TITLE'		=> $row['idea_title'],
				'AUTHOR'	=> $this->get_user_link($row['idea_author']),
				'DATE'		=> $this->user->format_date($row['idea_date']),
				'READ'      => $row['read'],
				'VOTES_UP'	=> $row['idea_votes_up'],
				'VOTES_DOWN'=> $row['idea_votes_down'],
				'POINTS'    => $row['idea_votes_up'] - $row['idea_votes_down'],
			));
		}

		$this->template->assign_vars(array(
//			'U_VIEW_TOP'		=> append_sid('./list.php', 'sort=top'),
//			'U_VIEW_LATEST'		=> append_sid('./list.php', 'sort=date'),
//			'U_VIEW_IMPLEMENTED'=> append_sid('./list.php', 'status=3'),
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
	 * Shortcut method to get the link to a specified idea.
	 *
	 * @param $idea_id int The ID of the idea.
	 * @return string The route
	 */
	private function getIdeaLink($idea_id)
	{
		return $this->helper->route('ideas_idea_controller', array(
			'idea_id' => $idea_id
		));
	}

	/**
	 * Returns a link to the users profile, complete with colour.
	 *
	 * Is there a function that already does this? This seems fairly database heavy.
	 *
	 * @param int $id The ID of the user.
	 * @return string An HTML link to the users profile.
	 */
	private function get_user_link($id)
	{
		$this->user_loader->load_users(array($id));
		return $this->user_loader->get_username($id, 'full');
	}
}
