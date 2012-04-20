# phpBB Ideas #

phpBB Ideas is an ideas centre for phpBB. It is based on [WordPress ideas](http://wordpress.org/extend/ideas/), and allows users to suggest and vote on "ideas" - for example, features to add to phpBB or improvements.

## Installation ##

To install, copy `config.sample.php` to `config.php` and change the configuration. The current configuration settings:

- **PHPBB_ROOT_PATH** - The path to phpBB. Eg, if you put `ideas/` in the phpBB directory itself, this should be set to `../`

Then run install.php. It'll tell you to copy a couple files over, and then installation is complete.

**Remember to delete install.php once you are done!**

## Known bugs ##

phpBB Ideas was built on 3.1, and doesn't currently work in 3.0 due to usage of the request class.

## Work In Progress ##

This project is still very much a work in progress - unless you're testing it out or plan on contributing, it isn't recommended that you use this (and definitely not on a live board).
