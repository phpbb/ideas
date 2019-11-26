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
use Symfony\Component\HttpFoundation\RedirectResponse;

class post_controller extends base
{
	/**
	 * Controller for /post
	 *
	 * @throws http_exception
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function post()
	{
		if (!$this->is_available())
		{
			throw new http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}

		if ($this->user->data['user_id'] == ANONYMOUS)
		{
			throw new http_exception(404, 'LOGGED_OUT');
		}

		$this->language->add_lang('posting');

		if (!function_exists('submit_post'))
		{
			include $this->root_path . 'includes/functions_posting.' . $this->php_ext;
		}

		if (!function_exists('display_custom_bbcodes'))
		{
			include $this->root_path . 'includes/functions_display.' . $this->php_ext;
		}

		$title = $this->request->variable('title', '', true);
		$message = $this->request->variable('message', '', true);

		if ($this->request->is_set_post('post'))
		{
			$submit = $this->ideas->submit($title, $message, $this->user->data['user_id']);

			if (is_array($submit))
			{
				$this->template->assign_vars(array(
					'ERROR'		=> implode('<br />', $submit),
					'MESSAGE'	=> $message,
				));
			}
			else if (!$this->auth->acl_get('f_noapprove', $this->config['ideas_forum_id']))
			{
				// Show users who's posts need approval a special message
				throw new http_exception(200, 'IDEA_STORED_MOD', array($this->helper->route('phpbb_ideas_index_controller')));
			}
			else
			{
				return new RedirectResponse($this->helper->route('phpbb_ideas_idea_controller', array('idea_id' => $submit)));
			}
		}

		if ($this->request->is_set_post('preview'))
		{
			$preview_message = $this->ideas->preview($message);

			$this->template->assign_vars(array(
				'S_DISPLAY_PREVIEW'	=> !empty($preview_message),
				'PREVIEW_SUBJECT'	=> $title,
				'PREVIEW_MESSAGE'	=> $preview_message,
			));
		}

		display_custom_bbcodes();
		generate_smilies('inline', 0);

		// BBCode, Smilies, Images URL, and Flash statuses
		$bbcode_status	= (bool) $this->config['allow_bbcode'] && $this->auth->acl_get('f_bbcode', $this->config['ideas_forum_id']);
		$smilies_status	= (bool) $this->config['allow_smilies'] && $this->auth->acl_get('f_smilies', $this->config['ideas_forum_id']);
		$img_status		= (bool) $bbcode_status && $this->auth->acl_get('f_img', $this->config['ideas_forum_id']);
		$url_status		= (bool) $this->config['allow_post_links'];
		$flash_status	= (bool) $bbcode_status && $this->auth->acl_get('f_flash', $this->config['ideas_forum_id']) && $this->config['allow_post_flash'];

		$this->template->assign_vars(array(
			'TITLE'				=> $title,
			'MESSAGE'			=> $message,

			'S_POST_ACTION'		=> $this->helper->route('phpbb_ideas_post_controller'),
			'S_BBCODE_ALLOWED'	=> $bbcode_status,
			'S_SMILIES_ALLOWED'	=> $smilies_status,
			'S_LINKS_ALLOWED'	=> $url_status,
			'S_BBCODE_IMG'		=> $img_status,
			'S_BBCODE_FLASH'	=> $flash_status,
			'S_BBCODE_QUOTE'	=> true,

			'BBCODE_STATUS'		=> $this->language->lang(($bbcode_status ? 'BBCODE_IS_ON' : 'BBCODE_IS_OFF'), '<a href="' . $this->helper->route('phpbb_help_bbcode_controller') . '">', '</a>'),
			'IMG_STATUS'		=> $img_status ? $this->language->lang('IMAGES_ARE_ON') : $this->language->lang('IMAGES_ARE_OFF'),
			'FLASH_STATUS'		=> $flash_status ? $this->language->lang('FLASH_IS_ON') : $this->language->lang('FLASH_IS_OFF'),
			'URL_STATUS'		=> ($bbcode_status && $url_status) ? $this->language->lang('URL_IS_ON') : $this->language->lang('URL_IS_OFF'),
			'SMILIES_STATUS'	=> $smilies_status ? $this->language->lang('SMILIES_ARE_ON') : $this->language->lang('SMILIES_ARE_OFF'),
		));

		// Assign breadcrumb template vars
		$this->template->assign_block_vars_array('navlinks', array(
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('phpbb_ideas_index_controller'),
				'FORUM_NAME'	=> $this->language->lang('IDEAS'),
			),
			array(
				'U_VIEW_FORUM'	=> $this->helper->route('phpbb_ideas_post_controller'),
				'FORUM_NAME'	=> $this->language->lang('NEW_IDEA'),
			),
		));

		return $this->helper->render('@phpbb_ideas/idea_new.html', $this->language->lang('NEW_IDEA'));
	}
}
