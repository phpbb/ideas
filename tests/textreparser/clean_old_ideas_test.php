<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\textreparser;

include_once __DIR__ . '/../../../../../../tests/text_reparser/plugins/test_row_based_plugin.php';

class clean_old_ideas_test extends \phpbb_textreparser_test_row_based_plugin
{
	protected static function setup_extensions()
	{
		return array('phpbb/ideas');
	}

	public function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/fixtures/ideas.xml');
	}

	protected function get_reparser()
	{
		return new \phpbb\ideas\textreparser\plugins\clean_old_ideas(
			$this->db,
			new \phpbb\textformatter\s9e\utils(),
			'phpbb_posts',
			'phpbb_topics',
			'phpbb_ideas_ideas'
		);
	}

	public function get_reparse_tests()
	{
		return [
			[
				1,
				5,
				[
					[
						'id'   => '100',
						'text' => 'This post has nothing to change',
					],
					[
						'id'   => '200',
						'text' => '<r>This post is too new to be cleaned<IDEA idea="1000"><s>[idea=1000]</s>Linked Idea<e>[/idea]</e></IDEA><USER user="1000"><s>[user=1000]</s>testuser<e>[/user]</e></USER></r>',
					],
					[
						'id'   => '300',
						'text' => '<r>This post should be cleaned</r>',
					],
					[
						'id'   => '400',
						'text' => '<r>This post is a reply and should not be changed<IDEA idea="1000"><s>[idea=1000]</s>Linked Idea<e>[/idea]</e></IDEA><USER user="1000"><s>[user=1000]</s>testuser<e>[/user]</e></USER></r>',
					],
					[
						'id'   => '101010',
						'text' => '<r>This post is out of range<IDEA idea="1000"><s>[idea=1000]</s>Linked Idea<e>[/idea]</e></IDEA><USER user="1000"><s>[user=1000]</s>testuser<e>[/user]</e></USER></r>',
					],
				]
			],
		];
	}
}
