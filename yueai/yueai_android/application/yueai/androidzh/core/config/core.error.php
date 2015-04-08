<?php
/**
 * 系统内错误提示定义
**/
defined('YUEAI') or exit('Access Denied！');

class Core_Error {
	
	private static $error = array(
						"-1"	=>	"参数格式错误",	//登录相关错误
						"-2"	=>	"签名验证失败",
						"-3"	=>	"用户注册失败",
						"-4"	=>	"用户已被封停",
						"-5"	=>	"图片类型不合法",	//上传相关错误
						"-6"	=>	"图片超过2M",
						"-7"	=>	"系统错误",
	);
	
	//统一调用返回错误信息
	public static function getError($id=0){
		return array_key_exists($id,self::$error) ? self::$error[$id] : self::$error;
	}

}
?>