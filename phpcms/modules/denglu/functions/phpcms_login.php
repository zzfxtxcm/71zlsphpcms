<?php
defined('IN_PHPCMS') or exit('No permission resources.');

$this->_session_start();
//获取用户siteid
$siteid = isset($_REQUEST['siteid']) && trim($_REQUEST['siteid']) ? intval($_REQUEST['siteid']) : 1;
//定义站点id常量
if (!defined('SITEID')) {
   define('SITEID', $siteid);
}
$this->db = PC_BASE::load_model('member_model');
if(isset($_POST['dosubmit'])) {

	$username = isset($_POST['username']) && trim($_POST['username']) ? trim($_POST['username']) : showmessage(L('username_empty'), HTTP_REFERER);
	$password = isset($_POST['password']) && trim($_POST['password']) ? trim($_POST['password']) : showmessage(L('password_empty'), HTTP_REFERER);
	$synloginstr = ''; //同步登陆js代码
	
	if(pc_base::load_config('system', 'phpsso')) {
		$this->_init_phpsso();
		$status = $this->client->ps_member_login($username, $password);
		$memberinfo = unserialize($status);
		
		if(isset($memberinfo['uid'])) {
			//查询帐号
			$r = $this->db->get_one(array('phpssouid'=>$memberinfo['uid']));
			if(!$r) {
				//插入会员详细信息，会员不存在 插入会员
				$info = array(
							'phpssouid'=>$memberinfo['uid'],
				 			'username'=>$memberinfo['username'],
				 			'password'=>$memberinfo['password'],
				 			'encrypt'=>$memberinfo['random'],
				 			'email'=>$memberinfo['email'],
				 			'regip'=>$memberinfo['regip'],
				 			'regdate'=>$memberinfo['regdate'],
				 			'lastip'=>$memberinfo['lastip'],
				 			'lastdate'=>$memberinfo['lastdate'],
				 			'groupid'=>$this->_get_usergroup_bypoint(),	//会员默认组
				 			'modelid'=>10,	//普通会员
							);
							
				//如果是connect用户
				if(!empty($_SESSION['connectid'])) {
					$userinfo['connectid'] = $_SESSION['connectid'];
				}
				if(!empty($_SESSION['from'])) {
					$userinfo['from'] = $_SESSION['from'];
				}
				unset($_SESSION['connectid'], $_SESSION['from']);
				
				$this->db->insert($info);
				unset($info);
				$r = $this->db->get_one(array('phpssouid'=>$memberinfo['uid']));
			}
			$password = $r['password'];
			$synloginstr = $this->client->ps_member_synlogin($r['phpssouid']);
		} else {
			if($status == -1) {	//用户不存在
				showmessage(L('user_not_exist'), 'index.php?m=member&c=index&a=login');
			} elseif($status == -2) { //密码错误
				showmessage(L('password_error'), 'index.php?m=member&c=index&a=login');
			} else {
				showmessage(L('login_failure'), 'index.php?m=member&c=index&a=login');
			}
		}
		
	} else {
		//密码错误剩余重试次数
		$this->times_db = pc_base::load_model('times_model');
		$rtime = $this->times_db->get_one(array('username'=>$username));
		if($rtime['times'] > 4) {
			$minute = 60 - floor((SYS_TIME - $rtime['logintime']) / 60);
			showmessage(L('wait_1_hour', array('minute'=>$minute)));
		}
		
		//查询帐号
		$r = $this->db->get_one(array('username'=>$username));

		if(!$r) showmessage(L('user_not_exist'),'index.php?m=member&c=index&a=login');
		
		//验证用户密码
		$password = md5(md5(trim($password)).$r['encrypt']);
		if($r['password'] != $password) {				
			$ip = ip();
			if($rtime && $rtime['times'] < 5) {
				$times = 5 - intval($rtime['times']);
				$this->times_db->update(array('ip'=>$ip, 'times'=>'+=1'), array('username'=>$username));
			} else {
				$this->times_db->insert(array('username'=>$username, 'ip'=>$ip, 'logintime'=>SYS_TIME, 'times'=>1));
				$times = 5;
			}
			showmessage(L('password_error', array('times'=>$times)), 'index.php?m=member&c=index&a=login', 3000);
		}
		$this->times_db->delete(array('username'=>$username));
	}
	
	//如果用户被锁定
	if($r['islock']) {
		showmessage(L('user_is_lock'));
	}
	
	$userid = $r['userid'];
	$groupid = $r['groupid'];
	$username = $r['username'];
	$nickname = empty($r['nickname']) ? $username : $r['nickname'];
	
	$updatearr = array('lastip'=>ip(), 'lastdate'=>SYS_TIME);
	//vip过期，更新vip和会员组
	if($r['overduedate'] < SYS_TIME) {
		$updatearr['vip'] = 0;
	}		

	//检查用户积分，更新新用户组，除去邮箱认证、禁止访问、游客组用户、vip用户
	if($r['point'] >= 0 && !in_array($r['groupid'], array('1', '7', '8')) && empty($r[vip])) {
		$check_groupid = $this->_get_usergroup_bypoint($r['point']);

		if($check_groupid != $r['groupid']) {
			$updatearr['groupid'] = $groupid = $check_groupid;
		}
	}

	//如果是connect用户
	if(!empty($_SESSION['connectid'])) {
		$updatearr['connectid'] = $_SESSION['connectid'];
	}
	if(!empty($_SESSION['from'])) {
		$updatearr['from'] = $_SESSION['from'];
	}
	unset($_SESSION['connectid'], $_SESSION['from']);
				
	$this->db->update($updatearr, array('userid'=>$userid));
	
	if(!isset($cookietime)) {
		$get_cookietime = param::get_cookie('cookietime');
	}
	$_cookietime = $cookietime ? intval($cookietime) : ($get_cookietime ? $get_cookietime : 0);
	$cookietime = $_cookietime ? TIME + $_cookietime : 0;
	
	$phpcms_auth_key = md5(pc_base::load_config('system', 'auth_key').$this->http_user_agent);
	$phpcms_auth = sys_auth($userid."\t".$password, 'ENCODE', $phpcms_auth_key);
	
	param::set_cookie('auth', $phpcms_auth, $cookietime);
	param::set_cookie('_userid', $userid, $cookietime);
	param::set_cookie('_username', $username, $cookietime);
	param::set_cookie('_groupid', $groupid, $cookietime);
	param::set_cookie('_nickname', $nickname, $cookietime);
	param::set_cookie('cookietime', $_cookietime, $cookietime);
	$forward = isset($_POST['forward']) && !empty($_POST['forward']) ? urldecode($_POST['forward']) : 'index.php?m=member&c=index';
}
$this->db = PC_BASE::load_model('denglu_model');	
?>