<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace phpbb\ideas\template\twig\extension;

class ideas_status_icon extends \Twig_Extension
{
	/**
	 * Get the name of this extension
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'ideas_status_icon';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('ideas_status_icon', [$this, 'get_status_icon']),
		];
	}

	/**
	 * Generate a Font Awesome icon class name given an integer input
	 * representing one of the Ideas Statuses.
	 *
	 * @return string Status class name or empty string if no match found.
	 */
	public function get_status_icon()
	{
		$args = func_get_args();

		$icons = [
			\phpbb\ideas\factory\ideas::$statuses['NEW']         => 'fa-lightbulb-o',
			\phpbb\ideas\factory\ideas::$statuses['IN_PROGRESS'] => 'fa-code-fork',
			\phpbb\ideas\factory\ideas::$statuses['IMPLEMENTED'] => 'fa-check',
			\phpbb\ideas\factory\ideas::$statuses['DUPLICATE']   => 'fa-files-o',
			\phpbb\ideas\factory\ideas::$statuses['INVALID']     => 'fa-ban',
		];

		return isset($icons[$args[0]]) ? $icons[$args[0]] : '';
	}
}
