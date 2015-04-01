<?php
/**
 * 系统内错误提示定义
**/
defined('YUEAI') or exit('Access Denied！');

class Core_Error {
	
	private static $error = array(
						"-1"	=>	"签名验证失败",
						"-2"	=>	"签名验证失败",
						"-3"	=>	"签名验证失败",
	);
	
	//统一调用返回错误信息
	public static function getError($id=0){
		return array_key_exists($id,self::$error) ? self::$error[$id] : self::$error;
	}

}
?>