<?php
/**
 * 此类系统内相关配置
**/
defined('YUEAI') or exit('Access Denied！');

class Core_System {
	//站点url
	public static $appUrl  = 'http://121.40.118.22/';
	
	//httpmd5加密key
	public static $httpauth = 'yueaitogether';
	
	//sigrequest加密key
	public static $encryptkey = 'yueai3sf352s';
	
	//gz时间
	public static $gztime = '2015-05-06';
	
	public static $gzfile = 'http://121.40.118.22/yueai_android/application/yueai/androidzh/xml/system.xml';
}
?>