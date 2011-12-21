CREATE TABLE  phpbb_ideas_categories (
category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
category_name VARCHAR( 200 ) NOT NULL
) ENGINE = MYISAM ;

CREATE TABLE  phpbb_ideas_ideas (
idea_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
idea_author INT NOT NULL ,
idea_title VARCHAR( 200 ) NOT NULL ,
idea_desc TEXT NOT NULL ,
idea_date INT NOT NULL ,
idea_rating FLOAT NOT NULL ,
idea_votes INT NOT NULL ,
idea_category INT NOT NULL
) ENGINE = MYISAM ;

CREATE TABLE  phpbb_ideas_votes (
idea_id INT NOT NULL ,
user_id INT NOT NULL ,
value INT NOT NULL ,
UNIQUE (
idea_id , user_id
)
) ENGINE = MYISAM ;