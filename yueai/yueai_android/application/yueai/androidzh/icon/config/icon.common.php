<?php 
defined('YUEAI') or exit( 'Access Denied！');

class Icon_Common{
	
    /**
     *返回定义的用户图像地址
     *@return string $iconPath
     */
	public static function iconPath(){
		
		return $iconPath = ROOTPATH.'usericon/';
	}

    /**
     *返回用户图像地址的域名
     *@return string $icondomain
     */
	public static function iconDomain(){
		
		return $iconDomain = 'https://kingslave.boyaagame.com/';
	}
	
	/**
	 *修改图像允许 上传类型
	 *@return array $allowType
	 */
	public static function allowType(){
		
		return $allowType = array('jpg','jpeg','gif','png');
	}
	
}//end-class