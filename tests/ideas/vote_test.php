<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\tests\ideas;

class vote_test extends ideas_base
{
	/**
	 * Test vote() data
	 *
	 * @return array
	 */
	public function vote_test_data()
	{
		// idea 1 has 3 up votes (3 points)
		// idea 2 has 1 down vote (-1 point)
		// idea 3 has 1 up and 1 down vote (0 points)
		// idea 4 has 0 votes (0 points)
		return array(
			// add a new up vote to idea 1
			array(1, 5, 1, array(
				'message'	    => 'VOTE_SUCCESS',
				'votes_up'	    => 4,
				'votes_down'	=> 0,
			)),
			// add a new down vote to idea 1
			array(1, 5, 0, array(
				'message'	    => 'VOTE_SUCCESS',
				'votes_up'	    => 3,
				'votes_down'	=> 1,
			)),
			// change an existing vote to down in idea 1
			array(1, 3, 0, array(
				'message'	    => 'UPDATED_VOTE',
				'votes_up'	    => 2,
				'votes_down'	=> 1,
			)),
			// change an existing vote to up in idea 1
			array(1, 3, 1, array(
				'message'	    => 'UPDATED_VOTE',
				'votes_up'	    => 3,
				'votes_down'	=> 0,
			)),
			// add a new up vote to idea 2
			array(2, 5, 1, array(
				'message'	    => 'VOTE_SUCCESS',
				'votes_up'	    => 1,
				'votes_down'	=> 1,
			)),
			// change an existing vote to up in idea 2
			array(2, 2, 1, array(
				'message'	    => 'UPDATED_VOTE',
				'votes_up'	    => 1,
				'votes_down'	=> 0,
			)),
			// add a new down vote to idea 3
			array(3, 5, 0, array(
				'message'	    => 'VOTE_SUCCESS',
				'votes_up'	    => 1,
				'votes_down'	=> 2,
			)),
			// change an existing vote to down in idea 3
			array(3, 2, 0, array(
				'message'	    => 'UPDATED_VOTE',
				'votes_up'	    => 0,
				'votes_down'	=> 2,
			)),
			// add a new up vote to idea 4
			array(4, 5, 1, array(
				'message'	    => 'VOTE_SUCCESS',
				'votes_up'	    => 1,
				'votes_down'	=> 0,
			)),
		);
	}

	/**
	 * Test vote()
	 *
	 * @dataProvider vote_test_data
	 */
	public function test_vote($idea_id, $user_id, $vote, $expected)
	{
		$object = $this->get_idea_object();

		$idea = $object->get_idea($idea_id);

		$result = $object->vote($idea, $user_id, $vote);

		$this->assertEquals($expected['message'], $result['message'], 'Message did not match');
		$this->assertEquals($expected['votes_up'], $result['votes_up'], 'Up vote values did not match');
		$this->assertEquals($expected['votes_down'], $result['votes_down'], 'Down vote values did not match');
	}

	/**
	 * Test vote() fails data
	 *
	 * @return array
	 */
	public function vote_fails_test_data()
	{
		return array(
			array(2),
			array(-1),
			array('foo'),
			array(array(0)),
			array(null),
		);
	}

	/**
	 * Test vote() fails
	 *
	 * @dataProvider vote_fails_test_data
	 */
	public function test_vote_fails($vote)
	{
		$object = $this->get_idea_object();

		$idea = array(); // mock an empty idea

		$result = $object->vote($idea, 2, $vote);

		$this->assertEquals('INVALID_VOTE', $result);
	}

	/**
	 * Test remove_vote() data
	 *
	 * @return array
	 */
	public function remove_vote_test_data()
	{
		// idea 1 has 3 up votes (3 points)
		// idea 2 has 1 down vote (-1 point)
		// idea 3 has 1 up and 1 down vote (0 points)
		// idea 4 has 0 votes (0 points)
		return array(
			// Remove user 2's vote from idea 1
			array(1, 2, array(
				'message'	    => 'UPDATED_VOTE',
				'votes_up'	    => 2,
				'votes_down'	=> 0,
			)),
			// Remove user 2's vote from idea 2
			array(2, 2, array(
				'message'	    => 'UPDATED_VOTE',
				'votes_up'	    => 0,
				'votes_down'	=> 0,
			)),
			// Remove user 2's vote from idea 3
			array(3, 2, array(
				'message'	    => 'UPDATED_VOTE',
				'votes_up'	    => 0,
				'votes_down'	=> 1,
			)),
			// Remove non-existing user's vote from idea 1 (no change)
			array(1, 20, array(
				'message'	    => 'UPDATED_VOTE',
				'votes_up'	    => 3,
				'votes_down'	=> 0,
			)),
		);
	}

	/**
	 * Test remove_vote()
	 *
	 * @dataProvider remove_vote_test_data
	 */
	public function test_remove_vote($idea_id, $user_id, $expected)
	{
		$object = $this->get_idea_object();

		$idea = $object->get_idea($idea_id);

		$result = $object->remove_vote($idea, $user_id);

		$this->assertEquals($expected['message'], $result['message'], 'Message did not match');
		$this->assertEquals($expected['votes_up'], $result['votes_up'], 'Up vote values did not match');
		$this->assertEquals($expected['votes_down'], $result['votes_down'], 'Down vote values did not match');
	}
}
