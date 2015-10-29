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

	/** @var array of idea data */
	protected $data;

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
		parent::__construct($config, $helper, $ideas, $language, $link_helper, $request, $template, $user, $root_path, $php_ext);
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

		$this->data = $this->ideas->get_idea($idea_id);
		if (!$this->data)
		{
			throw new http_exception(404, 'IDEA_NOT_FOUND');
		}

		$mode = $this->request->variable('mode', '');
		if ($this->request->is_ajax() && !empty($mode))
		{
			$result = call_user_func(array($this, $mode));

			return new \Symfony\Component\HttpFoundation\JsonResponse($result);
		}

		$url = reapply_sid(generate_board_url() .
			"/viewtopic.{$this->php_ext}?f={$this->config['ideas_forum_id']}&t={$this->data['topic_id']}"
		);

		return new RedirectResponse($url);
	}

	/**
	 * Delete action (deletes an idea via confirm dialog)
	 *
	 * @return null
	 * @access public
	 */
	public function delete()
	{
		if ($this->is_mod() || ($this->is_own() && $this->auth->acl_get('f_delete', (int) $this->config['ideas_forum_id'])))
		{
			if (confirm_box(true))
			{
				include($this->root_path . 'includes/functions_admin.' . $this->php_ext);
				$this->ideas->delete($this->data['idea_id'], $this->data['topic_id']);

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
						'idea_id' => $this->data['idea_id'],
						'mode' => 'delete',
					)),
					'confirm_body.html',
					$this->helper->route(
						'ideas_idea_controller',
						array(
							'idea_id' => $this->data['idea_id'],
							'mode'    => 'delete',
						),
						true,
						false,
						UrlGeneratorInterface::ABSOLUTE_URL
					)
				);
			}
		}
	}

	/**
	 * Duplicate action (sets an idea's duplicate link)
	 *
	 * @return bool True if set, false if not
	 * @access public
	 */
	public function duplicate()
	{
		if ($this->is_mod() && check_link_hash($this->get_hash(), "duplicate_{$this->data['idea_id']}"))
		{
			$duplicate = $this->request->variable('duplicate', 0);
			return $this->ideas->set_duplicate($this->data['idea_id'], $duplicate);
		}

		return false;
	}

	/**
	 * Remove vote action (remove a user's vote from an idea)
	 *
	 * @return mixed Array of vote data, an error message, or false if failed
	 * @access public
	 */
	public function removevote()
	{
		if ($this->data['idea_status'] == ideas::STATUS_IMPLEMENTED || $this->data['idea_status'] == ideas::STATUS_DUPLICATE || !check_link_hash($this->get_hash(), "removevote_{$this->data['idea_id']}"))
		{
			return false;
		}

		if ($this->auth->acl_get('f_vote', (int) $this->config['ideas_forum_id']))
		{
			$result = $this->ideas->remove_vote($this->data, $this->user->data['user_id']);
		}
		else
		{
			$result = $this->language->lang('NO_AUTH_OPERATION');
		}

		return $result;
	}

	/**
	 * RFC action (sets an idea's rfc link)
	 *
	 * @return bool True if set, false if not
	 * @access public
	 */
	public function rfc()
	{
		if (($this->is_own() || $this->is_mod()) && check_link_hash($this->get_hash(), "rfc_{$this->data['idea_id']}"))
		{
			$rfc = $this->request->variable('rfc', '');
			return $this->ideas->set_rfc($this->data['idea_id'], $rfc);
		}

		return false;
	}

	/**
	 * Status action (sets an idea's status)
	 *
	 * @return bool True if set, false if not
	 * @access public
	 */
	public function status()
	{
		$status = $this->request->variable('status', 0);

		if ($status && $this->is_mod() && check_link_hash($this->get_hash(), "status_{$this->data['idea_id']}"))
		{
			$this->ideas->change_status($this->data['idea_id'], $status);
			return true;
		}

		return false;
	}

	/**
	 * Ticket action (sets an idea's ticket link)
	 *
	 * @return bool True if set, false if not
	 * @access public
	 */
	public function ticket()
	{
		if (($this->is_own() || $this->is_mod()) && check_link_hash($this->get_hash(), "ticket_{$this->data['idea_id']}"))
		{
			$ticket = $this->request->variable('ticket', 0);
			return $this->ideas->set_ticket($this->data['idea_id'], $ticket);
		}

		return false;
	}

	/**
	 * Title action (sets an idea's title)
	 *
	 * @return bool True if set, false if not
	 * @access public
	 */
	public function title()
	{
		if (($this->is_own() || $this->is_mod()) && check_link_hash($this->get_hash(), "title_{$this->data['idea_id']}"))
		{
			$title = $this->request->variable('title', '');
			return $this->ideas->set_title($this->data['idea_id'], $title);
		}

		return false;
	}

	/**
	 * Vote action (sets an idea's vote)
	 *
	 * @return mixed Array of vote data, an error message, or false if failed
	 * @access public
	 */
	public function vote()
	{
		$vote = $this->request->variable('v', 1);

		if ($this->data['idea_status'] == ideas::STATUS_IMPLEMENTED || $this->data['idea_status'] == ideas::STATUS_DUPLICATE || !check_link_hash($this->get_hash(), "vote_{$this->data['idea_id']}"))
		{
			return false;
		}

		if ($this->auth->acl_get('f_vote', (int) $this->config['ideas_forum_id']))
		{
			$result = $this->ideas->vote($this->data, $this->user->data['user_id'], $vote);
		}
		else
		{
			$result = $this->language->lang('NO_AUTH_OPERATION');
		}

		return $result;
	}

	/**
	 * Get a hash query parameter
	 *
	 * @return string The hash
	 * @access protected
	 */
	protected function get_hash()
	{
		return $this->request->variable('hash', '');
	}

	/**
	 * Does the user have moderator privileges?
	 *
	 * @return bool
	 * @access protected
	 */
	protected function is_mod()
	{
		return $this->auth->acl_get('m_', (int) $this->config['ideas_forum_id']);
	}

	/**
	 * Is the user the author of the idea?
	 *
	 * @return bool
	 * @access protected
	 */
	protected function is_own()
	{
		return $this->data['idea_author'] === $this->user->data['user_id'];
	}
}
