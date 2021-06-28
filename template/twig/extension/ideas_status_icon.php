<?php
/**
 *
 * Ideas extension for the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbb\ideas\template\twig\extension;

use phpbb\ideas\ext;

class ideas_status_icon extends \Twig\Extension\AbstractExtension
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
			new \Twig\TwigFunction('ideas_status_icon', [$this, 'get_status_icon']),
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
			ext::$statuses['NEW']         => 'fa-lightbulb-o',
			ext::$statuses['IN_PROGRESS'] => 'fa-code-fork',
			ext::$statuses['IMPLEMENTED'] => 'fa-check',
			ext::$statuses['DUPLICATE']   => 'fa-files-o',
			ext::$statuses['INVALID']     => 'fa-ban',
		];

		return isset($icons[$args[0]]) ? $icons[$args[0]] : '';
	}
}
