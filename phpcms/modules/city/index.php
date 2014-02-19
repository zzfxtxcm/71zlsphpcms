<?php
    defined('IN_PHPCMS') or exit('No permission resources.');
    
    class index {
        function __construct() {
        $this->db = pc_base::load_model('content_model');
        $this->_userid = param::get_cookie('_userid');
        $this->_username = param::get_cookie('_username');
        $this->_groupid = param::get_cookie('_groupid');
        }
        public function init()
        {
            showmessage('正在转向首页...','index.php');
        }
        
        public function mylist()
        {
            if(isset($_GET['siteid'])) {
            $siteid = intval($_GET['siteid']);
        } else {
            $siteid = 1;
        }
        $siteid = $GLOBALS['siteid'] = max($siteid,1);
        define('SITEID', $siteid);
        $_userid = $this->_userid;
        $_username = $this->_username;
        $_groupid = $this->_groupid;
        //SEO
        $SEO = seo($siteid);
        $sitelist  = getcache('sitelist','commons');
        $default_style = $sitelist[$siteid]['default_style'];
        $CATEGORYS = getcache('category_content_'.$siteid,'commons');
        $list= $_GET['list'];
      		include template('content','member_list');
        }
  }
?>