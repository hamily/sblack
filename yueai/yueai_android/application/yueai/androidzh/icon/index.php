<?php 
defined('YUEAI') or exit( 'Access Denied！');
$userinfo = Logs::getSigInfo($_REQUEST['sigRequest']);

if(empty($userinfo['mid'])){
	Lib_Mobile::jsonRet( array("flag"=>0),1);
}

$ret = icon_upload::factory()->upload( $userinfo['mid'],$_FILES);
Lib_Mobile::jsonRet( $ret,1);
?>