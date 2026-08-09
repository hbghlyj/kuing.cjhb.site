<?php exit('Access Denied');?>

DELETE FROM pre_common_credit_rule
WHERE action IN ('promotion_visit', 'promotion_register');

DELETE FROM pre_common_setting
WHERE skey IN ('archiver', 'archiverredirect', 'robotarchiver');

DELETE FROM pre_common_nav
WHERE identifier = 'archiver' OR url = 'archiver/';

DELETE FROM pre_common_cron
WHERE filename = 'cron_promotion_hourly.php';

DELETE taskvar
FROM pre_common_taskvar AS taskvar
INNER JOIN pre_common_task AS task ON task.taskid = taskvar.taskid
WHERE task.scriptname = 'promotion';

DELETE FROM pre_common_task
WHERE scriptname = 'promotion';

DROP TABLE IF EXISTS pre_forum_promotion;
ALTER TABLE pre_common_member
	MODIFY username char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_mytask
	MODIFY username char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_report
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_collection
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_collectioncomment
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_collectionteamworker
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_collectionfollow
	MODIFY username char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_groupuser
	MODIFY username char (50) NOT NULL;

ALTER TABLE pre_forum_pollvoter
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_threadmod
	MODIFY username char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_album
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_blog
	MODIFY username char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_clickuser
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_docomment
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_doing
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_feed
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_follow
	MODIFY username char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_follow
	MODIFY fusername char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_follow_feed
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_follow_feed_archiver
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_friend
	MODIFY fusername varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_friend_request
	MODIFY fusername char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_pic
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_poke
	MODIFY fromusername varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_share
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_show
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_specialuser
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_specialuser
	MODIFY opusername varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_visitor
	MODIFY vusername char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_portal_topic_pic
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_card_log
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_diy_data
	MODIFY username varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_failedlogin
	MODIFY username char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_invite
	MODIFY fusername char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_member_verify_info
	MODIFY username varchar (100) NOT NULL DEFAULT '';

ALTER TABLE pre_common_grouppm
	MODIFY author varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_member_crime
	MODIFY operator varchar (50) NOT NULL;

ALTER TABLE pre_common_member_validate
	MODIFY `admin` varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_report
	MODIFY opname varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_task
	MODIFY prize varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_word
	MODIFY `admin` varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_announcement
	MODIFY author varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_collection
	MODIFY lastposter varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_debate
	MODIFY umpire varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_forumrecommend
	MODIFY author char (50) NOT NULL;

ALTER TABLE pre_forum_order
	MODIFY `admin` char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_order
	MODIFY `admin` char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_post
	MODIFY author varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_post
	DROP COLUMN lastupdate,
	DROP COLUMN updateuid;

DELETE FROM pre_common_setting WHERE skey = 'editedby';

ALTER TABLE pre_forum_postcomment
	MODIFY author varchar (50) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS pre_forum_editlog
(
	editid     bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	tid        int(10) unsigned NOT NULL DEFAULT '0',
	pid        int(10) unsigned NOT NULL DEFAULT '0',
	authorid   mediumint(8) unsigned NOT NULL DEFAULT '0',
	uid        mediumint(8) unsigned NOT NULL DEFAULT '0',
	username   char(50) NOT NULL DEFAULT '',
	dateline   int(10) unsigned NOT NULL DEFAULT '0',
	action     enum('edit','delete') NOT NULL DEFAULT 'edit',
	old_subject varchar(255) NOT NULL DEFAULT '',
	old_message mediumtext NOT NULL,
	old_content longtext NOT NULL,
	PRIMARY KEY (editid),
	KEY pid (pid, dateline),
	KEY tid (tid, dateline),
	KEY authorid (authorid)
) ENGINE = InnoDB;

ALTER TABLE pre_forum_editlog
	ADD KEY dateline (dateline);

ALTER TABLE pre_forum_rsscache
	MODIFY author char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_thread
	MODIFY author char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_thread
	MODIFY lastposter char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_forum_trade
	MODIFY seller char (50) NOT NULL;

ALTER TABLE pre_forum_trade
	MODIFY lastbuyer char (50) NOT NULL;

ALTER TABLE pre_forum_tradecomment
	MODIFY rater char (50) NOT NULL;

ALTER TABLE pre_forum_tradecomment
	MODIFY ratee char (50) NOT NULL;

ALTER TABLE pre_forum_tradelog
	MODIFY seller varchar (50) NOT NULL;

ALTER TABLE pre_forum_tradelog
	MODIFY buyer varchar (50) NOT NULL;

ALTER TABLE pre_forum_warning
	MODIFY operator char (50) NOT NULL;

