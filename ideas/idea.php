<?php
/**
 *
 * @package phpBB3 Ideas
 * @author Callum Macrae (callumacrae) <callum@lynxphp.com>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

define('IN_IDEAS', true);
$ideas_root_path = __DIR__ . '/';
include($ideas_root_path . 'common.php');


$mode = request_var('mode', '');
$id = request_var('id', 0);
$vote = request_var('v', 1);
$status = request_var('status', 0);
$idea = $ideas->get_idea($id);
if (!$idea)
{
	trigger_error('IDEA_NOT_FOUND');
}

$mod = $auth->acl_get('m_', IDEAS_FORUM_ID);
$own = $idea['idea_author'] === $user->data['user_id'];

if (ideas_is_ajax())
{
	header('Content-Type: application/json');

	switch ($mode)
	{
		case 'duplicate':
			if ($mod)
			{
				$duplicate = request_var('duplicate', 0);
				$ideas->set_duplicate($idea['idea_id'], $duplicate);
				echo 'true';
			}
			else
			{
				echo 'false';
			}
			break;

		case 'removevote':
			if ($idea['idea_status'] == 3 || $idea['idea_status'] == 4)
			{
				return false;
			}

			if ($auth->acl_get('f_vote', IDEAS_FORUM_ID))
			{
				echo json_encode($ideas->remove_vote($idea, $user->data['user_id']));
			}
			else
			{
				echo json_encode($user->lang('NO_AUTH_OPERATION'));
			}
			break;

		case 'rfc':
			if ($own || $mod)
			{
				$rfc = request_var('rfc', '');
				$ideas->set_rfc($idea['idea_id'], $rfc);
				echo 'true';
			}
			else
			{
				echo 'false';
			}
			break;

		case 'status':
			if ($status && $mod)
			{
				$ideas->change_status($idea['idea_id'], $status);
				echo 'true';
			}
			else
			{
				echo 'false';
			}
			break;

		case 'ticket':
			if ($own || $mod)
			{
				$ticket = request_var('ticket', 0);
				$ideas->set_ticket($idea['idea_id'], $ticket);
				echo 'true';
			}
			else
			{
				echo 'false';
			}
			break;

		case 'title':
			if ($own || $mod)
			{
				$title = request_var('title', '');
				$ideas->set_title($idea['idea_id'], $title);
				echo 'true';
			}
			else
			{
				echo 'false';
			}
			break;

		case 'vote':
			if ($idea['idea_status'] == 3 || $idea['idea_status'] == 4)
			{
				return false;
			}

			if ($auth->acl_get('f_vote', IDEAS_FORUM_ID))
			{
				echo json_encode($ideas->vote($idea, $user->data['user_id'], $vote));
			}
			else
			{
				echo json_encode($user->lang('NO_AUTH_OPERATION'));
			}
			break;

		default:
			echo '"?"';
			break;
	}

	garbage_collection();
	exit_handler();
}

if ($mode === 'delete' && ($mod || ($own && $auth->acl_get('f_delete', IDEAS_FORUM_ID))))
{
	include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
	$ideas->delete($id, $idea['topic_id']);

	$message = $user->lang['IDEA_DELETED'] . '<br /><br />';
	$message .= sprintf($user->lang['RETURN_INDEX'], '<a href="' . append_sid('./index.php') . '">', '</a>');
	trigger_error($message);
}

include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);

$delete_posts = $mod ||
	($own && $auth->acl_get('f_delete', IDEAS_FORUM_ID));

if ($mod)
{
	$statuses = $ideas->get_statuses();
	foreach ($statuses as $status)
	{
		$template->assign_block_vars('statuses', array(
			'ID'	=> $status['status_id'],
			'NAME'	=> $status['status_name'],
		));
	}
}

$points = $idea['idea_votes_up'] - $idea['idea_votes_down'];

$idea_topic_link = append_sid($phpbb_root_path . 'viewtopic.php', 't=' . $idea['topic_id']);

$can_vote = true;
if ($idea['idea_status'] == 3 || $idea['idea_status'] == 4 || !$auth->acl_get('f_vote', IDEAS_FORUM_ID))
{
	$can_vote = false;
}
// Topic locked is check later on: search for "TOPIC LOCK CHECK 123"

$template->assign_vars(array(
	'IDEA_ID'			=> $idea['idea_id'],
	'IDEA_TITLE'		=> $idea['idea_title'],
	'IDEA_AUTHOR'		=> ideas_get_user_link($idea['idea_author']),
	'IDEA_DATE'			=> $user->format_date($idea['idea_date']),
	'IDEA_VOTES'        => $idea['idea_votes_up'] + $idea['idea_votes_down'],
	'IDEA_VOTES_UP'	    => $idea['idea_votes_up'],
	'IDEA_VOTES_DOWN'   => $idea['idea_votes_down'],
	'IDEA_POINTS'       => $user->lang('VIEW_VOTES', $points),
	'IDEA_STATUS'		=> $ideas->get_status_from_id($idea['idea_status']),
	'IDEA_STATUS_LINK'	=> append_sid('./list.php', 'status=' . $idea['idea_status']),

	'IDEA_DUPLICATE'    => $idea['duplicate_id'],
	'IDEA_RFC'			=> $idea['rfc_link'],
	'IDEA_TICKET'		=> $idea['ticket_id'],

	'U_IDEA_TOPIC'      => $idea_topic_link,

	'IS_MOD'            => $mod,
	'CAN_EDIT'			=> $mod || $own,
	'CAN_VOTE'          => $can_vote,

	'U_DELETE_IDEA'		=> $delete_posts ? append_sid('./idea.php', 'mode=delete&amp;id=' . $id) : false,
	'U_CHANGE_STATUS'	=> append_sid('./idea.php', 'mode=status&amp;id=' . $id),
	'U_EDIT_DUPLICATE'	=> append_sid('./idea.php', 'mode=duplicate&amp;id=' . $id),
	'U_EDIT_RFC'		=> append_sid('./idea.php', 'mode=rfc&amp;id=' . $id),
	'U_EDIT_TICKET'		=> append_sid('./idea.php', 'mode=ticket&amp;id=' . $id),
	'U_EDIT_TITLE'		=> append_sid('./idea.php', 'mode=title&amp;id=' . $id),
	'U_REMOVE_VOTE'     => append_sid('./idea.php', 'mode=removevote&amp;id=' . $id),

	'U_IDEA_VOTE'		=> append_sid('./idea.php', 'mode=vote&amp;id=' . $id),
));


if ($idea['idea_votes_up'] || $idea['idea_votes_down'])
{
	$votes = $ideas->get_voters($idea['idea_id']);

	foreach ($votes as $vote)
	{
		$template->assign_block_vars('votes_' . ($vote['vote_value'] ? 'up' : 'down'), array(
			'USER'	=> get_username_string('full', $vote['user_id'], $vote['username'], $vote['user_colour']),
		));
	}
}


$forum_id = IDEAS_FORUM_ID;
$topic_id = $idea['topic_id'];
$post_id  = request_var('p', 0);

$start    = request_var('start', 0);
$view     = request_var('view', '');

$default_sort_days	= (!empty($user->data['user_post_show_days'])) ? $user->data['user_post_show_days'] : 0;
$default_sort_key	= (!empty($user->data['user_post_sortby_type'])) ? $user->data['user_post_sortby_type'] : 't';
$default_sort_dir	= (!empty($user->data['user_post_sortby_dir'])) ? $user->data['user_post_sortby_dir'] : 'a';

$sort_days	= request_var('st', $default_sort_days);
$sort_key	= request_var('sk', $default_sort_key);
$sort_dir	= request_var('sd', $default_sort_dir);

$update		= request_var('update', false);

$hilit_words = request_var('hilit', '', true);

// Find topic id if user requested a newer or older topic
if ($view && !$post_id)
{
	if ($view == 'unread')
	{
		// Get topic tracking info
		$topic_tracking_info = get_complete_topic_tracking($forum_id, $topic_id);

		$topic_last_read = (isset($topic_tracking_info[$topic_id])) ? $topic_tracking_info[$topic_id] : 0;

		$sql = 'SELECT post_id, topic_id, forum_id
			FROM ' . POSTS_TABLE . "
			WHERE topic_id = $topic_id
				" . (($auth->acl_get('m_approve', $forum_id)) ? '' : 'AND post_approved = 1') . "
				AND post_time > $topic_last_read
				AND forum_id = $forum_id
			ORDER BY post_time ASC";
		$result = $db->sql_query_limit($sql, 1);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			$sql = 'SELECT topic_last_post_id as post_id, topic_id, forum_id
				FROM ' . TOPICS_TABLE . '
				WHERE topic_id = ' . $topic_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		}

		if (!$row)
		{
			// Setup user environment so we can process lang string
			$user->setup('viewtopic');

			trigger_error('NO_TOPIC');
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
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			$user->setup('viewtopic');
			// OK, the topic doesn't exist. This error message is not helpful, but technically correct.
			trigger_error(($view == 'next') ? 'NO_NEWER_TOPICS' : 'NO_OLDER_TOPICS');
		}
		else
		{
			$sql = 'SELECT topic_id, forum_id
				FROM ' . TOPICS_TABLE . '
				WHERE forum_id = ' . $row['forum_id'] . "
					AND topic_moved_id = 0
					AND topic_last_post_time $sql_condition {$row['topic_last_post_time']}
					" . (($auth->acl_get('m_approve', $row['forum_id'])) ? '' : 'AND topic_approved = 1') . "
				ORDER BY topic_last_post_time $sql_ordering";
			$result = $db->sql_query_limit($sql, 1);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$row)
			{
				$user->setup('viewtopic');
				trigger_error(($view == 'next') ? 'NO_NEWER_TOPICS' : 'NO_OLDER_TOPICS');
			}
			else
			{
				$topic_id = $row['topic_id'];

				// Check for global announcement correctness?
				if (!$row['forum_id'] && !$forum_id)
				{
					trigger_error('NO_TOPIC');
				}
				else if ($row['forum_id'])
				{
					$forum_id = $row['forum_id'];
				}
			}
		}
	}

	// Check for global announcement correctness?
	if ((!isset($row) || !$row['forum_id']) && !$forum_id)
	{
		trigger_error('NO_TOPIC');
	}
	else if (isset($row) && $row['forum_id'])
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

// Firebird handles two columns of the same name a little differently, this
// addresses that by forcing the forum_id to come from the forums table.
if ($db->sql_layer === 'firebird')
{
	$sql_array['SELECT'] = 'f.forum_id AS forum_id, ' . $sql_array['SELECT'];
}

// The FROM-Order is quite important here, else t.* columns can not be correctly bound.
if ($post_id)
{
	$sql_array['SELECT'] .= ', p.post_approved, p.post_time, p.post_id';
	$sql_array['FROM'][POSTS_TABLE] = 'p';
}

// Topics table need to be the last in the chain
$sql_array['FROM'][TOPICS_TABLE] = 't';

if ($user->data['is_registered'])
{
	$sql_array['SELECT'] .= ', tw.notify_status';
	$sql_array['LEFT_JOIN'] = array();

	$sql_array['LEFT_JOIN'][] = array(
		'FROM'	=> array(TOPICS_WATCH_TABLE => 'tw'),
		'ON'	=> 'tw.user_id = ' . $user->data['user_id'] . ' AND t.topic_id = tw.topic_id'
	);

	if ($config['allow_bookmarks'])
	{
		$sql_array['SELECT'] .= ', bm.topic_id as bookmarked';
		$sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array(BOOKMARKS_TABLE => 'bm'),
			'ON'	=> 'bm.user_id = ' . $user->data['user_id'] . ' AND t.topic_id = bm.topic_id'
		);
	}

	if ($config['load_db_lastread'])
	{
		$sql_array['SELECT'] .= ', tt.mark_time, ft.mark_time as forum_mark_time';

		$sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array(TOPICS_TRACK_TABLE => 'tt'),
			'ON'	=> 'tt.user_id = ' . $user->data['user_id'] . ' AND t.topic_id = tt.topic_id'
		);

		$sql_array['LEFT_JOIN'][] = array(
			'FROM'	=> array(FORUMS_TRACK_TABLE => 'ft'),
			'ON'	=> 'ft.user_id = ' . $user->data['user_id'] . ' AND t.forum_id = ft.forum_id'
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

$sql_array['WHERE'] .= ' AND (f.forum_id = t.forum_id';

if (!$forum_id)
{
	// If it is a global announcement make sure to set the forum id to a postable forum
	$sql_array['WHERE'] .= ' OR (t.topic_type = ' . POST_GLOBAL . '
		AND f.forum_type = ' . FORUM_POST . ')';
}
else
{
	$sql_array['WHERE'] .= ' OR (t.topic_type = ' . POST_GLOBAL . "
		AND f.forum_id = $forum_id)";
}

$sql_array['WHERE'] .= ')';

// Join to forum table on topic forum_id unless topic forum_id is zero
// whereupon we join on the forum_id passed as a parameter ... this
// is done so navigation, forum name, etc. remain consistent with where
// user clicked to view a global topic
$sql = $db->sql_build_query('SELECT', $sql_array);
$result = $db->sql_query($sql);
$topic_data = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

// link to unapproved post or incorrect link
if (!$topic_data)
{
	// If post_id was submitted, we try at least to display the topic as a last resort...
	if ($post_id && $topic_id)
	{
		redirect(append_sid("idea.$phpEx", "id=$id"));
	}

	trigger_error('NO_TOPIC');
}

$forum_id = (int) $topic_data['forum_id'];
// This is for determining where we are (page)
if ($post_id)
{
	// are we where we are supposed to be?
	if (!$topic_data['post_approved'] && !$auth->acl_get('m_approve', $topic_data['forum_id']))
	{
		// If post_id was submitted, we try at least to display the topic as a last resort...
		if ($topic_id)
		{
			redirect(append_sid("idea.$phpEx", "id=$id"));
		}

		trigger_error('NO_TOPIC');
	}
	if ($post_id == $topic_data['topic_first_post_id'] || $post_id == $topic_data['topic_last_post_id'])
	{
		$check_sort = ($post_id == $topic_data['topic_first_post_id']) ? 'd' : 'a';

		if ($sort_dir == $check_sort)
		{
			$topic_data['prev_posts'] = ($auth->acl_get('m_approve', $forum_id)) ? $topic_data['topic_replies_real'] : $topic_data['topic_replies'];
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
				" . ((!$auth->acl_get('m_approve', $forum_id)) ? 'AND p.post_approved = 1' : '');

		if ($sort_dir == 'd')
		{
			$sql .= " AND (p.post_time > {$topic_data['post_time']} OR (p.post_time = {$topic_data['post_time']} AND p.post_id >= {$topic_data['post_id']}))";
		}
		else
		{
			$sql .= " AND (p.post_time < {$topic_data['post_time']} OR (p.post_time = {$topic_data['post_time']} AND p.post_id <= {$topic_data['post_id']}))";
		}

		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		$topic_data['prev_posts'] = $row['prev_posts'] - 1;
	}
}

$topic_id = (int) $topic_data['topic_id'];
//
$topic_replies = ($auth->acl_get('m_approve', $forum_id)) ? $topic_data['topic_replies_real'] : $topic_data['topic_replies'];

// Check sticky/announcement time limit
if (($topic_data['topic_type'] == POST_STICKY || $topic_data['topic_type'] == POST_ANNOUNCE) && $topic_data['topic_time_limit'] && ($topic_data['topic_time'] + $topic_data['topic_time_limit']) < time())
{
	$sql = 'UPDATE ' . TOPICS_TABLE . '
		SET topic_type = ' . POST_NORMAL . ', topic_time_limit = 0
		WHERE topic_id = ' . $topic_id;
	$db->sql_query($sql);

	$topic_data['topic_type'] = POST_NORMAL;
	$topic_data['topic_time_limit'] = 0;
}

// Setup look and feel
$user->setup('viewtopic', $topic_data['forum_style']);

if (!$topic_data['topic_approved'] && !$auth->acl_get('m_approve', $forum_id))
{
	trigger_error('NO_TOPIC');
}

// Start auth check
if (!$auth->acl_get('f_read', $forum_id))
{
	if ($user->data['user_id'] != ANONYMOUS)
	{
		trigger_error('SORRY_AUTH_READ');
	}

	login_box('', $user->lang['LOGIN_VIEWFORUM']);
}

// Forum is passworded ... check whether access has been granted to this
// user this session, if not show login box
if ($topic_data['forum_password'])
{
	login_forum_box($topic_data);
}

// Redirect to login or to the correct post upon emailed notification links
if (isset($_GET['e']))
{
	$jump_to = request_var('e', 0);

	$redirect_url = append_sid("idea.$phpEx", "id=$id");

	if ($user->data['user_id'] == ANONYMOUS)
	{
		login_box($redirect_url . "&amp;p=$post_id&amp;e=$jump_to", $user->lang['LOGIN_NOTIFY_TOPIC']);
	}

	if ($jump_to > 0)
	{
		// We direct the already logged in user to the correct post...
		redirect($redirect_url . ((!$post_id) ? "&amp;p=$jump_to" : "&amp;p=$post_id") . "#p$jump_to");
	}
}

// What is start equal to?
if ($post_id)
{
	$start = floor(($topic_data['prev_posts']) / $config['posts_per_page']) * $config['posts_per_page'];
}

// Get topic tracking info
if (!isset($topic_tracking_info))
{
	$topic_tracking_info = array();

	// Get topic tracking info
	if ($config['load_db_lastread'] && $user->data['is_registered'])
	{
		$tmp_topic_data = array($topic_id => $topic_data);
		$topic_tracking_info = get_topic_tracking($forum_id, $topic_id, $tmp_topic_data, array($forum_id => $topic_data['forum_mark_time']));
		unset($tmp_topic_data);
	}
	else if ($config['load_anon_lastread'] || $user->data['is_registered'])
	{
		$topic_tracking_info = get_complete_topic_tracking($forum_id, $topic_id);
	}
}

// Post ordering options
$limit_days = array(0 => $user->lang['ALL_POSTS'], 1 => $user->lang['1_DAY'], 7 => $user->lang['7_DAYS'], 14 => $user->lang['2_WEEKS'], 30 => $user->lang['1_MONTH'], 90 => $user->lang['3_MONTHS'], 180 => $user->lang['6_MONTHS'], 365 => $user->lang['1_YEAR']);

$sort_by_text = array('a' => $user->lang['AUTHOR'], 't' => $user->lang['POST_TIME'], 's' => $user->lang['SUBJECT']);
$sort_by_sql = array('a' => array('u.username_clean', 'p.post_id'), 't' => 'p.post_time', 's' => array('p.post_subject', 'p.post_id'));
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
		" . (($auth->acl_get('m_approve', $forum_id)) ? '' : 'AND post_approved = 1');
	$result = $db->sql_query($sql);
	$total_posts = (int) $db->sql_fetchfield('num_posts');
	$db->sql_freeresult($result);

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
	foreach (explode(' ', trim($hilit_words)) as $word)
	{
		if (trim($word))
		{
			$word = str_replace('\*', '\w+?', preg_quote($word, '#'));
			$word = preg_replace('#(^|\s)\\\\w\*\?(\s|$)#', '$1\w+?$2', $word);
			$highlight_match .= (($highlight_match != '') ? '|' : '') . $word;
		}
	}

	$highlight = urlencode($hilit_words);
}

// Make sure $start is set to the last page if it exceeds the amount
if ($start < 0 || $start >= $total_posts)
{
	$start = ($start < 0) ? 0 : floor(($total_posts - 1) / $config['posts_per_page']) * $config['posts_per_page'];
}

// General Viewtopic URL for return links
$viewtopic_url = append_sid("idea.$phpEx", "id=$id" . (($start == 0) ? '' : "&amp;start=$start") . ((strlen($u_sort_param)) ? "&amp;$u_sort_param" : '') . (($highlight_match) ? "&amp;hilit=$highlight" : ''));

// Are we watching this topic?
$s_watching_topic = array(
	'link'			=> '',
	'title'			=> '',
	'is_watching'	=> false,
);

if (($config['email_enable'] || $config['jab_enable']) && $config['allow_topic_notify'])
{
	$notify_status = (isset($topic_data['notify_status'])) ? $topic_data['notify_status'] : null;
	watch_topic_forum('topic', $s_watching_topic, $user->data['user_id'], $forum_id, $topic_id, $notify_status, $start, $topic_data['topic_title']);

	// Reset forum notification if forum notify is set
	if ($config['allow_forum_notify'] && $auth->acl_get('f_subscribe', $forum_id))
	{
		$s_watching_forum = $s_watching_topic;
		watch_topic_forum('forum', $s_watching_forum, $user->data['user_id'], $forum_id, 0);
	}
}

// Bookmarks
if ($config['allow_bookmarks'] && $user->data['is_registered'] && request_var('bookmark', 0))
{
	if (check_link_hash(request_var('hash', ''), "topic_$topic_id"))
	{
		if (!$topic_data['bookmarked'])
		{
			$sql = 'INSERT INTO ' . BOOKMARKS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'user_id'	=> $user->data['user_id'],
				'topic_id'	=> $topic_id,
			));
			$db->sql_query($sql);
		}
		else
		{
			$sql = 'DELETE FROM ' . BOOKMARKS_TABLE . "
				WHERE user_id = {$user->data['user_id']}
					AND topic_id = $topic_id";
			$db->sql_query($sql);
		}
		$message = (($topic_data['bookmarked']) ? $user->lang['BOOKMARK_REMOVED'] : $user->lang['BOOKMARK_ADDED']) . '<br /><br />' . sprintf($user->lang['RETURN_TOPIC'], '<a href="' . $viewtopic_url . '">', '</a>');
	}
	else
	{
		$message = $user->lang['BOOKMARK_ERR'] . '<br /><br />' . sprintf($user->lang['RETURN_TOPIC'], '<a href="' . $viewtopic_url . '">', '</a>');
	}
	meta_refresh(3, $viewtopic_url);

	trigger_error($message);
}

// Grab ranks
$ranks = $cache->obtain_ranks();

// Grab icons
$icons = $cache->obtain_icons();

// Grab extensions
$extensions = array();
if ($topic_data['topic_attachment'])
{
	$extensions = $cache->obtain_attach_extensions($forum_id);
}

// Forum rules listing
$s_forum_rules = '';
gen_forum_auth_level('topic', $forum_id, $topic_data['forum_status']);

// If we've got a hightlight set pass it on to pagination.
$pagination = generate_pagination(append_sid("idea.$phpEx", "id=$id" . ((strlen($u_sort_param)) ? "&amp;$u_sort_param" : '') . (($highlight_match) ? "&amp;hilit=$highlight" : '')), $total_posts, $config['posts_per_page'], $start);

// Navigation links
generate_forum_nav($topic_data);

// Forum Rules
generate_forum_rules($topic_data);

// Moderators
$forum_moderators = array();
if ($config['load_moderators'])
{
	get_moderators($forum_moderators, $forum_id);
}

// This is only used for print view so ...
$server_path = (!$view) ? $phpbb_root_path : generate_board_url() . '/';

// Replace naughty words in title
$topic_data['topic_title'] = censor_text($topic_data['topic_title']);

$s_search_hidden_fields = array(
	't' => $topic_id,
	'sf' => 'msgonly',
);
if ($_SID)
{
	$s_search_hidden_fields['sid'] = $_SID;
}

if (!empty($_EXTRA_URL))
{
	foreach ($_EXTRA_URL as $url_param)
	{
		$url_param = explode('=', $url_param, 2);
		$s_hidden_fields[$url_param[0]] = $url_param[1];
	}
}

// TOPIC LOCK CHECK 123
if ($topic_data['topic_status'] == ITEM_LOCKED)
{
	$template->assign_var('CAN_VOTE', false);
}

// Send vars to template
$template->assign_vars(array(
	'FORUM_ID' 		=> $forum_id,
	'FORUM_NAME' 	=> $topic_data['forum_name'],
	'FORUM_DESC'	=> generate_text_for_display($topic_data['forum_desc'], $topic_data['forum_desc_uid'], $topic_data['forum_desc_bitfield'], $topic_data['forum_desc_options']),
	'TOPIC_ID' 		=> $topic_id,
	'TOPIC_TITLE' 	=> $topic_data['topic_title'],
	'TOPIC_POSTER'	=> $topic_data['topic_poster'],

	'TOPIC_AUTHOR_FULL'		=> get_username_string('full', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),
	'TOPIC_AUTHOR_COLOUR'	=> get_username_string('colour', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),
	'TOPIC_AUTHOR'			=> get_username_string('username', $topic_data['topic_poster'], $topic_data['topic_first_poster_name'], $topic_data['topic_first_poster_colour']),

	'PAGINATION' 	=> $pagination,
	'PAGE_NUMBER' 	=> on_page($total_posts, $config['posts_per_page'], $start),
	'TOTAL_POSTS'	=> ($total_posts == 1) ? $user->lang['VIEW_TOPIC_POST'] : sprintf($user->lang['VIEW_TOPIC_POSTS'], $total_posts),
	'U_MCP' 		=> ($auth->acl_get('m_', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", "i=main&amp;mode=topic_view&amp;f=$forum_id&amp;t=$topic_id" . (($start == 0) ? '' : "&amp;start=$start") . ((strlen($u_sort_param)) ? "&amp;$u_sort_param" : ''), true, $user->session_id) : '',
	'MODERATORS'	=> (isset($forum_moderators[$forum_id]) && sizeof($forum_moderators[$forum_id])) ? implode(', ', $forum_moderators[$forum_id]) : '',

	'POST_IMG' 			=> ($topic_data['forum_status'] == ITEM_LOCKED) ? $user->img('button_topic_locked', 'FORUM_LOCKED') : $user->img('button_topic_new', 'POST_NEW_TOPIC'),
	'QUOTE_IMG' 		=> $user->img('icon_post_quote', 'REPLY_WITH_QUOTE'),
	'REPLY_IMG'			=> ($topic_data['forum_status'] == ITEM_LOCKED || $topic_data['topic_status'] == ITEM_LOCKED) ? $user->img('button_topic_locked', 'TOPIC_LOCKED') : $user->img('button_topic_reply', 'REPLY_TO_TOPIC'),
	'EDIT_IMG' 			=> $user->img('icon_post_edit', 'EDIT_POST'),
	'DELETE_IMG' 		=> $user->img('icon_post_delete', 'DELETE_POST'),
	'INFO_IMG' 			=> $user->img('icon_post_info', 'VIEW_INFO'),
	'PROFILE_IMG'		=> $user->img('icon_user_profile', 'READ_PROFILE'),
	'SEARCH_IMG' 		=> $user->img('icon_user_search', 'SEARCH_USER_POSTS'),
	'PM_IMG' 			=> $user->img('icon_contact_pm', 'SEND_PRIVATE_MESSAGE'),
	'EMAIL_IMG' 		=> $user->img('icon_contact_email', 'SEND_EMAIL'),
	'WWW_IMG' 			=> $user->img('icon_contact_www', 'VISIT_WEBSITE'),
	'ICQ_IMG' 			=> $user->img('icon_contact_icq', 'ICQ'),
	'AIM_IMG' 			=> $user->img('icon_contact_aim', 'AIM'),
	'MSN_IMG' 			=> $user->img('icon_contact_msnm', 'MSNM'),
	'YIM_IMG' 			=> $user->img('icon_contact_yahoo', 'YIM'),
	'JABBER_IMG'		=> $user->img('icon_contact_jabber', 'JABBER') ,
	'REPORT_IMG'		=> $user->img('icon_post_report', 'REPORT_POST'),
	'REPORTED_IMG'		=> $user->img('icon_topic_reported', 'POST_REPORTED'),
	'UNAPPROVED_IMG'	=> $user->img('icon_topic_unapproved', 'POST_UNAPPROVED'),
	'WARN_IMG'			=> $user->img('icon_user_warn', 'WARN_USER'),

	'S_IS_LOCKED'			=> ($topic_data['topic_status'] == ITEM_UNLOCKED && $topic_data['forum_status'] == ITEM_UNLOCKED) ? false : true,
	'S_SELECT_SORT_DIR' 	=> $s_sort_dir,
	'S_SELECT_SORT_KEY' 	=> $s_sort_key,
	'S_SELECT_SORT_DAYS' 	=> $s_limit_days,
	'S_SINGLE_MODERATOR'	=> (!empty($forum_moderators[$forum_id]) && sizeof($forum_moderators[$forum_id]) > 1) ? false : true,
	'S_TOPIC_ACTION' 		=> append_sid("idea.$phpEx", "id=$id" . (($start == 0) ? '' : "&amp;start=$start")),
	'S_MOD_ACTION' 			=> append_sid("{$phpbb_root_path}mcp.$phpEx", "f=$forum_id&amp;t=$topic_id" . (($start == 0) ? '' : "&amp;start=$start") . "&amp;quickmod=1&amp;redirect=" . urlencode(str_replace('&amp;', '&', $viewtopic_url)), true, $user->session_id),

	'S_VIEWTOPIC'			=> true,
	'S_DISPLAY_SEARCHBOX'	=> ($auth->acl_get('u_search') && $auth->acl_get('f_search', $forum_id) && $config['load_search']) ? true : false,
	'S_SEARCHBOX_ACTION'	=> append_sid("{$phpbb_root_path}search.$phpEx"),
	'S_SEARCH_LOCAL_HIDDEN_FIELDS'	=> build_hidden_fields($s_search_hidden_fields),

	'S_DISPLAY_POST_INFO'	=> ($topic_data['forum_type'] == FORUM_POST && ($auth->acl_get('f_post', $forum_id) || $user->data['user_id'] == ANONYMOUS)) ? true : false,
	'S_DISPLAY_REPLY_INFO'	=> ($topic_data['forum_type'] == FORUM_POST && ($auth->acl_get('f_reply', $forum_id) || $user->data['user_id'] == ANONYMOUS)) ? true : false,
	'S_ENABLE_FEEDS_TOPIC'	=> ($config['feed_topic'] && !phpbb_optionget(FORUM_OPTION_FEED_EXCLUDE, $topic_data['forum_options'])) ? true : false,

	'U_TOPIC'				=> "{$server_path}viewtopic.$phpEx?f=$forum_id&amp;t=$topic_id",
	'U_FORUM'				=> $server_path,
	'U_VIEW_TOPIC' 			=> $viewtopic_url,
	'U_VIEW_FORUM' 			=> append_sid("{$phpbb_root_path}viewforum.$phpEx", 'f=' . $forum_id),
	'U_VIEW_OLDER_TOPIC'	=> append_sid("idea.$phpEx", "id=$id&amp;view=previous"),
	'U_VIEW_NEWER_TOPIC'	=> append_sid("idea.$phpEx", "id=$id&amp;view=next"),
	'U_PRINT_TOPIC'			=> false,
	'U_EMAIL_TOPIC'			=> false,

	'U_WATCH_TOPIC' 		=> $s_watching_topic['link'],
	'L_WATCH_TOPIC' 		=> $s_watching_topic['title'],
	'S_WATCHING_TOPIC'		=> $s_watching_topic['is_watching'],

	'U_BOOKMARK_TOPIC'		=> ($user->data['is_registered'] && $config['allow_bookmarks']) ? $viewtopic_url . '&amp;bookmark=1&amp;hash=' . generate_link_hash("topic_$topic_id") : '',
	'L_BOOKMARK_TOPIC'		=> ($user->data['is_registered'] && $config['allow_bookmarks'] && $topic_data['bookmarked']) ? $user->lang['BOOKMARK_TOPIC_REMOVE'] : $user->lang['BOOKMARK_TOPIC'],

	'U_POST_NEW_TOPIC' 		=> ($auth->acl_get('f_post', $forum_id) || $user->data['user_id'] == ANONYMOUS) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=post&amp;f=$forum_id") : '',
	'U_POST_REPLY_TOPIC' 	=> ($auth->acl_get('f_reply', $forum_id) || $user->data['user_id'] == ANONYMOUS) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=reply&amp;f=$forum_id&amp;t=$topic_id") : '',
	'U_BUMP_TOPIC'			=> (bump_topic_allowed($forum_id, $topic_data['topic_bumped'], $topic_data['topic_last_post_time'], $topic_data['topic_poster'], $topic_data['topic_last_poster_id'])) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=bump&amp;f=$forum_id&amp;t=$topic_id&amp;hash=" . generate_link_hash("topic_$topic_id")) : '')
);

// If the user is trying to reach the second half of the topic, fetch it starting from the end
$store_reverse = false;
$sql_limit = $config['posts_per_page'];
$sql_sort_order = $direction = '';

if ($start > $total_posts / 2)
{
	$store_reverse = true;

	if ($start + $config['posts_per_page'] > $total_posts)
	{
		$sql_limit = min($config['posts_per_page'], max(1, $total_posts - $start));
	}

	// Select the sort order
	$direction = (($sort_dir == 'd') ? 'ASC' : 'DESC');
	$sql_start = max(0, $total_posts - $sql_limit - $start);
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
$post_list = $user_cache = $id_cache = $attachments = $attach_list = $rowset = $update_count = $post_edit_list = array();
$has_attachments = $display_notice = false;
$bbcode_bitfield = '';
$i = $i_total = 0;

// Go ahead and pull all data for this topic
$sql = 'SELECT p.post_id
	FROM ' . POSTS_TABLE . ' p' . (($join_user_sql[$sort_key]) ? ', ' . USERS_TABLE . ' u': '') . "
	WHERE p.topic_id = $topic_id
		" . ((!$auth->acl_get('m_approve', $forum_id)) ? 'AND p.post_approved = 1' : '') . "
		" . (($join_user_sql[$sort_key]) ? 'AND u.user_id = p.poster_id': '') . "
		$limit_posts_time
	ORDER BY $sql_sort_order";
$result = $db->sql_query_limit($sql, $sql_limit, $sql_start);

$i = ($store_reverse) ? $sql_limit - 1 : 0;
while ($row = $db->sql_fetchrow($result))
{
	$post_list[$i] = (int) $row['post_id'];
	($store_reverse) ? $i-- : $i++;
}
$db->sql_freeresult($result);

if (!sizeof($post_list))
{
	if ($sort_days)
	{
		trigger_error('NO_POSTS_TIME_FRAME');
	}
	else
	{
		trigger_error('NO_TOPIC');
	}
}

// Holding maximum post time for marking topic read
// We need to grab it because we do reverse ordering sometimes
$max_post_time = 0;

$sql = $db->sql_build_query('SELECT', array(
	'SELECT'	=> 'u.*, z.friend, z.foe, p.*',

	'FROM'		=> array(
		USERS_TABLE		=> 'u',
		POSTS_TABLE		=> 'p',
	),

	'LEFT_JOIN'	=> array(
		array(
			'FROM'	=> array(ZEBRA_TABLE => 'z'),
			'ON'	=> 'z.user_id = ' . $user->data['user_id'] . ' AND z.zebra_id = p.poster_id'
		)
	),

	'WHERE'		=> $db->sql_in_set('p.post_id', $post_list) . '
		AND u.user_id = p.poster_id'
));

$result = $db->sql_query($sql);

$now = phpbb_gmgetdate(time() + $user->timezone + $user->dst);

// Posts are stored in the $rowset array while $attach_list, $user_cache
// and the global bbcode_bitfield are built
while ($row = $db->sql_fetchrow($result))
{
	// Set max_post_time
	if ($row['post_time'] > $max_post_time)
	{
		$max_post_time = $row['post_time'];
	}

	$poster_id = (int) $row['poster_id'];

	// Does post have an attachment? If so, add it to the list
	if ($row['post_attachment'] && $config['allow_attachments'])
	{
		$attach_list[] = (int) $row['post_id'];

		if ($row['post_approved'])
		{
			$has_attachments = true;
		}
	}

	$rowset[$row['post_id']] = array(
		'hide_post'			=> ($row['foe'] && ($view != 'show' || $post_id != $row['post_id'])) ? true : false,

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

		// Make sure the icon actually exists
		'icon_id'			=> (isset($icons[$row['icon_id']]['img'], $icons[$row['icon_id']]['height'], $icons[$row['icon_id']]['width'])) ? $row['icon_id'] : 0,
		'post_attachment'	=> $row['post_attachment'],
		'post_approved'		=> $row['post_approved'],
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

	// Define the global bbcode bitfield, will be used to load bbcodes
	$bbcode_bitfield = $bbcode_bitfield | base64_decode($row['bbcode_bitfield']);

	// Is a signature attached? Are we going to display it?
	if ($row['enable_sig'] && $config['allow_sig'] && $user->optionget('viewsigs'))
	{
		$bbcode_bitfield = $bbcode_bitfield | base64_decode($row['user_sig_bbcode_bitfield']);
	}

	// Cache various user specific data ... so we don't have to recompute
	// this each time the same user appears on this page
	if (!isset($user_cache[$poster_id]))
	{
		if ($poster_id == ANONYMOUS)
		{
			$user_cache[$poster_id] = array(
				'joined'		=> '',
				'posts'			=> '',
				'from'			=> '',

				'sig'					=> '',
				'sig_bbcode_uid'		=> '',
				'sig_bbcode_bitfield'	=> '',

				'online'			=> false,
				'avatar'			=> ($user->optionget('viewavatars')) ? get_user_avatar($row['user_avatar'], $row['user_avatar_type'], $row['user_avatar_width'], $row['user_avatar_height']) : '',
				'rank_title'		=> '',
				'rank_image'		=> '',
				'rank_image_src'	=> '',
				'sig'				=> '',
				'profile'			=> '',
				'pm'				=> '',
				'email'				=> '',
				'www'				=> '',
				'icq_status_img'	=> '',
				'icq'				=> '',
				'aim'				=> '',
				'msn'				=> '',
				'yim'				=> '',
				'jabber'			=> '',
				'search'			=> '',
				'age'				=> '',

				'username'			=> $row['username'],
				'user_colour'		=> $row['user_colour'],

				'warnings'			=> 0,
				'allow_pm'			=> 0,
			);

			get_user_rank($row['user_rank'], false, $user_cache[$poster_id]['rank_title'], $user_cache[$poster_id]['rank_image'], $user_cache[$poster_id]['rank_image_src']);
		}
		else
		{
			$user_sig = '';

			// We add the signature to every posters entry because enable_sig is post dependant
			if ($row['user_sig'] && $config['allow_sig'] && $user->optionget('viewsigs'))
			{
				$user_sig = $row['user_sig'];
			}

			$id_cache[] = $poster_id;

			$user_cache[$poster_id] = array(
				'joined'		=> $user->format_date($row['user_regdate']),
				'posts'			=> $row['user_posts'],
				'warnings'		=> (isset($row['user_warnings'])) ? $row['user_warnings'] : 0,
				'from'			=> (!empty($row['user_from'])) ? $row['user_from'] : '',

				'sig'					=> $user_sig,
				'sig_bbcode_uid'		=> (!empty($row['user_sig_bbcode_uid'])) ? $row['user_sig_bbcode_uid'] : '',
				'sig_bbcode_bitfield'	=> (!empty($row['user_sig_bbcode_bitfield'])) ? $row['user_sig_bbcode_bitfield'] : '',

				'viewonline'	=> $row['user_allow_viewonline'],
				'allow_pm'		=> $row['user_allow_pm'],

				'avatar'		=> ($user->optionget('viewavatars')) ? get_user_avatar($row['user_avatar'], $row['user_avatar_type'], $row['user_avatar_width'], $row['user_avatar_height']) : '',
				'age'			=> '',

				'rank_title'		=> '',
				'rank_image'		=> '',
				'rank_image_src'	=> '',

				'username'			=> $row['username'],
				'user_colour'		=> $row['user_colour'],

				'online'		=> false,
				'profile'		=> append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=viewprofile&amp;u=$poster_id"),
				'www'			=> $row['user_website'],
				'aim'			=> ($row['user_aim'] && $auth->acl_get('u_sendim')) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=contact&amp;action=aim&amp;u=$poster_id") : '',
				'msn'			=> ($row['user_msnm'] && $auth->acl_get('u_sendim')) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=contact&amp;action=msnm&amp;u=$poster_id") : '',
				'yim'			=> ($row['user_yim']) ? 'http://edit.yahoo.com/config/send_webmesg?.target=' . urlencode($row['user_yim']) . '&amp;.src=pg' : '',
				'jabber'		=> ($row['user_jabber'] && $auth->acl_get('u_sendim')) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=contact&amp;action=jabber&amp;u=$poster_id") : '',
				'search'		=> ($auth->acl_get('u_search')) ? append_sid("{$phpbb_root_path}search.$phpEx", "author_id=$poster_id&amp;sr=posts") : '',

				'author_full'		=> get_username_string('full', $poster_id, $row['username'], $row['user_colour']),
				'author_colour'		=> get_username_string('colour', $poster_id, $row['username'], $row['user_colour']),
				'author_username'	=> get_username_string('username', $poster_id, $row['username'], $row['user_colour']),
				'author_profile'	=> get_username_string('profile', $poster_id, $row['username'], $row['user_colour']),
			);

			get_user_rank($row['user_rank'], $row['user_posts'], $user_cache[$poster_id]['rank_title'], $user_cache[$poster_id]['rank_image'], $user_cache[$poster_id]['rank_image_src']);

			if ((!empty($row['user_allow_viewemail']) && $auth->acl_get('u_sendemail')) || $auth->acl_get('a_email'))
			{
				$user_cache[$poster_id]['email'] = ($config['board_email_form'] && $config['email_enable']) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=email&amp;u=$poster_id") : (($config['board_hide_emails'] && !$auth->acl_get('a_email')) ? '' : 'mailto:' . $row['user_email']);
			}
			else
			{
				$user_cache[$poster_id]['email'] = '';
			}

			if (!empty($row['user_icq']))
			{
				$user_cache[$poster_id]['icq'] = 'http://www.icq.com/people/' . urlencode($row['user_icq']) . '/';
				$user_cache[$poster_id]['icq_status_img'] = '<img src="http://web.icq.com/whitepages/online?icq=' . $row['user_icq'] . '&amp;img=5" width="18" height="18" alt="" />';
			}
			else
			{
				$user_cache[$poster_id]['icq_status_img'] = '';
				$user_cache[$poster_id]['icq'] = '';
			}

			if ($config['allow_birthdays'] && !empty($row['user_birthday']))
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
$db->sql_freeresult($result);

// Load custom profile fields
if ($config['load_cpf_viewtopic'])
{
	if (!class_exists('custom_profile'))
	{
		include($phpbb_root_path . 'includes/functions_profile_fields.' . $phpEx);
	}
	$cp = new custom_profile();

	// Grab all profile fields from users in id cache for later use - similar to the poster cache
	$profile_fields_tmp = $cp->generate_profile_fields_template('grab', $id_cache);

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
if ($config['load_onlinetrack'] && sizeof($id_cache))
{
	$sql = 'SELECT session_user_id, MAX(session_time) as online_time, MIN(session_viewonline) AS viewonline
		FROM ' . SESSIONS_TABLE . '
		WHERE ' . $db->sql_in_set('session_user_id', $id_cache) . '
		GROUP BY session_user_id';
	$result = $db->sql_query($sql);

	$update_time = $config['load_online_time'] * 60;
	while ($row = $db->sql_fetchrow($result))
	{
		$user_cache[$row['session_user_id']]['online'] = (time() - $update_time < $row['online_time'] && (($row['viewonline']) || $auth->acl_get('u_viewonline'))) ? true : false;
	}
	$db->sql_freeresult($result);
}
unset($id_cache);

// Pull attachment data
if (sizeof($attach_list))
{
	if ($auth->acl_get('u_download') && $auth->acl_get('f_download', $forum_id))
	{
		$sql = 'SELECT *
			FROM ' . ATTACHMENTS_TABLE . '
			WHERE ' . $db->sql_in_set('post_msg_id', $attach_list) . '
				AND in_message = 0
			ORDER BY filetime DESC, post_msg_id ASC';
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			$attachments[$row['post_msg_id']][] = $row;
		}
		$db->sql_freeresult($result);

		// No attachments exist, but post table thinks they do so go ahead and reset post_attach flags
		if (!sizeof($attachments))
		{
			$sql = 'UPDATE ' . POSTS_TABLE . '
				SET post_attachment = 0
				WHERE ' . $db->sql_in_set('post_id', $attach_list);
			$db->sql_query($sql);

			// We need to update the topic indicator too if the complete topic is now without an attachment
			if (sizeof($rowset) != $total_posts)
			{
				// Not all posts are displayed so we query the db to find if there's any attachment for this topic
				$sql = 'SELECT a.post_msg_id as post_id
					FROM ' . ATTACHMENTS_TABLE . ' a, ' . POSTS_TABLE . " p
					WHERE p.topic_id = $topic_id
						AND p.post_approved = 1
						AND p.topic_id = a.topic_id";
				$result = $db->sql_query_limit($sql, 1);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				if (!$row)
				{
					$sql = 'UPDATE ' . TOPICS_TABLE . "
						SET topic_attachment = 0
						WHERE topic_id = $topic_id";
					$db->sql_query($sql);
				}
			}
			else
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . "
					SET topic_attachment = 0
					WHERE topic_id = $topic_id";
				$db->sql_query($sql);
			}
		}
		else if ($has_attachments && !$topic_data['topic_attachment'])
		{
			// Topic has approved attachments but its flag is wrong
			$sql = 'UPDATE ' . TOPICS_TABLE . "
				SET topic_attachment = 1
				WHERE topic_id = $topic_id";
			$db->sql_query($sql);

			$topic_data['topic_attachment'] = 1;
		}
	}
	else
	{
		$display_notice = true;
	}
}

// Instantiate BBCode if need be
if ($bbcode_bitfield !== '')
{
	$bbcode = new bbcode(base64_encode($bbcode_bitfield));
}

$i_total = sizeof($rowset) - 1;
$prev_post_id = '';

$template->assign_vars(array(
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

	$row =& $rowset[$post_list[$i]];
	$poster_id = $row['user_id'];

	// End signature parsing, only if needed
	if ($user_cache[$poster_id]['sig'] && $row['enable_sig'] && empty($user_cache[$poster_id]['sig_parsed']))
	{
		$user_cache[$poster_id]['sig'] = censor_text($user_cache[$poster_id]['sig']);

		if ($user_cache[$poster_id]['sig_bbcode_bitfield'])
		{
			$bbcode->bbcode_second_pass($user_cache[$poster_id]['sig'], $user_cache[$poster_id]['sig_bbcode_uid'], $user_cache[$poster_id]['sig_bbcode_bitfield']);
		}

		$user_cache[$poster_id]['sig'] = bbcode_nl2br($user_cache[$poster_id]['sig']);
		$user_cache[$poster_id]['sig'] = smiley_text($user_cache[$poster_id]['sig']);
		$user_cache[$poster_id]['sig_parsed'] = true;
	}

	// Parse the message and subject
	$message = censor_text($row['post_text']);

	// Remove ideas link
	if (is_numeric(strpos($message, "\n\n----------\n\n"))) {
		$message = explode("\n\n----------\n\n", $message);
		$message[count($message) - 1] = '';
		$message = implode("\n\n----------\n\n", $message);
		$message = substr($message, 0, -14);
	}

	// Second parse bbcode here
	if ($row['bbcode_bitfield'])
	{
		$bbcode->bbcode_second_pass($message, $row['bbcode_uid'], $row['bbcode_bitfield']);
	}

	$message = bbcode_nl2br($message);
	$message = smiley_text($message);

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
	if (($row['post_edit_count'] && $config['display_last_edited']) || $row['post_edit_reason'])
	{
		// Get usernames for all following posts if not already stored
		if (!sizeof($post_edit_list) && ($row['post_edit_reason'] || ($row['post_edit_user'] && !isset($user_cache[$row['post_edit_user']]))))
		{
			// Remove all post_ids already parsed (we do not have to check them)
			$post_storage_list = (!$store_reverse) ? array_slice($post_list, $i) : array_slice(array_reverse($post_list), $i);

			$sql = 'SELECT DISTINCT u.user_id, u.username, u.user_colour
				FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
				WHERE ' . $db->sql_in_set('p.post_id', $post_storage_list) . '
					AND p.post_edit_count <> 0
					AND p.post_edit_user <> 0
					AND p.post_edit_user = u.user_id';
			$result2 = $db->sql_query($sql);
			while ($user_edit_row = $db->sql_fetchrow($result2))
			{
				$post_edit_list[$user_edit_row['user_id']] = $user_edit_row;
			}
			$db->sql_freeresult($result2);

			unset($post_storage_list);
		}

		$l_edit_time_total = ($row['post_edit_count'] == 1) ? $user->lang['EDITED_TIME_TOTAL'] : $user->lang['EDITED_TIMES_TOTAL'];

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

			$l_edited_by = sprintf($l_edit_time_total, $display_username, $user->format_date($row['post_edit_time'], false, true), $row['post_edit_count']);
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

			$l_edited_by = sprintf($l_edit_time_total, $display_username, $user->format_date($row['post_edit_time'], false, true), $row['post_edit_count']);
		}
	}
	else
	{
		$l_edited_by = '';
	}

	// Bump information
	if ($topic_data['topic_bumped'] && $row['post_id'] == $topic_data['topic_last_post_id'] && isset($user_cache[$topic_data['topic_bumper']]) )
	{
		// It is safe to grab the username from the user cache array, we are at the last
		// post and only the topic poster and last poster are allowed to bump.
		// Admins and mods are bound to the above rules too...
		$l_bumped_by = sprintf($user->lang['BUMPED_BY'], $user_cache[$topic_data['topic_bumper']]['username'], $user->format_date($topic_data['topic_last_post_time'], false, true));
	}
	else
	{
		$l_bumped_by = '';
	}

	$cp_row = array();

	//
	if ($config['load_cpf_viewtopic'])
	{
		$cp_row = (isset($profile_fields_cache[$poster_id])) ? $cp->generate_profile_fields_template('show', false, $profile_fields_cache[$poster_id]) : array();
	}

	$post_unread = (isset($topic_tracking_info[$topic_id]) && $row['post_time'] > $topic_tracking_info[$topic_id]) ? true : false;

	$s_first_unread = false;
	if (!$first_unread && $post_unread)
	{
		$s_first_unread = $first_unread = true;
	}

	$edit_allowed = ($user->data['is_registered'] && ($auth->acl_get('m_edit', $forum_id) || (
		$user->data['user_id'] == $poster_id &&
		$auth->acl_get('f_edit', $forum_id) &&
		!$row['post_edit_locked'] &&
		($row['post_time'] > time() - ($config['edit_time'] * 60) || !$config['edit_time'])
	)));

	$delete_allowed = ($user->data['is_registered'] && ($auth->acl_get('m_delete', $forum_id) || (
		$user->data['user_id'] == $poster_id &&
		$auth->acl_get('f_delete', $forum_id) &&
		$topic_data['topic_last_post_id'] == $row['post_id'] &&
		($row['post_time'] > time() - ($config['delete_time'] * 60) || !$config['delete_time']) &&
		// we do not want to allow removal of the last post if a moderator locked it!
		!$row['post_edit_locked']
	)));

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
		'POSTER_FROM'		=> $user_cache[$poster_id]['from'],
		'POSTER_AVATAR'		=> $user_cache[$poster_id]['avatar'],
		'POSTER_WARNINGS'	=> $user_cache[$poster_id]['warnings'],
		'POSTER_AGE'		=> $user_cache[$poster_id]['age'],

		'POST_DATE'			=> $user->format_date($row['post_time'], false, ($view == 'print') ? true : false),
		'POST_SUBJECT'		=> $row['post_subject'],
		'MESSAGE'			=> $message,
		'SIGNATURE'			=> ($row['enable_sig']) ? $user_cache[$poster_id]['sig'] : '',
		'EDITED_MESSAGE'	=> $l_edited_by,
		'EDIT_REASON'		=> $row['post_edit_reason'],
		'BUMPED_MESSAGE'	=> $l_bumped_by,

		'MINI_POST_IMG'			=> ($post_unread) ? $user->img('icon_post_target_unread', 'UNREAD_POST') : $user->img('icon_post_target', 'POST'),
		'POST_ICON_IMG'			=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['img'] : '',
		'POST_ICON_IMG_WIDTH'	=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['width'] : '',
		'POST_ICON_IMG_HEIGHT'	=> ($topic_data['enable_icons'] && !empty($row['icon_id'])) ? $icons[$row['icon_id']]['height'] : '',
		'ICQ_STATUS_IMG'		=> $user_cache[$poster_id]['icq_status_img'],
		'ONLINE_IMG'			=> ($poster_id == ANONYMOUS || !$config['load_onlinetrack']) ? '' : (($user_cache[$poster_id]['online']) ? $user->img('icon_user_online', 'ONLINE') : $user->img('icon_user_offline', 'OFFLINE')),
		'S_ONLINE'				=> ($poster_id == ANONYMOUS || !$config['load_onlinetrack']) ? false : (($user_cache[$poster_id]['online']) ? true : false),

		'U_EDIT'			=> ($edit_allowed) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=edit&amp;f=$forum_id&amp;p={$row['post_id']}") : '',
		'U_QUOTE'			=> ($auth->acl_get('f_reply', $forum_id)) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=quote&amp;f=$forum_id&amp;p={$row['post_id']}") : '',
		'U_INFO'			=> ($auth->acl_get('m_info', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", "i=main&amp;mode=post_details&amp;f=$forum_id&amp;p=" . $row['post_id'], true, $user->session_id) : '',
		'U_DELETE'			=> ($delete_allowed) ? append_sid("{$phpbb_root_path}posting.$phpEx", "mode=delete&amp;f=$forum_id&amp;p={$row['post_id']}") : '',

		'U_PROFILE'		=> $user_cache[$poster_id]['profile'],
		'U_SEARCH'		=> $user_cache[$poster_id]['search'],
		'U_PM'			=> ($poster_id != ANONYMOUS && $config['allow_privmsg'] && $auth->acl_get('u_sendpm') && ($user_cache[$poster_id]['allow_pm'] || $auth->acl_gets('a_', 'm_') || $auth->acl_getf_global('m_'))) ? append_sid("{$phpbb_root_path}ucp.$phpEx", 'i=pm&amp;mode=compose&amp;action=quotepost&amp;p=' . $row['post_id']) : '',
		'U_EMAIL'		=> $user_cache[$poster_id]['email'],
		'U_WWW'			=> $user_cache[$poster_id]['www'],
		'U_ICQ'			=> $user_cache[$poster_id]['icq'],
		'U_AIM'			=> $user_cache[$poster_id]['aim'],
		'U_MSN'			=> $user_cache[$poster_id]['msn'],
		'U_YIM'			=> $user_cache[$poster_id]['yim'],
		'U_JABBER'		=> $user_cache[$poster_id]['jabber'],

		'U_REPORT'			=> ($auth->acl_get('f_report', $forum_id)) ? append_sid("{$phpbb_root_path}report.$phpEx", 'f=' . $forum_id . '&amp;p=' . $row['post_id']) : '',
		'U_MCP_REPORT'		=> ($auth->acl_get('m_report', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=reports&amp;mode=report_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $user->session_id) : '',
		'U_MCP_APPROVE'		=> ($auth->acl_get('m_approve', $forum_id)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=queue&amp;mode=approve_details&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $user->session_id) : '',
		'U_MINI_POST'		=> append_sid("idea.$phpEx", 'id=' . $id . '&p=' . $row['post_id']) . (($topic_data['topic_type'] == POST_GLOBAL) ? '&amp;f=' . $forum_id : '') . '#p' . $row['post_id'],
		'U_NEXT_POST_ID'	=> ($i < $i_total && isset($rowset[$post_list[$i + 1]])) ? $rowset[$post_list[$i + 1]]['post_id'] : '',
		'U_PREV_POST_ID'	=> $prev_post_id,
		'U_NOTES'			=> ($auth->acl_getf_global('m_')) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=notes&amp;mode=user_notes&amp;u=' . $poster_id, true, $user->session_id) : '',
		'U_WARN'			=> ($auth->acl_get('m_warn') && $poster_id != $user->data['user_id'] && $poster_id != ANONYMOUS) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=warn&amp;mode=warn_post&amp;f=' . $forum_id . '&amp;p=' . $row['post_id'], true, $user->session_id) : '',

		'POST_ID'			=> $row['post_id'],
		'POST_NUMBER'		=> $i + $start + 1,
		'POSTER_ID'			=> $poster_id,

		'S_HAS_ATTACHMENTS'	=> (!empty($attachments[$row['post_id']])) ? true : false,
		'S_POST_UNAPPROVED'	=> ($row['post_approved']) ? false : true,
		'S_POST_REPORTED'	=> ($row['post_reported'] && $auth->acl_get('m_report', $forum_id)) ? true : false,
		'S_DISPLAY_NOTICE'	=> $display_notice && $row['post_attachment'],
		'S_FRIEND'			=> ($row['friend']) ? true : false,
		'S_UNREAD_POST'		=> $post_unread,
		'S_FIRST_UNREAD'	=> $s_first_unread,
		'S_CUSTOM_FIELDS'	=> (isset($cp_row['row']) && sizeof($cp_row['row'])) ? true : false,
		'S_TOPIC_POSTER'	=> ($topic_data['topic_poster'] == $poster_id) ? true : false,

		'S_IGNORE_POST'		=> ($row['hide_post']) ? true : false,
		'L_IGNORE_POST'		=> ($row['hide_post']) ? sprintf($user->lang['POST_BY_FOE'], get_username_string('full', $poster_id, $row['username'], $row['user_colour'], $row['post_username']), '<a href="' . $viewtopic_url . "&amp;p={$row['post_id']}&amp;view=show#p{$row['post_id']}" . '">', '</a>') : '',
	);

	if (isset($cp_row['row']) && sizeof($cp_row['row']))
	{
		$postrow = array_merge($postrow, $cp_row['row']);
	}

	// Dump vars into template
	$template->assign_block_vars('postrow', $postrow);

	if (!empty($cp_row['blockrow']))
	{
		foreach ($cp_row['blockrow'] as $field_data)
		{
			$template->assign_block_vars('postrow.custom_fields', $field_data);
		}
	}

	// Display not already displayed Attachments for this post, we already parsed them. ;)
	if (!empty($attachments[$row['post_id']]))
	{
		foreach ($attachments[$row['post_id']] as $attachment)
		{
			$template->assign_block_vars('postrow.attachment', array(
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
if (isset($user->data['session_page']) && !$user->data['is_bot'] && (strpos($user->data['session_page'], '&t=' . $topic_id) === false || isset($user->data['session_created'])))
{
	$sql = 'UPDATE ' . TOPICS_TABLE . '
		SET topic_views = topic_views + 1, topic_last_view_time = ' . time() . "
		WHERE topic_id = $topic_id";
	$db->sql_query($sql);

	// Update the attachment download counts
	if (sizeof($update_count))
	{
		$sql = 'UPDATE ' . ATTACHMENTS_TABLE . '
			SET download_count = download_count + 1
			WHERE ' . $db->sql_in_set('attach_id', array_unique($update_count));
		$db->sql_query($sql);
	}
}

// Get last post time for all global announcements
// to keep proper forums tracking
if ($topic_data['topic_type'] == POST_GLOBAL)
{
	$sql = 'SELECT topic_last_post_time as forum_last_post_time
		FROM ' . TOPICS_TABLE . '
		WHERE forum_id = 0
		ORDER BY topic_last_post_time DESC';
	$result = $db->sql_query_limit($sql, 1);
	$topic_data['forum_last_post_time'] = (int) $db->sql_fetchfield('forum_last_post_time');
	$db->sql_freeresult($result);

	$sql = 'SELECT mark_time as forum_mark_time
		FROM ' . FORUMS_TRACK_TABLE . '
		WHERE forum_id = 0
			AND user_id = ' . $user->data['user_id'];
	$result = $db->sql_query($sql);
	$topic_data['forum_mark_time'] = (int) $db->sql_fetchfield('forum_mark_time');
	$db->sql_freeresult($result);
}

// Only mark topic if it's currently unread. Also make sure we do not set topic tracking back if earlier pages are viewed.
if (isset($topic_tracking_info[$topic_id]) && $topic_data['topic_last_post_time'] > $topic_tracking_info[$topic_id] && $max_post_time > $topic_tracking_info[$topic_id])
{
	markread('topic', (($topic_data['topic_type'] == POST_GLOBAL) ? 0 : $forum_id), $topic_id, $max_post_time);

	// Update forum info
	$all_marked_read = update_forum_tracking_info((($topic_data['topic_type'] == POST_GLOBAL) ? 0 : $forum_id), $topic_data['forum_last_post_time'], (isset($topic_data['forum_mark_time'])) ? $topic_data['forum_mark_time'] : false, false);
}
else
{
	$all_marked_read = true;
}

// If there are absolutely no more unread posts in this forum and unread posts shown, we can savely show the #unread link
if ($all_marked_read)
{
	if ($post_unread)
	{
		$template->assign_vars(array(
			'U_VIEW_UNREAD_POST'	=> '#unread',
		));
	}
	else if (isset($topic_tracking_info[$topic_id]) && $topic_data['topic_last_post_time'] > $topic_tracking_info[$topic_id])
	{
		$template->assign_vars(array(
			'U_VIEW_UNREAD_POST'	=> append_sid("idea.$phpEx", "id=$id&amp;view=unread") . '#unread',
		));
	}
}
else if (!$all_marked_read)
{
	$last_page = ((floor($start / $config['posts_per_page']) + 1) == max(ceil($total_posts / $config['posts_per_page']), 1)) ? true : false;

	// What can happen is that we are at the last displayed page. If so, we also display the #unread link based in $post_unread
	if ($last_page && $post_unread)
	{
		$template->assign_vars(array(
			'U_VIEW_UNREAD_POST'	=> '#unread',
		));
	}
	else if (!$last_page)
	{
		$template->assign_vars(array(
			'U_VIEW_UNREAD_POST'	=> append_sid("idea.$phpEx", "id=$id&amp;view=unread") . '#unread',
		));
	}
}

// let's set up quick_reply
$s_quick_reply = false;
if ($user->data['is_registered'] && $config['allow_quick_reply'] && ($topic_data['forum_flags'] & FORUM_FLAG_QUICK_REPLY) && $auth->acl_get('f_reply', $forum_id))
{
	// Quick reply enabled forum
	$s_quick_reply = (($topic_data['forum_status'] == ITEM_UNLOCKED && $topic_data['topic_status'] == ITEM_UNLOCKED) || $auth->acl_get('m_edit', $forum_id)) ? true : false;
}

if ($s_quick_reply)
{
	add_form_key('posting');

	if ($s_quick_reply)
	{
		$s_attach_sig	= $config['allow_sig'] && $user->optionget('attachsig') && $auth->acl_get('f_sigs', $forum_id) && $auth->acl_get('u_sig');
		$s_smilies		= $config['allow_smilies'] && $user->optionget('smilies') && $auth->acl_get('f_smilies', $forum_id);
		$s_bbcode		= $config['allow_bbcode'] && $user->optionget('bbcode') && $auth->acl_get('f_bbcode', $forum_id);
		$s_notify		= $config['allow_topic_notify'] && ($user->data['user_notify'] || $s_watching_topic['is_watching']);

		$qr_hidden_fields = array(
			'topic_cur_post_id'		=> (int) $topic_data['topic_last_post_id'],
			'lastclick'				=> (int) time(),
			'topic_id'				=> (int) $topic_data['topic_id'],
			'forum_id'				=> (int) $forum_id,
		);

		// Originally we use checkboxes and check with isset(), so we only provide them if they would be checked
		(!$s_bbcode)					? $qr_hidden_fields['disable_bbcode'] = 1		: true;
		(!$s_smilies)					? $qr_hidden_fields['disable_smilies'] = 1		: true;
		(!$config['allow_post_links'])	? $qr_hidden_fields['disable_magic_url'] = 1	: true;
		($s_attach_sig)					? $qr_hidden_fields['attach_sig'] = 1			: true;
		($s_notify)						? $qr_hidden_fields['notify'] = 1				: true;
		($topic_data['topic_status'] == ITEM_LOCKED) ? $qr_hidden_fields['lock_topic'] = 1 : true;

		$template->assign_vars(array(
			'S_QUICK_REPLY'			=> true,
			'U_QR_ACTION'			=> append_sid("{$phpbb_root_path}posting.$phpEx", "mode=reply&amp;f=$forum_id&amp;t=$topic_id"),
			'QR_HIDDEN_FIELDS'		=> build_hidden_fields($qr_hidden_fields),
			'SUBJECT'				=> 'Re: ' . censor_text($topic_data['topic_title']),
		));
	}
}
// now I have the urge to wash my hands :(


// We overwrite $_REQUEST['f'] if there is no forum specified
// to be able to display the correct online list.
// One downside is that the user currently viewing this topic/post is not taken into account.
if (empty($_REQUEST['f']))
{
	$_REQUEST['f'] = $forum_id;
}

// We need to do the same with the topic_id. See #53025.
if (empty($_REQUEST['t']) && !empty($topic_id))
{
	$_REQUEST['t'] = $topic_id;
}


// Output the page
page_header($user->lang['VIEW_IDEA'] . ' - ' . $idea['idea_title'], false);

$template->set_filenames(array(
	'body' => 'ideas/idea_body.html'
));

page_footer();

?>
