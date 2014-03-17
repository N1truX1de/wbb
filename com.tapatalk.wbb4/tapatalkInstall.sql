CREATE TABLE wbb1_tapatalk_push_user (
	user_id INT(10) NOT NULL PRIMARY KEY,
	create_time INT(10) NOT NULL DEFAULT '0',
	update_time INT(10) NOT NULL DEFAULT '0',
);

CREATE TABLE wbb1_tapatalk_status (
	status_info TEXT NOT NULL,
	create_time INT(10) NOT NULL DEFAULT '0',
	update_time INT(10) NOT NULL DEFAULT '0'
);

ALTER TABLE wbb1_tapatalk_push_user ADD FOREIGN KEY (userID) REFERENCES wcf1_user (userID) ON DELETE CASCADE;
