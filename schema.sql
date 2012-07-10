CREATE TABLE phpbb_ideas_ideas (
  idea_id int(11) NOT NULL AUTO_INCREMENT,
  idea_author int(11) NOT NULL,
  idea_title varchar(200) NOT NULL,
  idea_date int(11) NOT NULL,
  idea_comments int(11) NOT NULL DEFAULT '0',
  idea_rating float NOT NULL DEFAULT '0',
  idea_votes int(11) NOT NULL DEFAULT '0',
  idea_status int(11) NOT NULL DEFAULT '1',
  topic_id int(11) NOT NULL,
  PRIMARY KEY (idea_id)
);

CREATE TABLE phpbb_ideas_statuses (
  status_id int(11) NOT NULL AUTO_INCREMENT,
  status_name varchar(200) NOT NULL,
  PRIMARY KEY (status_id)
);

CREATE TABLE phpbb_ideas_votes (
  idea_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  vote_value int(11) NOT NULL,
  UNIQUE KEY idea_id (idea_id,user_id)
);