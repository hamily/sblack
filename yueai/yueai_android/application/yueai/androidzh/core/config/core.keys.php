<?php
/**
 * 应用中所用到的cache key 定义类
**/
defined('YUEAI') or exit('Access Denied！');

class Core_Keys {
	const PREFIX = 'YUEAI_';
	
	//用户个人信息key
	public static function getOneById( $mid){
		return self::PREFIX . $mid;
	}
	
	//存储用户mid
	public static function getOneBySitemid($sitemid,$sid){
		return self::PREFIX . $sitemid . $sid;
	}
	
}
?>