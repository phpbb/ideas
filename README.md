# phpBB Ideas

phpBB Ideas is an ideas centre for phpBB. It is based on [WordPress ideas](http://wordpress.org/extend/ideas/), and allows users to suggest and vote on "ideas" that would help improve and enhance phpBB.

## Installation

1. Upload the package to `phpBB3/ext/phpbb/ideas`.
2. Navigate in the ACP to `Customise -> Manage extensions`.
3. Look for `phpBB Ideas` under the Disabled Extensions list, and click its `Enable` link.

Open ideas/controller/base.php and change the constants:

- **IDEAS_FORUM_ID** - The ID of the forum that ideas topics will be posted to.
- **IDEA_POSTER_ID** - The ID of the user that will post idea topics into the forums.

## Contributing

Please fork this repository and submit a pull request to contribute to phpBB Ideas

## Bug Reporting & Support

You can report bugs or suggest features in the [issue tracker](https://github.com/phpbb/phpbb-ideas/issues).
Support is not available for phpBB Ideas however you may email `website [at] phpbb [dot] com` where you may receive some limited support should the problem be with phpBB Ideas.

Note: This extension is currently under development and is not recommended for use on any live forum.

## License
[GNU General Public License v2](http://opensource.org/licenses/GPL-2.0)
