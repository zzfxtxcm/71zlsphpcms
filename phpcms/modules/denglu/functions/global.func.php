<?php
defined('IN_PHPCMS') or exit('No permission resources.');
function get_userinfo(){
	if(!empty($_GET['token'])){
		$api = load_denglu_api2();
		return $api->get_user_info($_GET['token']);
	}elseif(!empty($_POST['userbak'])){
		$result = sys_auth($_POST['userbak'],'DECODE');
		$result = unserialize($result);

		return $result;
	}else{
		return array();
	}
}

function dl_a($a='001'){
	echo '<script>alert("'.$a.'")</script>';
}

function denglu_data(){
	
	require dirname(__FILE__).'/denglu_data.php';/////////////denglu_data.php和denglu_api.class.php在一个目录下

	foreach($denglu_data as $data){
		$arr[$data['mediaID']] = $data;
	}
	return $arr;
}

function denglu_cache(){
	require dirname(__FILE__).'/denglu_cache.php';
	return $denglu_cache;
}

function load_denglu_cache($key){
	static $denglu_cache;
	if(isset($denglu_cache[$key])) return $denglu_cache[$key];
	require dirname(__FILE__).'/denglu_cache.php';
	return $denglu_cache[$key];
}

function is_bind($mediaUserID){
	global $_userid;
	$sql = "select uid,mediaID,tag from ".DB_PRE."denglu_bind_info where mediaUserID='$mediaUserID' and tag=1";
	$result = get_one($sql);

	if(!empty($result['uid'])){
		if($result['uid']==$_userid){
		 	return 1;	
		}else{
			return 2;
		}
	}else{
		return false;
	}

}
function denglu_bind($userinfo){
	global $db,$_userid,$LANG;
	$sql = "select * from ".DB_PRE."denglu_bind_info  where  uid=$_userid";
	
	$r = get_All($sql);
	$is_first = 1;
	if(!empty($r)){
		$is_first=0;
	}
	foreach($r as $v){
		if($v['mediaID']==$userinfo['mediaID']){		
			showmessage($LANG['binded_with_other'],'index.php');
		}
	}
	
	$time = time();
	$sql = "delete from ".DB_PRE."denglu_bind_info where mediaUserID=$userinfo[mediaUserID]";
	$db->query($sql);
	$sql = "insert into ".DB_PRE."denglu_bind_info(`uid`,`mediaUserID`,`screenName`,`mediaID`,`is_first`,`thread_syn`,`log_syn`,`tag`,`createtime`) values('$_userid','$userinfo[mediaUserID]','$userinfo[screenName]','$userinfo[mediaID]','$is_first','1','1','$userinfo[tag]',$time)";
	$db->query($sql);
	
	if(!empty($userinfo['profileImageUrl'])){///////////////头像处理
		//make_media_avatar($userinfo['profileImageUrl'],$userinfo['mediaUserID']);
		$photo_url = SITE_URL.'denglu.php?image='.urlencode($userinfo['profileImageUrl']).'&muid='.$userinfo['mediaUserID'];
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo "<div style='display:none'><iframe src='".$photo_url."'></iframe></div>";
	}
}

function denglu_unbind($mediaUserID){
	global $api,$db,$_userid;
	$sql = "select * from ".DB_PRE."denglu_bind_info  where uid={$_userid} and mediaUserID='$mediaUserID'";
	$noerror = get_one($sql);
	empty($noerror) && exit('hack access');
	$r = $api->post_unbind($mediaUserID);

	$sql = "delete from ".DB_PRE."denglu_bind_info  where uid={$_userid} and mediaUserID='$mediaUserID'";
	return $db->query($sql);

	return false;
}

function check_media($bind_users,$denglu_data){////////检查用户还有哪些媒体可以绑定
	$bind_users = (array)$bind_users;
	$result = array();
	
	foreach($bind_users as $v){
		$arr[] = $v['mediaID'];
	}
	if(empty($arr)){
		return $denglu_data;
	}

	foreach($denglu_data as $key => $value){
		if(in_array($value['mediaID'],$arr)){
			continue;
		}
		$result[] = $value;
	}
	return $result;
}
function get_bind_users($uid){//////////取出绑定的所有媒体用户
	$result = array();
	$sql = "select * from ".DB_PRE."denglu_bind_info  where uid=$uid";
	$ret = get_All($sql);

	return $ret;
}
function get_media_settings($uid){////////取出用户有效的媒体分享设置
	require_once dirname(__FILE__).'/denglu_api.class.php';
	
	$denglu_data = denglu_data();
	$users = get_bind_users($uid);

	$arr = array();
	if($users[0]['tag']==0) return $arr;
	
	foreach($users as $v){
		if($denglu_data[$v[mediaID]][shareFlag]){
			continue;
		}
		$arr[] = $v;
	}
	return $arr;
}
function get_media_info($mediaUserID){
	return $GLOBALS['db']->getOne("select * from ".DB_PRE."denglu_bind_info'  where mediaUserID=$mediaUserID");
}



function denglu_userset($uid){////////hidden an array for mediaUserID
	global $db,$mediaUserID;
	foreach($mediaUserID as $v){
		$condition = "uid={$uid} and mediaUserID={$v}";
		$sql = "update ".DB_PRE."denglu_bind_info  set thread_syn=".intval($_POST['thread_syn_'.$v]).", log_syn=".intval($_POST['log_syn_'.$v])." where ".$condition;

		$db->query($sql);
	}
}



function get_ALL($sql){
	global $db;
	$result = $db->query($sql);
	$arr = array();
	while($r = $db->fetch_array($result)){
		$arr[] = $r;
	}
	$db->free_result($result);
	return $arr;
}
function get_one($sql){
	global $db;
	$result = $db->get_one($sql);
	return $result;
}

function make_media_avatar($img,$muid){
	require_once dirname(__FILE__).'/thumb.class.php';

	$thumb = new thumb;
	$type = $thumb->getImagesInfo($img);
	$tempimg = DENGLU_ROOT.'avatar/'.substr(md5($muid),0,10).$type;
	$tempimg = str_replace('\\','/',$tempimg);
	
	@$thumb->upload_image($img,$tempimg);
	$bigger = $thumb->image_path($muid,'bigger');
	$big = $thumb->image_path($muid,'big');
	$middle = $thumb->image_path($muid,'middle');
	$small = $thumb->image_path($muid,'small');
	
	$thumb->make_thumb($tempimg,180,180,$bigger);
	$thumb->make_thumb($tempimg,90,90,$big);
	$thumb->make_thumb($tempimg,45,45,$middle);
	$thumb->make_thumb($tempimg,30,30,$small);

	unlink($tempimg);
}
function getEmailById($_userid){
	$sql = "select email from ".DB_PRE."member where userid=$_userid";
	$r = get_one($sql);
	return $r['email'];
}

function load_denglu_api($denglu_cache){
	require_once PC_PATH.'modules'.DIRECTORY_SEPARATOR.'denglu'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'denglu_api.class.php';
	return new denglu_api($denglu_cache['denglu_appid'],$denglu_cache['denglu_appkey'],CHARSET);
}

function load_denglu_api2(){
	require_once PC_PATH.'modules'.DIRECTORY_SEPARATOR.'denglu'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'denglu_api.class.php';
	return new denglu_api(load_denglu_cache('denglu_appid'),load_denglu_cache('denglu_appkey'),CHARSET);
}
?>