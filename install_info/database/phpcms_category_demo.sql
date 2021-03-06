CREATE TABLE IF NOT EXISTS `phpcms_house` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `inputtime` int(10) unsigned NOT NULL DEFAULT '0',
  `catid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `typeid` smallint(5) unsigned NOT NULL,
  `title` char(80) NOT NULL DEFAULT '',
  `style` char(24) NOT NULL DEFAULT '',
  `thumb` varchar(100) NOT NULL DEFAULT '',
  `keywords` char(40) NOT NULL DEFAULT '',
  `description` char(255) NOT NULL DEFAULT '',
  `posids` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `url` char(100) NOT NULL,
  `islink` int(10) unsigned NOT NULL DEFAULT '0',
  `agent` varchar(255) NOT NULL DEFAULT '1',
  `rent_mode` varchar(255) NOT NULL DEFAULT '1',
  `objecttype` varchar(255) NOT NULL DEFAULT '2',
  `fittype` varchar(255) NOT NULL DEFAULT '2',
  `toward` varchar(255) NOT NULL DEFAULT '',
  `houseallocation` varchar(255) NOT NULL DEFAULT '',
  `zone` int(10) unsigned NOT NULL DEFAULT '0',
  `pay_type_int` varchar(255) NOT NULL DEFAULT '1',
  `sysadd` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `username` char(20) NOT NULL,
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0',
  `listorder` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `price` int(10) unsigned NOT NULL DEFAULT '0',
  `bedroom` int(10) unsigned NOT NULL DEFAULT '0',
  `bathroom` int(10) unsigned NOT NULL DEFAULT '0',
  `city` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`listorder`,`id`),
  KEY `listorder` (`catid`,`status`,`listorder`,`id`),
  KEY `catid` (`catid`,`status`,`id`)
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS `phpcms_house_data` (
  `id` mediumint(8) unsigned DEFAULT '0',
  `content` mediumtext NOT NULL,
  `readpoint` smallint(5) unsigned NOT NULL DEFAULT '0',
  `groupids_view` varchar(100) NOT NULL,
  `paginationtype` tinyint(1) NOT NULL,
  `maxcharperpage` mediumint(6) NOT NULL,
  `template` varchar(30) NOT NULL,
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allow_comment` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `relation` varchar(255) NOT NULL DEFAULT '',
  `xiaoqu_address` varchar(255) NOT NULL DEFAULT '',
  `area` varchar(10) NOT NULL DEFAULT '',
  `phone` varchar(255) NOT NULL DEFAULT '',
  `area_info` varchar(255) NOT NULL DEFAULT '',
  `price_info` varchar(255) NOT NULL DEFAULT '',
  `household` varchar(255) NOT NULL DEFAULT '',
  `floor` varchar(100) NOT NULL DEFAULT '',
  `floors` varchar(100) NOT NULL DEFAULT '',
  `address` varchar(100) NOT NULL DEFAULT '',
  `contact` varchar(20) NOT NULL DEFAULT '',
  `photos` mediumtext NOT NULL,
  `hall` int(10) unsigned NOT NULL DEFAULT '0',
  `info_area` varchar(255) NOT NULL DEFAULT '',
  `map` varchar(255) NOT NULL DEFAULT '',
  KEY `id` (`id`)
) TYPE=MyISAM;
