<?php
/**
 * 此文件为修改玩家相关资料
**/
defined('YUEAI') or exit('Access Denied！');
$userinfo = Logs::getSigInfo($_REQUEST['sigRequest']);
if(empty($userinfo)){
	Lib_Mobile::jsonRet( array("flag"=>0),0);
}

$fields = Lib_Mobile::decode($_REQUEST['fields']);
if(empty($fields)){
	Lib_Mobile::jsonRet( array("flag"=>0),0);
}
$ret = Core_Member::factory()->updateInfo($userinfo['mid'],$fields);
Lib_Mobile::jsonRet( $ret,1);
?>