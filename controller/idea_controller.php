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
use phpbb\cache\service;
use phpbb\config\config;
use phpbb\content_visibility;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\exception\http_exception;
use phpbb\ideas\factory\ideas;
use phpbb\ideas\factory\linkhelper;
use phpbb\pagination;
use phpbb\profilefields\manager;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;

class idea_controller extends base
{
	/** @var auth */
	protected $auth;

	/** @var service */
	protected $cache;

	/** @var config */
	protected $config;

	/** @var content_visibility */
	protected $content_visibility;

	/** @var manager */
	protected $cp;

	/** @var driver_interface */
	protected $db;

	/** @var pagination */
	protected $pagination;

	/**
	 * @param \phpbb\auth\auth                  $auth
	 * @param \phpbb\cache\service              $cache
	 * @param \phpbb\config\config              $config
	 * @param \phpbb\content_visibility         $content_visibility
	 * @param \phpbb\profilefields\manager      $cp
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\controller\helper          $helper
	 * @param \phpbb\ideas\factory\ideas        $ideas
	 * @param \phpbb\ideas\factory\linkhelper   $link_helper
	 * @param \phpbb\pagination                 $pagination
	 * @param \phpbb\request\request            $request
	 * @param \phpbb\template\template          $template
	 * @param \phpbb\user                       $user
	 * @param                                   $root_path
	 * @param                                   $php_ext
	 */
	public function __construct(auth $auth, service $cache, config $config, content_visibility $content_visibility, manager $cp, driver_interface $db, helper $helper, ideas $ideas, linkhelper $link_helper, pagination $pagination, request $request, template $template, user $user, $root_path, $php_ext)
	{
		parent::__construct($config, $helper, $ideas, $link_helper, $request, $template, $user, $root_path, $php_ext);

		$this->auth = $auth;
		$this->cache = $cache;
		$this->content_visibility = $content_visibility;
		$this->cp = $cp;
		$this->db = $db;
		$this->pagination = $pagination;

		$this->user->add_lang('viewtopic');
	}

	/**
	 * Controller for /idea/{idea_id}
	 *
	 * @param $idea_id int The ID of the requested idea, maybe?
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 * @throws http_exception
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
				$message = $this->user->lang('IDEA_DELETED') . '<br /><br />' . $this->user->lang('RETURN_IDEAS', '<a href="' . $redirect . '">', '</a>');
				meta_refresh(3, $redirect);
				trigger_error($message); // trigger error needed for data-ajax
			}
			else
			{
				confirm_box(false, $this->user->lang('CONFIRM_OPERATION'), build_hidden_fields(array(
					'idea_id' => $idea_id,
					'mode' => 'delete',
				)));
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
						$this->ideas->set_duplicate($idea['idea_id'], $duplicate);
						$result = true;
					}
					else
					{
						$result = false;
					}
				break;

				case 'removevote':
					if ($idea['idea_status'] == 3 || $idea['idea_status'] == 4 || !check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						return false;
					}

					if ($this->auth->acl_get('f_vote', (int) $this->config['ideas_forum_id']))
					{
						$result = $this->ideas->remove_vote($idea, $this->user->data['user_id']);
					}
					else
					{
						$result = $this->user->lang('NO_AUTH_OPERATION');
					}
				break;

				case 'rfc':
					if (($own || $mod) && check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						$rfc = $this->request->variable('rfc', '');
						$this->ideas->set_rfc($idea['idea_id'], $rfc);
						$result = true;
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
						$this->ideas->set_ticket($idea['idea_id'], $ticket);
						$result = true;
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
						$this->ideas->set_title($idea['idea_id'], $title);
						$result = true;
					}
					else
					{
						$result = false;
					}
				break;

				case 'vote':
					if ($idea['idea_status'] == 3 || $idea['idea_status'] == 4 || !check_link_hash($hash, "{$mode}_{$idea_id}"))
					{
						return false;
					}

					if ($this->auth->acl_get('f_vote', (int) $this->config['ideas_forum_id']))
					{
						$result = $this->ideas->vote($idea, $this->user->data['user_id'], $vote);
					}
					else
					{
						$result = $this->user->lang('NO_AUTH_OPERATION');
					}
				break;

				default:
					$result = '?';
				break;
			}

			return new \Symfony\Component\HttpFoundation\JsonResponse($result);
		}

		include($this->root_path . 'includes/functions_display.' . $this->php_ext);
		include($this->root_path . 'includes/bbcode.' . $this->php_ext);
		include($this->root_path . 'includes/functions_user.' . $this->php_ext);

		$delete_posts = $mod || ($own && $this->auth->acl_get('f_delete', (int) $this->config['ideas_forum_id']));

		if ($mod)
		{
			$statuses = $this->ideas->get_statuses();
			foreach ($statuses as $status)
			{
				$this->template->assign_block_vars('statuses', array(
					'ID'	=> $status['status_id'],
					'NAME'	=> $this->user->lang($status['status_name']),
				));
			}
		}

		$points = $idea['idea_votes_up'] - $idea['idea_votes_down'];

		$idea_topic_link = append_sid("{$this->root_path}viewtopic.{$this->php_ext}", 't=' . $idea['topic_id']);

		$can_vote = true;
		if ($idea['idea_status'] == 3 || $idea['idea_status'] == 4 || !$this->auth->acl_get('f_vote', (int) $this->config['ideas_forum_id']))
		{
			$can_vote = false;
		}
		// Topic locked is check later on: search for "TOPIC LOCK CHECK 123"

		$this->template->assign_vars(array(
			'IDEA_ID'			=> $idea['idea_id'],
			'IDEA_TITLE'		=> $idea['idea_title'],
			'IDEA_AUTHOR'		=> $this->link_helper->get_user_link($idea['idea_author']),
			'IDEA_DATE'			=> $this->user->format_date($idea['idea_date']),
			'IDEA_VOTES'		=> $idea['idea_votes_up'] + $idea['idea_votes_down'],
			'IDEA_VOTES_UP'		=> $idea['idea_votes_up'],
			'IDEA_VOTES_DOWN'	=> $idea['idea_votes_down'],
			'IDEA_POINTS'		=> $this->user->lang('VIEW_VOTES', $points),
			'IDEA_STATUS'		=> $this->ideas->get_status_from_id($idea['idea_status']),
			'IDEA_STATUS_LINK'	=> $this->helper->route('phpbb_ideas_list_controller', array('status' => $idea['idea_status'])),

			'IDEA_DUPLICATE'	=> $idea['duplicate_id'],
			'IDEA_RFC'			=> $idea['rfc_link'],
			'IDEA_TICKET'		=> $idea['ticket_id'],

			'U_IDEA_TOPIC'		=> $idea_topic_link,
			'U_IDEA_DUPLICATE'	=> $this->link_helper->get_idea_link((int) $idea['duplicate_id']),

			'S_IS_MOD'			=> $mod,
			'S_CAN_EDIT'		=> $mod || $own,
			'S_CAN_VOTE'		=> $can_vote,

			'U_DELETE_IDEA'		=> ($delete_posts) ? $this->link_helper->get_idea_link($idea_id, 'delete') : false,
			'U_CHANGE_STATUS'	=> $this->link_helper->get_idea_link($idea_id, 'status', true),
			'U_EDIT_DUPLICATE'	=> $this->link_helper->get_idea_link($idea_id, 'duplicate', true),
			'U_EDIT_RFC'		=> $this->link_helper->get_idea_link($idea_id, 'rfc', true),
			'U_EDIT_TICKET'		=> $this->link_helper->get_idea_link($idea_id, 'ticket', true),
			'U_EDIT_TITLE'		=> $this->link_helper->get_idea_link($idea_id, 'title', true),
			'U_REMOVE_VOTE'		=> $this->link_helper->get_idea_link($idea_id, 'removevote', true),
			'U_IDEA_VOTE'		=> $this->link_helper->get_idea_link($idea_id, 'vote', true),
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

		$forum_id = (int) $this->config['ideas_forum_id'];
		$topic_id = $idea['topic_id'];
		$post_id  = $this->request->variable('p', 0);

		$start    = $this->request->variable('start', 0);
		$view     = $this->request->variable('view', '');

		$default_sort_days	= (!empty($this->user->data['user_post_show_days'])) ? $this->user->data['user_post_show_days'] : 0;
		$default_sort_key	= (!empty($this->user->data['user_post_sortby_type'])) ? $this->user->data['user_post_sortby_type'] : 't';
		$default_sort_dir	= (!empty($this->user->data['user_post_sortby_dir'])) ? $this->user->data['user_post_sortby_dir'] : 'a';

		$sort_days	= $this->request->variable('st', $default_sort_days);
		$sort_key	= $this->request->variable('sk', $default_sort_key);
		$sort_dir	= $this->request->variable('sd', $default_sort_dir);

		$update		= $this->request->variable('update', false);

		$hilit_words = $this->request->variable('hilit', '', true);

		// Find topic id if user requested a newer or older topic
		if ($view && !$post_id)
		{
			/*
			if (!$forum_id)
			{
				$sql = 'SELECT forum_id
					FROM ' . TOPICS_TABLE . "
					WHERE topic_id = $topic_id";
				$result = $db->sql_query($sql);
				$forum_id = (int) $db->sql_fetchfield('forum_id');
				$db->sql_freeresult($result);

				if (!$forum_id)
				{
					throw new http_exception(404, 'NO_TOPIC');
				}
			}
			*/

			if ($view == 'unread')
			{
				// Get topic tracking info
				$topic_tracking_info = get_complete_topic_tracking($forum_id, $topic_id);
				$topic_last_read = (isset($topic_tracking_info[$topic_id])) ? $topic_tracking_info[$topic_id] : 0;

				$sql = 'SELECT post_id, topic_id, forum_id
					FROM ' . POSTS_TABLE . "
					WHERE topic_id = $topic_id
						AND " . $this->content_visibility->get_visibility_sql('post', $forum_id) . "
						AND post_time > $topic_last_read
						AND forum_id = $forum_id
					ORDER BY post_time ASC, post_id ASC";
				$result = $this->db->sql_query_limit($sql, 1);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					$sql = 'SELECT topic_last_post_id as post_id, topic_id, forum_id
						FROM ' . TOPICS_TABLE . '
						WHERE topic_id = ' . $topic_id;
					$result = $this->db->sql_query($sql);
					$row = $this->db->sql_fetchrow($result);
					$this->db->sql_freeresult($result);
				}

				if (!$row)
				{
					// Setup user environment so we can process lang string
					//$this->user->setup('viewtopic');

					throw new http_exception(404, 'NO_TOPIC');
				}

