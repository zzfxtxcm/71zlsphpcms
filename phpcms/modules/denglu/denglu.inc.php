<?php
defined('IN_PHPCMS') or exit('No permission resources.');

define('DENGLU_ROOT',dirname(__FILE__).DIRECTORY_SEPARATOR);

pc_base::load_app_func('global', 'denglu');
$denglu_data = denglu_data();
$denglu_cache = denglu_cache();

$renren_appid = empty($denglu_data[7]['apikey']) ? '' : $denglu_data[7]['apikey'];

define('DENGLU_URL','open');
define('DENGLU_APPID',$denglu_cache['denglu_appid']);
define('RENREN_APPID',$renren_appid);


?>