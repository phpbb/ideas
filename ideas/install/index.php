<?php

define('IN_IDEAS', true);
$ideas_root_path = '../';
include($ideas_root_path . 'common.php');

$sample = request_var('sample', false);


// Add permissions (we use SQL to insert so that we can get the row ID without another query.)

$sql = 'INSERT INTO ' . ACL_OPTIONS_TABLE . ' (auth_option, is_global, is_local, founder_only) VALUES (\'m_mod_ideas\', 1, 0, 0)';
$db->sql_query($sql);

$sql_ary = array(
	'group_id'			=> 4,
	'auth_option_id'	=> $db->sql_nextid(),
	'auth_setting'		=> ACL_YES,
);

$sql = 'INSERT INTO ' . ACL_GROUPS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary);
$db->sql_query($sql);

$cache->destroy('acl_options');
$auth->acl_clear_prefetch();


// Create and populate database tables

$db->sql_query('CREATE TABLE  ' . $table_prefix . '_ideas_statuses (
status_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
status_name VARCHAR( 200 ) NOT NULL
) ENGINE = MYISAM ;');

$db->sql_query('CREATE TABLE  ' . $table_prefix . '_ideas_ideas (
idea_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
idea_author INT NOT NULL ,
idea_title VARCHAR( 200 ) NOT NULL ,
idea_desc TEXT NOT NULL ,
idea_date INT NOT NULL ,
idea_comments INT NOT NULL DEFAULT 0 ,
idea_rating FLOAT NOT NULL DEFAULT 0 ,
idea_votes INT NOT NULL DEFAULT 0 ,
idea_status INT NOT NULL DEFAULT 1 ,
bbcode_bitfield VARCHAR( 255 ) NOT NULL DEFAULT \'\' ,
bbcode_uid VARCHAR( 8 ) NOT NULL DEFAULT \'\' ,
bbcode_options INT( 11 ) NOT NULL
) ENGINE = MYISAM ;');

$db->sql_query('CREATE TABLE  ' . $table_prefix . '_ideas_votes (
idea_id INT NOT NULL ,
user_id INT NOT NULL ,
value INT NOT NULL ,
UNIQUE (
idea_id , user_id
)
) ENGINE = MYISAM ;');

$db->sql_query('CREATE TABLE  ' . $table_prefix . '_ideas_comments (
comment_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
idea_id INT NOT NULL ,
comment_author INT NOT NULL ,
comment_text TEXT NOT NULL ,
comment_date INT NOT NULL ,
comment_ip VARCHAR( 255 ) NOT NULL ,
bbcode_bitfield VARCHAR( 255 ) NOT NULL DEFAULT \'\' ,
bbcode_uid VARCHAR( 8 ) NOT NULL DEFAULT \'\' ,
bbcode_options INT( 11 ) NOT NULL
) ENGINE = MYISAM ;');

