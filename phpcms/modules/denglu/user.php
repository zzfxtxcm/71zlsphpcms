<?php
defined('IN_PHPCMS') or exit('Access Denied');
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'denglu.inc.php';
pc_base::load_app_class('foreground','member');

class user extends foreground {
	function __construct() {
		parent::__construct();
		$this->http_user_agent = str_replace('7.0' ,'8.0',$_SERVER['HTTP_USER_AGENT']);
		$this->db = PC_BASE::load_model('denglu_model');
		$this->api = load_denglu_api2();
		$this->init();
	}

	public function init() {
		$memberinfo = $this->memberinfo;
		//初始化phpsso
		$phpsso_api_url = $this->_init_phpsso();
		//获取头像数组
		$avatar = $this->client->ps_getavatar($this->memberinfo['phpssouid']);

		$grouplist = getcache('grouplist');
		$memberinfo['groupname'] = $grouplist[$memberinfo[groupid]]['name'];
		
		$this->bind_users = $this->get_bind_users(param::get_cookie('_userid'));
		count($this->bind_users)==1 && $this->bind_users[0]['tag']==0 && ( $this->mediaUserID=$this->bind_users[0]['mediaUserID']);
	}

	function bind(){
		$bind_users = $this->bind_users;
		$check_media = (count($this->bind_users)==1 && $this->bind_users[0]['tag']==0) ? array() : check_media($this->bind_users,denglu_data()) ;  //show bindding users
		$op = false;

		include template('denglu','denglu_user');
	}
	function unbind(){
		$ubind = $this->denglu_unbind($_POST['mediaUserID']);
		!$ubind && showmessage(L('unbind_failed'), 'index.php?m=denglu&c=user&a=bind');
		
		showmessage(L('denglu_unbind_success'), 'index.php?m=denglu&c=user&a=bind');
	}
	protected function get_bind_users($uid){//////////取出绑定的所有媒体用户
		$ret = $this->db->select('uid='.$uid,'*');
		return $ret;
	}
	
	function do_photo(){
		$muid = $_POST['muid'];
		$uid = param::get_cookie('_userid');
		$ssouid = $this->getSsoUid($uid);
		if(!$this->check_photo($muid,$uid )){
			exit('hack accesse');
		}
		myphoto_set($muid, $this->ps_avatar($ssouid));
		showmessage(L('photo_set_success'),'index.php?m=denglu&c=user&a=myphoto');
	}
	
	function getSsoUid($uid){
		$this->db = PC_BASE::load_model('member_model');
		$r = $this->db->get_one('userid='.$uid);
		$this->db = PC_BASE::load_model('denglu_model');
		return $r['phpssouid'];
	}
	function check_photo($muid,$_userid){
		return  $this->db->get_one(array('mediaUserID'=>$muid,'uid'=>$_userid));
	}
	
	function myphoto(){
		$myphoto = myphoto($this->bind_users);
		$op = 'photo';
		include template('denglu','denglu_user');
	}
	
	function olbind(){
		if((count($this->bind_users)==1 && !$this->bind_users[0]['tag']) || empty($_GET['mid']) ){
			exit('Access denied');
		}
		param::set_cookie('olbind','1', 0);
		header('location:http://open.denglu.cc/transfer/'.$_GET['mid'].'?appid='.DENGLU_APPID.'&uid='.param::get_cookie('_userid'));
	}
	
