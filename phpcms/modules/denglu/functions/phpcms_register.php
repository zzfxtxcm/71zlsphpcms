<?php
defined('IN_PHPCMS') or exit('No permission resources.');
$this->_session_start();
//加载用户模块配置
$member_setting = getcache('member_setting','member');
if(!$member_setting['allowregister']) {
	showmessage(L('deny_register',array(),'member'), 'index.php?m=member&c=index&a=login');
}

//获取用户siteid
$siteid = isset($_REQUEST['siteid']) && trim($_REQUEST['siteid']) ? intval($_REQUEST['siteid']) : 1;
//定义站点id常量
if (!defined('SITEID')) {
   define('SITEID', $siteid);
}

header("Cache-control: private");
if(isset($_POST['dosubmit'])) {
	
	
	$userinfo['encrypt'] = create_randomstr(6);

	$this->userinfo['username'] = $userinfo['username'] = (isset($_POST['username']) && is_username($_POST['username'])) ? $_POST['username'] : exit('0');
	$userinfo['nickname'] = $this->userinfo['screenName'];
	
	$this->userinfo['email'] = $userinfo['email'] = (isset($_POST['email']) && is_email($_POST['email'])) ? $_POST['email'] : exit('0');
	$userinfo['password'] = isset($_POST['password']) ? $_POST['password'] : exit('0');

	$userinfo['modelid'] = isset($_POST['modelid']) ? intval($_POST['modelid']) : 10;
	$userinfo['regip'] = ip();
	$userinfo['point'] = $member_setting['defualtpoint'] ? $member_setting['defualtpoint'] : 0;
	$userinfo['amount'] = $member_setting['defualtamount'] ? $member_setting['defualtamount'] : 0;
	$userinfo['regdate'] = $userinfo['lastdate'] = SYS_TIME;
	$userinfo['siteid'] = $siteid;
	$userinfo['connectid'] = isset($_SESSION['connectid']) ? $_SESSION['connectid'] : '';
	$userinfo['from'] = isset($_SESSION['from']) ? $_SESSION['from'] : '';
	unset($_SESSION['connectid'], $_SESSION['from']);
	
	if($member_setting['enablemailcheck']) {	//是否需要邮件验证
		$userinfo['groupid'] = 7;
	} elseif($member_setting['registerverify']) {	//是否需要管理员审核
		$userinfo['modelinfo'] = isset($_POST['info']) ? array2string($_POST['info']) : '';
		$this->verify_db = pc_base::load_model('member_verify_model');
		unset($userinfo['lastdate'],$userinfo['connectid'],$userinfo['from']);
		$this->verify_db->insert($userinfo);
	} else {
		$userinfo['groupid'] = $this->_get_usergroup_bypoint($userinfo['point']);
	}

	if(pc_base::load_config('system', 'phpsso')) {
		$this->_init_phpsso();
		$this->db = PC_BASE::load_model('member_model');
		$status = $this->client->ps_member_register($userinfo['username'], $userinfo['password'], $userinfo['email'], $userinfo['regip'], $userinfo['encrypt']);
		
		if($status > 0) {
			$userinfo['phpssouid'] = $status;
			//传入phpsso为明文密码，加密后存入phpcms_v9
			$userinfo['password'] = password($userinfo['password'], $userinfo['encrypt']);
			$this->userinfo['uid'] = $userid = $this->db->insert($userinfo, 1);
			
			if($member_setting['choosemodel']) {	//如果开启选择模型
				//通过模型获取会员信息					
				require_once CACHE_MODEL_PATH.'member_input.class.php';
		        require_once CACHE_MODEL_PATH.'member_update.class.php';
				$member_input = new member_input($userinfo['modelid']);
				$user_model_info = $member_input->get($_POST['info']);
				$user_model_info['userid'] = $userid;

				//插入会员模型数据
				$this->db->set_model($userinfo['modelid']);
				$this->db->insert($user_model_info);
				$this->db = PC_BASE::load_model('denglu_model');
			}
			
			if($userid > 0) {
				//执行登陆操作
				if(!$cookietime) $get_cookietime = param::get_cookie('cookietime');
				$_cookietime = $cookietime ? intval($cookietime) : ($get_cookietime ? $get_cookietime : 0);
				$cookietime = $_cookietime ? TIME + $_cookietime : 0;
				
				if($userinfo['groupid'] == 7) {
					param::set_cookie('_username', $userinfo['username'], $cookietime);
					param::set_cookie('email', $userinfo['email'], $cookietime);							
				} else {
					$phpcms_auth_key = md5(pc_base::load_config('system', 'auth_key').$this->http_user_agent);
					$phpcms_auth = sys_auth($userid."\t".$userinfo['password'], 'ENCODE', $phpcms_auth_key);
					
					param::set_cookie('auth', $phpcms_auth, $cookietime);
					param::set_cookie('_userid', $userid, $cookietime);
					param::set_cookie('_username', $userinfo['username'], $cookietime);
					param::set_cookie('_nickname', $userinfo['nickname'], $cookietime);
					param::set_cookie('_groupid', $userinfo['groupid'], $cookietime);
					param::set_cookie('cookietime', $_cookietime, $cookietime);
				}
			}
			//如果需要邮箱认证
			if($member_setting['enablemailcheck']) {
				pc_base::load_sys_func('mail');
				$phpcms_auth_key = md5(pc_base::load_config('system', 'auth_key').$this->http_user_agent);
				$code = sys_auth($userid.'|'.md5($phpcms_auth_key), 'ENCODE', $phpcms_auth_key);
				$url = APP_PATH."index.php?m=member&c=index&a=register&code=$code&verify=1";
				$message = $member_setting['registerverifymessage'];
				$message = str_replace(array('{click}','{url}'), array('<a href="'.$url.'">'.L('please_click').'</a>',$url), $message);
				
				sendmail($userinfo['email'], L('reg_verify_email'), $message);
			}
		}
	} else {
		showmessage(L('enable_register').L('enable_phpsso'), 'index.php?m=member&c=index&a=login');
	}
}

?>