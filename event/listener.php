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

	/**
	 * @param \phpbb\auth\auth                $auth
	 * @param \phpbb\config\config            $config
	 * @param \phpbb\controller\helper        $helper
	 * @param \phpbb\ideas\factory\ideas      $ideas
	 * @param \phpbb\language\language        $language
	 * @param \phpbb\ideas\factory\linkhelper $link_helper
	 * @param \phpbb\template\template        $template
	 * @param \phpbb\user                     $user
	 */
	public function __construct(auth $auth, config $config, helper $helper, ideas $ideas, language $language, linkhelper $link_helper, template $template, user $user)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->helper = $helper;
		$this->ideas = $ideas;
		$this->language = $language;
		$this->link_helper = $link_helper;
		$this->template = $template;
		$this->user = $user;

		$this->language->add_lang('common', 'phpbb/ideas');
	}

	/**
	 * @inheritDoc
	 */
	static public function getSubscribedEvents()
	{
		return array(
			'core.viewtopic_modify_post_row'	=> 'clean_message',
			'core.viewtopic_modify_page_title'	=> 'load_idea',
		);
	}

	/**
	 * Clean obsolete link-backs from idea topics
	 *
	 * @param $event
	 * @return null
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

	public function load_idea($event)
	/**
	 * Show the idea related to the current topic
	 *
	 * @param $event
	 * @return null
	 * @access public
	 */
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
			$statuses = $this->ideas->get_statuses();
			foreach ($statuses as $status)
			{
				$this->template->assign_block_vars('statuses', array(
					'ID'	=> $status['status_id'],
					'NAME'	=> $this->language->lang($status['status_name']),
				));
			}
		}

		$points = $idea['idea_votes_up'] - $idea['idea_votes_down'];
		$can_vote = (bool) ($idea['idea_status'] != ideas::STATUS_IMPLEMENTED &&
			$idea['idea_status'] != ideas::STATUS_DUPLICATE &&
			$this->auth->acl_get('f_vote', (int) $this->config['ideas_forum_id']) &&
			$event['topic_data']['topic_status'] != ITEM_LOCKED);
		$delete_posts = $mod || ($own && $this->auth->acl_get('f_delete', (int) $this->config['ideas_forum_id']));

		$this->template->assign_vars(array(
			'IDEA_ID'			=> $idea['idea_id'],
			'IDEA_TITLE'		=> $idea['idea_title'],
			'IDEA_AUTHOR'		=> $this->link_helper->get_user_link($idea['idea_author']),
			'IDEA_DATE'			=> $this->user->format_date($idea['idea_date']),
			'IDEA_VOTES'		=> $idea['idea_votes_up'] + $idea['idea_votes_down'],
			'IDEA_VOTES_UP'		=> $idea['idea_votes_up'],
			'IDEA_VOTES_DOWN'	=> $idea['idea_votes_down'],
			'IDEA_POINTS'		=> $this->language->lang('VIEW_VOTES', $points),
			'IDEA_STATUS'		=> $this->ideas->get_status_from_id($idea['idea_status']),
			'IDEA_STATUS_LINK'	=> $this->helper->route('phpbb_ideas_list_controller', array('status' => $idea['idea_status'])),

			'IDEA_DUPLICATE'	=> $idea['duplicate_id'],
			'IDEA_RFC'			=> $idea['rfc_link'],
			'IDEA_TICKET'		=> $idea['ticket_id'],

			'U_IDEA_DUPLICATE'	=> $this->link_helper->get_idea_link((int) $idea['duplicate_id']),

			'S_IS_MOD'			=> $mod,
			'S_CAN_EDIT'		=> $mod || $own,
			'S_CAN_VOTE'		=> $can_vote,

			'U_DELETE_IDEA'		=> ($delete_posts) ? $this->link_helper->get_idea_link($idea['idea_id'], 'delete') : false,
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
			$votes = $this->ideas->get_voters($idea['idea_id']);

			foreach ($votes as $vote)
			{
				$this->template->assign_block_vars('votes_' . ($vote['vote_value'] ? 'up' : 'down'), array(
					'USER'	=> get_username_string('full', $vote['user_id'], $vote['username'], $vote['user_colour']),
					'S_VOTED' => ($this->user->data['user_id'] == $vote['user_id']) ? true : false,
				));
			}
		}

		// Use Ideas breadcrumbs
		$this->template->destroy_block_vars('navlinks');
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'		=> $this->helper->route('phpbb_ideas_index_controller'),
			'FORUM_NAME'		=> $this->language->lang('IDEAS'),
		));
	}
}