	private function _session_start() {
		$session_storage = 'session_'.pc_base::load_config('system','session_storage');
		pc_base::load_sys_class($session_storage);
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
	
	function olbind_do_haveno(){
		$mediaUserID = $this->mediaUserID;
		if(!empty($mediaUserID) ){
			require_once DENGLU_ROOT.'functions/phpcms_register.php';
			if($userid){
				$this->db->update(array('tag'=>1,'uid'=>$userid), 'mediaUserID='.$mediaUserID ) ;
				$this->api->post_bind_info(array('mediaUserID'=>$mediaUserID,'uid'=>$userid,'username'=>$_POST['username'],'email'=>$_POST['email']));
				showmessage(L('denglu_login_success'),'index.php?m=denglu&c=user&a=bind');
			}
			showmessage(L('denglu_failed'),'index.php?m=denglu&c=user&a=bind');	
		}
	}
	function olbind_do_have(){
		$mediaUserID = $this->mediaUserID;
		if(!empty($mediaUserID) ){
			require_once DENGLU_ROOT.'functions/phpcms_login.php';
			$this->db->update(array('tag'=>1,'uid'=>$r['userid']), 'mediaUserID='.$mediaUserID ) ;

			$bind_r = $this->api->post_bind_info( array('mediaUserID'=>$mediaUserID,'uid'=>$r['userid'],'username'=>$r['username'],'email'=>$r['email']) );
			showmessage(L('denglu_bind_success'), 'index.php?m=denglu&c=user&a=bind');
		}
		showmessage(L('denglu_bind_failed'), 'index.php?m=denglu&c=user&a=bind');
	}
	
	function denglu_bind($userinfo){
		global $db,$_userid,$LANG;
		
		$r = $this->db->get_one("uid=".$userinfo['uid'],'*');
		$is_first = 1;
		if(!empty($r)){
			$is_first=0;
		}
		foreach($r as $v){
			if($v['mediaID']==$userinfo['mediaID']){		
				showmessage(L('binded_with_other'),'index.php');
			}
		}
		
		$time = time();
		$this->db->delete('mediaUserID='.$userinfo['mediaUserID']);
		
		$this->db->insert(array('uid'=>$userinfo['uid'],'mediaUserID'=>$userinfo['mediaUserID'],'screenName'=>$userinfo['screenName'],'mediaID'=>$userinfo['mediaID'],'is_first'=>$is_first,'thread_syn'=>1,'log_syn'=>1,'tag'=>$userinfo['tag'],'createtime'=>$time,'extendfield2'=>$userinfo['profileImageUrl']));
		
		if(!empty($userinfo['profileImageUrl'])){///////////////头像处理
			//make_media_avatar($userinfo['profileImageUrl'],$userinfo['mediaUserID']);
			$photo_url = SITE_URL.'denglu.php?muid='.$userinfo['mediaUserID'];
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
			echo "<div style='display:none'><iframe src='".$photo_url."'></iframe></div>";
		}
	}
	function _init_phpsso() {
		pc_base::load_app_class('client', 'member', 0);
		define('APPID', pc_base::load_config('system', 'phpsso_appid'));
		$phpsso_api_url = pc_base::load_config('system', 'phpsso_api_url');
		$phpsso_auth_key = pc_base::load_config('system', 'phpsso_auth_key');
		$this->client = new client($phpsso_api_url, $phpsso_auth_key);
		return $phpsso_api_url;
	}
	
	function denglu_unbind($mediaUserID){
		$r = $this->api->post_unbind($mediaUserID);
		return $this->db->delete(array('uid'=>param::get_cookie('_userid'),'mediaUserID'=>$mediaUserID));
	}
	
	function olbind_have(){
		$op = empty($_GET['op']) ? 'olbind_have' : $_GET['op'];
		include template('denglu','denglu_user');
	}
	function is_bind($mediaUserID){
		$result = $this->db->get_one(array('mediaUserID'=>$mediaUserID,'tag'=>1));
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
	
	function isbind($muid){
		$tag = $this->db->get_one('mediaUserID='.$muid);
	
		return $tag['tag'];
	}
	
	function denglu_olbind($muid,$userid){
		$r = $this->db->select('uid='.$userid);
		$mr = $this->db->get_one('mediaUserID='.$muid);
	
		foreach($r as $v){
			if($r['mediaID']==$mr){
				showmessage(L('cannot_bind_same_media'), 'index.php' );
			}
		}
		return $this->db->update(array('tag'=>1,'uid'=>$userid), 'mediaUserID='.$muid ); 
	}
	function ps_avatar($uid) {
		$dir1 = ceil($uid / 10000);
		$dir2 = ceil($uid % 10000 / 1000);
		$sso_url = pc_base::load_config('system', 'phpsso_api_url');
		$sso_path = str_replace(SITE_PROTOCOL.SITE_URL.WEB_PATH,'',$sso_url);
		$url = PHPCMS_PATH.$sso_path.'/uploadfile/avatar/'.$dir1.'/'.$dir2.'/'.$uid.'/';
		$this->denglu_makedir($url);
		return $url;
	}
	function denglu_makedir($url){
		if(file_exists(dirname($url))){
			mkdir($url,0777);
			if(!is_writeable($url)){
				exit($url.L('cannot_write'));
			}
		}else{
			$this->denglu_makedir($url);
		}
	}

}

function myphoto($bind_users){
	$photo = array();
	if($bind_users[0]['tag'] == 0 && count($bind_users)==1){
		return $photo;
	}
	$photo_path = PC_PATH.'modules/denglu/avatar/';
	foreach($bind_users as $u){
		if(file_exists($photo_path.$u['mediaUserID'].'_middle.jpg')){
			$photo[] = $u;
		}
	}
	return $photo;
}

function myphoto_set($muid,$path){
	$avatar = array('bigger'=>'180x180','big'=>'90x90','middle'=>'45x45','small'=>'30x30');
	foreach($avatar as  $k => $v){
		copy(DENGLU_ROOT.'avatar'.DIRECTORY_SEPARATOR.$muid.'_'.$k.'.jpg',$path.$v.'.jpg');
	}
}











?>