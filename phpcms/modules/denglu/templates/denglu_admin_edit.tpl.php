<?php 
defined('IN_ADMIN') or exit('No permission resources.');

include $this->admin_tpl('header', 'admin');

$arr_cache[] = show_onoff('setting[denglu_top]',$denglu_cache['denglu_top'],L('denglu_top'),L('denglu_top_comment'));
$arr_cache[] = show_onoff('setting[denglu_force_bind]',$denglu_cache['denglu_force_bind'],L('denglu_force_bind'),L('denglu_force_bind_comment'));
$arr_cache[] = show_onoff('setting[denglu_login_syn]',$denglu_cache['denglu_login_syn'],L('denglu_login_syn'),L('denglu_login_syn_comment'));

$arr_cache[] = show_input('setting[denglu_appid]',$denglu_cache['denglu_appid'],L('denglu_appid'),L('denglu_appid_comment'));
$arr_cache[] = show_input('setting[denglu_appkey]',$denglu_cache['denglu_appkey'],'APPKEY',L('denglu_appkey_comment'));
?>

<div class="content-menu ib-a blue line-x">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="index.php?m=denglu&c=denglu_admin&a=media" ><em><?php echo L('denglu_mediainfo')?></em></a>
<a class="on" href="javascript:;"><em><?php echo L('denglu_setting')?></em></a>    </div>


<form method="post" action="?m=denglu&c=denglu_admin&a=edit" id="myform">
<table class="table_form" width="100%" cellspacing="0">
<tbody>
<?php 
foreach ($arr_cache as $v){
	echo $v;
}
?>
</tbody>
</table>
<div class="btn">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" value="<?php  echo L('save')?>" name="dosubmit" class="button"></div>
<?php
function show_onoff($name,$value,$title,$comment){
	$y = L('yes');$n = L('no');
	$yes = $no = '';
	if($value){
		$yes = 'checked';
	}else{
		$no = 'checked';
	}
	return <<<EOT
	  <tr>
      <th width="200" style="text-align:left"><strong>{$title}</strong></th>
      <td>
	  <input type='radio' name='{$name}'  value='1' {$yes}> {$y}&nbsp;&nbsp;&nbsp;&nbsp;
	  <input type='radio' name='{$name}' value='0'  {$no}> {$n}&nbsp;&nbsp;&nbsp;&nbsp;{$comment}
	 </td>
	 </tr>
EOT;
}
function show_input($name,$value,$title,$comment){
	return <<<EOT
	<tr>
      <th style="text-align:left"><strong>{$title}</strong></th>
      <td><input type="text" maxlength="50" size="40" value="{$value}"  name="{$name}" class="input_blur">&nbsp;&nbsp;&nbsp;&nbsp;{$comment}</td>
    </tr>
EOT;
}


?></form>
</body>

</html>