				$post_id = $row['post_id'];
				$topic_id = $row['topic_id'];
			}
			else if ($view == 'next' || $view == 'previous')
			{
				$sql_condition = ($view == 'next') ? '>' : '<';
				$sql_ordering = ($view == 'next') ? 'ASC' : 'DESC';

				$sql = 'SELECT forum_id, topic_last_post_time
					FROM ' . TOPICS_TABLE . '
					WHERE topic_id = ' . $topic_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if (!$row)
				{
					$this->user->setup('viewtopic');
					// OK, the topic doesn't exist. This error message is not helpful, but technically correct.
					throw new http_exception(404, ($view == 'next') ? 'NO_NEWER_TOPICS' : 'NO_OLDER_TOPICS');
				}
				else
				{
					$sql = 'SELECT topic_id, forum_id
						FROM ' . TOPICS_TABLE . '
						WHERE forum_id = ' . $row['forum_id'] . "
							AND topic_moved_id = 0
							AND topic_last_post_time $sql_condition {$row['topic_last_post_time']}
							AND " . $this->content_visibility->get_visibility_sql('topic', $row['forum_id']) . "
						ORDER BY topic_last_post_time $sql_ordering, topic_last_post_id $sql_ordering";
					$result = $this->db->sql_query_limit($sql, 1);
					$row = $this->db->sql_fetchrow($result);
					$this->db->sql_freeresult($result);

					if (!$row)
					{
//						$sql = 'SELECT forum_style
//							FROM ' . FORUMS_TABLE . "
//							WHERE forum_id = $forum_id";
//						$result = $this->db->sql_query($sql);
//						$forum_style = (int) $this->db->sql_fetchfield('forum_style');
//						$this->db->sql_freeresult($result);
//
//						$this->user->setup('viewtopic', $forum_style);
						throw new http_exception(404, ($view == 'next') ? 'NO_NEWER_TOPICS' : 'NO_OLDER_TOPICS');
					}
					else
					{
						$topic_id = $row['topic_id'];
						$forum_id = $row['forum_id'];
					}
				}
			}

			if (isset($row) && $row['forum_id'])
			{
				$forum_id = $row['forum_id'];
			}
		}

		// This rather complex gaggle of code handles querying for topics but
		// also allows for direct linking to a post (and the calculation of which
		// page the post is on and the correct display of viewtopic)
		$sql_array = array(
			'SELECT'	=> 't.*, f.*',

			'FROM'		=> array(FORUMS_TABLE => 'f'),
		);

		// The FROM-Order is quite important here, else t.* columns can not be correctly bound.
		if ($post_id)
		{
			$sql_array['SELECT'] .= ', p.post_visibility, p.post_time, p.post_id';
			$sql_array['FROM'][POSTS_TABLE] = 'p';
		}

		// Topics table need to be the last in the chain
		$sql_array['FROM'][TOPICS_TABLE] = 't';

		if ($this->user->data['is_registered'])
		{
			$sql_array['SELECT'] .= ', tw.notify_status';
			$sql_array['LEFT_JOIN'] = array();

			$sql_array['LEFT_JOIN'][] = array(
				'FROM'	=> array(TOPICS_WATCH_TABLE => 'tw'),
				'ON'	=> 'tw.user_id = ' . $this->user->data['user_id'] . ' AND t.topic_id = tw.topic_id'
			);

			if ($this->config['allow_bookmarks'])
			{
				$sql_array['SELECT'] .= ', bm.topic_id as bookmarked';
				$sql_array['LEFT_JOIN'][] = array(
					'FROM'	=> array(BOOKMARKS_TABLE => 'bm'),
					'ON'	=> 'bm.user_id = ' . $this->user->data['user_id'] . ' AND t.topic_id = bm.topic_id'
				);
			}

			if ($this->config['load_db_lastread'])
			{
				$sql_array['SELECT'] .= ', tt.mark_time, ft.mark_time as forum_mark_time';

				$sql_array['LEFT_JOIN'][] = array(
					'FROM'	=> array(TOPICS_TRACK_TABLE => 'tt'),
					'ON'	=> 'tt.user_id = ' . $this->user->data['user_id'] . ' AND t.topic_id = tt.topic_id'
				);

				$sql_array['LEFT_JOIN'][] = array(
					'FROM'	=> array(FORUMS_TRACK_TABLE => 'ft'),
					'ON'	=> 'ft.user_id = ' . $this->user->data['user_id'] . ' AND t.forum_id = ft.forum_id'
				);
			}
		}

		if (!$post_id)
		{
			$sql_array['WHERE'] = "t.topic_id = $topic_id";
		}
		else
		{
			$sql_array['WHERE'] = "p.post_id = $post_id AND t.topic_id = p.topic_id";
		}

		$sql_array['WHERE'] .= ' AND f.forum_id = t.forum_id';

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		$topic_data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// link to unapproved post or incorrect link
		if (!$topic_data)
		{
			// If post_id was submitted, we try at least to display the topic as a last resort...
			if ($post_id && $topic_id)
			{
				redirect($this->link_helper->get_idea_link($idea_id));
			}

			throw new http_exception(404, 'NO_TOPIC');
		}

		$forum_id = (int) $topic_data['forum_id'];

		// Now we know the forum_id and can check the permissions
		if ($topic_data['topic_visibility'] != ITEM_APPROVED && !$this->auth->acl_get('m_approve', $forum_id))
		{
			throw new http_exception(404, 'NO_TOPIC');
		}

		// This is for determining where we are (page)
		if ($post_id)
		{
			// are we where we are supposed to be?
			if (($topic_data['post_visibility'] == ITEM_UNAPPROVED || $topic_data['post_visibility'] == ITEM_REAPPROVE) && !$this->auth->acl_get('m_approve', $topic_data['forum_id']))
			{
				// If post_id was submitted, we try at least to display the topic as a last resort...
				if ($topic_id)
				{
					redirect($this->link_helper->get_idea_link($idea_id));
				}

				throw new http_exception(404, 'NO_TOPIC');
			}
			if ($post_id == $topic_data['topic_first_post_id'] || $post_id == $topic_data['topic_last_post_id'])
			{
				$check_sort = ($post_id == $topic_data['topic_first_post_id']) ? 'd' : 'a';

				if ($sort_dir == $check_sort)
				{
					$topic_data['prev_posts'] = $this->content_visibility->get_count('topic_posts', $topic_data, $forum_id) - 1;
				}
				else
				{
					$topic_data['prev_posts'] = 0;
				}
			}
			else
			{
				$sql = 'SELECT COUNT(p.post_id) AS prev_posts
					FROM ' . POSTS_TABLE . " p
					WHERE p.topic_id = {$topic_data['topic_id']}
						AND " . $this->content_visibility->get_visibility_sql('post', $forum_id, 'p.');

				if ($sort_dir == 'd')
				{
					$sql .= " AND (p.post_time > {$topic_data['post_time']} OR (p.post_time = {$topic_data['post_time']} AND p.post_id >= {$topic_data['post_id']}))";
				}
				else
				{
					$sql .= " AND (p.post_time < {$topic_data['post_time']} OR (p.post_time = {$topic_data['post_time']} AND p.post_id <= {$topic_data['post_id']}))";
				}

				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				$topic_data['prev_posts'] = $row['prev_posts'] - 1;
			}
		}

		$topic_id = (int) $topic_data['topic_id'];
		$topic_replies = $this->content_visibility->get_count('topic_posts', $topic_data, $forum_id) - 1;

		// Check sticky/announcement time limit
		if (($topic_data['topic_type'] == POST_STICKY || $topic_data['topic_type'] == POST_ANNOUNCE) && $topic_data['topic_time_limit'] && ($topic_data['topic_time'] + $topic_data['topic_time_limit']) < time())
		{
			$sql = 'UPDATE ' . TOPICS_TABLE . '
				SET topic_type = ' . POST_NORMAL . ', topic_time_limit = 0
				WHERE topic_id = ' . $topic_id;
			$this->db->sql_query($sql);

			$topic_data['topic_type'] = POST_NORMAL;
			$topic_data['topic_time_limit'] = 0;
		}

		// Setup look and feel
		//$this->user->setup('viewtopic', $topic_data['forum_style']);

		// Start auth check
		if (!$this->auth->acl_get('f_read', $forum_id))
		{
			if ($this->user->data['user_id'] != ANONYMOUS)
			{
				throw new http_exception(403, 'SORRY_AUTH_READ');
			}

			login_box('', $this->user->lang('LOGIN_VIEWFORUM'));
		}

		// Forum is passworded ... check whether access has been granted to this
		// user this session, if not show login box
		if ($topic_data['forum_password'])
		{
			login_forum_box($topic_data);
		}

		// Redirect to login upon emailed notification links if user is not logged in.
		if (isset($_GET['e']) && $this->user->data['user_id'] == ANONYMOUS)
		{
			login_box(build_url('e') . '#unread', $this->user->lang('LOGIN_NOTIFY_TOPIC'));
		}

		// What is start equal to?
		if ($post_id)
		{
			$start = floor(($topic_data['prev_posts']) / $this->config['posts_per_page']) * $this->config['posts_per_page'];
		}

		// Get topic tracking info
		if (!isset($topic_tracking_info))
		{
			$topic_tracking_info = array();

			// Get topic tracking info
			if ($this->config['load_db_lastread'] && $this->user->data['is_registered'])
			{
				$tmp_topic_data = array($topic_id => $topic_data);
				$topic_tracking_info = get_topic_tracking($forum_id, $topic_id, $tmp_topic_data, array($forum_id => $topic_data['forum_mark_time']));
				unset($tmp_topic_data);
			}
			else if ($this->config['load_anon_lastread'] || $this->user->data['is_registered'])
			{
				$topic_tracking_info = get_complete_topic_tracking($forum_id, $topic_id);
			}
		}

		// Post ordering options
		$limit_days = array(0 => $this->user->lang('ALL_POSTS'), 1 => $this->user->lang('1_DAY'), 7 => $this->user->lang('7_DAYS'), 14 => $this->user->lang('2_WEEKS'), 30 => $this->user->lang('1_MONTH'), 90 => $this->user->lang('3_MONTHS'), 180 => $this->user->lang('6_MONTHS'), 365 => $this->user->lang('1_YEAR'));

		$sort_by_text = array('a' => $this->user->lang('AUTHOR'), 't' => $this->user->lang('POST_TIME'), 's' => $this->user->lang('SUBJECT'));
		$sort_by_sql = array('a' => array('u.username_clean', 'p.post_id'), 't' => array('p.post_time', 'p.post_id'), 's' => array('p.post_subject', 'p.post_id'));
		$join_user_sql = array('a' => true, 't' => false, 's' => false);

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';

		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param, $default_sort_days, $default_sort_key, $default_sort_dir);

		// Obtain correct post count and ordering SQL if user has
		// requested anything different
		if ($sort_days)
		{
			$min_post_time = time() - ($sort_days * 86400);

			$sql = 'SELECT COUNT(post_id) AS num_posts
				FROM ' . POSTS_TABLE . "
				WHERE topic_id = $topic_id
					AND post_time >= $min_post_time
						AND " . $this->content_visibility->get_visibility_sql('post', $forum_id);
			$result = $this->db->sql_query($sql);
			$total_posts = (int) $this->db->sql_fetchfield('num_posts');
			$this->db->sql_freeresult($result);

			$limit_posts_time = "AND p.post_time >= $min_post_time ";

			if (isset($_POST['sort']))
			{
				$start = 0;
			}
		}
		else
		{
			$total_posts = $topic_replies + 1;
			$limit_posts_time = '';
		}

		// Was a highlight request part of the URI?
		$highlight_match = $highlight = '';
		if ($hilit_words)
		{
			$highlight_match = phpbb_clean_search_string($hilit_words);
			$highlight = urlencode($highlight_match);
			$highlight_match = str_replace('\*', '\w+?', preg_quote($highlight_match, '#'));
			$highlight_match = preg_replace('#(?<=^|\s)\\\\w\*\?(?=\s|$)#', '\w+?', $highlight_match);
			$highlight_match = str_replace(' ', '|', $highlight_match);
		}

		// Make sure $start is set to the last page if it exceeds the amount
		$start = $this->pagination->validate_start($start, $this->config['posts_per_page'], $total_posts);

		// General Viewtopic URL for return links
		$params = array('idea_id' => $idea_id);
		$params = ($start != 0) ? array_merge($params, array('start' => $start)) : $params;
		$params = (strlen($u_sort_param)) ? array_merge($params, explode('=', $u_sort_param)) : $params;
		$params = ($highlight_match) ? array_merge($params, array('hilit' => $highlight)) : $params;
		$viewtopic_url = $this->helper->route('phpbb_ideas_idea_controller', $params);

		// Are we watching this topic?
		$s_watching_topic = array(
			'link'			=> '',
			'link_toggle'	=> '',
			'title'			=> '',
			'title_toggle'	=> '',
			'is_watching'	=> false,
		);

		if ($this->config['allow_topic_notify'])
		{
			$notify_status = (isset($topic_data['notify_status'])) ? $topic_data['notify_status'] : null;
			watch_topic_forum('topic', $s_watching_topic, $this->user->data['user_id'], $forum_id, $topic_id, $notify_status, $start, $topic_data['topic_title']);

			// Reset forum notification if forum notify is set
			if ($this->config['allow_forum_notify'] && $this->auth->acl_get('f_subscribe', $forum_id))
			{
				$s_watching_forum = $s_watching_topic;
				watch_topic_forum('forum', $s_watching_forum, $this->user->data['user_id'], $forum_id, 0);
			}
		}

		// Bookmarks
		if ($this->config['allow_bookmarks'] && $this->user->data['is_registered'] && $this->request->variable('bookmark', 0))
		{
			if (check_link_hash($this->request->variable('hash', ''), "topic_$topic_id"))
			{
				if (!$topic_data['bookmarked'])
				{
					$sql = 'INSERT INTO ' . BOOKMARKS_TABLE . ' ' . $this->db->sql_build_array('INSERT', array(
						'user_id'	=> $this->user->data['user_id'],
						'topic_id'	=> $topic_id,
					));
					$this->db->sql_query($sql);
				}
				else
				{
					$sql = 'DELETE FROM ' . BOOKMARKS_TABLE . "
						WHERE user_id = {$this->user->data['user_id']}
							AND topic_id = $topic_id";
					$this->db->sql_query($sql);
				}
				$message = (($topic_data['bookmarked']) ? $this->user->lang('BOOKMARK_REMOVED') : $this->user->lang('BOOKMARK_ADDED')) . '<br /><br />' . $this->user->lang('RETURN_TOPIC', '<a href="' . $viewtopic_url . '">', '</a>');

				if (!$this->request->is_ajax())
				{
					$message .= '<br /><br />' . $this->user->lang('RETURN_TOPIC', '<a href="' . $viewtopic_url . '">', '</a>');
				}
			}
			else
			{
				$message = $this->user->lang['BOOKMARK_ERR'];

				if (!$this->request->is_ajax())
				{
					$message .= '<br /><br />' . $this->user->lang('RETURN_TOPIC', '<a href="' . $viewtopic_url . '">', '</a>');
				}
			}
			meta_refresh(3, $viewtopic_url);

			throw new http_exception(200, $message);
		}

		// Grab ranks
		$ranks = $this->cache->obtain_ranks();

		// Grab icons
		$icons = $this->cache->obtain_icons();

		// Grab extensions
		$extensions = array();
		if ($topic_data['topic_attachment'])
		{
			$extensions = $this->cache->obtain_attach_extensions($forum_id);
		}

		// Forum rules listing
		$s_forum_rules = '';
		gen_forum_auth_level('topic', $forum_id, $topic_data['forum_status']);

		// Dont use quickmod tools
		$s_quickmod_action = false;