$db->sql_query("INSERT INTO {$table_prefix}_ideas_statuses (status_id, status_name) VALUES
(1, 'New'),
(2, 'Accepted'),
(3, 'Rejected'),
(4, 'Merged'),
(5, 'Duplicate');");

if ($sample)
{
	$db->sql_query(<<<EOT
INSERT INTO {$table_prefix}_ideas_ideas (idea_id, idea_author, idea_title, idea_desc, idea_date, idea_rating, idea_votes, idea_status, bbcode_bitfield, bbcode_uid, bbcode_options) VALUES
(1, 2, 'Contact Page', 'At the moment, phpBB installations provide no way for a non-registered user to contact the administrators of the board. In fact, depending on permissions, even a registered user may not be able to PM administrators. There are many cases where getting in touch with an administrator is very important (DMCA notices, problems with registration, etc.). In fact, some countries require contact information to be readily available on websites. \n\nOn a personal note, this results in tons of complaints in my inbox. People follow the link from &quot;powered by phpBB&quot; to our contact page and give me an earful about things we have nothing to do with. \n\nAdditionally, we currently list the administrator\'s email address in plaintext on the registration page. I usually end up forwarding complaints to this email address. Needless to say, listing the email address in plaintext is less than ideal. \n\n[b:3shllt4i]Suggestion:[/b:3shllt4i] \n1) Create a default contact page and link to it in the footer of each board. \n2) The existing email template may be re-used or at least adapted for this page. \n3) Add a new configuration option to the ACP to allow administrators to add a custom message above the textbox \n4) The header to the email message should make it clear where the email came from \n5) Replace the message on the registration page with a link to the contact page \n\n[b:3shllt4i]Notes:[/b:3shllt4i] \n1) A CAPTCHA cannot be used on this page due to accessibility issues.', 1325503192, 5, 2, 1, 'QA==', '3shllt4i', 7),
(2, 2, 'Soft Delete', 'Hey Guys \n\nFirst of all I\'m not sure if this is an RFC, so put it here - if not move it or whatrver. Second of all I dont think 3.1 will have soft delete, hence this post - if it does then ignore it. \n\n[b:362dg4gc]Soft Delete[/b:362dg4gc] \nSoft Delete is a feature only avaiable by mod for phpBB3, but exists in other forum software. Such a feature is in my opinion necesary, and many forums who do not have soft delete go through the trouble of splitting a post into another forum which brings disadvantages. \n\nThough theres about 8 weeks until the phpBB3.1 date, I\'m not sure if theres enough time for such an addition however this is what I propose (maybe this should be an RFC?) \n\nAbout&#058; \n- Applies to topics and posts, and can be evolved to perhaps forums? \n\nConfiguration - On/Off: \n- Soft Delete/Full Delete permision based on forums; option applies to all delete actions within that forum \n- Global (forum wide) force soft delete/full delete option; as says would force the selected option - would be &quot;Soft Delete&quot;, &quot;Full Delete&quot;, &quot;Forum Default&quot; \n- Forum \'Default\' would be full delete - so admin must define soft delete; saves on database size from smaller forum admins or people no need for this \n\nProessing: \n- As normal delete options, as in mcp/mass moderation or inline post \n- AJAX post delete - Saves the extra page when deleting posts inline, confirmation window is a MUST despite undo being able since its only a soft delete. Makes mass inline moderation much quicker - Being asynchronous after confirmation the post block can simply grey out into that rolling image to show progress, then once its done formatting will be applied, if an error a dialog is displayed. Such a system would allow the moderator to hit post delete -&gt; click yes -&gt; do next post, etc etc - no need for the delete page \n- Poster\'s post count would go down one, a \'void\' or whatever field would determine whether the post has been deleted (so remains in the phpbb_posts table) - Not displayed for non moderators \n- An additional permision for moderators &quot;Display Deleted Posts&quot;; determines whether deleted posts can be seen or not within forums they moderate. \n- Further to the above, users who have deleted their posts cannot undelete (or perhaps a time limit to undelete?) \n- Last Edit updated to the &quot; @ - soft deleted this post&quot; \n\nPurging/Full Delete of Soft Deleted Posts \n- Eventually database size may become an issue, so purging is necesary \n- Moderator Permision: Allow Deletion of soft deleted posts; lets moderators with this permisions fully delete (remove from db) posts which have been soft deleted \n- Admin Panel - Purge soft deleted posts - gives time frame to delete, and perhaps forums to include/exclude? \n\nDisplay: \n- Admin must be able to distinguish easily between a soft deleted post; perhaps: \n- Template would remain almost the same, however CSS would be used to identify identify posts: \n- Reason for diffrent template would be something like a dialog to partially grayscale the post - this means images etc would be greyed out as they are displayed beneath a semi transparent block \n- Buttons: Undelete, Full Delete \n\nThanks \n\nTicket: <!-- m --><a class=\"postlink\" href=\"http://tracker.phpbb.com/browse/PHPBB3-9657\">http://tracker.phpbb.com/browse/PHPBB3-9657</a><!-- m -->', 1325503246, 4, 2, 2, 'QA==', '362dg4gc', 7),
(3, 2, 'Plural Forms', '[url=http&#58;//www&#46;phpbb&#46;com/community/memberlist&#46;php?mode=viewprofile&amp;u=1310839:2ol5zivi]kenan3008[/url:2ol5zivi], Bosnian language package maintainer, just point out that our system is failing in the plural forms area. I quote him: \n\n[quote=&quot;kenan3008&quot;:2ol5zivi]I have one problem with plural forms. As you already know, situation with singular and plural forms in English is quite simple. One of something is singular and everything above that is plural. In Bosnian (like in most Slavic languages), we have a more complex situation. Here is an example: \nBosnian word for elephant is &quot;slon&quot;. \nEnglish: 1 elephant, 2 elephants, 3 elephants etc. (stupid example <!-- s:D --><img src=\"{SMILIES_PATH}/icon_e_biggrin.gif\" alt=\":D\" title=\"Very Happy\" /><!-- s:D -->) \nBosnian: \nfor numbers 0,5,6 and those ending with these numbers (like 25): \n0 slonova, 5 slonova, 6 slonova \n\nfor numbers 2,3,4 and those ending with these numbers (like 22): \n2 slona, 3 slona, 4 slona \n\nfor numbers 1,21,31,... : \n1 slon, 21 slon, 31, slon \n\nIn software that uses PO/MO architecture this is solved by adding an equation for plural forms (for example, WordPress uses that system). For Bosnian, this equation is: \n[b:2ol5zivi]nplurals=3; plural=(n%10==1 &amp;&amp; n%100!=11 ? 0 : n%10&gt;=2 &amp;&amp; n%10&lt;=4 &amp;&amp; (n%100&lt;10 || n%100&gt;=20) ? 1 : 2) [/b:2ol5zivi] \nwhere n is the number of something. Complete list of languages and their plural forms is available here: <!-- m --><a class=\"postlink\" href=\"http://translate.sourceforge.net/wiki/l10n/pluralforms\">http://translate.sourceforge.net/wiki/l10n/pluralforms</a><!-- m --> \nAfter adding this equation, I get three rows for every string that has plural form and I am able to type in everything correctly. Depending on the number that is displayed in the string, one of these three translations is pulled and displayed to the user. Is it possible to implement something like that into phpBB\'s translation?[/quote:2ol5zivi] \n\nIt would be preferable to integrate this system of equations, avoiding incorrect translations. I do not know if it is easy to implement because we\'re not using PO/MO and it looks complex, or if this system is the best, but the actual string system is not sufficient with some languages, like Bosnian: \n[code=php:2ol5zivi]<span class=\"syntaxdefault\">&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxcomment\">//&nbsp;Nullar/Singular/Plural&nbsp;language&nbsp;entry&#46;&nbsp;The&nbsp;key&nbsp;numbers&nbsp;define&nbsp;the&nbsp;number&nbsp;range&nbsp;in&nbsp;which&nbsp;a&nbsp;certain&nbsp;grammatical&nbsp;expression&nbsp;is&nbsp;valid&#46;&nbsp;<br />&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxstring\">\'NUM_POSTS_IN_QUEUE\'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxkeyword\">=&gt;&nbsp;array(&nbsp;<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxdefault\">0&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxkeyword\">=&gt;&nbsp;</span><span class=\"syntaxstring\">\'No&nbsp;posts&nbsp;in&nbsp;queue\'</span><span class=\"syntaxkeyword\">,&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxcomment\">//&nbsp;0&nbsp;<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxdefault\">1&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxkeyword\">=&gt;&nbsp;</span><span class=\"syntaxstring\">\'1&nbsp;post&nbsp;in&nbsp;queue\'</span><span class=\"syntaxkeyword\">,&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxcomment\">//&nbsp;1&nbsp;<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxdefault\">2&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxkeyword\">=&gt;&nbsp;</span><span class=\"syntaxstring\">\'%d&nbsp;posts&nbsp;in&nbsp;queue\'</span><span class=\"syntaxkeyword\">,&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxcomment\">//&nbsp;2+&nbsp;<br />&nbsp;&nbsp;&nbsp;&nbsp;</span><span class=\"syntaxkeyword\">),&nbsp;&nbsp;</span><span class=\"syntaxdefault\"></span>[/code:2ol5zivi] \n\nWhat do you think? Your feedbacks would be appreciated <!-- s;) --><img src=\"{SMILIES_PATH}/icon_e_wink.gif\" alt=\";)\" title=\"Wink\" /><!-- s;) --> . \n\n[url=http&#58;//tracker&#46;phpbb&#46;com/browse/PHPBB3-10345:2ol5zivi]Ticket[/url:2ol5zivi] \n[url=https&#58;//github&#46;com/phpbb/phpbb3/pull/376:2ol5zivi]Pull request[/url:2ol5zivi]', 1325503269, 5, 4, 4, '0IA=', '2ol5zivi', 7);
EOT
);

	$db->sql_query('INSERT INTO ' . $table_prefix . '_ideas_votes (idea_id, user_id, value) VALUES (1,1,5),(1,2,5),(2,1,5),(2,2,3),(3,1,5),(3,2,5),(3,3,5),(3,4,5);');
}

echo 'Successfully set up permissions and database. To complete installation, please copy permissions_ideas.php (found in the files directory in this directory) into language/en/mods, and delete this directory.';
