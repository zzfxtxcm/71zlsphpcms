CREATE TABLE IF NOT EXISTS `phpcms_denglu_bind_info` (
	`uid` mediumint(8) unsigned NOT NULL,
	`mediaUserID` mediumint(8) NOT NULL, 
	`mediaID` tinyint(1) NOT NULL,
	`screenName` char(250) NOT NULL,
	`createtime` int(10) NOT NULL,
	`is_first` tinyint(1) NOT NULL,
	`thread_syn` tinyint(1) NOT NULL,
	`log_syn` tinyint(1) NOT NULL,
	`tag` tinyint(1) NOT NULL,
	`extendfield1` tinyint(1) NOT NULL,
	`extendfield2` char(250) NOT NULL,
	`extendfield3` tinyint(1) NOT NULL,
	`extendfield4` char(250) NOT NULL,
	`extendfield5` tinyint(1) NOT NULL,
	PRIMARY KEY  (`mediaUserID`),
	KEY `dz_uid` (`uid`),
	KEY `mediaID` (`mediaID`)
) TYPE=MyISAM;