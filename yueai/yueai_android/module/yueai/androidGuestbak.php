<?php
/**
 * 此PHP为安卓的游客登录
 * 前端传的数据格式 $_POST['api']= {"api":"112","deviceToken":"b35ae1cf5624f09e736dddc869b80262","deviceno":"354781043183299","macid":"0","mid":"0","mtkey":"009bf200de3a132b9b063022abb525d7","openudid":"0","param":{"nick":"zzp"},"sid":"202","sig":"6bc7a3454bc002f2fec056e4927f0998","time":"1341801183","version":"1.0.5"}
 * @param $api 112  安卓锄大地登陆  $api  114  安卓大老二登陆  115 安卓admob大老二
 * @param $sid 202  游客登陆
 */
!defined('BOYAA') AND exit('Access Denied!');
if($_REQUEST['debuged']==1){
	ini_set('display_errors',1);
	error_reporting(E_ALL);
}    
$param = Lib_Mobile::decode( $_REQUEST['api']);
    
if( Lib_Mobile::auth($param)== FALSE ) {
    $ret = array('code'=>'-1',
	             'msg' => Core_Error::$coreError[-1] );
    
	Lib_Mobile::jsonRet($ret, 0);
}
	
$api = (int)$param['api'];

$sid = (int)$param['sid'];

$nick = $param['param']['nick'];

$version = $param['version'];

$aGuest['deviceno'] = $param['deviceno']; 			//设备号

$aGuest['macid']    = $param['macid']; 				//MAC 地址

$aGuest['openudid'] = $param['openudid']; 			//第三方类库的udid

$appid =  $param['param']['appid'];			//渠道上报appid			


$appKey = $param['param']['appkey'];		//渠道上报key

$mobid = $param['param']['mobid'];			//渠道上报mobid

$imei = empty($param['imei']) ? $param['macid'] : $param['imei'];
/*
if(in_array($version,array("2.3.0","2.3.1","2.3.2"))){
	$aGuest['deviceno'] = !empty($param['imei']) ? md5($param['imei']) : $aGuest['deviceno'];
}
*/

$info = Mobile_Member::factory()->androidGusetLogin( $aGuest );    

if( !$info ) {
	$ret['code'] = '-3';
	$ret['msg']  = Core_Loginerror::$index[-3];
	
	Lib_Mobile::jsonRet( $ret, 0 );
}
		
$userInfo = Member::factory()->getOneBySitemid( $info['sitemid'], $sid );

if( !$userInfo ) {
	
	$userInfo['sid']     = $sid;
	
	$userInfo['sitemid'] = $info['sitemid'];
	
	$userInfo['mnick']   = $nick;
	
	$userInfo['micon']   = '';
	
	$userInfo['mbig']    = '';
	
	$userInfo['sex']     = 2;
	
	$userInfo = Member::factory()->insert( $userInfo,$api );
	
	if(!$userInfo){
		$ret['code'] = '-4';
		
		$ret['msg']  = Core_Loginerror::$index[-4];
		
		Lib_Mobile::jsonRet($ret, 0);
	}

	$userInfo['isFirst'] = 1;
	
	//用户注册上报
	DCDATA && @Data_Dcdata::factory($api)->sendNewLog('user_signup',array('uid'=>$userInfo['mid'],'platform_uid'=>$userInfo['sitemid'],'signup_at'=>time(),'ip'=>Helper::getip(),'entrance_id'=>$sid,'version_info'=>$version,'m_imei'=>$imei));

}
		
if( $userInfo['mstatus'] == 1 ){
	
	$ret['code']         = '-10';
	
	$ret['noAllowLogin'] = 1 ;
	
	$ret['msg']          =  Core_Loginerror::$forbidden;
 
	Lib_Mobile::jsonRet( $ret, 0 );
}

//用户登陆上报
DCDATA && @Data_Dcdata::factory($api)->sendNewLog('user_login',array('uid'=>$userInfo['mid'],'platform_uid'=>$userInfo['sitemid'],'login_at'=>time(),'ip'=>Helper::getip(),'entrance_id'=>$sid,'version_info'=>$version,'user_gamecoins'=>$userInfo['money'],'m_imei'=>$imei));


if(empty($param['imei'])){
	Logs::factory()->debugNew("imei",$param);
}
//渠道推广上报
if(DCHANNEL) {
	
	if($appid && $appKey && $mobid)	 {
		
		$aData = array('appid'=>$appid,'appkey'=>$appKey,'mobid'=>$mobid,'userid'=>$userInfo['mid'],'ip'=>Helper::getip(),'isFirst'=>$userInfo['isFirst'] ? 1 : 0);
		
		$sData = json_encode($aData);
		
		Data_Cache::redisChannel()->lPush(Data_Keys::$channelKey, $sData);
		
		Data_Cache::redisChannel()->set(Data_Keys::$channelPerKey.$userInfo['mid'],json_encode(array('appid'=>$appid,'appkey'=>$appKey,'mobid'=>$mobid)));			//保存用户登陆的appid和key
		
		Data_Cache::redisChannel()->setTimeout(Data_Keys::$channelPerKey.$userInfo['mid'],3600);
	}	
}
					
Mobile_Member::factory()->loginBid($userInfo['mid'], $api);
	
$userInfo['result']    = 1;

$userInfo['loginType'] = 3;

$userInfo['boyaaId']   = 0;
 
$ret = Core_Loadinit::factory()->loadinit( $userInfo, $api,$version );
 
/*升级按钮开放与否*/
$ret['isopen'] = 0;
 
Lib_Mobile::jsonRet( $ret );
	