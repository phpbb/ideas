CREATE TABLE  phpbb_ideas_statuses (
status_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
status_name VARCHAR( 200 ) NOT NULL
) ENGINE = MYISAM ;

CREATE TABLE  phpbb_ideas_ideas (
idea_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
idea_author INT NOT NULL ,
idea_title VARCHAR( 200 ) NOT NULL ,
idea_desc TEXT NOT NULL ,
idea_date INT NOT NULL ,
idea_rating FLOAT NOT NULL ,
idea_votes INT NOT NULL ,
idea_status INT NOT NULL
) ENGINE = MYISAM ;

CREATE TABLE  phpbb_ideas_votes (
idea_id INT NOT NULL ,
user_id INT NOT NULL ,
value INT NOT NULL ,
UNIQUE (
idea_id , user_id
)
) ENGINE = MYISAM ;


-- Sample data.

INSERT INTO phpbb_ideas_statuses (status_id, status_name) VALUES
(1, 'New'),
(2, 'Accepted'),
(3, 'Rejected'),
(4, 'Merged'),
(5, 'Duplicate');

INSERT INTO phpbb_ideas_ideas (idea_id, idea_author, idea_title, idea_desc, idea_date, idea_rating, idea_votes, idea_status) VALUES
(1, 2, 'Contact Page', 'At the moment, phpBB installations provide no way for a non-registered user to contact the administrators of the board. In fact, depending on permissions, even a registered user may not be able to PM administrators. There are many cases where getting in touch with an administrator is very important (DMCA notices, problems with registration, etc.). In fact, some countries require contact information to be readily available on websites.\r\n\r\nOn a personal note, this results in tons of complaints in my inbox. People follow the link from "powered by phpBB" to our contact page and give me an earful about things we have nothing to do with.\r\n\r\nAdditionally, we currently list the administrator''s email address in plaintext on the registration page. I usually end up forwarding complaints to this email address. Needless to say, listing the email address in plaintext is less than ideal.\r\n\r\n[b]Suggestion:[/b]\r\n1) Create a default contact page and link to it in the footer of each board.\r\n2) The existing email template may be re-used or at least adapted for this page.\r\n3) Add a new configuration option to the ACP to allow administrators to add a custom message above the textbox\r\n4) The header to the email message should make it clear where the email came from\r\n5) Replace the message on the registration page with a link to the contact page\r\n\r\n[b]Notes:[/b]\r\n1) A CAPTCHA cannot be used on this page due to accessibility issues.', 1324495738, 5, 2, 1),
(2, 2, 'Soft Delete', 'Hey Guys\r\n\r\nFirst of all I''m not sure if this is an RFC, so put it here - if not move it or whatrver. Second of all I dont think 3.1 will have soft delete, hence this post - if it does then ignore it.\r\n\r\n[b]Soft Delete[/b]\r\nSoft Delete is a feature only avaiable by mod for phpBB3, but exists in other forum software. Such a feature is in my opinion necesary, and many forums who do not have soft delete go through the trouble of splitting a post into another forum which brings disadvantages.\r\n\r\nThough theres about 8 weeks until the phpBB3.1 date, I''m not sure if theres enough time for such an addition however this is what I propose (maybe this should be an RFC?)\r\n\r\nAbout:\r\n- Applies to topics and posts, and can be evolved to perhaps forums? \r\n\r\nConfiguration - On/Off:\r\n- Soft Delete/Full Delete permision based on forums; option applies to all delete actions within that forum\r\n- Global (forum wide) force soft delete/full delete option; as says would force the selected option - would be "Soft Delete", "Full Delete", "Forum Default"\r\n- Forum ''Default'' would be full delete - so admin must define soft delete; saves on database size from smaller forum admins or people no need for this\r\n\r\nProessing:\r\n- As normal delete options, as in mcp/mass moderation or inline post\r\n- AJAX post delete - Saves the extra page when deleting posts inline, confirmation window is a MUST despite undo being able since its only a soft delete. Makes mass inline moderation much quicker - Being asynchronous after confirmation the post block can simply grey out into that rolling image to show progress, then once its done formatting will be applied, if an error a dialog is displayed. Such a system would allow the moderator to hit post delete -> click yes -> do next post, etc etc - no need for the delete page\r\n- Poster''s post count would go down one, a ''void'' or whatever field would determine whether the post has been deleted (so remains in the phpbb_posts table) - Not displayed for non moderators\r\n- An additional permision for moderators "Display Deleted Posts"; determines whether deleted posts can be seen or not within forums they moderate.\r\n- Further to the above, users who have deleted their posts cannot undelete (or perhaps a time limit to undelete?)\r\n- Last Edit updated to the "<username who deleted this post> @ <time> - <user> soft deleted this post"\r\n\r\nPurging/Full Delete of Soft Deleted Posts\r\n- Eventually database size may become an issue, so purging is necesary\r\n- Moderator Permision: Allow Deletion of soft deleted posts; lets moderators with this permisions fully delete (remove from db) posts which have been soft deleted\r\n- Admin Panel - Purge soft deleted posts - gives time frame to delete, and perhaps forums to include/exclude?\r\n\r\nDisplay:\r\n- Admin must be able to distinguish easily between a soft deleted post; perhaps: \r\n     - Template would remain almost the same, however CSS would be used to identify identify posts:\r\n     - Reason for diffrent template would be something like a dialog to partially grayscale the post - this means images etc would be greyed out as they are displayed beneath a semi transparent block\r\n      - Buttons: Undelete, Full Delete\r\n\r\nThanks\r\n\r\nTicket: http://tracker.phpbb.com/browse/PHPBB3-9657', 1324485738, 4, 2, 2),
(3, 2, 'Plural Forms', '[url=http://www.phpbb.com/community/memberlist.php?mode=viewprofile&u=1310839]kenan3008[/url], Bosnian language package maintainer, just point out that our system is failing in the plural forms area. I quote him:\r\n\r\n[quote="kenan3008"]I have one problem with plural forms. As you already know, situation with singular and plural forms in English is quite simple. One of something is singular and everything above that is plural. In Bosnian (like in most Slavic languages), we have a more complex situation. Here is an example:\r\nBosnian word for elephant is "slon". \r\nEnglish: 1 elephant, 2 elephants, 3 elephants etc. (stupid example :D)\r\nBosnian:\r\nfor numbers 0,5,6 and those ending with these numbers (like 25): \r\n0 slonova, 5 slonova, 6 slonova\r\n\r\nfor numbers 2,3,4 and those ending with these numbers (like 22): \r\n2 slona, 3 slona, 4 slona\r\n\r\nfor numbers 1,21,31,... :\r\n1 slon, 21 slon, 31, slon\r\n\r\nIn software that uses PO/MO architecture this is solved by adding an equation for plural forms (for example, WordPress uses that system). For Bosnian, this equation is: \r\n[b]nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2) [/b]\r\nwhere n is the number of something. Complete list of languages and their plural forms is available here: http://translate.sourceforge.net/wiki/l10n/pluralforms\r\nAfter adding this equation, I get three rows for every string that has plural form and I am able to type in everything correctly. Depending on the number that is displayed in the string, one of these three translations is pulled and displayed to the user. Is it possible to implement something like that into phpBB''s translation?[/quote]\r\n\r\nIt would be preferable to integrate this system of equations, avoiding incorrect translations. I do not know if it is easy to implement because we''re not using PO/MO and it looks complex, or if this system is the best, but the actual string system is not sufficient with some languages, like Bosnian:\r\n[code=php]    // Nullar/Singular/Plural language entry. The key numbers define the number range in which a certain grammatical expression is valid.\r\n    ''NUM_POSTS_IN_QUEUE''        => array(\r\n        0            => ''No posts in queue'',        // 0\r\n        1            => ''1 post in queue'',        // 1\r\n        2            => ''%d posts in queue'',        // 2+\r\n    ), [/code]\r\n\r\nWhat do you think? Your feedbacks would be appreciated ;) .\r\n\r\n[url=http://tracker.phpbb.com/browse/PHPBB3-10345]Ticket[/url]\r\n[url=https://github.com/phpbb/phpbb3/pull/376]Pull request[/url]', 1324495938, 5, 4, 4);

INSERT INTO phpbb_ideas_votes (idea_id, user_id, value) VALUES
('1',  '1',  '5'),
('1',  '2',  '5'),
('2',  '1',  '5'),
('2',  '2',  '3'),
('3',  '1',  '5'),
('3',  '2',  '5'),
('3',  '3',  '5'),
('3',  '4',  '5');
