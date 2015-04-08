<?php
defined('YUEAI') or exit('Access Denied！'); 
/**
 * 此文件为android用户登录接口文件
 * @author wyj 2015-04-04
 * @param api={"api":1,"sid":1,"deviceno":"25cc5eec303fb803370716900d3443cc","username":"user0","mac":"140c1f12feeb2c52dfbeb2da6066a73a","uid":0,"version":"1.
0.0","param":{"imei":"6fbbfd045ce44f13874ecb499d218455"},"sign":"04b29480233f4def5c875875b6bdc3b1","time":1426755868}
 * @return array()  sign = md5(api + sid + version + deviceno + time + secretkey)
**/
if($_GET['debug']==1){
	ini_set('display_errors',1);
	error_reporting(E_ALL);
}
if($_REQUEST['yueai']==1){
	$_POST['api'] = '{"api":1,"sid":1,"deviceno":"25cc5eec303fb803370716900d3443cc","username":"user0","mac":"140c1f12feeb2c52dfbeb2da6066a73a","uid":0,"version":"1.0.0","param":{"imei":"6fbbfd045ce44f13874ecb499d218455"},"sign":"04b29480233f4def5c875875b6bdc3b1","time":1426755868}';
}
$param = Lib_Mobile::decode($_POST['api']);
if(empty($param)){
	$ret = array(
			'code'	=>	'-1',
			'msg'	=>	Core_Error::getError(-1)
	);
	Lib_Mobile::jsonRet($ret,0);
}
//auth验证
if(!Core_Member::factory()->httpauth($param)){
	$ret = array('code'=>'-2',
	             'msg' => Core_Error::getError(-2) );
    
	Lib_Mobile::jsonRet($ret, 0);
}
$api = intval( $param['api']);		//客户端标识
$sid = intval( $param['sid']);		//用户sid
$version = $param['version'];		//版本号

//android设备号注册
$aGuest = array();
$aGuest['deviceno'] = $param['deviceno'];
$aGuest['macid'] = $param['mac'];
$info = Mobile_Member::factory()->androidGusetLogin( $aGuest);
if( !$info ) {
	$ret['code'] = '-3';
	$ret['msg']  = Core_Error::getError(-3);
	Lib_Mobile::jsonRet( $ret, 0 );
}

$sitemid = $info['sitemid'];
$userinfo = Member::factory()->getOneBySiteMid($sitemid,$sid);
if(!$userinfo){	//走注册流程
	$info = array();
	$info['sitemid'] = $sitemid;
	$info['sid'] = $sid;
	$info['mnick'] = $param['username'] ? $param['username'] : "Yueai" . $sitemid;
	$info['sitemid'] = $sitemid;
	$info['gender'] = 2;
	$userinfo = Member::factory()->insert( $info);

	$userinfo['isRegister'] = 1;
	$userinfo['FirstLogin'] = 1;
} else {
	//更新登录信息
	if(empty($userinfo['mnick'])){
		$userinfo['mnick'] = $param['username'];
	}
	Member::factory()->updateLogin( $userinfo);
	$userinfo['isRegister'] = 0;
	
}
//判断用户状态
if($userinfo['status']==1){
	$ret = array('code'=>'-4',
	             'msg' => Core_Error::getError(-4) );
    
	Lib_Mobile::jsonRet($ret, 0);
}
//格式化userinfo
Core_Member::factory()->formatUserInfo( $userinfo);

//初始化一些设置
$ret = Core_Member::factory()->loadinit( $userinfo, $api,$version );
Lib_Mobile::jsonRet( $ret,1);
?>