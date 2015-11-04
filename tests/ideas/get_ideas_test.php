<?php
/**
*
* Board Rules extension for the phpBB Forum Software package.
*
* @copyright (c) 2014 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace phpbb\ideas\tests\ideas;

class get_ideas_test extends ideas_base
{
	/**
	 * @var array Default argument values for get_ideas()
	 */
	protected $default_values = array(
		'number' => 10,
		'sort' => 'date',
		'sort_direction' => 'DESC',
		'status' => array(),
		'where' => '',
		'start' => 0,
	);

	/**
	 * @dataProvider get_ideas_data
	 */
	public function test_get_ideas($test_data, $expected)
	{
		// Initialize get_ideas argument values
		$number = $sort = $sort_direction = $status = $where = $start = null;

		// Update the default values array with test values
		foreach ($test_data as $key => $var)
		{
			$this->default_values[$key] = $var;
		}

		// Extract get_ideas argument values from the array
		extract($this->default_values);

		// Instantiate the ideas class
		$ideas = $this->get_ideas_object();

		// Call get_ideas() with the test data
		$results = $ideas->get_ideas($number, $sort, $sort_direction, $status, $start);

		// Get an array of the ideas IDs returned
		$ideas_ids = array();
		foreach ($results as $result)
		{
			$ideas_ids[] = $result['idea_id'];
		}

		// Assert the expected ideas were returned
		$this->assertEquals($expected, $ideas_ids);
	}

	public function get_ideas_data()
	{
		return array(
			///////// TEST STATUS /////////
			array(
				// Empty status gets all open ideas, ordered by date
				array('status' => 0),
				array(1, 2, 3),
			),
			array(
				// Get all new ideas, ordered by date
				array('status' => 1),
				array(1, 2),
			),
			array(
				// Get all new or in progress, ordered by date
				array('status' => array(1, 2)),
				array(1, 2, 3),
			),
			array(
				// Get all duplicate, invalid and implemented, ordered by date
				array('status' => array(3, 4, 5)),
				array(4),
			),

			///////// TEST START /////////
			array(
				// Get all open ideas starting with beginning, ordered by date
				array('start' => 0),
				array(1, 2, 3),
			),
			array(
				// Get all open ideas starting from position 1, ordered by date
				array('start' => 1),
				array(2, 3),
			),
			array(
				// Get all open ideas starting from position 11, ordered by date
				array('start' => 10),
				array(),
			),

			///////// TEST NUMBER /////////
			array(
				// 0 means no limit so get 'em all, ordered by date
				array('number' => 0),
				array(1, 2, 3),
			),
			array(
				// get the first newest
				array('number' => 1),
				array(1),
			),
			array(
				// get first 2 ordered by date
				array('number' => 2),
				array(1, 2),
			),

			array(
				// get all ordered by date
				array('number' => 10),
				array(1, 2, 3),
			),

			///////// TEST SORTING /////////
			// sorted by author, ascending, open ideas only
			array(
				array(
					'sort' => 'author',
					'sort_direction' => 'ASC',
				),
				array(1, 3, 2),
			),

			// sorted by author, descending, open ideas only
			array(
				array(
					'sort' => 'author',
					'sort_direction' => 'DESC',
				),
				array(2, 3, 1),
			),

			// sorted by date, ascending, open ideas only
			array(
				array(
					'sort' => 'date',
					'sort_direction' => 'ASC',
				),
				array(3, 2, 1),
			),

			// sorted by date, descending, open ideas only
			array(
				array(
					'sort' => 'date',
					'sort_direction' => 'DESC',
				),
				array(1, 2, 3),
			),

			// sorted by id, ascending, open ideas only
			array(
				array(
					'sort' => 'id',
					'sort_direction' => 'ASC',
				),
				array(1, 2, 3),
			),

			// sorted by id, descending, open ideas only
			array(
				array(
					'sort' => 'id',
					'sort_direction' => 'DESC',
				),
				array(3, 2, 1),
			),

			// sorted by score, ascending, open ideas only
			array(
				array(
					'sort' => 'score',
					'sort_direction' => 'ASC',
				),
				array(2, 3, 1),
			),

			// sorted by score, descending, open ideas only
			array(
				array(
					'sort' => 'score',
					'sort_direction' => 'DESC',
				),
				array(1, 3, 2),
			),

			// sorted by title, ascending, open ideas only
			array(
				array(
					'sort' => 'title',
					'sort_direction' => 'ASC',
				),
				array(2, 1, 3),
			),

			// sorted by title, descending, open ideas only
			array(
				array(
					'sort' => 'title',
					'sort_direction' => 'DESC',
				),
				array(3, 1, 2),
			),

			// sorted by votes, ascending, open ideas only
			array(
				array(
					'sort' => 'votes',
					'sort_direction' => 'ASC',
				),
				array(2, 3, 1),
			),

			// sorted by votes, descending, open ideas only
			array(
				array(
					'sort' => 'votes',
					'sort_direction' => 'DESC',
				),
				array(1, 3, 2),
			),

			// sorted by top, ascending, open ideas only
			array(
				array(
					'sort' => 'top',
					'sort_direction' => 'ASC',
				),
				array(1),
			),

			// sorted by top, descending, open ideas only
			array(
				array(
					'sort' => 'top',
					'sort_direction' => 'DESC',
				),
				array(1),
			),

			// sorted by default (similar to score), ascending, open ideas only
			array(
				array(
					'sort' => '',
					'sort_direction' => 'ASC',
				),
				array(2, 3, 1),
			),

			// sorted by default (similar to score), descending, open ideas only
			array(
				array(
					'sort' => '',
					'sort_direction' => 'DESC',
				),
				array(1, 3, 2),
			),
		);
	}
}