/*
		// Quick mod tools
		$allow_change_type = ($this->auth->acl_get('m_', $forum_id) || ($this->user->data['is_registered'] && $this->user->data['user_id'] == $topic_data['topic_poster'])) ? true : false;

		$s_quickmod_action = append_sid(
			"{$this->root_path}mcp.{$this->php_ext}",
			array(
				'f'	=> $forum_id,
				't'	=> $topic_id,
				'start'		=> $start,
				'quickmod'	=> 1,
				'redirect'	=> urlencode(str_replace('&amp;', '&', $viewtopic_url)),
			),
			true,
			$this->user->session_id
		);

		$quickmod_array = array(
			//	'key'			=> array('LANG_KEY', $userHasPermissions),

			'lock'					=> array('LOCK_TOPIC', ($topic_data['topic_status'] == ITEM_UNLOCKED) && ($this->auth->acl_get('m_lock', $forum_id) || ($this->auth->acl_get('f_user_lock', $forum_id) && $this->user->data['is_registered'] && $this->user->data['user_id'] == $topic_data['topic_poster']))),
			'unlock'				=> array('UNLOCK_TOPIC', ($topic_data['topic_status'] != ITEM_UNLOCKED) && ($this->auth->acl_get('m_lock', $forum_id))),
			'delete_topic'		=> array('DELETE_TOPIC', ($this->auth->acl_get('m_delete', $forum_id) || (($topic_data['topic_visibility'] != ITEM_DELETED) && $this->auth->acl_get('m_softdelete', $forum_id)))),
			'restore_topic'		=> array('RESTORE_TOPIC', (($topic_data['topic_visibility'] == ITEM_DELETED) && $this->auth->acl_get('m_approve', $forum_id))),
			'move'					=> array('MOVE_TOPIC', $this->auth->acl_get('m_move', $forum_id) && $topic_data['topic_status'] != ITEM_MOVED),
			'split'					=> array('SPLIT_TOPIC', $this->auth->acl_get('m_split', $forum_id)),
			'merge'					=> array('MERGE_POSTS', $this->auth->acl_get('m_merge', $forum_id)),
			'merge_topic'		=> array('MERGE_TOPIC', $this->auth->acl_get('m_merge', $forum_id)),
			'fork'					=> array('FORK_TOPIC', $this->auth->acl_get('m_move', $forum_id)),
			'make_normal'		=> array('MAKE_NORMAL', ($allow_change_type && $this->auth->acl_gets('f_sticky', 'f_announce', 'f_announce_global', $forum_id) && $topic_data['topic_type'] != POST_NORMAL)),
			'make_sticky'		=> array('MAKE_STICKY', ($allow_change_type && $this->auth->acl_get('f_sticky', $forum_id) && $topic_data['topic_type'] != POST_STICKY)),
			'make_announce'	=> array('MAKE_ANNOUNCE', ($allow_change_type && $this->auth->acl_get('f_announce', $forum_id) && $topic_data['topic_type'] != POST_ANNOUNCE)),
			'make_global'		=> array('MAKE_GLOBAL', ($allow_change_type && $this->auth->acl_get('f_announce_global', $forum_id) && $topic_data['topic_type'] != POST_GLOBAL)),
			'topic_logs'			=> array('VIEW_TOPIC_LOGS', $this->auth->acl_get('m_', $forum_id)),
		);

		foreach ($quickmod_array as $option => $qm_ary)
		{
			if (!empty($qm_ary[1]))
			{
				phpbb_add_quickmod_option($s_quickmod_action, $option, $qm_ary[0]);
			}
		}
*/

		// Navigation links
		generate_forum_nav($topic_data);

		// Forum Rules
		generate_forum_rules($topic_data);

		// Moderators
		$forum_moderators = array();
		if ($this->config['load_moderators'])
		{
			get_moderators($forum_moderators, $forum_id);
		}

		// This is only used for print view so ...
		$server_path = (!$view) ? $this->root_path : generate_board_url() . '/';

		// Replace naughty words in title
		$topic_data['topic_title'] = censor_text($topic_data['topic_title']);

		$s_search_hidden_fields = array(
			't' => $topic_id,
			'sf' => 'msgonly',
		);
		if (!empty($_SID))
		{
			$s_search_hidden_fields['sid'] = $_SID;
		}

		if (!empty($_EXTRA_URL))
		{
			foreach ($_EXTRA_URL as $url_param)
			{
				$url_param = explode('=', $url_param, 2);
				$s_search_hidden_fields[$url_param[0]] = $url_param[1];
			}
		}

		// TOPIC LOCK CHECK 123
		if ($topic_data['topic_status'] == ITEM_LOCKED)
		{
			$this->template->assign_var('S_CAN_VOTE', false);
		}

		// If we've got a highlight set pass it on to pagination.
		$params = array('idea_id' => $idea_id);
		$params = (strlen($u_sort_param)) ? array_merge($params, explode('=', $u_sort_param)) : $params;
		$params = ($highlight_match) ? array_merge($params, array('hilit' => $highlight)) : $params;
		$this->pagination->generate_template_pagination($this->helper->route('phpbb_ideas_idea_controller', $params), 'pagination', 'start', $total_posts, $this->config['posts_per_page'], $start);

		// Send vars to template
		$this->template->assign_vars(array(
			'FORUM_ID' 		=> $forum_id,
			'FORUM_NAME' 	=> $topic_data['forum_name'],
			'FORUM_DESC'	=> generate_text_for_display($topic_data['forum_desc'], $topic_data['forum_desc_uid'], $topic_data['forum_desc_bitfield'], $topic_data['forum_desc_options']),
			'TOPIC_ID' 		=> $topic_id,
			'TOPIC_TITLE' 	=> $topic_data['topic_title'],
			'TOPIC_POSTER'	=> $topic_data['topic_poster'],

			'TOPIC_AUTHOR_FULL'		=> get_username_string('full', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),
			'TOPIC_AUTHOR_COLOUR'	=> get_username_string('colour', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),
			'TOPIC_AUTHOR'			=> get_username_string('username', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),

			'TOTAL_POSTS'	=> $this->user->lang('VIEW_TOPIC_POSTS', (int) $total_posts),
			'U_MCP' 		=> ($this->auth->acl_get('m_', $forum_id)) ? append_sid("{$this->root_path}mcp.$this->php_ext", "i=main&amp;mode=topic_view&amp;f=$forum_id&amp;t=$topic_id" . (($start == 0) ? '' : "&amp;start=$start") . ((strlen($u_sort_param)) ? "&amp;$u_sort_param" : ''), true, $this->user->session_id) : '',
			'MODERATORS'	=> (isset($forum_moderators[$forum_id]) && sizeof($forum_moderators[$forum_id])) ? implode($this->user->lang['COMMA_SEPARATOR'], $forum_moderators[$forum_id]) : '',

			'POST_IMG' 			=> ($topic_data['forum_status'] == ITEM_LOCKED) ? $this->user->img('button_topic_locked', 'FORUM_LOCKED') : $this->user->img('button_topic_new', 'POST_NEW_TOPIC'),
			'QUOTE_IMG' 		=> $this->user->img('icon_post_quote', 'REPLY_WITH_QUOTE'),
			'REPLY_IMG'			=> ($topic_data['forum_status'] == ITEM_LOCKED || $topic_data['topic_status'] == ITEM_LOCKED) ? $this->user->img('button_topic_locked', 'TOPIC_LOCKED') : $this->user->img('button_topic_reply', 'REPLY_TO_TOPIC'),
			'EDIT_IMG' 			=> $this->user->img('icon_post_edit', 'EDIT_POST'),
			'DELETE_IMG' 		=> $this->user->img('icon_post_delete', 'DELETE_POST'),
			'DELETED_IMG'		=> $this->user->img('icon_topic_deleted', 'POST_DELETED_RESTORE'),
			'INFO_IMG' 			=> $this->user->img('icon_post_info', 'VIEW_INFO'),
			'PROFILE_IMG'		=> $this->user->img('icon_user_profile', 'READ_PROFILE'),
			'SEARCH_IMG' 		=> $this->user->img('icon_user_search', 'SEARCH_USER_POSTS'),
			'PM_IMG' 			=> $this->user->img('icon_contact_pm', 'SEND_PRIVATE_MESSAGE'),
			'EMAIL_IMG' 		=> $this->user->img('icon_contact_email', 'SEND_EMAIL'),
			'JABBER_IMG'		=> $this->user->img('icon_contact_jabber', 'JABBER') ,
			'REPORT_IMG'		=> $this->user->img('icon_post_report', 'REPORT_POST'),
			'REPORTED_IMG'		=> $this->user->img('icon_topic_reported', 'POST_REPORTED'),
			'UNAPPROVED_IMG'	=> $this->user->img('icon_topic_unapproved', 'POST_UNAPPROVED'),
			'WARN_IMG'			=> $this->user->img('icon_user_warn', 'WARN_USER'),

			'S_IS_LOCKED'			=> ($topic_data['topic_status'] == ITEM_UNLOCKED && $topic_data['forum_status'] == ITEM_UNLOCKED) ? false : true,
			'S_SELECT_SORT_DIR' 	=> $s_sort_dir,
			'S_SELECT_SORT_KEY' 	=> $s_sort_key,
			'S_SELECT_SORT_DAYS' 	=> $s_limit_days,
			'S_SINGLE_MODERATOR'	=> (!empty($forum_moderators[$forum_id]) && sizeof($forum_moderators[$forum_id]) > 1) ? false : true,
			'S_TOPIC_ACTION' 		=> $this->helper->route('phpbb_ideas_idea_controller', array('idea_id' => $idea_id, 'start' => $start)),
			'S_MOD_ACTION' 			=> $s_quickmod_action,

			'L_RETURN_TO_FORUM'		=> $this->user->lang('RETURN_TO', $topic_data['forum_name']),
			'S_VIEWTOPIC'			=> true,
			'S_UNREAD_VIEW'			=> $view == 'unread',
			'S_DISPLAY_SEARCHBOX'	=> ($this->auth->acl_get('u_search') && $this->auth->acl_get('f_search', $forum_id) && $this->config['load_search']) ? true : false,
			'S_SEARCHBOX_ACTION'	=> append_sid("{$this->root_path}search.$this->php_ext"),
			'S_SEARCH_LOCAL_HIDDEN_FIELDS'	=> build_hidden_fields($s_search_hidden_fields),

			'S_DISPLAY_POST_INFO'	=> ($topic_data['forum_type'] == FORUM_POST && ($this->auth->acl_get('f_post', $forum_id) || $this->user->data['user_id'] == ANONYMOUS)) ? true : false,
			'S_DISPLAY_REPLY_INFO'	=> ($topic_data['forum_type'] == FORUM_POST && ($this->auth->acl_get('f_reply', $forum_id) || $this->user->data['user_id'] == ANONYMOUS)) ? true : false,
			'S_ENABLE_FEEDS_TOPIC'	=> ($this->config['feed_topic'] && !phpbb_optionget(FORUM_OPTION_FEED_EXCLUDE, $topic_data['forum_options'])) ? true : false,

			'U_TOPIC'				=> "{$server_path}viewtopic.$this->php_ext?f=$forum_id&amp;t=$topic_id",
			'U_FORUM'				=> $server_path,
			'U_VIEW_TOPIC' 			=> $viewtopic_url,
			'U_CANONICAL'			=> generate_board_url() . '/' . append_sid("viewtopic.{$this->php_ext}", "t=$topic_id" . (($start) ? "&amp;start=$start" : ''), true, ''),
			'U_VIEW_FORUM' 			=> append_sid("{$this->root_path}viewforum.$this->php_ext", 'f=' . $forum_id),
			'U_VIEW_OLDER_TOPIC'	=> $this->helper->route('phpbb_ideas_idea_controller', array('idea_id' => $idea_id, 'view' => 'previous')),
			'U_VIEW_NEWER_TOPIC'	=> $this->helper->route('phpbb_ideas_idea_controller', array('idea_id' => $idea_id, 'view' => 'next')),
			'U_PRINT_TOPIC'			=> false,
			'U_EMAIL_TOPIC'			=> false,

			'U_WATCH_TOPIC'			=> $s_watching_topic['link'],
			'U_WATCH_TOPIC_TOGGLE'	=> $s_watching_topic['link_toggle'],
			'S_WATCH_TOPIC_TITLE'	=> $s_watching_topic['title'],
			'S_WATCH_TOPIC_TOGGLE'	=> $s_watching_topic['title_toggle'],
			'S_WATCHING_TOPIC'		=> $s_watching_topic['is_watching'],

			'U_BOOKMARK_TOPIC'		=> ($this->user->data['is_registered'] && $this->config['allow_bookmarks']) ? append_sid($viewtopic_url, array('bookmark' => 1, 'hash' => generate_link_hash("topic_$topic_id"))) : '',
			'S_BOOKMARK_TOPIC'		=> ($this->user->data['is_registered'] && $this->config['allow_bookmarks'] && $topic_data['bookmarked']) ? $this->user->lang('BOOKMARK_TOPIC_REMOVE') : $this->user->lang('BOOKMARK_TOPIC'),
			'S_BOOKMARK_TOGGLE'		=> (!$this->user->data['is_registered'] || !$this->config['allow_bookmarks'] || !$topic_data['bookmarked']) ? $this->user->lang['BOOKMARK_TOPIC_REMOVE'] : $this->user->lang['BOOKMARK_TOPIC'],
			'S_BOOKMARKED_TOPIC'	=> ($this->user->data['is_registered'] && $this->config['allow_bookmarks'] && $topic_data['bookmarked']) ? true : false,

			'U_POST_NEW_TOPIC' 		=> ($this->auth->acl_get('f_post', $forum_id) || $this->user->data['user_id'] == ANONYMOUS) ? append_sid("{$this->root_path}posting.$this->php_ext", "mode=post&amp;f=$forum_id") : '',
			'U_POST_REPLY_TOPIC' 	=> ($this->auth->acl_get('f_reply', $forum_id) || $this->user->data['user_id'] == ANONYMOUS) ? append_sid("{$this->root_path}posting.$this->php_ext", "mode=reply&amp;f=$forum_id&amp;t=$topic_id") : '',
			'U_BUMP_TOPIC'			=> (bump_topic_allowed($forum_id, $topic_data['topic_bumped'], $topic_data['topic_last_post_time'], $topic_data['topic_poster'], $topic_data['topic_last_poster_id'])) ? append_sid("{$this->root_path}posting.$this->php_ext", "mode=bump&amp;f=$forum_id&amp;t=$topic_id&amp;hash=" . generate_link_hash("topic_$topic_id")) : '')
		);

		// If the user is trying to reach the second half of the topic, fetch it starting from the end
		$store_reverse = false;
		$sql_limit = $this->config['posts_per_page'];
		$sql_sort_order = $direction = '';

		if ($start > $total_posts / 2)
		{
			$store_reverse = true;

			// Select the sort order
			$direction = (($sort_dir == 'd') ? 'ASC' : 'DESC');

			$sql_limit = $this->pagination->reverse_limit($start, $sql_limit, $total_posts);
			$sql_start = $this->pagination->reverse_start($start, $sql_limit, $total_posts);
		}
		else
		{
			// Select the sort order
			$direction = (($sort_dir == 'd') ? 'DESC' : 'ASC');
			$sql_start = $start;
		}

		if (is_array($sort_by_sql[$sort_key]))
		{
			$sql_sort_order = implode(' ' . $direction . ', ', $sort_by_sql[$sort_key]) . ' ' . $direction;
		}
		else
		{
			$sql_sort_order = $sort_by_sql[$sort_key] . ' ' . $direction;
		}

		// Container for user details, only process once
		$post_list = $user_cache = $id_cache = $attachments = $attach_list = $rowset = $update_count = $post_edit_list = $post_delete_list = array();
		$has_unapproved_attachments = $has_approved_attachments = $display_notice = false;
		$i = $i_total = 0;

		// Go ahead and pull all data for this topic
		$sql = 'SELECT p.post_id
			FROM ' . POSTS_TABLE . ' p' . (($join_user_sql[$sort_key]) ? ', ' . USERS_TABLE . ' u': '') . "
			WHERE p.topic_id = $topic_id
				AND " . $this->content_visibility->get_visibility_sql('post', $forum_id, 'p.') . "
				" . (($join_user_sql[$sort_key]) ? 'AND u.user_id = p.poster_id': '') . "
				$limit_posts_time
			ORDER BY $sql_sort_order";
		$result = $this->db->sql_query_limit($sql, $sql_limit, $sql_start);

		$i = ($store_reverse) ? $sql_limit - 1 : 0;
		while ($row = $this->db->sql_fetchrow($result))
		{
			$post_list[$i] = (int) $row['post_id'];
			($store_reverse) ? $i-- : $i++;
		}
		$this->db->sql_freeresult($result);

		if (!sizeof($post_list))
		{
			if ($sort_days)
			{
				throw new http_exception(404, 'NO_POSTS_TIME_FRAME');
			}
			else
			{
				throw new http_exception(404, 'NO_TOPIC');
			}
		}

		// Holding maximum post time for marking topic read
		// We need to grab it because we do reverse ordering sometimes
		$max_post_time = 0;

		$sql_ary = array(
			'SELECT'	=> 'u.*, z.friend, z.foe, p.*',

			'FROM'		=> array(
				USERS_TABLE		=> 'u',
				POSTS_TABLE		=> 'p',
			),

			'LEFT_JOIN'	=> array(
				array(
					'FROM'	=> array(ZEBRA_TABLE => 'z'),
					'ON'	=> 'z.user_id = ' . $this->user->data['user_id'] . ' AND z.zebra_id = p.poster_id',
				),
			),

			'WHERE'		=> $this->db->sql_in_set('p.post_id', $post_list) . '
				AND u.user_id = p.poster_id',
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);

		$now = $this->user->create_datetime();
		$now = phpbb_gmgetdate($now->getTimestamp() + $now->getOffset());

		// Posts are stored in the $rowset array while $attach_list, $user_cache
		// and the global bbcode_bitfield are built
		while ($row = $this->db->sql_fetchrow($result))
		{
			// Set max_post_time
			if ($row['post_time'] > $max_post_time)
			{
				$max_post_time = $row['post_time'];
			}

			$poster_id = (int) $row['poster_id'];

			// Does post have an attachment? If so, add it to the list
			if ($row['post_attachment'] && $this->config['allow_attachments'])
			{
				$attach_list[] = (int) $row['post_id'];

				if ($row['post_visibility'] == ITEM_UNAPPROVED || $row['post_visibility'] == ITEM_REAPPROVE)
				{
					$has_unapproved_attachments = true;
				}
				else if ($row['post_visibility'] == ITEM_APPROVED)
				{
					$has_approved_attachments = true;
				}
			}

			$rowset[$row['post_id']] = array(
				'hide_post'			=> (($row['foe'] || $row['post_visibility'] == ITEM_DELETED) && ($view != 'show' || $post_id != $row['post_id'])) ? true : false,

				'post_id'			=> $row['post_id'],
				'post_time'			=> $row['post_time'],
				'user_id'			=> $row['user_id'],
				'username'			=> $row['username'],
				'user_colour'		=> $row['user_colour'],
				'topic_id'			=> $row['topic_id'],
				'forum_id'			=> $row['forum_id'],
				'post_subject'		=> $row['post_subject'],
				'post_edit_count'	=> $row['post_edit_count'],
				'post_edit_time'	=> $row['post_edit_time'],
				'post_edit_reason'	=> $row['post_edit_reason'],
				'post_edit_user'	=> $row['post_edit_user'],
				'post_edit_locked'	=> $row['post_edit_locked'],
				'post_delete_time'	=> $row['post_delete_time'],
				'post_delete_reason'=> $row['post_delete_reason'],
				'post_delete_user'	=> $row['post_delete_user'],

				// Make sure the icon actually exists
				'icon_id'			=> (isset($icons[$row['icon_id']]['img'], $icons[$row['icon_id']]['height'], $icons[$row['icon_id']]['width'])) ? $row['icon_id'] : 0,
				'post_attachment'	=> $row['post_attachment'],
				'post_visibility'	=> $row['post_visibility'],
				'post_reported'		=> $row['post_reported'],
				'post_username'		=> $row['post_username'],
				'post_text'			=> $row['post_text'],
				'bbcode_uid'		=> $row['bbcode_uid'],
				'bbcode_bitfield'	=> $row['bbcode_bitfield'],
				'enable_smilies'	=> $row['enable_smilies'],
				'enable_sig'		=> $row['enable_sig'],
				'friend'			=> $row['friend'],
				'foe'				=> $row['foe'],
			);

			// Cache various user specific data ... so we don't have to recompute
			// this each time the same user appears on this page
			if (!isset($user_cache[$poster_id]))
			{
				if ($poster_id == ANONYMOUS)
				{
					$user_cache[$poster_id] = array(
						'user_type'		=> USER_IGNORE,
						'joined'		=> '',
						'posts'			=> '',

						'sig'					=> '',
						'sig_bbcode_uid'		=> '',
						'sig_bbcode_bitfield'	=> '',

						'online'			=> false,
						'avatar'			=> ($this->user->optionget('viewavatars')) ? get_user_avatar($row['user_avatar'], $row['user_avatar_type'], $row['user_avatar_width'], $row['user_avatar_height']) : '',
						'rank_title'		=> '',
						'rank_image'		=> '',
						'rank_image_src'	=> '',
						'sig'				=> '',
						'pm'				=> '',
						'email'				=> '',
						'jabber'			=> '',
						'search'			=> '',
						'age'				=> '',

						'username'			=> $row['username'],
						'user_colour'		=> $row['user_colour'],
						'contact_user'		=> '',

						'warnings'			=> 0,
						'allow_pm'			=> 0,
					);

					$user_rank_data = phpbb_get_user_rank($row, false);
					$user_cache[$poster_id]['rank_title'] = $user_rank_data['title'];
					$user_cache[$poster_id]['rank_image'] = $user_rank_data['img'];
					$user_cache[$poster_id]['rank_image_src'] = $user_rank_data['img_src'];
				}
				else
				{
					$user_sig = '';

					// We add the signature to every posters entry because enable_sig is post dependent
					if ($row['user_sig'] && $this->config['allow_sig'] && $this->user->optionget('viewsigs'))
					{
						$user_sig = $row['user_sig'];
					}

					$id_cache[] = $poster_id;

					$user_cache[$poster_id] = array(
						'user_type'					=> $row['user_type'],
						'user_inactive_reason'		=> $row['user_inactive_reason'],

						'joined'		=> $this->user->format_date($row['user_regdate']),
						'posts'			=> $row['user_posts'],
						'warnings'		=> (isset($row['user_warnings'])) ? $row['user_warnings'] : 0,

						'sig'					=> $user_sig,
						'sig_bbcode_uid'		=> (!empty($row['user_sig_bbcode_uid'])) ? $row['user_sig_bbcode_uid'] : '',
						'sig_bbcode_bitfield'	=> (!empty($row['user_sig_bbcode_bitfield'])) ? $row['user_sig_bbcode_bitfield'] : '',

						'viewonline'	=> $row['user_allow_viewonline'],
						'allow_pm'		=> $row['user_allow_pm'],

						'avatar'		=> ($this->user->optionget('viewavatars')) ? get_user_avatar($row['user_avatar'], $row['user_avatar_type'], $row['user_avatar_width'], $row['user_avatar_height']) : '',
						'age'			=> '',

						'rank_title'		=> '',
						'rank_image'		=> '',
						'rank_image_src'	=> '',

						'username'			=> $row['username'],
						'user_colour'		=> $row['user_colour'],
						'contact_user' 		=> $this->user->lang('CONTACT_USER', get_username_string('username', $poster_id, $row['username'], $row['user_colour'], $row['username'])),

						'online'		=> false,
						'jabber'		=> ($this->config['jab_enable'] && $row['user_jabber'] && $this->auth->acl_get('u_sendim')) ? append_sid("{$this->root_path}memberlist.{$this->php_ext}", "mode=contact&amp;action=jabber&amp;u=$poster_id") : '',
						'search'		=> ($this->config['load_search'] && $this->auth->acl_get('u_search')) ? append_sid("{$this->root_path}search.{$this->php_ext}", "author_id=$poster_id&amp;sr=posts") : '',

						'author_full'		=> get_username_string('full', $poster_id, $row['username'], $row['user_colour']),
						'author_colour'		=> get_username_string('colour', $poster_id, $row['username'], $row['user_colour']),
						'author_username'	=> get_username_string('username', $poster_id, $row['username'], $row['user_colour']),
						'author_profile'	=> get_username_string('profile', $poster_id, $row['username'], $row['user_colour']),
					);

					$user_rank_data = phpbb_get_user_rank($row, $row['user_posts']);
					$user_cache[$poster_id]['rank_title'] = $user_rank_data['title'];
					$user_cache[$poster_id]['rank_image'] = $user_rank_data['img'];
					$user_cache[$poster_id]['rank_image_src'] = $user_rank_data['img_src'];

					if ((!empty($row['user_allow_viewemail']) && $this->auth->acl_get('u_sendemail')) || $this->auth->acl_get('a_email'))
					{
						$user_cache[$poster_id]['email'] = ($this->config['board_email_form'] && $this->config['email_enable']) ? append_sid("{$this->root_path}memberlist.$this->php_ext", "mode=email&amp;u=$poster_id") : (($this->config['board_hide_emails'] && !$this->auth->acl_get('a_email')) ? '' : 'mailto:' . $row['user_email']);
					}
					else
					{
						$user_cache[$poster_id]['email'] = '';
					}

					if ($this->config['allow_birthdays'] && !empty($row['user_birthday']))
					{
						list($bday_day, $bday_month, $bday_year) = array_map('intval', explode('-', $row['user_birthday']));

						if ($bday_year)
						{
							$diff = $now['mon'] - $bday_month;
							if ($diff == 0)
							{
								$diff = ($now['mday'] - $bday_day < 0) ? 1 : 0;
							}
							else
							{
								$diff = ($diff < 0) ? 1 : 0;
							}

							$user_cache[$poster_id]['age'] = (int) ($now['year'] - $bday_year - $diff);
						}
					}
				}
			}
		}
		$this->db->sql_freeresult($result);

		// Load custom profile fields
		if ($this->config['load_cpf_viewtopic'])
		{
			// Grab all profile fields from users in id cache for later use - similar to the poster cache
			$profile_fields_tmp = $this->cp->grab_profile_fields_data($id_cache);

			// filter out fields not to be displayed on viewtopic. Yes, it's a hack, but this shouldn't break any MODs.
			$profile_fields_cache = array();
			foreach ($profile_fields_tmp as $profile_user_id => $profile_fields)
			{
				$profile_fields_cache[$profile_user_id] = array();
				foreach ($profile_fields as $used_ident => $profile_field)
				{
					if ($profile_field['data']['field_show_on_vt'])
					{
						$profile_fields_cache[$profile_user_id][$used_ident] = $profile_field;
					}
				}
			}
			unset($profile_fields_tmp);
		}

		// Generate online information for user
		if ($this->config['load_onlinetrack'] && sizeof($id_cache))
		{
			$sql = 'SELECT session_user_id, MAX(session_time) as online_time, MIN(session_viewonline) AS viewonline
				FROM ' . SESSIONS_TABLE . '
				WHERE ' . $this->db->sql_in_set('session_user_id', $id_cache) . '
				GROUP BY session_user_id';
			$result = $this->db->sql_query($sql);

			$update_time = $this->config['load_online_time'] * 60;
			while ($row = $this->db->sql_fetchrow($result))
			{
				$user_cache[$row['session_user_id']]['online'] = (time() - $update_time < $row['online_time'] && (($row['viewonline']) || $this->auth->acl_get('u_viewonline'))) ? true : false;
			}
			$this->db->sql_freeresult($result);
		}
		unset($id_cache);

		// Pull attachment data
		if (sizeof($attach_list))
		{
			if ($this->auth->acl_get('u_download') && $this->auth->acl_get('f_download', $forum_id))
			{
				$sql = 'SELECT *
					FROM ' . ATTACHMENTS_TABLE . '
					WHERE ' . $this->db->sql_in_set('post_msg_id', $attach_list) . '
						AND in_message = 0
					ORDER BY filetime DESC, post_msg_id ASC';
				$result = $this->db->sql_query($sql);

				while ($row = $this->db->sql_fetchrow($result))
				{
					$attachments[$row['post_msg_id']][] = $row;
				}
				$this->db->sql_freeresult($result);

				// No attachments exist, but post table thinks they do so go ahead and reset post_attach flags
				if (!sizeof($attachments))
				{
					$sql = 'UPDATE ' . POSTS_TABLE . '
						SET post_attachment = 0
						WHERE ' . $this->db->sql_in_set('post_id', $attach_list);
					$this->db->sql_query($sql);

					// We need to update the topic indicator too if the complete topic is now without an attachment
					if (sizeof($rowset) != $total_posts)
					{
						// Not all posts are displayed so we query the db to find if there's any attachment for this topic
						$sql = 'SELECT a.post_msg_id as post_id
							FROM ' . ATTACHMENTS_TABLE . ' a, ' . POSTS_TABLE . " p
							WHERE p.topic_id = $topic_id
								AND p.post_visibility = " . ITEM_APPROVED . '
								AND p.topic_id = a.topic_id';
						$result = $this->db->sql_query_limit($sql, 1);
						$row = $this->db->sql_fetchrow($result);
						$this->db->sql_freeresult($result);

						if (!$row)
						{
							$sql = 'UPDATE ' . TOPICS_TABLE . "
								SET topic_attachment = 0
								WHERE topic_id = $topic_id";
							$this->db->sql_query($sql);
						}
					}
					else
					{
						$sql = 'UPDATE ' . TOPICS_TABLE . "
							SET topic_attachment = 0
							WHERE topic_id = $topic_id";
						$this->db->sql_query($sql);
					}
				}
				else if ($has_approved_attachments && !$topic_data['topic_attachment'])
				{
					// Topic has approved attachments but its flag is wrong
					$sql = 'UPDATE ' . TOPICS_TABLE . "
						SET topic_attachment = 1
						WHERE topic_id = $topic_id";
					$this->db->sql_query($sql);

					$topic_data['topic_attachment'] = 1;
				}
				else if ($has_unapproved_attachments && !$topic_data['topic_attachment'])
				{
					// Topic has only unapproved attachments but we have the right to see and download them
					$topic_data['topic_attachment'] = 1;
				}
			}
			else
			{
				$display_notice = true;
			}
		}

		// Get the list of users who can receive private messages
		$can_receive_pm_list = $this->auth->acl_get_list(array_keys($user_cache), 'u_readpm');
		$can_receive_pm_list = (empty($can_receive_pm_list) || !isset($can_receive_pm_list[0]['u_readpm'])) ? array() : $can_receive_pm_list[0]['u_readpm'];

		// Get the list of permanently banned users
		$permanently_banned_users = phpbb_get_banned_user_ids(array_keys($user_cache), false);

		$i_total = sizeof($rowset) - 1;
		$prev_post_id = '';

		$this->template->assign_vars(array(
			'S_HAS_ATTACHMENTS' => $topic_data['topic_attachment'],
			'S_NUM_POSTS' => sizeof($post_list))
		);

		// Output the posts
		$first_unread = $post_unread = false;
		for ($i = 0, $end = sizeof($post_list); $i < $end; ++$i)
		{
			// A non-existing rowset only happens if there was no user present for the entered poster_id
			// This could be a broken posts table.
			if (!isset($rowset[$post_list[$i]]))
			{
				continue;
			}

			$row = $rowset[$post_list[$i]];
			$poster_id = $row['user_id'];

			// End signature parsing, only if needed
			if ($user_cache[$poster_id]['sig'] && $row['enable_sig'] && empty($user_cache[$poster_id]['sig_parsed']))
			{
				$parse_flags = ($user_cache[$poster_id]['sig_bbcode_bitfield'] ? OPTION_FLAG_BBCODE : 0) | OPTION_FLAG_SMILIES;
				$user_cache[$poster_id]['sig'] = generate_text_for_display($user_cache[$poster_id]['sig'], $user_cache[$poster_id]['sig_bbcode_uid'], $user_cache[$poster_id]['sig_bbcode_bitfield'],  $parse_flags, true);
			}

			// Parse the message and subject
			$parse_flags = ($row['bbcode_bitfield'] ? OPTION_FLAG_BBCODE : 0) | OPTION_FLAG_SMILIES;
			$message = generate_text_for_display($row['post_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], $parse_flags, true);

			// This freakish looking regex pattern should
			// remove the ideas link-backs from the message.
			$message = preg_replace('/(<br[^>]*>\\n?)\\1-{10}\\1\\1.*/s', '', $message);

			if (!empty($attachments[$row['post_id']]))
			{
				parse_attachments($forum_id, $message, $attachments[$row['post_id']], $update_count);
			}

			// Replace naughty words such as farty pants
			$row['post_subject'] = censor_text($row['post_subject']);

			// Highlight active words (primarily for search)
			if ($highlight_match)
			{
				$message = preg_replace('#(?!<.*)(?<!\w)(' . $highlight_match . ')(?!\w|[^<>]*(?:</s(?:cript|tyle))?>)#is', '<span class="posthilit">\1</span>', $message);
				$row['post_subject'] = preg_replace('#(?!<.*)(?<!\w)(' . $highlight_match . ')(?!\w|[^<>]*(?:</s(?:cript|tyle))?>)#is', '<span class="posthilit">\1</span>', $row['post_subject']);
			}

			// Editing information
			if (($row['post_edit_count'] && $this->config['display_last_edited']) || $row['post_edit_reason'])
			{
				// Get usernames for all following posts if not already stored
				if (!sizeof($post_edit_list) && ($row['post_edit_reason'] || ($row['post_edit_user'] && !isset($user_cache[$row['post_edit_user']]))))
				{
					// Remove all post_ids already parsed (we do not have to check them)
					$post_storage_list = (!$store_reverse) ? array_slice($post_list, $i) : array_slice(array_reverse($post_list), $i);

					$sql = 'SELECT DISTINCT u.user_id, u.username, u.user_colour
						FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
						WHERE ' . $this->db->sql_in_set('p.post_id', $post_storage_list) . '
							AND p.post_edit_count <> 0
							AND p.post_edit_user <> 0
							AND p.post_edit_user = u.user_id';
					$result2 = $this->db->sql_query($sql);
					while ($user_edit_row = $this->db->sql_fetchrow($result2))
					{
						$post_edit_list[$user_edit_row['user_id']] = $user_edit_row;
					}
					$this->db->sql_freeresult($result2);

					unset($post_storage_list);
				}

				if ($row['post_edit_reason'])
				{
					// User having edited the post also being the post author?
					if (!$row['post_edit_user'] || $row['post_edit_user'] == $poster_id)
					{
						$display_username = get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username']);
					}
					else
					{
						$display_username = get_username_string('full', $row['post_edit_user'], $post_edit_list[$row['post_edit_user']]['username'], $post_edit_list[$row['post_edit_user']]['user_colour']);
					}

					$l_edited_by = $this->user->lang('EDITED_TIMES_TOTAL', (int) $row['post_edit_count'], $display_username, $this->user->format_date($row['post_edit_time'], false, true));
				}
				else
				{
					if ($row['post_edit_user'] && !isset($user_cache[$row['post_edit_user']]))
					{
						$user_cache[$row['post_edit_user']] = $post_edit_list[$row['post_edit_user']];
					}

					// User having edited the post also being the post author?
					if (!$row['post_edit_user'] || $row['post_edit_user'] == $poster_id)
					{
						$display_username = get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username']);
					}
					else
					{
						$display_username = get_username_string('full', $row['post_edit_user'], $user_cache[$row['post_edit_user']]['username'], $user_cache[$row['post_edit_user']]['user_colour']);
					}

					$l_edited_by = $this->user->lang('EDITED_TIMES_TOTAL', (int) $row['post_edit_count'], $display_username, $this->user->format_date($row['post_edit_time'], false, true));
				}
			}
			else
			{
				$l_edited_by = '';
			}

			// Deleting information
			if ($row['post_visibility'] == ITEM_DELETED && $row['post_delete_user'])
			{
				// Get usernames for all following posts if not already stored
				if (!sizeof($post_delete_list) && ($row['post_delete_reason'] || ($row['post_delete_user'] && !isset($user_cache[$row['post_delete_user']]))))
				{
					// Remove all post_ids already parsed (we do not have to check them)
					$post_storage_list = (!$store_reverse) ? array_slice($post_list, $i) : array_slice(array_reverse($post_list), $i);

					$sql = 'SELECT DISTINCT u.user_id, u.username, u.user_colour
						FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
						WHERE ' . $this->db->sql_in_set('p.post_id', $post_storage_list) . '
							AND p.post_delete_user <> 0
							AND p.post_delete_user = u.user_id';
					$result2 = $this->db->sql_query($sql);
					while ($user_delete_row = $this->db->sql_fetchrow($result2))
					{
						$post_delete_list[$user_delete_row['user_id']] = $user_delete_row;
					}
					$this->db->sql_freeresult($result2);

					unset($post_storage_list);
				}

				if ($row['post_delete_user'] && !isset($user_cache[$row['post_delete_user']]))
				{
					$user_cache[$row['post_delete_user']] = $post_delete_list[$row['post_delete_user']];
				}

				$display_postername = get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username']);

				// User having deleted the post also being the post author?
				if (!$row['post_delete_user'] || $row['post_delete_user'] == $poster_id)
				{
					$display_username = $display_postername;
				}
				else
				{
					$display_username = get_username_string('full', $row['post_delete_user'], $user_cache[$row['post_delete_user']]['username'], $user_cache[$row['post_delete_user']]['user_colour']);
				}

				if ($row['post_delete_reason'])
				{
					$l_deleted_message = $this->user->lang('POST_DELETED_BY_REASON', $display_postername, $display_username, $this->user->format_date($row['post_delete_time'], false, true), $row['post_delete_reason']);
				}
				else
				{
					$l_deleted_message = $this->user->lang('POST_DELETED_BY', $display_postername, $display_username, $this->user->format_date($row['post_delete_time'], false, true));
				}
				$l_deleted_by = $this->user->lang('DELETED_INFORMATION', $display_username, $this->user->format_date($row['post_delete_time'], false, true));
			}
			else
			{
				$l_deleted_by = $l_deleted_message = '';
			}

			// Bump information
			if ($topic_data['topic_bumped'] && $row['post_id'] == $topic_data['topic_last_post_id'] && isset($user_cache[$topic_data['topic_bumper']]) )
			{
				// It is safe to grab the username from the user cache array, we are at the last
				// post and only the topic poster and last poster are allowed to bump.
				// Admins and mods are bound to the above rules too...
				$l_bumped_by = $this->user->lang('BUMPED_BY', $user_cache[$topic_data['topic_bumper']]['username'], $this->user->format_date($topic_data['topic_last_post_time'], false, true));
			}
			else
			{
				$l_bumped_by = '';
			}

			$cp_row = array();

			//
			if ($this->config['load_cpf_viewtopic'])
			{
				$cp_row = (isset($profile_fields_cache[$poster_id])) ? $this->cp->generate_profile_fields_template_data($profile_fields_cache[$poster_id]) : array();
			}

			$post_unread = (isset($topic_tracking_info[$topic_id]) && $row['post_time'] > $topic_tracking_info[$topic_id]) ? true : false;

			$s_first_unread = false;
			if (!$first_unread && $post_unread)
			{
				$s_first_unread = $first_unread = true;
			}

			$force_edit_allowed = $force_delete_allowed = false;

			$s_cannot_edit = !$this->auth->acl_get('f_edit', $forum_id) || $this->user->data['user_id'] != $poster_id;
			$s_cannot_edit_time = $this->config['edit_time'] && $row['post_time'] <= time() - ($this->config['edit_time'] * 60);
			$s_cannot_edit_locked = $topic_data['topic_status'] == ITEM_LOCKED || $row['post_edit_locked'];

			$s_cannot_delete = $this->user->data['user_id'] != $poster_id || (
				!$this->auth->acl_get('f_delete', $forum_id) &&
				(!$this->auth->acl_get('f_softdelete', $forum_id) || $row['post_visibility'] == ITEM_DELETED)
			);
			$s_cannot_delete_lastpost = $topic_data['topic_last_post_id'] != $row['post_id'];
			$s_cannot_delete_time = $this->config['delete_time'] && $row['post_time'] <= time() - ($this->config['delete_time'] * 60);
			// we do not want to allow removal of the last post if a moderator locked it!
			$s_cannot_delete_locked = $topic_data['topic_status'] == ITEM_LOCKED || $row['post_edit_locked'];

			$edit_allowed = $force_edit_allowed || ($this->user->data['is_registered'] && ($this->auth->acl_get('m_edit', $forum_id) || (
				!$s_cannot_edit &&
				!$s_cannot_edit_time &&
				!$s_cannot_edit_locked
			)));

			$quote_allowed = $this->auth->acl_get('m_edit', $forum_id) || ($topic_data['topic_status'] != ITEM_LOCKED &&
				($this->user->data['user_id'] == ANONYMOUS || $this->auth->acl_get('f_reply', $forum_id))
			);

			$delete_allowed = $force_delete_allowed || ($this->user->data['is_registered'] && (
				($this->auth->acl_get('m_delete', $forum_id) || ($this->auth->acl_get('m_softdelete', $forum_id) && $row['post_visibility'] != ITEM_DELETED)) ||
				(!$s_cannot_delete && !$s_cannot_delete_lastpost && !$s_cannot_delete_time && !$s_cannot_delete_locked)
			));

			$softdelete_allowed = ($this->auth->acl_get('m_softdelete', $forum_id) ||
				($this->auth->acl_get('f_softdelete', $forum_id) && $this->user->data['user_id'] == $poster_id)) && ($row['post_visibility'] != ITEM_DELETED);

			$permanent_delete_allowed = ($this->auth->acl_get('m_delete', $forum_id) ||
				($this->auth->acl_get('f_delete', $forum_id) && $this->user->data['user_id'] == $poster_id));

			// Can this user receive a Private Message?
			$can_receive_pm = (
				// They must be a "normal" user
				$user_cache[$poster_id]['user_type'] != USER_IGNORE &&

				// They must not be deactivated by the administrator
				($user_cache[$poster_id]['user_type'] != USER_INACTIVE || $user_cache[$poster_id]['user_inactive_reason'] != INACTIVE_MANUAL) &&

				// They must be able to read PMs
				in_array($poster_id, $can_receive_pm_list) &&

				// They must not be permanently banned
				!in_array($poster_id, $permanently_banned_users) &&

				// They must allow users to contact via PM
				(($this->auth->acl_gets('a_', 'm_') || $this->auth->acl_getf_global('m_')) || $user_cache[$poster_id]['allow_pm'])
			);

			$u_pm = '';

			if ($this->config['allow_privmsg'] && $this->auth->acl_get('u_sendpm') && $can_receive_pm)
			{
				$u_pm = append_sid("{$this->root_path}ucp.{$this->php_ext}", 'i=pm&amp;mode=compose&amp;action=quotepost&amp;p=' . $row['post_id']);
			}

			//
			$postrow = array(
				'POST_AUTHOR_FULL'		=> ($poster_id != ANONYMOUS) ? $user_cache[$poster_id]['author_full'] : get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username']),
				'POST_AUTHOR_COLOUR'	=> ($poster_id != ANONYMOUS) ? $user_cache[$poster_id]['author_colour'] : get_username_string('colour', $poster_id, $row['username'], $row['user_colour'], $row['post_username']),
				'POST_AUTHOR'			=> ($poster_id != ANONYMOUS) ? $user_cache[$poster_id]['author_username'] : get_username_string('username', $poster_id, $row['username'], $row['user_colour'], $row['post_username']),
				'U_POST_AUTHOR'			=> ($poster_id != ANONYMOUS) ? $user_cache[$poster_id]['author_profile'] : get_username_string('profile', $poster_id, $row['username'], $row['user_colour'], $row['post_username']),

				'RANK_TITLE'		=> $user_cache[$poster_id]['rank_title'],
				'RANK_IMG'			=> $user_cache[$poster_id]['rank_image'],
				'RANK_IMG_SRC'		=> $user_cache[$poster_id]['rank_image_src'],
				'POSTER_JOINED'		=> $user_cache[$poster_id]['joined'],
				'POSTER_POSTS'		=> $user_cache[$poster_id]['posts'],
				'POSTER_AVATAR'		=> $user_cache[$poster_id]['avatar'],
				'POSTER_WARNINGS'	=> $this->auth->acl_get('m_warn') ? $user_cache[$poster_id]['warnings'] : '',
				'POSTER_AGE'		=> $user_cache[$poster_id]['age'],
				'CONTACT_USER'		=> $user_cache[$poster_id]['contact_user'],

				'POST_DATE'			=> $this->user->format_date($row['post_time'], false, ($view == 'print') ? true : false),
				'POST_SUBJECT'		=> $row['post_subject'],
				'MESSAGE'			=> $message,
				'SIGNATURE'			=> ($row['enable_sig']) ? $user_cache[$poster_id]['sig'] : '',
				'EDITED_MESSAGE'	=> $l_edited_by,
				'EDIT_REASON'		=> $row['post_edit_reason'],
				'DELETED_MESSAGE'	=> $l_deleted_by,
				'DELETE_REASON'		=> $row['post_delete_reason'],
				'BUMPED_MESSAGE'	=> $l_bumped_by,

				'MINI_POST_IMG'			=> ($post_unread) ? $this->user->img('icon_post_target_unread', 'UNREAD_POST') : $this->user->img('icon_post_target', 'POST'),
				'POST_ICON_IMG'			=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['img'] : '',
				'POST_ICON_IMG_WIDTH'	=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['width'] : '',
				'POST_ICON_IMG_HEIGHT'	=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['height'] : '',
				'POST_ICON_IMG_ALT' 	=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['alt'] : '',
				'ONLINE_IMG'			=> ($poster_id == ANONYMOUS || !$this->config['load_onlinetrack']) ? '' : (($user_cache[$poster_id]['online']) ? $this->user->img('icon_user_online', 'ONLINE') : $this->user->img('icon_user_offline', 'OFFLINE')),
				'S_ONLINE'				=> ($poster_id == ANONYMOUS || !$this->config['load_onlinetrack']) ? false : (($user_cache[$poster_id]['online']) ? true : false),

				'U_EDIT'			=> ($edit_allowed) ? append_sid("{$this->root_path}posting.$this->php_ext", "mode=edit&amp;f=$forum_id&amp;p={$row['post_id']}") : '',
				'U_QUOTE'			=> ($quote_allowed) ? append_sid("{$this->root_path}posting.$this->php_ext", "mode=quote&amp;f=$forum_id&amp;p={$row['post_id']}") : '',
				'U_INFO'			=> ($this->auth->acl_get('m_info', $forum_id)) ? append_sid("{$this->root_path}mcp.$this->php_ext", "i=main&amp;mode=post_details&amp;f=$forum_id&amp;p=" . $row['post_id'], true, $this->user->session_id) : '',
				'U_DELETE'			=> ($delete_allowed) ? append_sid("{$this->root_path}posting.$this->php_ext", 'mode=' . (($softdelete_allowed) ? 'soft_delete' : 'delete') . "&amp;f=$forum_id&amp;p={$row['post_id']}") : '',

				'U_SEARCH'		=> $user_cache[$poster_id]['search'],
				'U_PM'			=> $u_pm,
				'U_EMAIL'		=> $user_cache[$poster_id]['email'],
				'U_JABBER'		=> $user_cache[$poster_id]['jabber'],

				'U_APPROVE_ACTION'		=> append_sid("{$this->root_path}mcp.{$this->php_ext}", "i=queue&amp;p={$row['post_id']}&amp;f=$forum_id&amp;redirect=" . urlencode(str_replace('&amp;', '&', $viewtopic_url . '&amp;p=' . $row['post_id'] . '#p' . $row['post_id']))),
				'U_REPORT'			=> ($this->auth->acl_get('f_report', $forum_id)) ? append_sid("{$this->root_path}report.$this->php_ext", 'f=' . $forum_id . '&amp;p=' . $row['post_id']) : '',
				'U_MCP_REPORT'		=> ($this->auth->acl_get('m_report', $forum_id)) ? append_sid("{$this->root_path}mcp.$this->php_ext", 'i=reports&amp;mode=report_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $this->user->session_id) : '',
				'U_MCP_APPROVE'		=> ($this->auth->acl_get('m_approve', $forum_id)) ? append_sid("{$this->root_path}mcp.$this->php_ext", 'i=queue&amp;mode=approve_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $this->user->session_id) : '',
				'U_MCP_RESTORE'		=> ($this->auth->acl_get('m_approve', $forum_id)) ? append_sid("{$this->root_path}mcp.{$this->php_ext}", 'i=queue&amp;mode=' . (($topic_data['topic_visibility'] != ITEM_DELETED) ? 'deleted_posts' : 'deleted_topics') . '&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $this->user->session_id) : '',
				'U_MINI_POST'		=> $this->helper->route('phpbb_ideas_idea_controller', array_merge(array('idea_id' => $idea_id, '#' => "p{$row['post_id']}"), (($topic_data['topic_type'] == POST_GLOBAL) ? array('f' => $forum_id) : array()))),
				'U_NEXT_POST_ID'	=> ($i < $i_total && isset($rowset[$post_list[$i + 1]])) ? $rowset[$post_list[$i + 1]]['post_id'] : '',
				'U_PREV_POST_ID'	=> $prev_post_id,
				'U_NOTES'			=> ($this->auth->acl_getf_global('m_')) ? append_sid("{$this->root_path}mcp.$this->php_ext", 'i=notes&amp;mode=user_notes&amp;u=' . $poster_id, true, $this->user->session_id) : '',
				'U_WARN'			=> ($this->auth->acl_get('m_warn') && $poster_id != $this->user->data['user_id'] && $poster_id != ANONYMOUS) ? append_sid("{$this->root_path}mcp.$this->php_ext", 'i=warn&amp;mode=warn_post&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $this->user->session_id) : '',

				'POST_ID'			=> $row['post_id'],
				'POST_NUMBER'		=> $i + $start + 1,
				'POSTER_ID'			=> $poster_id,

				'S_HAS_ATTACHMENTS'	=> (!empty($attachments[$row['post_id']])) ? true : false,
				'S_MULTIPLE_ATTACHMENTS'	=> !empty($attachments[$row['post_id']]) && sizeof($attachments[$row['post_id']]) > 1,
				'S_POST_UNAPPROVED'	=> ($row['post_visibility'] == ITEM_UNAPPROVED || $row['post_visibility'] == ITEM_REAPPROVE) ? true : false,
				'S_POST_DELETED'	=> ($row['post_visibility'] == ITEM_DELETED) ? true : false,
				'L_POST_DELETED_MESSAGE'	=> $l_deleted_message,
				'S_POST_REPORTED'	=> ($row['post_reported'] && $this->auth->acl_get('m_report', $forum_id)) ? true : false,
				'S_DISPLAY_NOTICE'	=> $display_notice && $row['post_attachment'],
				'S_FRIEND'			=> ($row['friend']) ? true : false,
				'S_UNREAD_POST'		=> $post_unread,
				'S_FIRST_UNREAD'	=> $s_first_unread,
				'S_CUSTOM_FIELDS'	=> (isset($cp_row['row']) && sizeof($cp_row['row'])) ? true : false,
				'S_TOPIC_POSTER'	=> ($topic_data['topic_poster'] == $poster_id) ? true : false,

				'S_IGNORE_POST'		=> ($row['foe']) ? true : false,
				'L_IGNORE_POST'		=> ($row['foe']) ? $this->user->lang('POST_BY_FOE', get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username'])) : '',
				'S_POST_HIDDEN'		=> $row['hide_post'],
				'L_POST_DISPLAY'	=> ($row['hide_post']) ? $this->user->lang('POST_DISPLAY', '<a class="display_post" data-post-id="' . $row['post_id'] . '" href="' . $viewtopic_url . "&amp;p={$row['post_id']}&amp;view=show#p{$row['post_id']}" . '">', '</a>') : '',
				'S_DELETE_PERMANENT'	=> $permanent_delete_allowed,
			);

			if (isset($cp_row['row']) && sizeof($cp_row['row']))
			{
				$postrow = array_merge($postrow, $cp_row['row']);
			}

			// Dump vars into template
			$this->template->assign_block_vars('postrow', $postrow);

			$contact_fields = array(
				array(
					'ID'		=> 'pm',
					'NAME' 		=> $this->user->lang['SEND_PRIVATE_MESSAGE'],
					'U_CONTACT'	=> $u_pm,
				),
				array(
					'ID'		=> 'email',
					'NAME'		=> $this->user->lang['SEND_EMAIL'],
					'U_CONTACT'	=> $user_cache[$poster_id]['email'],
				),
				array(
					'ID'		=> 'jabber',
					'NAME'		=> $this->user->lang['JABBER'],
					'U_CONTACT'	=> $user_cache[$poster_id]['jabber'],
				),
			);

			foreach ($contact_fields as $field)
			{
				if ($field['U_CONTACT'])
				{
					$this->template->assign_block_vars('postrow.contact', $field);
				}
			}

			if (!empty($cp_row['blockrow']))
			{
				foreach ($cp_row['blockrow'] as $field_data)
				{
					$this->template->assign_block_vars('postrow.custom_fields', $field_data);

					if ($field_data['S_PROFILE_CONTACT'])
					{
						$this->template->assign_block_vars('postrow.contact', array(
							'ID'		=> $field_data['PROFILE_FIELD_IDENT'],
							'NAME'		=> $field_data['PROFILE_FIELD_NAME'],
							'U_CONTACT'	=> $field_data['PROFILE_FIELD_CONTACT'],
						));
					}
				}
			}

			// Display not already displayed Attachments for this post, we already parsed them. ;)
			if (!empty($attachments[$row['post_id']]))
			{
				foreach ($attachments[$row['post_id']] as $attachment)
				{
					$this->template->assign_block_vars('postrow.attachment', array(
						'DISPLAY_ATTACHMENT'	=> $attachment)
					);
				}
			}

			$prev_post_id = $row['post_id'];

			unset($rowset[$post_list[$i]]);
			unset($attachments[$row['post_id']]);
		}
		unset($rowset, $user_cache);

		// Update topic view and if necessary attachment view counters ... but only for humans and if this is the first 'page view'
		if (isset($this->user->data['session_page']) && !$this->user->data['is_bot'] && (strpos($this->user->data['session_page'], '&t=' . $topic_id) === false || isset($this->user->data['session_created'])))
		{
			$sql = 'UPDATE ' . TOPICS_TABLE . '
				SET topic_views = topic_views + 1, topic_last_view_time = ' . time() . "
				WHERE topic_id = $topic_id";
			$this->db->sql_query($sql);

			// Update the attachment download counts
			if (sizeof($update_count))
			{
				$sql = 'UPDATE ' . ATTACHMENTS_TABLE . '
					SET download_count = download_count + 1
					WHERE ' . $this->db->sql_in_set('attach_id', array_unique($update_count));
				$this->db->sql_query($sql);
			}
		}

		// Only mark topic if it's currently unread. Also make sure we do not set topic tracking back if earlier pages are viewed.
		if (isset($topic_tracking_info[$topic_id]) && $topic_data['topic_last_post_time'] > $topic_tracking_info[$topic_id] && $max_post_time > $topic_tracking_info[$topic_id])
		{
			markread('topic', $forum_id, $topic_id, $max_post_time);

			// Update forum info
			$all_marked_read = update_forum_tracking_info($forum_id, $topic_data['forum_last_post_time'], (isset($topic_data['forum_mark_time'])) ? $topic_data['forum_mark_time'] : false, false);
		}
		else
		{
			$all_marked_read = true;
		}

		// If there are absolutely no more unread posts in this forum
		// and unread posts shown, we can safely show the #unread link
		if ($all_marked_read)
		{
			if ($post_unread)
			{
				$this->template->assign_vars(array(
					'U_VIEW_UNREAD_POST'	=> '#unread',
				));
			}
			else if (isset($topic_tracking_info[$topic_id]) && $topic_data['topic_last_post_time'] > $topic_tracking_info[$topic_id])
			{
				$this->template->assign_vars(array(
					'U_VIEW_UNREAD_POST'	=> $this->helper->route('phpbb_ideas_idea_controller', array('idea_id' => $idea_id, 'view' => 'unread', '#' => 'unread')),
				));
			}
		}
		else if (!$all_marked_read)
		{
			$last_page = ((floor($start / $this->config['posts_per_page']) + 1) == max(ceil($total_posts / $this->config['posts_per_page']), 1)) ? true : false;

			// What can happen is that we are at the last displayed page. If so, we also display the #unread link based in $post_unread
			if ($last_page && $post_unread)
			{
				$this->template->assign_vars(array(
					'U_VIEW_UNREAD_POST'	=> '#unread',
				));
			}
			else if (!$last_page)
			{
				$this->template->assign_vars(array(
					'U_VIEW_UNREAD_POST'	=> $this->helper->route('phpbb_ideas_idea_controller', array('idea_id' => $idea_id, 'view' => 'unread', '#' => 'unread')),
				));
			}
		}

		// let's set up quick_reply
		$s_quick_reply = false;
		if ($this->user->data['is_registered'] && $this->config['allow_quick_reply'] && ($topic_data['forum_flags'] & FORUM_FLAG_QUICK_REPLY) && $this->auth->acl_get('f_reply', $forum_id))
		{
			// Quick reply enabled forum
			$s_quick_reply = (($topic_data['forum_status'] == ITEM_UNLOCKED && $topic_data['topic_status'] == ITEM_UNLOCKED) || $this->auth->acl_get('m_edit', $forum_id)) ? true : false;
		}

		if ($s_quick_reply)
		{
			add_form_key('posting');

			if ($s_quick_reply)
			{
				$s_attach_sig	= $this->config['allow_sig'] && $this->user->optionget('attachsig') && $this->auth->acl_get('f_sigs', $forum_id) && $this->auth->acl_get('u_sig');
				$s_smilies		= $this->config['allow_smilies'] && $this->user->optionget('smilies') && $this->auth->acl_get('f_smilies', $forum_id);
				$s_bbcode		= $this->config['allow_bbcode'] && $this->user->optionget('bbcode') && $this->auth->acl_get('f_bbcode', $forum_id);
				$s_notify		= $this->config['allow_topic_notify'] && ($this->user->data['user_notify'] || $s_watching_topic['is_watching']);

				$qr_hidden_fields = array(
					'topic_cur_post_id'		=> (int) $topic_data['topic_last_post_id'],
					'lastclick'				=> (int) time(),
					'topic_id'				=> (int) $topic_data['topic_id'],
					'forum_id'				=> (int) $forum_id,
				);

				// Originally we use checkboxes and check with isset(), so we only provide them if they would be checked
				(!$s_bbcode)					? $qr_hidden_fields['disable_bbcode'] = 1		: true;
				(!$s_smilies)					? $qr_hidden_fields['disable_smilies'] = 1		: true;
				(!$this->config['allow_post_links'])	? $qr_hidden_fields['disable_magic_url'] = 1	: true;
				($s_attach_sig)					? $qr_hidden_fields['attach_sig'] = 1			: true;
				($s_notify)						? $qr_hidden_fields['notify'] = 1				: true;
				($topic_data['topic_status'] == ITEM_LOCKED) ? $qr_hidden_fields['lock_topic'] = 1 : true;

				$this->template->assign_vars(array(
					'S_QUICK_REPLY'			=> true,
					'U_QR_ACTION'			=> append_sid("{$this->root_path}posting.$this->php_ext", "mode=reply&amp;f=$forum_id&amp;t=$topic_id"),
					'QR_HIDDEN_FIELDS'		=> build_hidden_fields($qr_hidden_fields),
					'SUBJECT'				=> 'Re: ' . censor_text($topic_data['topic_title']),
				));
			}
		}
		// now I have the urge to wash my hands :(

		// Use Ideas breadcrumbs
		$this->template->destroy_block_vars('navlinks');
		$this->template->assign_block_vars('navlinks', array(
			'U_VIEW_FORUM'		=> $this->helper->route('phpbb_ideas_index_controller'),
			'FORUM_NAME'		=> $this->user->lang('IDEAS'),
		));

		// We overwrite $_REQUEST['f'] if there is no forum specified
		// to be able to display the correct online list.
		// One downside is that the user currently viewing this topic/post is not taken into account.
		if (!$this->request->variable('f', 0))
		{
			$this->request->overwrite('f', $forum_id);
		}

		// We need to do the same with the topic_id. See #53025.
		if (!$this->request->variable('t', 0) && !empty($topic_id))
		{
			$this->request->overwrite('t', $topic_id);
		}

		return $this->helper->render('idea_body.html', $this->user->lang('VIEW_IDEA') . ' - ' . $idea['idea_title']);
	}
}
