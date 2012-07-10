# phpBB Ideas #

phpBB Ideas is an ideas centre for phpBB. It is based on [WordPress ideas](http://wordpress.org/extend/ideas/), and allows users to suggest and vote on "ideas" - for example, features to add to phpBB or improvements.

## Installation ##

To install, copy `config.sample.php` to `config.php` and change the configuration. The current configuration settings:

- **PHPBB_ROOT_PATH** - The path to phpBB. Eg, if you put `ideas/` in the phpBB directory itself, this should be set to `../`
- **IDEAS_FORUM_ID** - The ID of the forum that ideas will be posted to.
- **IDEA_POSTER_ID** - The user ID of the bot that will post ideas in the forums.

Then run install.php, which will set up the database.

**Remember to delete install.php once you are done!**

## Work In Progress ##

This project is still very much a work in progress - unless you're testing it out or plan on contributing, it isn't recommended that you use this (and definitely not on a live board).
