<?php 
defined('IN_ADMIN') or exit('No permission resources.');

include $this->admin_tpl('header', 'admin');

?>

<div class="content-menu ib-a blue line-x">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:;" class="on"><em><?php echo L('denglu_mediainfo')?></em></a>
<a  href="index.php?m=denglu&c=denglu_admin&a=edit"><em><?php echo L('denglu_setting')?></em></a>    </div>

<form method="post" action="?m=denglu&c=denglu_admin&a=media" id="myform">
<table class="table_form" width="100%" cellspacing="0" >
<tbody>

<tr>
<th width='10%' style='text-align:center;'><strong><?php echo L('media_icon')?></strong></th>
    <th width='20%' style='text-align:center'><?php echo L('media_name')?></strong></th> 
    <th width='50' style='text-align:center'><strong>APPID</strong></th>
  </tr>

<? foreach($denglu_data as $v){ 
	print <<<EOT
<tr class="">
<th style='text-align:center;'><img src="phpcms/modules/denglu/images/denglu_second_icon_{$v['mediaID']}.png" ></th>
<th style='text-align:center;'> {$v['mediaName']}</th>
<th style='text-align:center;'> {$v['apiKey']}</th>
</tr>

EOT;
}
?>

</tbody>
</table>

<div class="btn">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" value="<?php  echo L('reload')?>" name="dosubmit" class="button"></div>
</form>
</body>


</html>