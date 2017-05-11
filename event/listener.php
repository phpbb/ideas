<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\event;

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\ideas\factory\ideas;
use phpbb\ideas\factory\linkhelper;
use phpbb\language\language;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var auth */
	protected $auth;

	/* @var config */
	protected $config;

	/* @var helper */
	protected $helper;

	/* @var ideas */
	protected $ideas;

	/** @var language */
	protected $language;

	/* @var linkhelper */
	protected $link_helper;

	/* @var template */
	protected $template;

	/* @var user */
	protected $user;

	/** @var string */
	protected $php_ext;

	/**
	 * @param \phpbb\auth\auth                $auth
	 * @param \phpbb\config\config            $config
	 * @param \phpbb\controller\helper        $helper
	 * @param \phpbb\ideas\factory\ideas      $ideas
	 * @param \phpbb\language\language        $language
	 * @param \phpbb\ideas\factory\linkhelper $link_helper
	 * @param \phpbb\template\template        $template
	 * @param \phpbb\user                     $user
	 * @param string                          $php_ext
	 */
	public function __construct(auth $auth, config $config, helper $helper, ideas $ideas, language $language, linkhelper $link_helper, template $template, user $user, $php_ext)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;
		$this->ideas = $ideas;
		$this->language = $language;
		$this->link_helper = $link_helper;
		$this->template = $template;
		$this->user = $user;
		$this->php_ext = $php_ext;

		$this->language->add_lang('common', 'phpbb/ideas');
	}

	/**
	 * @inheritDoc
	 */
	static public function getSubscribedEvents()
	{
		return array(
			'core.viewforum_get_topic_data'				=> 'ideas_forum_redirect',
			'core.viewtopic_modify_post_row'			=> array(array('clean_message'), array('show_post_buttons')),
			'core.viewtopic_modify_page_title'			=> 'show_idea',
			'core.viewtopic_add_quickmod_option_before'	=> 'adjust_quickmod_tools',
			'core.viewonline_overwrite_location'		=> 'viewonline_ideas',
		);
	}

	/**
	 * Redirect users from the forum to the Ideas centre
	 *
	 * @param $event
	 * @return void
	 * @access public
	 */
	public function ideas_forum_redirect($event)
	{
		if ($event['forum_id'] == $this->config['ideas_forum_id'])
		{
			// Use the custom base url if set, otherwise default to normal routing
			$url = $this->config['ideas_base_url'] ?: $this->helper->route('phpbb_ideas_index_controller');
			redirect($url);
		}
	}

	/**
	 * Clean obsolete link-backs from idea topics
	 *
	 * @param $event
	 * @return void
	 * @access public
	 */
	public function clean_message($event)
	{
		if ($event['row']['forum_id'] != $this->config['ideas_forum_id'])
		{
			return;
		}

		if ($event['topic_data']['topic_first_post_id'] == $event['row']['post_id'])
		{
			$post_row = $event['post_row'];
			$message = $post_row['MESSAGE'];

			// This freakish looking regex pattern should
			// remove the ideas link-backs from the message.
			$message = preg_replace('/(<br[^>]*>\\n?)\\1-{10}\\1\\1.*/s', '', $message);

			$post_row['MESSAGE'] = $message;
			$event['post_row'] = $post_row;
		}
	}

	/**
	 * Show post buttons (hide edit, delete, warn, quote; show others)
	 *
	 * @param $event
	 * @return void
	 * @access public
	 */
	public function show_post_buttons($event)
	{
		if ($event['row']['forum_id'] != $this->config['ideas_forum_id'])
		{
			return;
		}

		if ($event['topic_data']['topic_first_post_id'] == $event['row']['post_id'])
		{
			$post_row = $event['post_row'];

			// Do not display edit, delete, quote or warn user buttons
			$post_row['U_EDIT']   = false;
			$post_row['U_DELETE'] = false;
			$post_row['U_QUOTE']  = false;
			$post_row['U_WARN']   = false;

			$event['post_row'] = $post_row;
		}
	}

	/**
	 * Show the idea related to the current topic
	 *
	 * @param $event
	 * @return void
	 * @access public
	 */
	public function show_idea($event)
	{
		if ($event['forum_id'] != $this->config['ideas_forum_id'])
		{
			return;
		}

		$idea = $this->ideas->get_idea_by_topic_id($event['topic_data']['topic_id']);

		if (!$idea)
		{
			return;
		}

		$mod = $this->auth->acl_get('m_', (int) $this->config['ideas_forum_id']);
		$own = $idea['idea_author'] === $this->user->data['user_id'];

		if ($mod)
		{
			$this->template->assign_var('STATUS_ARY', ideas::$statuses);
		}

		$points = $idea['idea_votes_up'] - $idea['idea_votes_down'];
		$can_vote = (bool) ($idea['idea_status'] != ideas::$statuses['IMPLEMENTED'] &&
			$idea['idea_status'] != ideas::$statuses['DUPLICATE'] &&
			$this->auth->acl_get('f_vote', (int) $this->config['ideas_forum_id']) &&
			$event['topic_data']['topic_status'] != ITEM_LOCKED);

		$this->template->assign_vars(array(
			'IDEA_ID'			=> $idea['idea_id'],
			'IDEA_TITLE'		=> $idea['idea_title'],
			'IDEA_AUTHOR'		=> $this->link_helper->get_user_link($idea['idea_author']),
			'IDEA_DATE'			=> $this->user->format_date($idea['idea_date']),
			'IDEA_VOTES'		=> $idea['idea_votes_up'] + $idea['idea_votes_down'],
			'IDEA_VOTES_UP'		=> $idea['idea_votes_up'],
			'IDEA_VOTES_DOWN'	=> $idea['idea_votes_down'],
			'IDEA_POINTS'		=> $points,
			'IDEA_STATUS_ID'	=> $idea['idea_status'],
			'IDEA_STATUS_NAME'	=> $this->ideas->get_status_from_id($idea['idea_status']),
			'IDEA_STATUS_LINK'	=> $this->helper->route('phpbb_ideas_list_controller', array('status' => $idea['idea_status'])),

			'IDEA_DUPLICATE'	=> $idea['duplicate_id'],
			'IDEA_RFC'			=> $idea['rfc_link'],
			'IDEA_TICKET'		=> $idea['ticket_id'],

			'U_IDEA_DUPLICATE'	=> $this->link_helper->get_idea_link((int) $idea['duplicate_id']),

			'S_IS_MOD'			=> $mod,
			'S_CAN_EDIT'		=> $mod || $own,
			'S_CAN_VOTE'		=> $can_vote,

			'U_DELETE_IDEA'		=> $this->link_helper->get_idea_link($idea['idea_id'], 'delete'),
			'U_CHANGE_STATUS'	=> $this->link_helper->get_idea_link($idea['idea_id'], 'status', true),
			'U_EDIT_DUPLICATE'	=> $this->link_helper->get_idea_link($idea['idea_id'], 'duplicate', true),
			'U_EDIT_RFC'		=> $this->link_helper->get_idea_link($idea['idea_id'], 'rfc', true),
			'U_EDIT_TICKET'		=> $this->link_helper->get_idea_link($idea['idea_id'], 'ticket', true),
			'U_EDIT_TITLE'		=> $this->link_helper->get_idea_link($idea['idea_id'], 'title', true),
			'U_REMOVE_VOTE'		=> $this->link_helper->get_idea_link($idea['idea_id'], 'removevote', true),
			'U_IDEA_VOTE'		=> $this->link_helper->get_idea_link($idea['idea_id'], 'vote', true),
		));

		if ($idea['idea_votes_up'] || $idea['idea_votes_down'])
		{
			$s_voted = false;

			$votes = $this->ideas->get_voters($idea['idea_id']);

			foreach ($votes as $vote)
			{
				$this->template->assign_block_vars('votes_' . ($vote['vote_value'] ? 'up' : 'down'), array(
					'USER' => $vote['user'],
				));

				if ($this->user->data['user_id'] == $vote['user_id'])
				{
					$s_voted = true;
				}
			}

			$this->template->assign_var('S_VOTED', $s_voted);
		}

		// Use Ideas breadcrumbs
		$this->template->destroy_block_vars('navlinks');
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'		=> $this->helper->route('phpbb_ideas_index_controller'),
			'FORUM_NAME'		=> $this->language->lang('IDEAS'),
		));
	}

	public function adjust_quickmod_tools($event)
	{
		if ($event['forum_id'] != $this->config['ideas_forum_id'])
		{
			return;
		}

		$quickmod_array = $event['quickmod_array'];

		//$quickmod_array['lock'][1] = false;
		//$quickmod_array['unlock'][1] = false;
		$quickmod_array['delete_topic'][1] = false;
		$quickmod_array['restore_topic'][1] = false;
		//$quickmod_array['move'][1] = false;
		//$quickmod_array['split'][1] = false;
		//$quickmod_array['merge'][1] = false;
		//$quickmod_array['merge_topic'][1] = false;
		//$quickmod_array['fork'][1] = false;
		$quickmod_array['make_normal'][1] = false;
		$quickmod_array['make_sticky'][1] = false;
		$quickmod_array['make_announce'][1] = false;
		$quickmod_array['make_global'][1] = false;

		$event['quickmod_array'] = $quickmod_array;
	}

	/**
	 * Show users as viewing Ideas on Who Is Online page
	 *
	 * @param \phpbb\event\data $event The event object
	 * @return void
	 * @access public
	 */
	public function viewonline_ideas($event)
	{
		if ($event['on_page'][1] === 'app')
		{
			if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/ideas/post') === 0)
			{
				$event['location'] = $this->language->lang('POSTING_NEW_IDEA');
				$event['location_url'] = $this->helper->route('phpbb_ideas_index_controller');
			}
			else if (strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/ideas') === 0)
			{
				$event['location'] = $this->language->lang('VIEWING_IDEAS');
				$event['location_url'] = $this->helper->route('phpbb_ideas_index_controller');
			}
		}
		else if ($event['on_page'][1] === 'viewtopic' && $event['row']['session_forum_id'] == $this->config['ideas_forum_id'])
		{
			$event['location'] = $this->language->lang('VIEWING_IDEAS');
			$event['location_url'] = $this->helper->route('phpbb_ideas_index_controller');
		}
	}
}
