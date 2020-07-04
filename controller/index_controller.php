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
use phpbb\ideas\ext;

class index_controller extends base
{
	/* @var \phpbb\ideas\factory\ideas */
	protected $entity;

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
		$ideas = $this->entity->get_ideas(ext::NUM_IDEAS, ext::SORT_DATE, 'DESC');
		$this->assign_template_block_vars('latest_ideas', $ideas);

		// Generate top ideas
		$ideas = $this->entity->get_ideas(ext::NUM_IDEAS, ext::SORT_TOP, 'DESC');
		$this->assign_template_block_vars('top_ideas', $ideas);

		// Generate recently implemented
		$ideas = $this->entity->get_ideas(ext::NUM_IDEAS, ext::SORT_DATE, 'DESC', ext::$statuses['IMPLEMENTED']);
		$this->assign_template_block_vars('implemented_ideas', $ideas);

		$this->template->assign_vars(array(
			'U_VIEW_TOP'		=> $this->helper->route('phpbb_ideas_list_controller', ['sort' => ext::SORT_TOP]),
			'U_VIEW_LATEST'		=> $this->helper->route('phpbb_ideas_list_controller', ['sort' => ext::SORT_NEW]),
			'U_VIEW_IMPLEMENTED'=> $this->helper->route('phpbb_ideas_list_controller', ['sort' => ext::SORT_DATE, 'status' => ext::$statuses['IMPLEMENTED']]),
			'U_POST_ACTION'		=> $this->helper->route('phpbb_ideas_post_controller'),
			'U_MCP' 			=> ($this->auth->acl_get('m_', $this->config['ideas_forum_id'])) ? append_sid("{$this->root_path}mcp.{$this->php_ext}", "f={$this->config['ideas_forum_id']}&amp;i=main&amp;mode=forum_view", true, $this->user->session_id) : '',

		));

		// Assign breadcrumb template vars
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'		=> $this->helper->route('phpbb_ideas_index_controller'),
			'FORUM_NAME'		=> $this->language->lang('IDEAS'),
		));

		// Display common ideas template vars
		$this->display_common_vars();

		return $this->helper->render('index_body.html', $this->language->lang('IDEAS_TITLE'));
	}
}
