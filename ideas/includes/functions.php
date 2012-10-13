<?php

/**
*
* @package phpBB3 Ideas
* @author Callum Macrae (callumacrae) <callum@lynxphp.com>
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
 * Returns a link to the users profile, complete with colour.
 *
 * Is there a function that already does this? This seems fairly database heavy.
 *
 * @param int $id The ID of the user.
 * @return string An HTML link to the users profile.
 */
function ideas_get_user_link($id)
{
	global $db;
	$sql = 'SELECT username, user_colour
		FROM ' . USERS_TABLE . '
		WHERE user_id = ' . $id;
	$result = $db->sql_query_limit($sql, 1);
	$author = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	return get_username_string('full', $id, $author['username'], $author['user_colour']);
}

/**
 * Returns whether a request was requested using XMLHttpRequest or not.
 *
 * @return bool True if request is AJAX.
 */
function ideas_is_ajax()
{
	return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
		strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Submit post from a specified user ID. Modified version of submit_post function
 */
function ideas_submit_post($subject, $topic_type, &$data, $update_search_index = true)
{
	global $db, $auth, $user, $config, $phpEx, $phpbb_root_path;

	$current_time = time();
	$mode = 'post';

	// First of all make sure the subject and topic title are having the correct length.
	// To achieve this without cutting off between special chars we convert to an array and then count the elements.
	$subject = truncate_string($subject);
	$data['topic_title'] = truncate_string($data['topic_title']);

	// Collect some basic information about which tables and which rows to update/insert
	$sql_data = $topic_row = array();
	$poster_id = $data['poster_id'];

	// TODO: Introduce an approval system
	$post_approval = 1;

	// Get Ideas Bot info
	$sql = 'SELECT username, user_colour
		FROM ' . USERS_TABLE . '
		WHERE user_id = ' . $poster_id;
	$result = $db->sql_query_limit($sql, 1);
	$poster_bot = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	// Start the transaction here
	$db->sql_transaction('begin');

	$sql_data[POSTS_TABLE]['sql'] = array(
		'forum_id'			=> $data['forum_id'],
		'poster_id'			=> (int) $poster_id,
		'icon_id'			=> $data['icon_id'],
		'poster_ip'			=> $user->ip,
		'post_time'			=> $current_time,
		'post_approved'		=> $post_approval,
		'enable_bbcode'		=> $data['enable_bbcode'],
		'enable_smilies'	=> $data['enable_smilies'],
		'enable_magic_url'	=> $data['enable_urls'],
		'enable_sig'		=> $data['enable_sig'],
		'post_username'		=> $poster_bot['username'],
		'post_subject'		=> $subject,
		'post_text'			=> $data['message'],
		'post_checksum'		=> $data['message_md5'],
		'post_attachment'	=> 0,
		'bbcode_bitfield'	=> $data['bbcode_bitfield'],
		'bbcode_uid'		=> $data['bbcode_uid'],
		'post_postcount'	=> 1,
		'post_edit_locked'	=> $data['post_edit_locked']
	);

	$post_approved = $sql_data[POSTS_TABLE]['sql']['post_approved'];

	// And the topic ladies and gentlemen
	$sql_data[TOPICS_TABLE]['sql'] = array(
		'topic_poster'				=> (int) $poster_id,
		'topic_time'				=> $current_time,
		'topic_last_view_time'		=> $current_time,
		'forum_id'					=> ($topic_type == POST_GLOBAL) ? 0 : $data['forum_id'],
		'icon_id'					=> $data['icon_id'],
		'topic_approved'			=> $post_approval,
		'topic_title'				=> $subject,
		'topic_first_poster_name'	=> $poster_bot['username'],
		'topic_first_poster_colour'	=> $poster_bot['user_colour'],
		'topic_type'				=> $topic_type,
		'topic_time_limit'			=> ($topic_type == POST_STICKY || $topic_type == POST_ANNOUNCE) ? ($data['topic_time_limit'] * 86400) : 0,
		'topic_attachment'			=> (!empty($data['attachment_data'])) ? 1 : 0,
	);

	$sql_data[USERS_TABLE]['stat'][] = "user_lastpost_time = $current_time" . (($auth->acl_get('f_postcount', $data['forum_id']) && $post_approval) ? ', user_posts = user_posts + 1' : '');

	if ($topic_type != POST_GLOBAL)
	{
		if ($post_approval)
		{
			$sql_data[FORUMS_TABLE]['stat'][] = 'forum_posts = forum_posts + 1';
		}
		$sql_data[FORUMS_TABLE]['stat'][] = 'forum_topics_real = forum_topics_real + 1' . (($post_approval) ? ', forum_topics = forum_topics + 1' : '');
	}

	// Submit new topic
	$sql = 'INSERT INTO ' . TOPICS_TABLE . ' ' .
		$db->sql_build_array('INSERT', $sql_data[TOPICS_TABLE]['sql']);
	$db->sql_query($sql);

	$data['topic_id'] = $db->sql_nextid();

	$sql_data[POSTS_TABLE]['sql'] = array_merge($sql_data[POSTS_TABLE]['sql'], array(
			'topic_id' => $data['topic_id'])
	);
	unset($sql_data[TOPICS_TABLE]['sql']);

	// Submit new post
	$sql = 'INSERT INTO ' . POSTS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_data[POSTS_TABLE]['sql']);
	$db->sql_query($sql);
	$data['post_id'] = $db->sql_nextid();

	$sql_data[TOPICS_TABLE]['sql'] = array(
		'topic_first_post_id'		=> $data['post_id'],
		'topic_last_post_id'		=> $data['post_id'],
		'topic_last_post_time'		=> $current_time,
		'topic_last_poster_id'		=> (int) $poster_id,
		'topic_last_poster_name'	=> $poster_bot['username'],
		'topic_last_poster_colour'	=> $poster_bot['user_colour'],
		'topic_last_post_subject'	=> (string) $subject,
	);

	unset($sql_data[POSTS_TABLE]['sql']);

	// Update the topics table
	if (isset($sql_data[TOPICS_TABLE]['sql']))
	{
		$sql = 'UPDATE ' . TOPICS_TABLE . '
			SET ' . $db->sql_build_array('UPDATE', $sql_data[TOPICS_TABLE]['sql']) . '
			WHERE topic_id = ' . $data['topic_id'];
		$db->sql_query($sql);
	}

	// Update the posts table
	if (isset($sql_data[POSTS_TABLE]['sql']))
	{
		$sql = 'UPDATE ' . POSTS_TABLE . '
			SET ' . $db->sql_build_array('UPDATE', $sql_data[POSTS_TABLE]['sql']) . '
			WHERE post_id = ' . $data['post_id'];
		$db->sql_query($sql);
	}

	// we need to update the last forum information
	// only applicable if the topic is not global and it is approved
	// we also check to make sure we are not dealing with globaling the latest topic (pretty rare but still needs to be checked)
	if ($post_approved || !$data['post_approved'])
	{
		$sql_data[FORUMS_TABLE]['stat'][] = 'forum_last_post_id = ' . $data['post_id'];
		$sql_data[FORUMS_TABLE]['stat'][] = "forum_last_post_subject = '" . $db->sql_escape($subject) . "'";
		$sql_data[FORUMS_TABLE]['stat'][] = 'forum_last_post_time = ' . $current_time;
		$sql_data[FORUMS_TABLE]['stat'][] = 'forum_last_poster_id = ' . (int) $poster_id;
		$sql_data[FORUMS_TABLE]['stat'][] = "forum_last_poster_name = '" . $db->sql_escape($poster_bot['username']) . "'";
		$sql_data[FORUMS_TABLE]['stat'][] = "forum_last_poster_colour = '" . $db->sql_escape($poster_bot['user_colour']) . "'";
	}

	// Update total post count, do not consider moderated posts/topics
	if ($post_approval)
	{
		set_config_count('num_topics', 1, true);
		set_config_count('num_posts', 1, true);
	}

	// Update forum stats
	$where_sql = array(POSTS_TABLE => 'post_id = ' . $data['post_id'], TOPICS_TABLE => 'topic_id = ' . $data['topic_id'], FORUMS_TABLE => 'forum_id = ' . $data['forum_id'], USERS_TABLE => 'user_id = ' . $poster_id);

	foreach ($sql_data as $table => $update_ary)
	{
		if (isset($update_ary['stat']) && implode('', $update_ary['stat']))
		{
			$sql = "UPDATE $table SET " . implode(', ', $update_ary['stat']) . ' WHERE ' . $where_sql[$table];
			$db->sql_query($sql);
		}
	}

	// Committing the transaction before updating search index
	$db->sql_transaction('commit');

	// Index message contents
	if ($update_search_index && $data['enable_indexing'])
	{
		// Select the search method and do some additional checks to ensure it can actually be utilised
		$search_type = basename($config['search_type']);

		if (!file_exists($phpbb_root_path . 'includes/search/' . $search_type . '.' . $phpEx))
		{
			trigger_error('NO_SUCH_SEARCH_MODULE');
		}

		if (!class_exists($search_type))
		{
			include("{$phpbb_root_path}includes/search/$search_type.$phpEx");
		}

		$error = false;
		$search = new $search_type($error);

		if ($error)
		{
			trigger_error($error);
		}

		$search->index($mode, $data['post_id'], $data['message'], $subject, $poster_id, ($topic_type == POST_GLOBAL) ? 0 : $data['forum_id']);
	}

	// Mark this topic as posted to
	markread('post', $data['forum_id'], $data['topic_id']);

	// Mark this topic as read
	// We do not use post_time here, this is intended (post_time can have a date in the past if editing a message)
	markread('topic', (($topic_type == POST_GLOBAL) ? 0 : $data['forum_id']), $data['topic_id'], time());

	if ($config['load_db_lastread'] && $user->data['is_registered'])
	{
		$sql = 'SELECT mark_time
			FROM ' . FORUMS_TRACK_TABLE . '
			WHERE user_id = ' . $user->data['user_id'] . '
				AND forum_id = ' . (($topic_type == POST_GLOBAL) ? 0 : $data['forum_id']);
		$result = $db->sql_query($sql);
		$f_mark_time = (int) $db->sql_fetchfield('mark_time');
		$db->sql_freeresult($result);
	}
	else if ($config['load_anon_lastread'] || $user->data['is_registered'])
	{
		$f_mark_time = false;
	}

	if (($config['load_db_lastread'] && $user->data['is_registered']) || $config['load_anon_lastread'] || $user->data['is_registered'])
	{
		// Update forum info
		$sql = 'SELECT forum_last_post_time
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . $data['forum_id'];
		$result = $db->sql_query($sql);
		$forum_last_post_time = (int) $db->sql_fetchfield('forum_last_post_time');
		$db->sql_freeresult($result);

		update_forum_tracking_info((($topic_type == POST_GLOBAL) ? 0 : $data['forum_id']), $forum_last_post_time, $f_mark_time, false);
	}

	// Send Notifications
	if ($mode == 'post' && $post_approval)
	{
		user_notification($mode, $subject, $data['topic_title'], $data['forum_name'], $data['forum_id'], $data['topic_id'], $data['post_id']);
	}

	$params = $add_anchor = '';

	if ($post_approval)
	{
		$params .= '&amp;t=' . $data['topic_id'];
	}

	$url = (!$params) ? "{$phpbb_root_path}viewforum.$phpEx" : "{$phpbb_root_path}viewtopic.$phpEx";
	$url = append_sid($url, 'f=' . $data['forum_id'] . $params) . $add_anchor;

	return $url;
}