ALTER TABLE pre_forum_warning
	MODIFY author char (50) NOT NULL;

ALTER TABLE pre_home_comment
	MODIFY author varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_home_notification
	MODIFY author varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_portal_rsscache
	MODIFY author char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_adminnote
	MODIFY `admin` varchar (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_banned
	MODIFY `admin` varchar (50) NOT NULL DEFAULT '';

ALTER TABLE `pre_common_member`
	ADD COLUMN loginname char(50) NOT NULL DEFAULT '' AFTER `email`;

UPDATE `pre_common_member` SET `loginname`=`username`;

ALTER TABLE `pre_common_member`
	ADD UNIQUE INDEX loginname (loginname);

ALTER TABLE `pre_common_member_archive`
    ADD COLUMN loginname char(50) NOT NULL DEFAULT '' AFTER `email`;

UPDATE `pre_common_member_archive` SET `loginname`=`username`;

ALTER TABLE `pre_common_member_archive`
    ADD UNIQUE INDEX loginname (loginname);

DROP TABLE IF EXISTS pre_common_member_username_history;
CREATE TABLE pre_common_member_username_history
(
	username char(50) NOT NULL DEFAULT '',
	uid      mediumint(8) unsigned NOT NULL,
	dateline int(10) unsigned      NOT NULL DEFAULT '0',
	PRIMARY KEY (username),
	KEY      uid (uid)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS pre_common_member_account;
CREATE TABLE pre_common_member_account
(
	id          int(11) unsigned                      NOT NULL AUTO_INCREMENT,
	uid         mediumint(8) unsigned                 NOT NULL,
	atype       tinyint(1)                            NOT NULL,
	account     varchar(255) NOT NULL,
	create_time TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	bindname    varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE KEY (uid, atype),
	UNIQUE KEY (atype, account)
) ENGINE = InnoDB;

ALTER TABLE `pre_forum_thread`
	ADD COLUMN `summary` varchar(255) NOT NULL DEFAULT ''  AFTER `subject`;

DROP TABLE IF EXISTS `pre_restful_api`;
CREATE TABLE `pre_restful_api`
(
	`baseuri`   varchar(255) NOT NULL,
	`ver`       smallint(6) unsigned NOT NULL,
	`name`      varchar(255) NOT NULL,
	`copyright` varchar(255) NOT NULL,
	`data`      text         NOT NULL,
	`status`    tinyint(1)           NOT NULL DEFAULT '1',
	`dateline`  int(10) unsigned     NOT NULL,
	PRIMARY KEY (`baseuri`, `ver`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `pre_restful_app`;
CREATE TABLE `pre_restful_app`
(
	`appid`    int(10) unsigned NOT NULL,
	`secret`   varchar(255) NOT NULL,
	`name`     varchar(255) NOT NULL,
	`data`     text         NOT NULL,
	`status`   tinyint(1)       NOT NULL DEFAULT '1',
	`dateline` int(10) unsigned NOT NULL,
	PRIMARY KEY (`appid`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `pre_restful_permission`;
CREATE TABLE `pre_restful_permission`
(
	`appid`    int(10) unsigned     NOT NULL,
	`uri`      varchar(255)         NOT NULL,
	`ver`      smallint(6) unsigned NOT NULL,
	`isbase`   tinyint(1)           NOT NULL,
	`freq`     int(10) unsigned     NOT NULL,
	`dateline` int(10) unsigned     NOT NULL,
	PRIMARY KEY (`appid`, `uri`, `ver`),
	KEY `isbase` (`isbase`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS pre_restful_stat;
CREATE TABLE pre_restful_stat
(
	`appid`    int(10) unsigned NOT NULL,
	`uri`      varchar(255)     NOT NULL,
	`daytime`  int(10) unsigned NOT NULL DEFAULT '0',
	`request`  int(10) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`appid`, `uri`, `daytime`),
	KEY daytime (daytime)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS `pre_common_log`;
CREATE TABLE `pre_common_log`
(
	`id`           bigint unsigned AUTO_INCREMENT,
	`uid`          mediumint(8) unsigned NOT NULL DEFAULT '0',
	`loginname`    char(50)     NOT NULL DEFAULT '',
	`username`     char(50)     NOT NULL DEFAULT '',
	`type`         varchar(255) NOT NULL DEFAULT '',
	`data`         json         NOT NULL,
	`operationuid` mediumint(8) unsigned NOT NULL DEFAULT '0',
	`source`       varchar(255) NOT NULL DEFAULT '',
	`device`       json         NOT NULL,
	`record`       varchar(255) NOT NULL DEFAULT '',
	`dateline`     bigint unsigned       NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	KEY            uid (uid),
	KEY            dateline (dateline),
	KEY            `type` (`type`)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `pre_common_robot_user_agents`
(
	`id`                 int unsigned NOT NULL AUTO_INCREMENT,
	`user_agent_keyword` varchar(255) NOT NULL,
	`category`           varchar(30)           DEFAULT NULL,
	`first_seen_at`      int unsigned NOT NULL DEFAULT '0',
	`last_seen_at`       int unsigned NOT NULL DEFAULT '0',
	`seen_count`         int unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	UNIQUE KEY `unique_ua_keyword` (`user_agent_keyword`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS pre_common_emaillog;
CREATE TABLE pre_common_emaillog
(
	`logid`     int(10) unsigned NOT NULL AUTO_INCREMENT,
	`uid`       mediumint(8) unsigned NOT NULL,
	`emailtype` int(10) NOT NULL DEFAULT '0',
	`svctype`   int(10) NOT NULL DEFAULT '0',
	`status`    int(10) NOT NULL DEFAULT '0',
	`verify`    int(10) NOT NULL DEFAULT '0',
	`email`     varchar(255) NOT NULL DEFAULT '',
	`ip`        varchar(45)  NOT NULL DEFAULT '',
	`port`      smallint(6) unsigned NOT NULL DEFAULT '0',
	`content`   text         NOT NULL,
	`dateline`  int(10) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`logid`),
	KEY         dateline (`email`, `dateline`),
	KEY         uid (uid)
) ENGINE=InnoDB;

ALTER TABLE `pre_common_usergroup`
	ADD COLUMN upgroupid smallint(6) unsigned DEFAULT '0';

ALTER TABLE `pre_common_usergroup`
	ADD INDEX upgroupid (upgroupid);

DROP TABLE IF EXISTS `pre_common_editorblock`;
CREATE TABLE `pre_common_editorblock`
(
	`blockid`     int(10) unsigned NOT NULL AUTO_INCREMENT,
	`available`   tinyint(1) NOT NULL DEFAULT '0',
	`columns`     tinyint(1) NOT NULL DEFAULT '0',
	`type`        int(10) NOT NULL DEFAULT '0',
	`sort`        int(10) NOT NULL DEFAULT '0',
	`name`        varchar(255) NOT NULL DEFAULT '',
	`version`     varchar(255) NOT NULL DEFAULT '',
	`identifier`  varchar(255) NOT NULL DEFAULT '',
	`description` varchar(255) NOT NULL DEFAULT '',
	`class`       varchar(255) NOT NULL DEFAULT '0',
	`parser`      mediumtext   NOT NULL,
	`style`       mediumtext   NOT NULL,
	`config`      text         NOT NULL,
	`filename`    varchar(255) NOT NULL DEFAULT '',
	`i18n`        text         NOT NULL,
	`parameters`  text         NOT NULL,
	`plugin`      varchar(255) NOT NULL DEFAULT '',
	`filemtime`   int(10) unsigned NOT NULL DEFAULT '0',
	`copyright`   varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (blockid)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `pre_forum_post`
	ADD COLUMN `content` JSON DEFAULT NULL  AFTER `message`;

DROP TABLE IF EXISTS `pre_restful_source`;
CREATE TABLE `pre_restful_source`
(
	`sourceid` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name`     varchar(255) NOT NULL,
	`url`      varchar(255) NOT NULL,
	PRIMARY KEY (`sourceid`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS pre_common_admincp_menu_platform;
CREATE TABLE pre_common_admincp_menu_platform
(
	platform varchar(255) NOT NULL DEFAULT 'system',
	menu     text         NOT NULL,
	PRIMARY KEY (platform)
) ENGINE = InnoDB;

ALTER TABLE `pre_common_member_profile`
	ADD COLUMN fields json NOT NULL AFTER `field8`;

ALTER TABLE `pre_common_member_profile_history`
	ADD COLUMN fields json NOT NULL AFTER `field8`;

ALTER TABLE `pre_common_member_profile`
	CHANGE COLUMN `msn` `wechat` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `pre_common_member_profile_history`
	CHANGE COLUMN `msn` `wechat` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `pre_common_member_profile_archive`
	CHANGE COLUMN `msn` `wechat` varchar(255) NOT NULL DEFAULT '';

UPDATE `pre_common_member_profile_setting`
	SET `fieldid` = 'wechat', `title` = 'WeChat'
	WHERE `fieldid` = 'msn';

DELETE FROM `pre_common_setting` WHERE `skey` = 'msn';

DROP TABLE IF EXISTS pre_common_stylevar_extra;
CREATE TABLE pre_common_stylevar_extra
(
	stylevarid   mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	styleid      smallint(6) unsigned  NOT NULL DEFAULT '0',
	displayorder tinyint(3)            NOT NULL DEFAULT '0',
	title        varchar(100) NOT NULL DEFAULT '',
	description  varchar(255) NOT NULL DEFAULT '',
	variable     varchar(40)  NOT NULL DEFAULT '',
	`type`       varchar(20)  NOT NULL DEFAULT 'text',
	`value`      text         NOT NULL,
	extra        text         NOT NULL,
	PRIMARY KEY (stylevarid),
	KEY          styleid (styleid)
) ENGINE = InnoDB;

ALTER TABLE pre_common_stylevar
	CHANGE COLUMN stylevarid stylevarid mediumint(8) unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE pre_common_pluginvar
	CHANGE COLUMN displayorder displayorder int (10) NOT NULL DEFAULT '0';

ALTER TABLE pre_common_stylevar_extra
	CHANGE COLUMN displayorder displayorder int (10) NOT NULL DEFAULT '0';

ALTER TABLE pre_common_style
	ADD COLUMN version varchar(20) NOT NULL DEFAULT '';

ALTER TABLE `pre_forum_post`
	ADD COLUMN `bestanswer` tinyint(1) NOT NULL,
	ADD INDEX (`bestanswer`);

ALTER TABLE pre_common_member_profile_setting
	ADD COLUMN encrypt tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE pre_common_usergroup
	ADD COLUMN creditsformula varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `pre_common_admincp_menu_platform`
	ADD displayorder smallint(6) NOT NULL DEFAULT '0';

ALTER TABLE `pre_common_pluginvar`
	MODIFY `type` varchar (255) NOT NULL DEFAULT 'text';

ALTER TABLE `pre_common_stylevar_extra`
	MODIFY `type` varchar (255) NOT NULL DEFAULT 'text';

DROP TABLE IF EXISTS pre_common_session;
CREATE TABLE pre_common_session
(
	sid          char(6)               NOT NULL DEFAULT '',
	ip           varchar(45)           NOT NULL DEFAULT '',
	uid          mediumint(8) unsigned NOT NULL DEFAULT '0',
	username     char(50)              NOT NULL DEFAULT '',
	bot_reason   varchar(255)          NOT NULL DEFAULT '',
	location     varchar(191)          NOT NULL DEFAULT '',
	city         varchar(191)          NOT NULL DEFAULT '',
	referrer     varchar(255)          NOT NULL DEFAULT '',
	groupid      smallint(6) unsigned  NOT NULL DEFAULT '0',
	invisible    tinyint(1)            NOT NULL DEFAULT '0',
	`action`     tinyint(3) unsigned   NOT NULL DEFAULT '0',
	lastactivity int(10) unsigned      NOT NULL DEFAULT '0',
	lastolupdate int(10) unsigned      NOT NULL DEFAULT '0',
	fid          mediumint(8) unsigned NOT NULL DEFAULT '0',
	tid          int(10) unsigned      NOT NULL DEFAULT '0',
	UNIQUE KEY sid (sid),
	KEY uid (uid)
) ENGINE = InnoDB;


ALTER TABLE pre_common_smslog ADD INDEX status (`status`, `dateline`, `uid`);
ALTER TABLE pre_common_smslog ADD INDEX dateline2 (`dateline`);

ALTER TABLE pre_portal_article_content
	MODIFY COLUMN content mediumtext NOT NULL;

ALTER TABLE pre_forum_thread
	ADD INDEX displayorder_dateline (fid, displayorder, dateline);
ALTER TABLE pre_forum_thread
	ADD INDEX typeid_dateline (fid, typeid, displayorder, dateline);
ALTER TABLE pre_forum_thread
	ADD INDEX displayorder_replies (fid, displayorder, replies);
ALTER TABLE pre_forum_thread
	ADD INDEX typeid_replies (fid, typeid, displayorder, replies);
ALTER TABLE pre_forum_thread
	ADD INDEX displayorder_views (fid, displayorder, views);
ALTER TABLE pre_forum_thread
	ADD INDEX typeid_views (fid, typeid, displayorder, views);
ALTER TABLE pre_forum_thread
	ADD INDEX displayorder_recommends (fid, displayorder, recommends);
ALTER TABLE pre_forum_thread
	ADD INDEX typeid_recommends (fid, typeid, displayorder, recommends);
ALTER TABLE pre_forum_thread
	ADD INDEX displayorder_heats (fid, displayorder, heats);
ALTER TABLE pre_forum_thread
	ADD INDEX typeid_heats (fid, typeid, displayorder, heats);

UPDATE pre_common_setting
	SET svalue = REPLACE(REPLACE(svalue, 's:5:"reply";i:5;', ''), 's:9:"recommend";i:3;', '')
	WHERE skey = 'heatthread';
UPDATE pre_common_setting
	SET svalue = REPLACE(REPLACE(svalue, 'a:6:{', 'a:4:{'), 'a:5:{', 'a:3:{')
	WHERE skey = 'heatthread' AND (svalue LIKE 'a:5:{%' OR svalue LIKE 'a:6:{%');

ALTER TABLE `pre_forum_collection`
	ADD COLUMN cover tinyint(1) unsigned NOT NULL DEFAULT '0';

ALTER TABLE `pre_forum_collection`
	ADD COLUMN icon tinyint(1) unsigned NOT NULL DEFAULT '0';

ALTER TABLE `pre_common_tag`
	MODIFY `tagname` char (50) NOT NULL DEFAULT '';

ALTER TABLE `pre_common_tag`
	ADD COLUMN `related_count` mediumint(8) unsigned NOT NULL DEFAULT '0'  AFTER `status`,
	ADD COLUMN `hot_score` float NOT NULL DEFAULT '0'  AFTER `related_count`,
	ADD COLUMN `created_at` int(10) unsigned NOT NULL DEFAULT '0'  AFTER `hot_score`,
	ADD COLUMN `updated_at` int(10) unsigned NOT NULL DEFAULT '0'  AFTER `created_at`,
	ADD KEY `idx_hot_score` (`hot_score`);

ALTER TABLE `pre_common_tagitem`
	ADD COLUMN `created_at` int(10) unsigned NOT NULL DEFAULT '0'  AFTER `idtype`,
ADD KEY `idx_created_at` (`created_at`);


ALTER TABLE `pre_portal_article_title` ADD `tags` VARCHAR(255) NOT NULL AFTER `click8`;

ALTER TABLE `pre_forum_threadtype` ADD super text NOT NULL;

ALTER TABLE pre_common_credit_log_field
	ADD COLUMN dateline int(10) unsigned NOT NULL DEFAULT 0;

ALTER TABLE pre_common_credit_log_field
	ADD INDEX dateline (dateline);

ALTER TABLE pre_common_credit_log_field
	ADD COLUMN ac_extcredits1 int(10)          NOT NULL,
    	ADD COLUMN ac_extcredits2 int(10)          NOT NULL,
    	ADD COLUMN ac_extcredits3 int(10)          NOT NULL,
    	ADD COLUMN ac_extcredits4 int(10)          NOT NULL,
    	ADD COLUMN ac_extcredits5 int(10)          NOT NULL,
    	ADD COLUMN ac_extcredits6 int(10)          NOT NULL,
    	ADD COLUMN ac_extcredits7 int(10)          NOT NULL,
    	ADD COLUMN ac_extcredits8 int(10)          NOT NULL;

ALTER TABLE pre_forum_forum
	ADD COLUMN editormode tinyint(1) NOT NULL DEFAULT '-1';

DELETE FROM pre_common_credit_log
	WHERE dateline < UNIX_TIMESTAMP(NOW() - INTERVAL 1 YEAR);

UPDATE pre_common_credit_log_field f
	JOIN pre_common_credit_log l ON f.logid = l.logid
	SET f.dateline = l.dateline;

ALTER TABLE pre_common_credit_log_field
	CHANGE COLUMN logid logid int(10) unsigned NOT NULL;

ALTER TABLE pre_common_credit_log_field
	ADD COLUMN uid mediumint(8) unsigned NOT NULL DEFAULT '0' AFTER logid;

ALTER TABLE pre_common_credit_log_field
	ADD INDEX uid (uid);

UPDATE pre_common_credit_log_field f
	JOIN pre_common_credit_log l ON f.logid = l.logid
	SET f.uid = l.uid;

CREATE TABLE IF NOT EXISTS pre_home_doing_attachment
(
	`aid`           int UNSIGNED            NOT NULL AUTO_INCREMENT,
	`doid`          int UNSIGNED            NOT NULL DEFAULT '0',
	`uid`           mediumint UNSIGNED      NOT NULL DEFAULT '0',
	`dateline`      int UNSIGNED            NOT NULL DEFAULT '0',
	`filename`      varchar(255)            NOT NULL DEFAULT '',
	`filesize`      int UNSIGNED            NOT NULL DEFAULT '0',
	`attachment`    varchar(255)            NOT NULL DEFAULT '',
	`remote`        tinyint(1)              NOT NULL DEFAULT '0',
	`isimage`       tinyint(1)              NOT NULL DEFAULT '0',
	`width`         mediumint UNSIGNED      NOT NULL DEFAULT '0',
	`height`        mediumint UNSIGNED      NOT NULL DEFAULT '0',
	`displayorder`  int NOT NULL,
	PRIMARY KEY (`aid`),
	KEY `uid` (`uid`),
	KEY `doid` (`doid`)
) ENGINE=InnoDB;

ALTER TABLE `pre_home_doing` 
	ADD COLUMN `itemid` mediumint(8) UNSIGNED NOT NULL DEFAULT '0' AFTER `doid`,
	ADD COLUMN `type` varchar(30) NOT NULL DEFAULT '' AFTER `itemid`,
	ADD COLUMN `body_template` text NOT NULL AFTER `dateline`,
	ADD COLUMN `body_data` text NOT NULL AFTER `body_template`,
	ADD COLUMN `recomends` int(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `replynum`,
	ADD INDEX `type`(`type`),
	ADD INDEX `itemid`(`itemid`);

ALTER TABLE `pre_common_usergroup_field`
	ADD COLUMN `fields` json;

ALTER TABLE `pre_forum_forumfield`
	ADD COLUMN `fields` json;

CREATE TABLE IF NOT EXISTS pre_home_doing_recomend_log
(
	id              int(10) unsigned NOT NULL AUTO_INCREMENT,
	doid            int(10) unsigned NOT NULL DEFAULT '0',
	uid             int(10) unsigned NOT NULL DEFAULT '0',
	dateline        int(10) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (id),
	UNIQUE KEY doid_uid (doid,uid),
	KEY doid (doid),
	KEY uid (uid)
) ENGINE=InnoDB;

ALTER TABLE `pre_home_doing` ADD `sharetimes` INT UNSIGNED NOT NULL AFTER `recomends`;
ALTER TABLE `pre_home_doing` ADD `favtimes` INT UNSIGNED NOT NULL AFTER `sharetimes`;
ALTER TABLE `pre_home_doing` ADD `fields` JSON NOT NULL AFTER `status`;

ALTER TABLE pre_common_member_archive
	MODIFY username char (50) NOT NULL DEFAULT '';

ALTER TABLE pre_common_member_profile_archive
	ADD COLUMN fields json NOT NULL AFTER `field8`;

ALTER TABLE `pre_home_docomment`
	ADD COLUMN `replynum` int UNSIGNED NOT NULL AFTER `grade`,
	ADD COLUMN `recomends` int UNSIGNED NOT NULL AFTER `replynum`,
	ADD COLUMN `status` tinyint NOT NULL AFTER `recomends`,
	ADD COLUMN `fields` json NOT NULL AFTER `status`,
	ADD INDEX `status`(`status`) USING BTREE;
CREATE TABLE IF NOT EXISTS pre_common_member_auth
(
	uid        mediumint(8) unsigned NOT NULL,
	`password` varchar(255)          NOT NULL DEFAULT '',
	`salt`     varchar(20)           NOT NULL DEFAULT '',
	`secques`  varchar(8)            NOT NULL DEFAULT '',
	PRIMARY KEY (uid)
) ENGINE = InnoDB;
INSERT IGNORE INTO pre_common_member_auth (uid, `password`, `salt`, `secques`)
SELECT uid, `password`, `salt`, `secques` FROM pre_ucenter_members;

CREATE TABLE IF NOT EXISTS pre_common_pm_thread (
	plid mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
	authorid mediumint(8) unsigned NOT NULL DEFAULT '0',
	pmtype tinyint(3) unsigned NOT NULL DEFAULT '1',
	subject varchar(80) NOT NULL DEFAULT '',
	members smallint(5) unsigned NOT NULL DEFAULT '0',
	min_max varchar(21) NOT NULL DEFAULT '',
	dateline int(10) unsigned NOT NULL DEFAULT '0',
	lastdateline int(10) unsigned NOT NULL DEFAULT '0',
	lastauthorid mediumint(8) unsigned NOT NULL DEFAULT '0',
	lastsummary text NOT NULL,
	PRIMARY KEY (plid), KEY min_max (min_max), KEY authorid (authorid,dateline), KEY lastdateline (lastdateline)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS pre_common_pm_member (
	plid mediumint(8) unsigned NOT NULL DEFAULT '0', uid mediumint(8) unsigned NOT NULL DEFAULT '0',
	isnew tinyint(1) NOT NULL DEFAULT '0', pmnum int(10) unsigned NOT NULL DEFAULT '0',
	lastupdate int(10) unsigned NOT NULL DEFAULT '0', lastdateline int(10) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (plid,uid), KEY uid_new (uid,isnew), KEY uid_lastdateline (uid,lastdateline)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS pre_common_pm_message (
	pmid int(10) unsigned NOT NULL AUTO_INCREMENT, plid mediumint(8) unsigned NOT NULL DEFAULT '0',
	authorid mediumint(8) unsigned NOT NULL DEFAULT '0', message mediumtext NOT NULL,
	dateline int(10) unsigned NOT NULL DEFAULT '0', PRIMARY KEY (pmid),
	KEY plid_dateline (plid,dateline), KEY authorid (authorid)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS pre_common_pm_message_status (
	pmid int(10) unsigned NOT NULL DEFAULT '0', uid mediumint(8) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (pmid,uid), KEY uid (uid)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS pre_common_pm_blacklist (
	uid mediumint(8) unsigned NOT NULL DEFAULT '0', usernames text NOT NULL, PRIMARY KEY (uid)
) ENGINE=InnoDB;

/* Remove the retired thread stamp feature and its persisted data. */
DELETE FROM pre_common_smiley WHERE `type` IN ('stamp', 'stamplist');
DELETE FROM pre_common_setting WHERE skey IN ('newbie', 'stamplistlevel');
DELETE FROM pre_common_syscache WHERE cname IN ('stamps', 'stamptypeid');
DELETE FROM pre_forum_threadmod WHERE action IN ('SPA', 'SPD', 'SLA', 'SLD') OR action REGEXP '^[SL][0-9]{2}$';

ALTER TABLE pre_common_admingroup
	DROP COLUMN allowstampthread,
	DROP COLUMN allowstamplist;
ALTER TABLE pre_forum_thread
	DROP COLUMN stamp,
	DROP COLUMN icon;
ALTER TABLE pre_forum_threadmod
	DROP COLUMN stamp;
ALTER TABLE pre_common_smiley
	MODIFY COLUMN `type` enum('smiley') NOT NULL DEFAULT 'smiley';

ALTER TABLE pre_common_admingroup
	DROP COLUMN allowlivethread;
ALTER TABLE pre_forum_forumfield
	DROP COLUMN livetid;

/* Store localized forum names in one JSON column. */
ALTER TABLE pre_forum_forum ADD COLUMN name_i18n JSON NULL AFTER `name`;
UPDATE pre_forum_forum
	SET name_i18n = JSON_OBJECT('SC', `name`);
ALTER TABLE pre_forum_forum
	DROP COLUMN `name`,
	CHANGE COLUMN name_i18n `name` JSON NOT NULL;

/* Guest posting is retired; posts no longer retain request network data. */
ALTER TABLE pre_forum_post
	DROP COLUMN useip,
	DROP COLUMN `port`;

/* Restore the standalone site chat history table. */
CREATE TABLE IF NOT EXISTS `chat`
(
	`time`    timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	uid       mediumint NOT NULL,
	author    char(30)  NOT NULL,
	message   text      NOT NULL,
	PRIMARY KEY (`time`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

/* Replace post ratings with comments and binary reply votes. */
INSERT INTO pre_forum_postcomment
	(tid, pid, author, authorid, dateline, `comment`, score, useip, `port`, rpid)
SELECT
	p.tid,
	r.pid,
	MAX(r.username),
	r.uid,
	r.dateline,
	LEFT(CONCAT_WS(' ',
		NULLIF(MAX(NULLIF(r.reason, '')), ''),
		CONCAT('[', GROUP_CONCAT(CONCAT(
			'extcredits', r.extcredits, ' ',
			IF(r.score > 0, '+', ''), r.score
		) ORDER BY r.extcredits SEPARATOR ', '), ']')
	), 255),
	0,
	'',
	0,
	0
FROM pre_forum_ratelog r
INNER JOIN pre_forum_post p ON p.pid = r.pid
WHERE NOT EXISTS (
	SELECT 1
	FROM pre_forum_postcomment c
	WHERE c.pid = r.pid
		AND c.authorid = r.uid
		AND c.dateline = r.dateline
)
GROUP BY p.tid, r.pid, r.uid, r.dateline;

INSERT IGNORE INTO pre_forum_hotreply_member (tid, pid, uid, attitude)
SELECT p.tid, r.pid, r.uid, IF(SUM(r.score) > 0, 1, 0)
FROM pre_forum_ratelog r
INNER JOIN pre_forum_post p ON p.pid = r.pid AND p.first = 0
GROUP BY p.tid, r.pid, r.uid
HAVING SUM(r.score) <> 0;

INSERT INTO pre_forum_hotreply_number (pid, tid, support, `against`, total)
SELECT
	pid,
	MAX(tid),
	SUM(attitude = 1),
	SUM(attitude = 0),
	COUNT(*)
FROM pre_forum_hotreply_member
GROUP BY pid
ON DUPLICATE KEY UPDATE
	tid = VALUES(tid),
	support = VALUES(support),
	`against` = VALUES(`against`),
	total = VALUES(total);

UPDATE pre_forum_post p
INNER JOIN (SELECT DISTINCT pid FROM pre_forum_ratelog) r ON r.pid = p.pid
SET p.`comment` = 1;

DELETE FROM pre_forum_postcache
WHERE pid IN (SELECT DISTINCT pid FROM pre_forum_ratelog);

DELETE FROM pre_common_setting
WHERE skey IN ('dupkarmarate', 'karmaratelimit', 'modratelimit', 'ratelogon', 'ratelogrecord');

DELETE FROM pre_home_notification
WHERE `type` = 'rate';

ALTER TABLE pre_common_usergroup_field
	DROP COLUMN raterange;
ALTER TABLE pre_forum_post
	DROP COLUMN rate,
	DROP COLUMN ratetimes;
ALTER TABLE pre_forum_postcache
	DROP COLUMN rate;
ALTER TABLE pre_forum_thread
	DROP COLUMN rate;
DROP TABLE pre_forum_ratelog;

ALTER TABLE pre_common_nav
	MODIFY `name` text NOT NULL;
ALTER TABLE pre_forum_onlinelist
	MODIFY title varchar(255) NOT NULL DEFAULT '';

UPDATE pre_common_setting
SET svalue = JSON_OBJECT('SC', svalue, 'TC', svalue, 'EN', svalue)
WHERE skey IN ('bbname', 'sitename') AND JSON_VALID(svalue) = 0;

UPDATE pre_common_nav
SET `name` = JSON_OBJECT(
	'SC', `name`,
	'TC', `name`,
	'EN', COALESCE(NULLIF(title, ''), `name`)
)
WHERE JSON_VALID(`name`) = 0;

ALTER TABLE pre_common_nav
	DROP COLUMN title;

UPDATE pre_forum_onlinelist
SET title = JSON_OBJECT(
	'SC', title,
	'TC', title,
	'EN', COALESCE(NULLIF(url, ''), title)
)
WHERE JSON_VALID(title) = 0;

CREATE TABLE IF NOT EXISTS pre_forum_emailpost
(
	messageid   varchar(255)             NOT NULL,
	mailuid     bigint(20) unsigned      NOT NULL DEFAULT '0',
	sender      varchar(255)             NOT NULL DEFAULT '',
	uid         mediumint(8) unsigned    NOT NULL DEFAULT '0',
	action      enum ('thread','reply')  NOT NULL,
	parentid    varchar(255)             NOT NULL DEFAULT '',
	fid         mediumint(8) unsigned    NOT NULL DEFAULT '0',
	tid         mediumint(8) unsigned    NOT NULL DEFAULT '0',
	pid         int(10) unsigned         NOT NULL DEFAULT '0',
	status      tinyint(1)               NOT NULL DEFAULT '0',
	dateline    int(10) unsigned         NOT NULL DEFAULT '0',
	detail      varchar(255)             NOT NULL DEFAULT '',
	PRIMARY KEY (messageid),
	KEY tid (tid, pid),
	KEY status (status, dateline),
	KEY uid (uid, dateline)
) ENGINE = InnoDB;

ALTER TABLE pre_forum_hotreply_member
	ADD COLUMN dateline int(10) unsigned NOT NULL DEFAULT '0',
	ADD KEY uid (uid, dateline);

DELETE FROM pre_common_setting WHERE skey = 'fastsmiley';

CREATE TABLE IF NOT EXISTS pre_forum_threadattention
(
	tid        int(10) unsigned      NOT NULL DEFAULT '0',
	uid        mediumint(8) unsigned NOT NULL DEFAULT '0',
	dateline   int(10) unsigned      NOT NULL DEFAULT '0',
	newreplies smallint(6) unsigned  NOT NULL DEFAULT '0',
	PRIMARY KEY (tid, uid),
	KEY uid (uid)
) ENGINE = InnoDB;

REPLACE INTO pre_common_setting (skey, svalue) VALUES ('attentionstatus', '1');

-- Ensure blockstyle hash is unique so upgrade data inserts are deduped by
-- runquery()/rundatasql(), which skip rows that raise a 'Duplicate' error.
ALTER TABLE pre_common_block_style
	DROP KEY hash,
	ADD UNIQUE KEY hash (hash);
