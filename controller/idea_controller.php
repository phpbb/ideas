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

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\exception\http_exception;
use phpbb\ideas\factory\ideas;
use phpbb\ideas\factory\linkhelper;
use phpbb\language\language;
use phpbb\pagination;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class idea_controller extends base
{
	/** @var auth */
	protected $auth;

	/** @var config */
	protected $config;

	/**
	 * @param \phpbb\auth\auth                  $auth
	 * @param \phpbb\config\config              $config
	 * @param \phpbb\controller\helper          $helper
	 * @param \phpbb\ideas\factory\ideas        $ideas
	 * @param \phpbb\language\language          $language
	 * @param \phpbb\ideas\factory\linkhelper   $link_helper
	 * @param \phpbb\request\request            $request
	 * @param \phpbb\template\template          $template
	 * @param \phpbb\user                       $user
	 * @param string                            $root_path
	 * @param string                            $php_ext
	 */
	public function __construct(auth $auth, config $config, helper $helper, ideas $ideas, language $language, linkhelper $link_helper, request $request, template $template, user $user, $root_path, $php_ext)
	{
		parent::__construct($config, $helper, $ideas, $link_helper, $request, $template, $user, $root_path, $php_ext);
		$this->auth = $auth;
	}

	/**
	 * Controller for /idea/{idea_id}
	 *
	 * @param $idea_id int The ID of the requested idea, maybe?
	 * @throws http_exception
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function idea($idea_id)
	{
		if (!$this->is_available())
		{
			throw new http_exception(404, 'IDEAS_NOT_AVAILABLE');
		}

		$mode = $this->request->variable('mode', '');
		$vote = $this->request->variable('v', 1);
		$hash = $this->request->variable('hash', '');
		$status = $this->request->variable('status', 0);
		$idea = $this->ideas->get_idea($idea_id);

		if (!$idea)
		{
			throw new http_exception(404, 'IDEA_NOT_FOUND');
		}

		$mod = $this->auth->acl_get('m_', (int) $this->config['ideas_forum_id']);
		$own = $idea['idea_author'] === $this->user->data['user_id'];

		if ($mode === 'delete' && ($mod || ($own && $this->auth->acl_get('f_delete', (int) $this->config['ideas_forum_id']))))
		{
			if (confirm_box(true))
			{
				include($this->root_path . 'includes/functions_admin.' . $this->php_ext);
				$this->ideas->delete($idea_id, $idea['topic_id']);

				$redirect = $this->helper->route('phpbb_ideas_index_controller');
				$message = $this->language->lang('IDEA_DELETED') . '<br /><br />' . $this->language->lang('RETURN_IDEAS', '<a href="' . $redirect . '">', '</a>');
				meta_refresh(3, $redirect);
				trigger_error($message); // trigger error needed for data-ajax
			}
			else
			{
				confirm_box(
					false,
					$this->language->lang('CONFIRM_OPERATION'),
					build_hidden_fields(array(
						'idea_id' => $idea_id,
						'mode' => 'delete',
					)),
					'confirm_body.html',
					$this->helper->route(
						'ideas_idea_controller',
						array(
							'idea_id' => $idea_id,
							'mode'    => 'delete',
						),
						true,
						false,
						UrlGeneratorInterface::ABSOLUTE_URL
					)
				);
			}
		}

		if ($this->request->is_ajax() && !empty($mode))
		{
			switch ($mode)
			{
				case 'duplicate':
					if ($mod && check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						$duplicate = $this->request->variable('duplicate', 0);
						$result = $this->ideas->set_duplicate($idea['idea_id'], $duplicate);
					}
					else
					{
						$result = false;
					}
				break;

				case 'removevote':
					if ($idea['idea_status'] == ideas::STATUS_IMPLEMENTED || $idea['idea_status'] == ideas::STATUS_DUPLICATE || !check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						return false;
					}

					if ($this->auth->acl_get('f_vote', (int) $this->config['ideas_forum_id']))
					{
						$result = $this->ideas->remove_vote($idea, $this->user->data['user_id']);
					}
					else
					{
						$result = $this->language->lang('NO_AUTH_OPERATION');
					}
				break;

				case 'rfc':
					if (($own || $mod) && check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						$rfc = $this->request->variable('rfc', '');
						$result = $this->ideas->set_rfc($idea['idea_id'], $rfc);
					}
					else
					{
						$result = false;
					}
				break;

				case 'status':
					if ($status && $mod && check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						$this->ideas->change_status($idea['idea_id'], $status);
						$result = true;
					}
					else
					{
						$result = false;
					}
				break;

				case 'ticket':
					if (($own || $mod) && check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						$ticket = $this->request->variable('ticket', 0);
						$result = $this->ideas->set_ticket($idea['idea_id'], $ticket);
					}
					else
					{
						$result = false;
					}
				break;

				case 'title':
					if (($own || $mod) && check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						$title = $this->request->variable('title', '');
						$result = $this->ideas->set_title($idea['idea_id'], $title);
					}
					else
					{
						$result = false;
					}
				break;

				case 'vote':
					if ($idea['idea_status'] == ideas::STATUS_IMPLEMENTED || $idea['idea_status'] == ideas::STATUS_DUPLICATE || !check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						return false;
					}

					if ($this->auth->acl_get('f_vote', (int) $this->config['ideas_forum_id']))
					{
						$result = $this->ideas->vote($idea, $this->user->data['user_id'], $vote);
					}
					else
					{
						$result = $this->language->lang('NO_AUTH_OPERATION');
					}
				break;

				default:
					$result = '?';
				break;
			}

			return new \Symfony\Component\HttpFoundation\JsonResponse($result);
		}

		$url = generate_board_url() . "/viewtopic.$this->php_ext?f={$this->config['ideas_forum_id']}&t={$idea['topic_id']}";
		return new RedirectResponse($url);
	}
}
