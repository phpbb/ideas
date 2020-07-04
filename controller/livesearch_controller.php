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

/**
 * Ideas live search controller
 */
class livesearch_controller extends base
{
	/* @var \phpbb\ideas\factory\livesearch */
	protected $entity;

	/**
	 * Title search handler
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function title_search()
	{
		$title_chars = $this->request->variable('duplicateeditinput', '', true);

		$matches = $this->entity->title_search($title_chars, 10);

		return new \Symfony\Component\HttpFoundation\JsonResponse([
			'keyword' => $title_chars,
			'results' => $matches,
		]);
	}
}
