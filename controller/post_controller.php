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

class post_controller extends base
{
	/**
	 * Controller for /post
	 *
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 * @throws http_exception
	 */
	public function post()
	{
		if (!$this->is_available())
		{
			throw new http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}

		include($this->root_path . 'includes/functions_posting.' . $this->php_ext);
		include($this->root_path . 'includes/functions_display.' . $this->php_ext);
		include($this->root_path . 'includes/message_parser.' . $this->php_ext);

		$this->user->add_lang('posting');

		if ($this->user->data['user_id'] == ANONYMOUS)
		{
			throw new http_exception(404, 'LOGGED_OUT');
		}

		$mode = $this->request->variable('mode', '');
		$title = $this->request->variable('title', '');
		$desc = $this->request->variable('desc', '', true);

		if ($mode === 'submit')
		{
			$submit = $this->ideas->submit($title, $desc, $this->user->data['user_id']);

			if (is_array($submit))
			{
				$this->template->assign_vars(array(
					'ERROR'		=> implode('<br />', $submit),
					'DESC'		=> $desc,
				));
			}
			else
			{
				redirect($this->helper->route('phpbb_ideas_idea_controller', array('idea_id' => $submit)));
			}
		}

		display_custom_bbcodes();
		generate_smilies('inline', 0);

		$this->template->assign_vars(array(
			'S_POST_ACTION'		=> $this->helper->route('phpbb_ideas_post_controller', array('mode' => 'submit')),
			'TITLE'				=> $title,
		));

		// Assign breadcrumb template vars
		$this->template->assign_block_vars_array('navlinks', array(
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('phpbb_ideas_index_controller'),
				'FORUM_NAME'	=> $this->user->lang('IDEAS'),
			),
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('phpbb_ideas_post_controller'),
				  'FORUM_NAME'	=> $this->user->lang('POST_IDEA'),
			),
		));

		return $this->helper->render('idea_new.html', $this->user->lang('NEW_IDEA'));
	}
}
