<?php
/**
 *
 * @package phpBB3 Ideas
 * @author Callum Macrae (callumacrae) <callum@lynxphp.com>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbb\ideas\factory;

class LinkHelper
{
	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\user_loader */
	protected $user_loader;

	public function __construct(\phpbb\controller\helper $helper, \phpbb\user_loader $user_loader)
	{
		$this->helper = $helper;
		$this->user_loader = $user_loader;
	}

	/**
	 * Shortcut method to get the link to a specified idea.
	 *
	 * @param $idea_id int The ID of the idea.
	 * @return string The route
	 */
	public function get_idea_link($idea_id)
	{
		return $this->helper->route('ideas_idea_controller', array(
			'idea_id' => $idea_id
		));
	}

	/**
	 * Returns a link to the users profile, complete with colour.
	 *
	 * Is there a function that already does this? This seems fairly database heavy.
	 *
	 * @param int $id The ID of the user.
	 * @return string An HTML link to the users profile.
	 */
	public function get_user_link($id)
	{
		$this->user_loader->load_users(array($id));
		return $this->user_loader->get_username($id, 'full');
	}
}
