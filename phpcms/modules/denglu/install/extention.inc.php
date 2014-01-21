<?php

defined('IN_PHPCMS') or exit('Access Denied');
defined('INSTALL') or exit('Access Denied');

$this->sql_execute("INSERT INTO `phpcms_module` (`module`, `name`, `url`, `iscore`, `version`, `description`, `setting`, `listorder`, `disabled`, `installdate`, `updatedate`) VALUES ('denglu', '$Dlang[denglu]', 'denglu/', 0, '1.0.0', '$Dlang[denglu]', '', 0, 0, '2010-9-05', '2010-9-05');");
$this->sql_execute("INSERT INTO `phpcms_member_menu` ( `name`, `parentid`, `m`, `c`, `a`, `data`, `listorder`, `display` ) VALUES ('denglu_setting', '0', 'denglu', 'user', 'bind', 't=3', 0, 1);");

$parentid = $menu_db->insert(array('name'=>'denglu_setting', 'parentid'=>29, 'm'=>'denglu', 'c'=>'denglu_admin', 'a'=>'init', 'data'=>'', 'listorder'=>0, 'display'=>'1'), true);
$menu_db->insert(array('name'=>'denglu_media_info', 'parentid'=>$parentid, 'm'=>'denglu', 'c'=>'denglu_admin', 'a'=>'media', 'data'=>'', 'listorder'=>0, 'display'=>'0'));

$language = array('denglu_setting'=>$Dlang['denglu_setting'],'denglu_media_info',$Dlang['denglu_media']);
?>
