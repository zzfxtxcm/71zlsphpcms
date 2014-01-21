<?php
/**
 * 
 * 有个文件程序里还没复制denglu_model.class.php
 * 安装时检查系统环境
 * 
 */
defined('IN_PHPCMS') or exit('Access Denied');
defined('INSTALL') or exit('Access Denied');


if(!function_exists('new_denglu_write')){
	function new_denglu_write($file,$add,$model,$find=''){
		global $Dlang;
		$str = file_get_contents($file);
		$str2 = $model ? str_replace($find,$add.$find,$str) : $str.$add;
		file_put_contents($file,$str2);
		$str3 = file_get_contents($file);
		if($str1 == $str3){
			exit($file.$Dlang['cannot_write']);
		}
	}
}


if(strtolower(CHARSET) == 'utf-8')
{
	copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'denglu_lang.utf8.php',dirname(__FILE__).DIRECTORY_SEPARATOR.'languages'.DIRECTORY_SEPARATOR.'zh-cn'.DIRECTORY_SEPARATOR.'denglu.lang.php');
	require dirname(__FILE__).DIRECTORY_SEPARATOR.'install_lang.utf8.php';
}
else
{
	copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'denglu_lang.gbk.php',dirname(__FILE__).DIRECTORY_SEPARATOR.'languages'.DIRECTORY_SEPARATOR.'zh-cn'.DIRECTORY_SEPARATOR.'denglu.lang.php');
	require dirname(__FILE__).DIRECTORY_SEPARATOR.'install_lang.gbk.php';
}
$functions = array('copy','fsockopen','file_get_contents','file_put_contents');
foreach($functions as $func){
	if(!function_exists($func)){
		exit($func.$Dlang['not_exists']);
	}
}
copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'denglu_model.class.php',PC_PATH.'model'.DIRECTORY_SEPARATOR.'denglu_model.class.php');
if(!file_exists(PC_PATH.'model'.DIRECTORY_SEPARATOR.'denglu_model.class.php')){
	exit(PC_PATH.'model'.$Dlang['cannot_write']);
}

$lang = pc_base::load_config('system','lang');

new_denglu_write(PC_PATH.'base.php',"\n"."@require_once PC_PATH.'modules/denglu/denglu.inc.php';");
new_denglu_write(PC_PATH.'languages'.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.'member_menu.lang.php',"\n"."\$LANG['denglu_setting'] = '".$Dlang['denglu_setting']."';\n",1,'?>');

copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'denglu.php',PHPCMS_PATH.'denglu.php');
copy(dirname(__FILE__).DIRECTORY_SEPARATOR.'dl_receiver.php',PHPCMS_PATH.'dl_receiver.php');

if(!file_exists(PHPCMS_PATH.'denglu.php') || !file_exists(PHPCMS_PATH.'denglu.php')){
	exit(PHPCMS_PATH.$Dlang['cannot_write']);
}
return array('denglu');

?>
