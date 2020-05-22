<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\textreparser\plugins;

class clean_old_ideas extends \phpbb\textreparser\row_based_plugin
{
	/** @var \phpbb\textformatter\s9e\utils */
	protected $text_formatter_utils;

	/** @var string */
	protected $ideas_table;

	/** @var string  */
	protected $topics_table;

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface $db                   Database connection
	 * @param \phpbb\textformatter\s9e\utils    $text_formatter_utils Text formatter utilities object
	 * @param string                            $table                Posts Table
	 * @param string                            $topics_table         Topics Table
	 * @param string                            $ideas_table          Ideas Table
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\textformatter\s9e\utils $text_formatter_utils, $table, $topics_table, $ideas_table)
	{
		parent::__construct($db, $table);

		$this->text_formatter_utils = $text_formatter_utils;
		$this->ideas_table = $ideas_table;
		$this->topics_table = $topics_table;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_columns()
	{
		return [
			'id'   => 'post_id',
			'text' => 'post_text',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_max_id()
	{
		$sql = 'SELECT MAX(idea_id) AS max_id FROM ' . $this->ideas_table;
		$result = $this->db->sql_query($sql);
		$max_id = (int) $this->db->sql_fetchfield('max_id');
		$this->db->sql_freeresult($result);

		return $max_id;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_records_by_range_query($min_id, $max_id)
	{
		$columns = $this->get_columns();

		$fields = [];
		foreach ($columns as $field_name => $column_name)
		{
			$fields[] = 'p.' . $column_name . ' AS ' . $field_name;
		}

		// Query the first post's text for ideas created prior to Sep. 2017
		return 'SELECT ' . implode(', ', $fields) . '
			FROM ' . $this->table . ' p
			INNER JOIN ' . $this->ideas_table . ' i
				ON i.topic_id = p.topic_id
			INNER JOIN ' . $this->topics_table . ' t
				ON p.' . $columns['id'] . ' = t.topic_first_post_id
			WHERE i.idea_date < ' . strtotime('September 1, 2017') . '
				AND  i.idea_id BETWEEN ' . $min_id . ' AND ' . $max_id;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function reparse_record(array $record)
	{
		$text = $record['text'];

		// Remove the USER bbcode from the idea post
		$text = $this->text_formatter_utils->remove_bbcode($text, 'user');

		// Remove the IDEA bbcode from the idea post
		$text = $this->text_formatter_utils->remove_bbcode($text, 'idea');

		// Remove old strings from the idea post
		$text = str_replace(['----------', 'View idea at: ', 'Posted by '], ['', '', ''], $text);

		// Save the new text if it has changed and it's not a dry run
		if ($text !== $record['text'] && $this->save_changes)
		{
			$record['text'] = $text;
			$this->save_record($record);
		}
	}
}
