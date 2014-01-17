<?php
    defined('IN_PHPCMS') or exit('No permission resources.');
    class index {
        function __construct() {
        }
        public function init()
        {
            showmessage('正在转向首页...','index.php');
        }
        
        public function mylist()
        {
      		include template('content','member_list');
        }
  }
?>