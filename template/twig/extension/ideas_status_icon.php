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

use phpbb\ideas\factory\base as factory;

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
			factory::$statuses['NEW']         => 'fa-lightbulb-o',
			factory::$statuses['IN_PROGRESS'] => 'fa-code-fork',
			factory::$statuses['IMPLEMENTED'] => 'fa-check',
			factory::$statuses['DUPLICATE']   => 'fa-files-o',
			factory::$statuses['INVALID']     => 'fa-ban',
		];

		return isset($icons[$args[0]]) ? $icons[$args[0]] : '';
	}
}
