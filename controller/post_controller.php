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

class post_controller extends base
{
	public function post()
	{
		include($this->root_path . 'includes/functions_posting.' . $this->php_ext);
		include($this->root_path . 'includes/functions_display.' . $this->php_ext);
		include($this->root_path . 'includes/message_parser.' . $this->php_ext);

		$this->user->add_lang('posting');

		if ($this->user->data['user_id'] == ANONYMOUS)
		{
			throw new \phpbb\exception\http_exception(404, 'LOGGED_OUT');
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
				redirect($this->helper->route('ideas_idea_controller', array('idea_id' => $submit)));
			}
		}

		display_custom_bbcodes();
		generate_smilies('inline', 0);

		$this->template->assign_vars(array(
			'S_POST_ACTION'		=> $this->helper->route('ideas_post_controller', array('mode' => 'submit')),
			'TITLE'				=> $title,
		));

		return $this->helper->render('idea_new.html', $this->user->lang('NEW_IDEA'));
	}
}
