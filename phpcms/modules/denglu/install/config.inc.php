<?php 
defined('IN_PHPCMS') or exit('Access Denied');
defined('INSTALL') or exit('Access Denied');


if(strtolower(CHARSET) == 'utf-8')
{
	require dirname(__FILE__).DIRECTORY_SEPARATOR.'install_lang.utf8.php';
}
else
{
	require dirname(__FILE__).DIRECTORY_SEPARATOR.'install_lang.gbk.php';
}

$module = 'denglu';
$modulename = $Dlang['denglu'];
$introduce =   $Dlang['denglu_introduce'];
$author = $Dlang['denglu_author'];
$authorsite = 'http://www.denglu.cc';
$authoremail = 'service@denglu.cc';
?>
