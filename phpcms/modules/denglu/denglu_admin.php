<?php
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('admin','admin',0);
pc_base::load_sys_class('form', '', 0);
pc_base::load_app_func('global', 'denglu');

class denglu_admin extends admin{
	
	function __construct(){}
	
	function init(){
		!defined('DENGLU_ROOT') && define('DENGLU_ROOT',dirname(__FILE__).DIRECTORY_SEPARATOR);
		$this->edit();
	}
	
	function media(){
		
		if(isset($_POST['dosubmit'])){
			$denglu_cache = denglu_cache();
			$api = load_denglu_api($denglu_cache);
			$denglu_data = $api->get_media_data();
			if(!is_array($denglu_data)){
				$denglu_data = array();
			}
			
			$str = "<?php\r\n \$denglu_data = ".var_export($denglu_data,1)."\r\n\n?>";
			
			if($fp = fopen(PC_PATH.'modules'.DIRECTORY_SEPARATOR.'denglu'.DIRECTORY_SEPARATOR.'functions'.DIRECTORY_SEPARATOR.'denglu_data.php','wb')){
				fwrite($fp,$str);
				@chmod(PC_PATH.'modules'.DIRECTORY_SEPARATOR.'denglu'.DIRECTORY_SEPARATOR.'functions'.DIRECTORY_SEPARATOR.'denglu_data.php', 0777);
				fclose($fp);
			}else{
				showmessage(L('denglu_dir_cannot_write'), '?m=denglu&c=denglu_admin&a=init' );
			}
		
			
			if(!is_array($denglu_data)){
				exit('network failed or data error');
			}	
			foreach($denglu_data as $v){	
				if(!file_exists(DENGLU_ROOT.'images/denglu_second_'.$v['mediaID'].'.png')){
					copy($v['mediaImage'], DENGLU_ROOT.'images/denglu_second_'.$v['mediaID'].'.png');
					copy($v['mediaIconImage'], DENGLU_ROOT.'images/denglu_second_icon_'.$v['mediaID'].'.png');
					copy($v['mediaIconNoImage'], DENGLU_ROOT.'images/denglu_second_icon_no_'.$v['mediaID'].'.png');
				}
			}
		    showmessage(L('get_media_success'), '?m=denglu&c=denglu_admin&a=media');
		}else{
			$denglu_data = denglu_data();
			include $this->admin_tpl('denglu_admin_media');
		}
	}
	
	function edit(){
		
		if(isset($_POST['dosubmit'])){
		$denglu_cache = $_POST['setting'];
	
		$str = "<?php\r\n \$denglu_cache = ".var_export($denglu_cache,1)."\r\n\n?>";
		
		if($fp = fopen(dirname(__FILE__).DIRECTORY_SEPARATOR.'functions'.DIRECTORY_SEPARATOR.'denglu_cache.php','wb')){
			fwrite($fp,$str);
			@chmod(dirname(__FILE__).DIRECTORY_SEPARATOR.'functions'.DIRECTORY_SEPARATOR.'denglu_cache.php', 0777);
			fclose($fp);
		}else{
			showmessage(L('denglu_dir_cannot_write'), '?m=denglu&c=denglu_admin&a=edit', '', '' );
		}	
			showmessage(L('save_success'), '?m=denglu&c=denglu_admin&a=edit', '', '');
		}else{
			$denglu_cache = denglu_cache();
			include $this->admin_tpl('denglu_admin_edit');
		}
	}
	
}



?>