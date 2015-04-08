<?php
/**
 * 此模块为用户VIP处理模块wq	
**/
defined('YUEAI') or exit('Access Denied！');

class Vip_Vip extends Core_Table {
	protected static $_instance = array();
	
	//单实例生成器
	public static function factory(){
		if(!isset(self::$_instance['vip']) && !is_object(self::$_instance['vip'])){
			self::$_instance['vip'] = new Vip_Vip();
		}
		return self::$_instance['vip'];
	}
	
}
?>