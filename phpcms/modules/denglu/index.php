<?php
/**
 *
 * date 2011-09-02 16:29
 * author www.denglu.cc
 */
defined('IN_PHPCMS') or exit('Access Denied');
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'denglu.inc.php';

class index{
	var $userinfo;
	var $api;
	var $userid;
	var $username;
	
	function __construct(){
		$this->http_user_agent = str_replace('7.0' ,'8.0',$_SERVER['HTTP_USER_AGENT']);
		$this->userid = param::get_cookie('_userid');
		$this->username = param::get_cookie('username');
		$this->api = load_denglu_api2();
		$this->db = PC_BASE::load_model('denglu_model');
	}
	
	function init(){
		$userinfo = $this->userinfo = get_userinfo();
		$userbak = $this->userbak = sys_auth(serialize($this->userinfo),'ENCODE');
		$this->act = $act = empty($_GET['act']) ? (empty($_POST['act']) ? '' : $_POST['act']) : $_GET['act'];
		
		if(!intval($userinfo['mediaID'])){
			showmessage(L('error_network'), 'index.php' );
		}
		
		!empty($this->userid) && param::get_cookie('olbind') && $this->olbind();//////on-line bindding

		$this->user_info = $this->get_bind_info($userinfo['mediaUserID']);////////check media user exist or no
		!empty($this->user_info['userid']) && $this->denglu_login();
		
		in_array($act,array('do_quick_login','do_bind_haveno')) && $this->denglu_reg_bind();
		
		$this->act=='do_bind_have' && $this->denglu_login_bind();
		
		if($act == ''){
			$act = 'quick_login';
		}
		if( load_denglu_cache('denglu_force_bind')){
			$act = 'bind_have';
		}
		in_array($act,array('quick_login','bind_have','bind_haveno')) && include(template('denglu', 'denglu'));

	}
	
	function olbind(){
		$this->userinfo['tag'] = 1;
	
		$result = $this->is_bind($this->userinfo['mediaUserID']);
		$result==1 && showmessage(L('denglu_bind_success'), 'index.php?m=denglu&c=user&a=bind' );
		$result==2 && showmessage(L('binded_with_other'), 'index.php?m=denglu&c=user&a=bind' );
	
		$this->userinfo['email'] = $this->getEmailById($this->userid);
		$this->userinfo['username'] = param::get_cookie('_username');
		$this->userinfo['uid'] = $this->userid;
	
		!$result && $this->denglu_bind($this->userinfo); 
	
		$bind_r = $this->api->post_bind_info($this->userinfo);
	
		if(load_denglu_cache('denglu_login_syn')){
			$login_r = $this->api->post_login($this->userinfo['mediaUserID']);
		}
		
		param::set_cookie('olbind',0,0);
		showmessage(L('denglu_bind_success'), 'index.php?m=denglu&c=user&a=bind');
	}
	
	function denglu_login(){
		$phpcms_auth_key = md5(pc_base::load_config('system', 'auth_key').$this->http_user_agent);
		$phpcms_auth = sys_auth($this->user_info['userid']."\t".$this->user_info['password'], 'ENCODE', $phpcms_auth_key);
		
		param::set_cookie('auth', $phpcms_auth, 0);
		param::set_cookie('_userid', $this->user_info['userid'], 0);
		param::set_cookie('_username', $this->user_info['username'], 0);
		param::set_cookie('_nickname', $this->user_info['nickname'], 0);
		param::set_cookie('_groupid', $this->user_info['groupid'], 0);
		param::set_cookie('cookietime', 0, 0);
			
		showmessage(L('login_success'),'index.php');
	}
	
	function denglu_reg_bind(){
		$this->userinfo['tag'] = 1;
		
		if($this->act == 'do_quick_login'){
			$this->userinfo['tag'] = 0;
			$this->userinfo['email'] = $_POST['email'] = substr(md5(time()),0,8).'@denglu.com';
			$_POST['password'] = substr(md5($userinfo['mediaUserID']),0,8);
		}
		require_once DENGLU_ROOT.'functions/phpcms_register.php';
		if($userid){
			$this->denglu_bind($this->userinfo);
			if(	$this->act == 'do_bind_haveno'){
				
				$bind_r = $this->api->post_bind_info($userinfo);
				if(load_denglu_cache('denglu_login_syn')){
					$login_r = $this->api->post_login($this->userinfo['mediaUserID']);
				}
			}
			
			showmessage(L('denglu_login_success'),'index.php');
		}
		showmessage(L('denglu_failed'),'index.php');
	}
	
