<?php
defined('YUEAI') or exit('Access Denied！'); 
/**
 * 此文件为android用户登录接口文件
 * @param api={"api":1,"sid":1,"deviceno":"25cc5eec303fb803370716900d3443cc","username":"user0","mac":"140c1f12feeb2c52dfbeb2da6066a73a","uid":0,"version":"1.
0.0","param":{"imei":"6fbbfd045ce44f13874ecb499d218455"},"sign":"04b29480233f4def5c875875b6bdc3b1","time":1426755868}
 * @return array()  sign = md5(api + sid + version + deviceno + time + secretkey)
**/
if($_GET['debug']==1){
	ini_set('display_errors',1);
	error_reporting(E_ALL ^ E_NOTICE);
}
$param = Lib_Mobile::decode($_REQUEST['api']);
if(empty($param)){
	
}
//auth验证
if(!Core_Member::factory()->httpauth($param)){
	$ret = array('code'=>'-1',
	             'msg' => Core_Error::getError(-1) );
    
	Lib_Mobile::jsonRet($ret, 0);
}
$api = intval( $param['api']);		//客户端标识
$sid = intval( $param['sid']);		//用户sid
$version = $param['version'];		//版本号

$aGuest = array();
$aGuest['deviceno'] = $param['deviceno'];
$aGuest['macid'] = $param['mac'];

$info = Mobile_Member::factory()->androidGusetLogin( $aGuest);
print_r($info);
?>