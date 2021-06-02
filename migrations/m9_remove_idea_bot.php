<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\migrations;

class m9_remove_idea_bot extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return !$this->config->offsetExists('ideas_poster_id');
	}

	public static function depends_on()
	{
		return [
			'\phpbb\ideas\migrations\m1_initial_schema',
			'\phpbb\ideas\migrations\m3_acp_data',
			'\phpbb\ideas\migrations\m4_update_statuses',
			'\phpbb\ideas\migrations\m6_migrate_old_tables',
			'\phpbb\ideas\migrations\m7_drop_old_tables',
		];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'update_topic_authors']]],
			['config.remove', ['ideas_poster_id']],
		];
	}

	/**
	 * Replace the Ideas Bot stored in the posts and topics tables with the
	 * original author's information. Bot gone, order restored to universe.
	 */
	public function update_topic_authors()
	{
		// Return if the Ideas Bot does not exist at this point for some reason.
		if (!$this->config->offsetExists('ideas_poster_id'))
		{
			return;
		}

		// Get real author info for ideas that were posted by the Ideas Bot
		$topics = [];
		$sql_array = [
			'SELECT'	=> 'i.topic_id, i.idea_author, u.username, u.user_colour, t.topic_first_post_id',
			'FROM'		=> [
				$this->table_prefix . 'ideas_ideas'	=> 'i',
			],
			'LEFT_JOIN'	=> [
				[
					'FROM'	=> [$this->table_prefix . 'topics' => 't'],
					'ON'	=> 't.topic_id = i.topic_id',
				],
				[
					'FROM'	=> [$this->table_prefix . 'users' => 'u'],
					'ON'	=> 'u.user_id = i.idea_author',
				],
			],
			'WHERE'		=> 't.topic_poster = ' . (int) $this->config->offsetGet('ideas_poster_id'),
		];

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$topics[$row['topic_id']] = [
				'topic_poster_id'     => $row['idea_author'] ?: ANONYMOUS,
				'topic_poster_name'   => $row['username'] ?: '',
				'topic_poster_colour' => $row['user_colour'] ?: '',
				'topic_first_post_id' => $row['topic_first_post_id'] ?: 0,
			];
		}
		$this->db->sql_freeresult($result);

		// Begin updating topics and posts
		$this->db->sql_transaction('begin');
		foreach ($topics as $topic_id => $data)
		{
			// Update topic author (first poster)
			$sql = 'UPDATE ' . $this->table_prefix . 'topics
				SET ' . $this->db->sql_build_array('UPDATE', [
					'topic_poster'              => $data['topic_poster_id'],
					'topic_first_poster_name'   => $data['topic_poster_name'],
					'topic_first_poster_colour' => $data['topic_poster_colour'],
				]) . '
				WHERE topic_id = ' . (int) $topic_id;
			$this->db->sql_query($sql);

			// Update last poster if it's also the Ideas Bot (i.e: no replies)
			$sql = 'UPDATE ' . $this->table_prefix . 'topics
				SET ' . $this->db->sql_build_array('UPDATE', [
					'topic_last_poster_id'     => $data['topic_poster_id'],
					'topic_last_poster_name'   => $data['topic_poster_name'],
					'topic_last_poster_colour' => $data['topic_poster_colour'],
				]) . '
				WHERE topic_id = ' . (int) $topic_id . '
					AND topic_last_poster_id = ' . (int) $this->config->offsetGet('ideas_poster_id');
			$this->db->sql_query($sql);

			// Update first post's poster id if it's the Ideas Bot
			$sql = 'UPDATE ' . $this->table_prefix . 'posts' . '
				SET poster_id = ' . (int) $data['topic_poster_id'] . '
				WHERE post_id = ' . (int) $data['topic_first_post_id'] . '
					AND poster_id = ' . (int) $this->config->offsetGet('ideas_poster_id');
			$this->db->sql_query($sql);
		}
		$this->db->sql_transaction('commit');
	}
}
