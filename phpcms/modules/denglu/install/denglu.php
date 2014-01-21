<?php
$token = empty($_GET['token']) ? '&' : '&token='.$_GET['token'];
header('location:index.php?m=denglu&c=index&a=init'.$token);

?>