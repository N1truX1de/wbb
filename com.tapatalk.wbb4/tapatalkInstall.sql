CREATE TABLE wbb1_tapatalk_push_user (
  user_id INT(11) NOT NULL DEFAULT '0',
  create_time INT(11) NOT NULL DEFAULT '0',
  update_time INT(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (user_id)
);

CREATE TABLE wbb1_tapatalk_status (
  status_info TEXT NOT NULL,
  create_time INT(11) NOT NULL DEFAULT '0',
  update_time INT(11) NOT NULL DEFAULT '0'
);