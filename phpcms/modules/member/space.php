<?php
/**
* 会员空间 space.php
* 传递个人主页主人的参数三种方法: index.php?m=member&c=space&uid=1 注意此链接可以自行构造,就是说点击进入个人空间时候的链接地址,需要你自己写.
* uid=1 传递用户id;
* name=tuzwu 传递用户名;
* nickname=化蝶自在飞 传递用户昵称;
*/
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_class('foreground');
class space extends foreground {
    private $times_db;
    function __construct() {
        parent::__construct();
    }
    public function init(){
        $_username = param::get_cookie('_username');//当前登录人用户名
        $_userid = param::get_cookie('_userid');//当然登录人id
        $_nickname = param::get_cookie('_nickname');//当然登录人昵称
        $cuserid = $_GET['uid']?intval($_GET['uid']):$_userid;//当前查看的个人空间会员id,在模板里get的时候应该使用该变量,如 index.php?m=member&c=space&uid=1 这里的 uid=1 .你给会员做链接的时候也应该用会员的id;
        $cusername = $_GET['name']?addslashes(trim($_GET['name'])):$_username;//当前查看的个人空间会员用户名
        $cnickname = $_GET['nickname']?addslashes(trim($_GET['nickname'])):$_nickname;//空间主人的昵称
        include template('member', 'space');
    }

}
?>