	function denglu_login_bind(){
		require_once DENGLU_ROOT.'functions/phpcms_login.php';
		
		$this->userinfo['tag'] = 1;
		$result = $this->is_bind($userinfo['mediaUserID']);
		$result==1 && showmessage(L('denglu_bind_success'),'index.php');
		$result==2 && showmessage(L('binded_with_other'),'index.php');
		$this->userinfo['email'] = $r['email'];
		$this->userinfo['username'] = $username;
		$this->userinfo['uid'] = $userid;
		!$result && $this->denglu_bind($this->userinfo); 

		$bind_r = $this->api->post_bind_info($this->userinfo);

		if(load_denglu_cache('denglu_login_syn')){
			$login_r = $this->api->post_login($this->userinfo['mediaUserID']);
		}
		
		showmessage(L('denglu_bind_success'), 'index.php' );
	}
	
	function get_bind_info($mediaUserID){////////////根据muid取出系统用户相关信息
		$ret = $this->db->get_one(array('mediaUserID'=>$mediaUserID));
		
		if(!empty($ret)){
			$member = PC_BASE::load_model('member_model');
			$result = $member->get_one(array('userid'=>$ret['uid']));
			if(!empty($result)){
				return $result;
			}
		}
		return false;
	}
	
	private function _session_start() {
		$session_storage = 'session_'.pc_base::load_config('system','session_storage');
		pc_base::load_sys_class($session_storage);
	}
	
	private function _init_phpsso() {
		pc_base::load_app_class('client', 'member', 0);
		define('APPID', pc_base::load_config('system', 'phpsso_appid'));
		$phpsso_api_url = pc_base::load_config('system', 'phpsso_api_url');
		$phpsso_auth_key = pc_base::load_config('system', 'phpsso_auth_key');
		$this->client = new client($phpsso_api_url, $phpsso_auth_key);
		return $phpsso_api_url;
	}
	
	protected function _get_usergroup_bypoint($point=0) {
		$groupid = 2;
		if(empty($point)) {
			$member_setting = getcache('member_setting');
			$point = $member_setting['defualtpoint'] ? $member_setting['defualtpoint'] : 0;
		}
		$grouplist = getcache('grouplist');
		
		foreach ($grouplist as $k=>$v) {
			$grouppointlist[$k] = $v['point'];
		}
		arsort($grouppointlist);

		//如果超出用户组积分设置则为积分最高的用户组
		if($point > max($grouppointlist)) {
			$groupid = key($grouppointlist);
		} else {
			foreach ($grouppointlist as $k=>$v) {
				if($point >= $v) {
					$groupid = $tmp_k;
					break;
				}
				$tmp_k = $k;
			}
		}
		return $groupid;
	}
	
	function denglu_bind($userinfo){
		global $db,$_userid,$LANG;
		
		$r = $this->db->select("uid=".$userinfo['uid'],'*');
		$is_first = 1;
		if(!empty($r)){
			$is_first=0;
		}
		foreach($r as $v){
			if($v['mediaID']==$userinfo['mediaID']){		
				showmessage(L('binded_with_other'),'index.php',1000000);
			}
		}
		
		$time = time();
		$this->db->delete('mediaUserID='.$userinfo['mediaUserID']);
		
		$this->db->insert(array('uid'=>$userinfo['uid'],'mediaUserID'=>$userinfo['mediaUserID'],'screenName'=>$userinfo['screenName'],'mediaID'=>$userinfo['mediaID'],'is_first'=>$is_first,'thread_syn'=>1,'log_syn'=>1,'tag'=>$userinfo['tag'],'createtime'=>$time,'extendfield2'=>$userinfo['profileImageUrl']));
		
		if(!empty($userinfo['profileImageUrl'])){
			$photo_url = SITE_PROTOCOL.SITE_URL.WEB_PATH.'index.php?m=denglu&c=index&a=photo&mid='.$userinfo['mediaUserID'];
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
			echo "<div style='display:none'><iframe src='".$photo_url."'></iframe></div>";
		}
	}
	
	protected function is_bind($mediaUserID){
		$result = $this->db->get_one(array('mediaUserID'=>$mediaUserID, 'tag'=>1));
		if(!empty($result['uid'])){
			if($result['uid']==param::get_cookie('_userid')){
			 	return 1;	
			}else{
				return 2;
			}
		}else{
			return false;
		}
	}
	function getEmailById($uid){
		$this->db = PC_BASE::load_model('member_model');
		$r = $this->db->get_one('userid='.$uid);
		$this->db = PC_BASE::load_model('denglu_model');
		return $r['email'];
	}
	function getImageByMid($uid){
		$r = $this->db->get_one('mediaUserID='.$uid);
		return $r['extendfield2'];
	}
	function photo(){
		$img = $this->getImageByMid($_GET['mid']);
		make_media_avatar($img,$_GET['mid']);
	}
	

}


?>
