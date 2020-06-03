<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\factory;

/**
 * Class for managing ideas
 */
class manager
{
	/** @var \phpbb\ideas\factory\idea */
	private $idea;

	/** @var \phpbb\ideas\factory\ideas */
	private $ideas;

	/** @var \phpbb\ideas\factory\vote */
	private $vote;

	/**
	 * Ideas manager constructor
	 *
	 * @param \phpbb\ideas\factory\idea  $idea
	 * @param \phpbb\ideas\factory\ideas $ideas
	 * @param \phpbb\ideas\factory\vote  $vote
	 */
	public function __construct(idea $idea, ideas $ideas, vote $vote)
	{
		$this->vote = $vote;
		$this->idea = $idea;
		$this->ideas = $ideas;
	}

	/**
	 * Returns an array of ideas. Defaults to ten ideas ordered by date
	 * excluding implemented, duplicate or invalid ideas.
	 *
	 * @param int       $number    The number of ideas to return
	 * @param string    $sort      A sorting option/collection
	 * @param string    $direction Should either be ASC or DESC
	 * @param array|int $status    The id of the status(es) to load
	 * @param int       $start     Start value for pagination
	 *
	 * @return array Array of row data
	 */
	public function get_ideas($number = 10, $sort = 'date', $direction = 'DESC', $status = [], $start = 0)
	{
		return $this->ideas->get($number, $sort, $direction, $status, $start);
	}

	/**
	 * Get the stored idea count
	 * Note: this should only be called after get_ideas()
	 *
	 * @return int Count of ideas
	 */
	public function get_idea_count()
	{
		return $this->ideas->count();
	}

	/**
	 * Returns the specified idea.
	 *
	 * @param int $id The ID of the idea to return.
	 *
	 * @return array|false The idea row set, or false if not found.
	 */
	public function get_idea($id)
	{
		return $this->idea->get_idea($id);
	}

	/**
	 * Returns an idea specified by its topic ID.
	 *
	 * @param int $id The ID of the idea to return.
	 *
	 * @return array|false The idea row set, or false if not found.
	 */
	public function get_idea_by_topic_id($id)
	{
		return $this->idea->get_idea_by_topic_id($id);
	}

	/**
	 * Returns the status name from the status ID specified.
	 *
	 * @param int $id ID of the status.
	 *
	 * @return string|bool The status name if it exists, false otherwise.
	 */
	public function get_status_from_id($id)
	{
		return $this->idea->get_status_from_id($id);
	}

	/**
	 * Updates the status of an idea.
	 *
	 * @param int $idea_id The ID of the idea.
	 * @param int $status  The ID of the status.
	 *
	 * @return void
	 */
	public function set_status($idea_id, $status)
	{
		$this->idea->set_status($idea_id, $status);
	}

	/**
	 * Sets the ID of the duplicate for an idea.
	 *
	 * @param int    $idea_id   ID of the idea to be updated.
	 * @param string $duplicate Idea ID of duplicate.
	 *
	 * @return bool True if set, false if invalid.
	 */
	public function set_duplicate($idea_id, $duplicate)
	{
		return $this->idea->set_duplicate($idea_id, $duplicate);
	}

	/**
	 * Sets the RFC link of an idea.
	 *
	 * @param int    $idea_id ID of the idea to be updated.
	 * @param string $rfc     Link to the RFC.
	 *
	 * @return bool True if set, false if invalid.
	 */
	public function set_rfc($idea_id, $rfc)
	{
		return $this->idea->set_rfc($idea_id, $rfc);
	}

	/**
	 * Sets the ticket ID of an idea.
	 *
	 * @param int    $idea_id ID of the idea to be updated.
	 * @param string $ticket  Ticket ID.
	 *
	 * @return bool True if set, false if invalid.
	 */
	public function set_ticket($idea_id, $ticket)
	{
		return $this->idea->set_ticket($idea_id, $ticket);
	}

	/**
	 * Sets the implemented version of an idea.
	 *
	 * @param int    $idea_id ID of the idea to be updated.
	 * @param string $version Version of phpBB the idea was implemented in.
	 *
	 * @return bool True if set, false if invalid.
	 */
	public function set_implemented($idea_id, $version)
	{
		return $this->idea->set_implemented($idea_id, $version);
	}

	/**
	 * Sets the title of an idea.
	 *
	 * @param int    $idea_id ID of the idea to be updated.
	 * @param string $title   New title.
	 *
	 * @return boolean True if updated, false if invalid length.
	 */
	public function set_title($idea_id, $title)
	{
		return $this->idea->set_title($idea_id, $title);
	}

	/**
	 * Get the title of an idea.
	 *
	 * @param int $id ID of an idea
	 *
	 * @return string The idea's title, empty string if not found
	 */
	public function get_title($id)
	{
		return $this->idea->get_title($id);
	}

	/**
	 * Submit new idea data to the ideas table
	 *
	 * @param array $data An array of post data from a newly posted idea
	 *
	 * @return int The ID of the new idea.
	 */
	public function submit($data)
	{
		return $this->idea->submit($data);
	}

	/**
	 * Deletes an idea and the topic to go with it.
	 *
	 * @param int $id       The ID of the idea to be deleted.
	 * @param int $topic_id The ID of the idea topic. Optional, but preferred.
	 *
	 * @return boolean Whether the idea was deleted or not.
	 */
	public function delete($id, $topic_id = 0)
	{
		return $this->idea->delete($id, $topic_id);
	}

	/**
	 * Do a live search on idea titles. Return any matches based on a given search query.
	 *
	 * @param string $search The string of characters to search using LIKE
	 * @param int    $limit  The number of results to return
	 *
	 * @return array An array of matching idea id/key and title/values
	 */
	public function ideas_title_livesearch($search, $limit = 10)
	{
		return $this->ideas->livesearch($search, $limit);
	}

	/**
	 * Delete orphaned ideas. Orphaned ideas may exist after a
	 * topic has been deleted or moved to another forum.
	 *
	 * @return int Number of rows affected
	 */
	public function delete_orphans()
	{
		return $this->ideas->delete_orphans();
	}

	/**
	 * Submits a vote on an idea.
	 *
	 * @param array $idea    The idea returned by get_idea().
	 * @param int   $user_id The ID of the user voting.
	 * @param int   $value   Up (1) or down (0)?
	 *
	 * @return array|string Array of information or string on error.
	 */
	public function vote(&$idea, $user_id, $value)
	{
		return $this->vote->submit($idea, $user_id, $value);
	}

	/**
	 * Remove a user's vote from an idea
	 *
	 * @param array   $idea    The idea returned by get_idea().
	 * @param int     $user_id The ID of the user voting.
	 *
	 * @return array Array of information.
	 */
	public function remove_vote(&$idea, $user_id)
	{
		return $this->vote->remove($idea, $user_id);
	}

	/**
	 * Returns voter info on an idea.
	 *
	 * @param int $id ID of the idea.
	 *
	 * @return array Array of row data
	 */
	public function get_voters($id)
	{
		return $this->vote->get_voters($id);
	}
}
