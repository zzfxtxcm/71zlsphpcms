<?php
defined('IN_PHPCMS') or exit('No permission resources.');
//模型缓存路径
define('CACHE_MODEL_PATH',CACHE_PATH.'caches_model'.DIRECTORY_SEPARATOR.'caches_data'.DIRECTORY_SEPARATOR);
pc_base::load_app_func('util','content');
class my_index extends index{
	private $db;
	function __construct() {
		$this->db = pc_base::load_model('content_model');
		$this->_userid = param::get_cookie('_userid');
		$this->_username = param::get_cookie('_username');
		$this->_groupid = param::get_cookie('_groupid');
	}
	//首页
	public function init() {
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
		include template('content','index',$default_style);
	}
	//内容页
	public function show() {
		$catid = intval($_GET['catid']);
		$id = intval($_GET['id']);

		if(!$catid || !$id) showmessage(L('information_does_not_exist'),'blank');
		$_userid = $this->_userid;
		$_username = $this->_username;
		$_groupid = $this->_groupid;

		$page = intval($_GET['page']);
		$page = max($page,1);
		$siteids = getcache('category_content','commons');
		$siteid = $siteids[$catid];
		$CATEGORYS = getcache('category_content_'.$siteid,'commons');
		
		if(!isset($CATEGORYS[$catid]) || $CATEGORYS[$catid]['type']!=0) showmessage(L('information_does_not_exist'),'blank');
		$this->category = $CAT = $CATEGORYS[$catid];
		$this->category_setting = $CAT['setting'] = string2array($this->category['setting']);
		$siteid = $GLOBALS['siteid'] = $CAT['siteid'];
		
		$MODEL = getcache('model','commons');
		$modelid = $CAT['modelid'];
		
		$tablename = $this->db->table_name = $this->db->db_tablepre.$MODEL[$modelid]['tablename'];
		$r = $this->db->get_one(array('id'=>$id));
		if(!$r || $r['status'] != 99) showmessage(L('info_does_not_exists'),'blank');
		
		$this->db->table_name = $tablename.'_data';
		$r2 = $this->db->get_one(array('id'=>$id));
		$rs = $r2 ? array_merge($r,$r2) : $r;

		//再次重新赋值，以数据库为准
		$catid = $CATEGORYS[$r['catid']]['catid'];
		$modelid = $CATEGORYS[$catid]['modelid'];
		
		require_once CACHE_MODEL_PATH.'content_output.class.php';
		$content_output = new content_output($modelid,$catid,$CATEGORYS);
		$data = $content_output->get($rs);
		extract($data);
		
		//检查文章会员组权限
		if($groupids_view && is_array($groupids_view)) {
			$_groupid = param::get_cookie('_groupid');
			$_groupid = intval($_groupid);
			if(!$_groupid) {
				$forward = urlencode(get_url());
				showmessage(L('login_website'),APP_PATH.'index.php?m=member&c=index&a=login&forward='.$forward);
			}
			if(!in_array($_groupid,$groupids_view)) showmessage(L('no_priv'));
		} else {
			//根据栏目访问权限判断权限
			$_priv_data = $this->_category_priv($catid);
			if($_priv_data=='-1') {
				$forward = urlencode(get_url());
				showmessage(L('login_website'),APP_PATH.'index.php?m=member&c=index&a=login&forward='.$forward);
			} elseif($_priv_data=='-2') {
				showmessage(L('no_priv'));
			}
		}
		if(module_exists('comment')) {
			$allow_comment = isset($allow_comment) ? $allow_comment : 1;
		} else {
			$allow_comment = 0;
		}
		//阅读收费 类型
		$paytype = $rs['paytype'];
		$readpoint = $rs['readpoint'];
		$allow_visitor = 1;
		if($readpoint || $this->category_setting['defaultchargepoint']) {
			if(!$readpoint) {
				$readpoint = $this->category_setting['defaultchargepoint'];
				$paytype = $this->category_setting['paytype'];
			}
			
			//检查是否支付过
			$allow_visitor = self::_check_payment($catid.'_'.$id,$paytype);
			if(!$allow_visitor) {
				$http_referer = urlencode(get_url());
				$allow_visitor = sys_auth($catid.'_'.$id.'|'.$readpoint.'|'.$paytype).'&http_referer='.$http_referer;
			} else {
				$allow_visitor = 1;
			}
		}
		//最顶级栏目ID
		$arrparentid = explode(',', $CAT['arrparentid']);
		$top_parentid = $arrparentid[1] ? $arrparentid[1] : $catid;
		
		$template = $template ? $template : $CAT['setting']['show_template'];
		if(!$template) $template = 'show';
		//SEO
		$seo_keywords = '';
		if(!empty($keywords)) $seo_keywords = implode(',',$keywords);
		$SEO = seo($siteid, $catid, $title, $description, $seo_keywords);
		
		define('STYLE',$CAT['setting']['template_list']);
		if(isset($rs['paginationtype'])) {
			$paginationtype = $rs['paginationtype'];
			$maxcharperpage = $rs['maxcharperpage'];
		}
		$pages = $titles = '';
		if($rs['paginationtype']==1) {
			//自动分页
			if($maxcharperpage < 10) $maxcharperpage = 500;
			$contentpage = pc_base::load_app_class('contentpage');
			$content = $contentpage->get_data($content,$maxcharperpage);
		}
		if($rs['paginationtype']!=0) {
			//手动分页
			$CONTENT_POS = strpos($content, '[page]');
			if($CONTENT_POS !== false) {
				$this->url = pc_base::load_app_class('url', 'content');
				$contents = array_filter(explode('[page]', $content));
				$pagenumber = count($contents);
				if (strpos($content, '[/page]')!==false && ($CONTENT_POS<7)) {
					$pagenumber--;
				}
				for($i=1; $i<=$pagenumber; $i++) {
					$pageurls[$i] = $this->url->show($id, $i, $catid, $rs['inputtime']);
				}
				$END_POS = strpos($content, '[/page]');
				if($END_POS !== false) {
					if($CONTENT_POS>7) {
						$content = '[page]'.$title.'[/page]'.$content;
					}
					if(preg_match_all("|\[page\](.*)\[/page\]|U", $content, $m, PREG_PATTERN_ORDER)) {
						foreach($m[1] as $k=>$v) {
							$p = $k+1;
							$titles[$p]['title'] = strip_tags($v);
							$titles[$p]['url'] = $pageurls[$p][0];
						}
					}
				}
				//当不存在 [/page]时，则使用下面分页
				$pages = content_pages($pagenumber,$page, $pageurls);
				//判断[page]出现的位置是否在第一位 
				if($CONTENT_POS<7) {
					$content = $contents[$page];
				} else {
					if ($page==1 && !empty($titles)) {
						$content = $title.'[/page]'.$contents[$page-1];
					} else {
						$content = $contents[$page-1];
					}
				}
				if($titles) {
					list($title, $content) = explode('[/page]', $content);
					$content = trim($content);
					if(strpos($content,'</p>')===0) {
						$content = '<p>'.$content;
					}
					if(stripos($content,'<p>')===0) {
						$content = $content.'</p>';
					}
				}
			}
		}
		$this->db->table_name = $tablename;
		//上一页
		$previous_page = $this->db->get_one("`catid` = '$catid' AND `id`<'$id' AND `status`=99",'*','id DESC');
		//下一页
		$next_page = $this->db->get_one("`catid`= '$catid' AND `id`>'$id' AND `status`=99");

		if(empty($previous_page)) {
			$previous_page = array('title'=>L('first_page'), 'thumb'=>IMG_PATH.'nopic_small.gif', 'url'=>'javascript:alert(\''.L('first_page').'\');');
		}

		if(empty($next_page)) {
			$next_page = array('title'=>L('last_page'), 'thumb'=>IMG_PATH.'nopic_small.gif', 'url'=>'javascript:alert(\''.L('last_page').'\');');
		}
		include template('content',$template);
	}
	//列表页
	public function lists() {
		$catid = $_GET['catid'] = intval($_GET['catid']);
		$_priv_data = $this->_category_priv($catid);
		if($_priv_data=='-1') {
			$forward = urlencode(get_url());
			showmessage(L('login_website'),APP_PATH.'index.php?m=member&c=index&a=login&forward='.$forward);
		} elseif($_priv_data=='-2') {
			showmessage(L('no_priv'));
		}
		$_userid = $this->_userid;
		$_username = $this->_username;
		$_groupid = $this->_groupid;

		if(!$catid) showmessage(L('category_not_exists'),'blank');
		$siteids = getcache('category_content','commons');
		$siteid = $siteids[$catid];
		$CATEGORYS = getcache('category_content_'.$siteid,'commons');
		if(!isset($CATEGORYS[$catid])) showmessage(L('category_not_exists'),'blank');
		$CAT = $CATEGORYS[$catid];
		$siteid = $GLOBALS['siteid'] = $CAT['siteid'];
		extract($CAT);
		$setting = string2array($setting);
		//SEO
		if(!$setting['meta_title']) $setting['meta_title'] = $catname;
		$SEO = seo($siteid, '',$setting['meta_title'],$setting['meta_description'],$setting['meta_keywords']);
		define('STYLE',$setting['template_list']);
		$page = intval($_GET['page']);

		$template = $setting['category_template'] ? $setting['category_template'] : 'category';
		$template_list = $setting['list_template'] ? $setting['list_template'] : 'list';
		
		if($type==0) {
			$template = $child ? $template : $template_list;
			$arrparentid = explode(',', $arrparentid);
			$top_parentid = $arrparentid[1] ? $arrparentid[1] : $catid;
			$array_child = array();
			$self_array = explode(',', $arrchildid);
			//获取一级栏目ids
			foreach ($self_array as $arr) {
				if($arr!=$catid && $CATEGORYS[$arr][parentid]==$catid) {
					$array_child[] = $arr;
				}
			}
			$arrchildid = implode(',', $array_child);
			//URL规则
			$urlrules = getcache('urlrules','commons');
			$urlrules = str_replace('|', '~',$urlrules[$category_ruleid]);
			$tmp_urls = explode('~',$urlrules);
			$tmp_urls = isset($tmp_urls[1]) ?  $tmp_urls[1] : $tmp_urls[0];
			preg_match_all('/{\$([a-z0-9_]+)}/i',$tmp_urls,$_urls);
			if(!empty($_urls[1])) {
				foreach($_urls[1] as $_v) {
					$GLOBALS['URL_ARRAY'][$_v] = $_GET[$_v];
				}
			}
			define('URLRULE', $urlrules);
			$GLOBALS['URL_ARRAY']['categorydir'] = $categorydir;
			$GLOBALS['URL_ARRAY']['catdir'] = $catdir;
			$GLOBALS['URL_ARRAY']['catid'] = $catid;
			include template('content',$template);
		} else {
		//单网页
			$this->page_db = pc_base::load_model('page_model');
			$r = $this->page_db->get_one(array('catid'=>$catid));
			if($r) extract($r);
			$template = $setting['page_template'] ? $setting['page_template'] : 'page';
			$arrchild_arr = $CATEGORYS[$parentid]['arrchildid'];
			if($arrchild_arr=='') $arrchild_arr = $CATEGORYS[$catid]['arrchildid'];
			$arrchild_arr = explode(',',$arrchild_arr);
			array_shift($arrchild_arr);
			$keywords = $keywords ? $keywords : $setting['meta_keywords'];
			$SEO = seo($siteid, 0, $title,$setting['meta_description'],$keywords);
			include template('content',$template);
		}
	}
	//搜索 项目
	public function searchpr(){

		
		$grouplist = getcache('grouplist','member');
		$_groupid = param::get_cookie('_groupid');
		if(!$_groupid) $_groupid = 8;
		if(!$grouplist[$_groupid]['allowsearch']) {
			if ($_groupid==8) showmessage(L('guest_not_allowsearch')); 
			else showmessage('');
		}
		if(!isset($_GET['catid'])) showmessage(L('missing_part_parameters'));
		$catid = intval($_GET['catid']);
		$siteids = getcache('category_content','commons');
		$siteid = $siteids[$catid];
		$this->categorys = getcache('category_content_'.$siteid,'commons');
		if(!isset($this->categorys[$catid])) showmessage(L('missing_part_parameters'));
		if(isset($_GET['info']['catid']) && $_GET['info']['catid']) {
			$catid = intval($_GET['info']['catid']);
		} else {
			$_GET['info']['catid'] = 0;
		}
		$modelid = $this->categorys[$catid]['modelid'];
		$modelid = intval($modelid);
		if(!$modelid) showmessage(L('illegal_parameters'));
		//搜索间隔
		$minrefreshtime = getcache('common','commons');
		$minrefreshtime = intval($minrefreshtime['minrefreshtime']);
		$minrefreshtime = $minrefreshtime ? $minrefreshtime : 5;
		if(param::get_cookie('search_cookie') && param::get_cookie('search_cookie')>SYS_TIME-2) {
			//showmessage(L('search_minrefreshtime',array('min'=>$minrefreshtime)),'index.php?m=content&c=index&a=searchpr&catid='.$catid,$minrefreshtime*1280);
		} else {
			param::set_cookie('search_cookie',SYS_TIME+2);
		}
		//搜索间隔
		
		$CATEGORYS = $this->categorys;
		//产生表单
		pc_base::load_sys_class('form','',0);
		$fields = getcache('model_field_'.$modelid,'model');
		$forminfos = array();
		foreach ($fields as $field=>$r) {
			if($r['issearch']) {
				if($r['formtype']=='catid') {
					$r['form'] = form::select_category('',$_GET['info']['catid'],'name="info[catid]"',L('please_select_category'),$modelid,0,1);
				} elseif($r['formtype']=='number') {
					$r['form'] = "<input type='text' name='{$field}_start' id='{$field}_start' value='' size=5 class='input-text'/> - <input type='text' name='{$field}_end' id='{$field}_start' value='' size=5 class='input-text'/>";
				} elseif($r['formtype']=='datetime') {
					$r['form'] = form::date("info[$field]");
				} elseif($r['formtype']=='box') {
					$options = explode("\n",$r['options']);
					foreach($options as $_k) {
						$v = explode("|",$_k);
						$option[$v[1]] = $v[0];
					}
					switch($r['boxtype']) {
						case 'radio':
							$string = form::radio($option,$value,"name='info[$field]' id='$field'");
						break;
			
						case 'checkbox':
							$string = form::radio($option,$value,"name='info[$field]' id='$field'");
						break;
			
						case 'select':
							$string = form::select($option,$value,"name='info[$field]' id='$field'");
						break;
			
						case 'multiple':
							$string = form::select($option,$value,"name='info[$field]' id='$field'");
						break;
					}
					$r['form'] = $string;
				} elseif($r['formtype']=='typeid') {
					$types = getcache('type_content','commons');
					$types_array = array(L('no_limit'));
					foreach ($types as $_k=>$_v) {
						if($modelid == $_v['modelid']) $types_array[$_k] = $_v['name'];
					}
					$r['form'] = form::select($types_array,0,"name='info[$field]' id='$field'");
				} elseif($r['formtype']=='linkage') {
					$setting = string2array($r['setting']);
					$value = $_GET['info'][$field];
					$r['form'] = menu_linkage($setting['linkageid'],$field,$value);
				} elseif(in_array($r['formtype'], array('text','keyword','textarea','editor','title','author','omnipotent'))) {
					$value = safe_replace($_GET['info'][$field]);
					$r['form'] = "<input type='text' name='info[$field]' id='$field' value='".$value."' class='input-text search-text'/>";
				} else {
					continue;
				}
				$forminfos[$field] = $r;
			}
		}
		//-----------
		if(isset($_GET['dosubmit'])) {
			$siteid = $this->categorys[$catid]['siteid'];
			$siteurl = siteurl($siteid);
			$this->db->set_model($modelid);
			$tablename = $this->db->table_name;
			//echo $tablename;
			$page = max(intval($_GET['page']), 1);
			$sql  = "SELECT * FROM `{$tablename}` a,`{$tablename}_data` b WHERE a.id=b.id AND a.status=99";
			$sql_count  = "SELECT COUNT(*) AS num FROM `{$tablename}` a,`{$tablename}_data` b WHERE a.id=b.id AND a.status=99";
			//构造搜索SQL
			$where = '';
		
			if($_GET['catid']!=9) $where .= " AND a.catid='$catid'";
			if ($_GET['price']==1) {
				$where .= " AND a.price<=50000";
			}
			if ($_GET['price']==2) {
				$where .= " AND a.price>=50000  AND a.price<=200000";
			}
			if ($_GET['price']==3) {
				$where .= " AND a.price>=200000  AND a.price<=500000";
			}
			if ($_GET['price']==4) {
				$where .= " AND a.price>=500000  ";
			}
			$keywor=$_GET['keyword'];
			if($_GET['city']) $where .= " AND a.city='$_GET[city]'";
				if($keywor) $where .= " AND (a.title LIKE '%$keywor%' or  a.keywords LIKE '%$keywor%'  or  a.description LIKE '%$keywor%')  ";
			
			$pagesize = 15;
			$offset = intval($pagesize*($page-1));
			$sql_count .= $where;
			$this->db->query($sql_count);
			$total = $this->db->fetch_array();
			$total = $total[0]['num'];
			if($total!=0) {
				$sql .= $where;
				$sql .= " LIMIT $offset,$pagesize";
				$this->db->query($sql);
				$datas = $this->db->fetch_array();
				
				$pages = pages($total, $page, $pagesize);
			} else {
				$datas = array();
				$pages = '';
			}
		}
include template('content','search_pr');
	} 
	//搜索资金
	public function searchzj(){

		
		$grouplist = getcache('grouplist','member');
		$_groupid = param::get_cookie('_groupid');
		if(!$_groupid) $_groupid = 8;
		if(!$grouplist[$_groupid]['allowsearch']) {
			if ($_groupid==8) showmessage(L('guest_not_allowsearch')); 
			else showmessage('');
		}
		if(!isset($_GET['catid'])) showmessage(L('missing_part_parameters'));
		$catid = intval($_GET['catid']);
		$siteids = getcache('category_content','commons');
		$siteid = $siteids[$catid];
		$this->categorys = getcache('category_content_'.$siteid,'commons');
		if(!isset($this->categorys[$catid])) showmessage(L('missing_part_parameters'));
		if(isset($_GET['info']['catid']) && $_GET['info']['catid']) {
			$catid = intval($_GET['info']['catid']);
		} else {
			$_GET['info']['catid'] = 0;
		}
		$modelid = $this->categorys[$catid]['modelid'];
		$modelid = intval($modelid);
		if(!$modelid) showmessage(L('illegal_parameters'));
		//搜索间隔
		$minrefreshtime = getcache('common','commons');
		$minrefreshtime = intval($minrefreshtime['minrefreshtime']);
		$minrefreshtime = $minrefreshtime ? $minrefreshtime : 5;
		if(param::get_cookie('search_cookie') && param::get_cookie('search_cookie')>SYS_TIME-2) {
			//showmessage(L('search_minrefreshtime',array('min'=>$minrefreshtime)),'index.php?m=content&c=index&a=searchpr&catid='.$catid,$minrefreshtime*1280);
		} else {
			param::set_cookie('search_cookie',SYS_TIME+2);
		}
		//搜索间隔
		
		$CATEGORYS = $this->categorys;
		//产生表单
		pc_base::load_sys_class('form','',0);
		$fields = getcache('model_field_'.$modelid,'model');
		$forminfos = array();
		foreach ($fields as $field=>$r) {
			if($r['issearch']) {
				if($r['formtype']=='catid') {
					$r['form'] = form::select_category('',$_GET['info']['catid'],'name="info[catid]"',L('please_select_category'),$modelid,0,1);
				} elseif($r['formtype']=='number') {
					$r['form'] = "<input type='text' name='{$field}_start' id='{$field}_start' value='' size=5 class='input-text'/> - <input type='text' name='{$field}_end' id='{$field}_start' value='' size=5 class='input-text'/>";
				} elseif($r['formtype']=='datetime') {
					$r['form'] = form::date("info[$field]");
				} elseif($r['formtype']=='box') {
					$options = explode("\n",$r['options']);
					foreach($options as $_k) {
						$v = explode("|",$_k);
						$option[$v[1]] = $v[0];
					}
					switch($r['boxtype']) {
						case 'radio':
							$string = form::radio($option,$value,"name='info[$field]' id='$field'");
						break;
			
						case 'checkbox':
							$string = form::radio($option,$value,"name='info[$field]' id='$field'");
						break;
			
						case 'select':
							$string = form::select($option,$value,"name='info[$field]' id='$field'");
						break;
			
						case 'multiple':
							$string = form::select($option,$value,"name='info[$field]' id='$field'");
						break;
					}
					$r['form'] = $string;
				} elseif($r['formtype']=='typeid') {
					$types = getcache('type_content','commons');
					$types_array = array(L('no_limit'));
					foreach ($types as $_k=>$_v) {
						if($modelid == $_v['modelid']) $types_array[$_k] = $_v['name'];
					}
					$r['form'] = form::select($types_array,0,"name='info[$field]' id='$field'");
				} elseif($r['formtype']=='linkage') {
					$setting = string2array($r['setting']);
					$value = $_GET['info'][$field];
					$r['form'] = menu_linkage($setting['linkageid'],$field,$value);
				} elseif(in_array($r['formtype'], array('text','keyword','textarea','editor','title','author','omnipotent'))) {
					$value = safe_replace($_GET['info'][$field]);
					$r['form'] = "<input type='text' name='info[$field]' id='$field' value='".$value."' class='input-text search-text'/>";
				} else {
					continue;
				}
				$forminfos[$field] = $r;
			}
		}
		//-----------
		if(isset($_GET['dosubmit'])) {
			$siteid = $this->categorys[$catid]['siteid'];
			$siteurl = siteurl($siteid);
			$this->db->set_model($modelid);
			$tablename = $this->db->table_name;
			//echo $tablename;
			$page = max(intval($_GET['page']), 1);
			$sql  = "SELECT * FROM `{$tablename}` a,`{$tablename}_data` b WHERE a.id=b.id AND a.status=99";
			$sql_count  = "SELECT COUNT(*) AS num FROM `{$tablename}` a,`{$tablename}_data` b WHERE a.id=b.id AND a.status=99";
			//构造搜索SQL
			$where = '';
		
			if($_GET['catid']!=16) $where .= " AND a.catid='$catid'";
			if ($_GET['price']==1) {
				$where .= " AND a.price<=50000";
			}
			if ($_GET['price']==2) {
				$where .= " AND a.price>=50000  AND a.price<=200000";
			}
			if ($_GET['price']==3) {
				$where .= " AND a.price>=200000  AND a.price<=500000";
			}
			if ($_GET['price']==4) {
				$where .= " AND a.price>=500000  ";
			}
			$keywor=$_GET['keyword'];
			if($_GET['city']) $where .= " AND a.city='$_GET[city]'";
				if($keywor) $where .= " AND (a.title LIKE '%$keywor%' or  a.keywords LIKE '%$keywor%'  or  b.info LIKE '%$keywor%')  ";
			
			$pagesize = 15;
			$offset = intval($pagesize*($page-1));
			$sql_count .= $where;
			$this->db->query($sql_count);
			$total = $this->db->fetch_array();
			$total = $total[0]['num'];
			if($total!=0) {
				$sql .= $where;
				$sql .= " LIMIT $offset,$pagesize";
				$this->db->query($sql);
				$datas = $this->db->fetch_array();
				
				$pages = pages($total, $page, $pagesize);
			} else {
				$datas = array();
				$pages = '';
			}
		}
include template('content','search_zj');
	} 
	//搜索服务
	public function searchfw(){

		
		$grouplist = getcache('grouplist','member');
		$_groupid = param::get_cookie('_groupid');
		if(!$_groupid) $_groupid = 8;
		if(!$grouplist[$_groupid]['allowsearch']) {
			if ($_groupid==8) showmessage(L('guest_not_allowsearch')); 
			else showmessage('');
		}
		if(!isset($_GET['catid'])) showmessage(L('missing_part_parameters'));
		$catid = intval($_GET['catid']);
		$siteids = getcache('category_content','commons');
		$siteid = $siteids[$catid];
		$this->categorys = getcache('category_content_'.$siteid,'commons');
		if(!isset($this->categorys[$catid])) showmessage(L('missing_part_parameters'));
		if(isset($_GET['info']['catid']) && $_GET['info']['catid']) {
			$catid = intval($_GET['info']['catid']);
		} else {
			$_GET['info']['catid'] = 0;
		}
		$modelid = $this->categorys[$catid]['modelid'];
		$modelid = intval($modelid);
		if(!$modelid) showmessage(L('illegal_parameters'));
		//搜索间隔
		$minrefreshtime = getcache('common','commons');
		$minrefreshtime = intval($minrefreshtime['minrefreshtime']);
		$minrefreshtime = $minrefreshtime ? $minrefreshtime : 5;
		if(param::get_cookie('search_cookie') && param::get_cookie('search_cookie')>SYS_TIME-2) {
			//showmessage(L('search_minrefreshtime',array('min'=>$minrefreshtime)),'index.php?m=content&c=index&a=searchpr&catid='.$catid,$minrefreshtime*1280);
		} else {
			param::set_cookie('search_cookie',SYS_TIME+2);
		}
		//搜索间隔
		
		$CATEGORYS = $this->categorys;
		//产生表单
		pc_base::load_sys_class('form','',0);
		$fields = getcache('model_field_'.$modelid,'model');
		$forminfos = array();
		foreach ($fields as $field=>$r) {
			if($r['issearch']) {
				if($r['formtype']=='catid') {
					$r['form'] = form::select_category('',$_GET['info']['catid'],'name="info[catid]"',L('please_select_category'),$modelid,0,1);
				} elseif($r['formtype']=='number') {
					$r['form'] = "<input type='text' name='{$field}_start' id='{$field}_start' value='' size=5 class='input-text'/> - <input type='text' name='{$field}_end' id='{$field}_start' value='' size=5 class='input-text'/>";
				} elseif($r['formtype']=='datetime') {
					$r['form'] = form::date("info[$field]");
				} elseif($r['formtype']=='box') {
					$options = explode("\n",$r['options']);
					foreach($options as $_k) {
						$v = explode("|",$_k);
						$option[$v[1]] = $v[0];
					}
					switch($r['boxtype']) {
						case 'radio':
							$string = form::radio($option,$value,"name='info[$field]' id='$field'");
						break;
			
						case 'checkbox':
							$string = form::radio($option,$value,"name='info[$field]' id='$field'");
						break;
			
						case 'select':
							$string = form::select($option,$value,"name='info[$field]' id='$field'");
						break;
			
						case 'multiple':
							$string = form::select($option,$value,"name='info[$field]' id='$field'");
						break;
					}
					$r['form'] = $string;
				} elseif($r['formtype']=='typeid') {
					$types = getcache('type_content','commons');
					$types_array = array(L('no_limit'));
					foreach ($types as $_k=>$_v) {
						if($modelid == $_v['modelid']) $types_array[$_k] = $_v['name'];
					}
					$r['form'] = form::select($types_array,0,"name='info[$field]' id='$field'");
				} elseif($r['formtype']=='linkage') {
					$setting = string2array($r['setting']);
					$value = $_GET['info'][$field];
					$r['form'] = menu_linkage($setting['linkageid'],$field,$value);
				} elseif(in_array($r['formtype'], array('text','keyword','textarea','editor','title','author','omnipotent'))) {
					$value = safe_replace($_GET['info'][$field]);
					$r['form'] = "<input type='text' name='info[$field]' id='$field' value='".$value."' class='input-text search-text'/>";
				} else {
					continue;
				}
				$forminfos[$field] = $r;
			}
		}
		//-----------
		if(isset($_GET['dosubmit'])) {
			$siteid = $this->categorys[$catid]['siteid'];
			$siteurl = siteurl($siteid);
			$this->db->set_model($modelid);
			$tablename = $this->db->table_name;
			//echo $tablename;
			$page = max(intval($_GET['page']), 1);
			$sql  = "SELECT * FROM `{$tablename}` a,`{$tablename}_data` b WHERE a.id=b.id AND a.status=99";
			$sql_count  = "SELECT COUNT(*) AS num FROM `{$tablename}` a,`{$tablename}_data` b WHERE a.id=b.id AND a.status=99";
			//构造搜索SQL
			$where = '';
		
			if($_GET['catid']) $where .= " AND a.catid='$catid'";
			if($_GET['servers']) $where .= " AND a.servers='$_GET[servers]'";
			$keywor=$_GET['keyword'];
			if($_GET['city']) $where .= " AND a.city='$_GET[city]'";
				if($keywor) $where .= " AND (a.title LIKE '%$keywor%' or  a.keywords LIKE '%$keywor%'  or  b.info LIKE '%$keywor%')  ";
			
			$pagesize = 15;
			$offset = intval($pagesize*($page-1));
			$sql_count .= $where;
			$this->db->query($sql_count);
			$total = $this->db->fetch_array();
			$total = $total[0]['num'];
			if($total!=0) {
				$sql .= $where;
				$sql .= " LIMIT $offset,$pagesize";
				$this->db->query($sql);
				$datas = $this->db->fetch_array();
				
				$pages = pages($total, $page, $pagesize);
			} else {
				$datas = array();
				$pages = '';
			}
		}
include template('content','search_fw');
	} 
	//JSON 输出
	public function json_list() {
		if($_GET['type']=='keyword' && $_GET['modelid'] && $_GET['keywords']) {
		//根据关键字搜索
			$modelid = intval($_GET['modelid']);
			$id = intval($_GET['id']);

			$MODEL = getcache('model','commons');
			if(isset($MODEL[$modelid])) {
				$keywords = safe_replace(new_html_special_chars($_GET['keywords']));
				$keywords = addslashes(iconv('utf-8','gbk',$keywords));
				$this->db->set_model($modelid);
				$result = $this->db->select("keywords LIKE '%$keywords%'",'id,title,url',10);
				if(!empty($result)) {
					$data = array();
					foreach($result as $rs) {
						if($rs['id']==$id) continue;
						if(CHARSET=='gbk') {
							foreach($rs as $key=>$r) {
								$rs[$key] = iconv('gbk','utf-8',$r);
							}
						}
						$data[] = $rs;
					}
					if(count($data)==0) exit('0');
					echo json_encode($data);
				} else {
					//没有数据
					exit('0');
				}
			}
		}

	}
	
	
	/**
	 * 检查支付状态
	 */
	protected function _check_payment($flag,$paytype) {
		$_userid = $this->_userid;
		$_username = $this->_username;
		if(!$_userid) return false;
		pc_base::load_app_class('spend','pay',0);
		$setting = $this->category_setting;
		$repeatchargedays = intval($setting['repeatchargedays']);
		if($repeatchargedays) {
			$fromtime = SYS_TIME - 86400 * $repeatchargedays;
			$r = spend::spend_time($_userid,$fromtime,$flag);
			if($r['id']) return true;
		}
		return false;
	}
	
	/**
	 * 检查阅读权限
	 *
	 */
	protected function _category_priv($catid) {
		$catid = intval($catid);
		if(!$catid) return '-2';
		$_groupid = $this->_groupid;
		$_groupid = intval($_groupid);
		if($_groupid==0) $_groupid = 8;
		$this->category_priv_db = pc_base::load_model('category_priv_model');
		$result = $this->category_priv_db->select(array('catid'=>$catid,'is_admin'=>0,'action'=>'visit'));
		if($result) {
			if(!$_groupid) return '-1';
			foreach($result as $r) {
				if($r['roleid'] == $_groupid) return '1';
			}
			return '-2';
		} else {
			return '1';
		}
	 }
}
?>