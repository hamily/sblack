<?php
/**
 * 此文件为处理用户相关功能
**/
defined('YUEAI') or exit('Access Denied！');
class Core_Member {
	
	protected static $_instance = array();
	
	//单实便对象
	public static function factory(){
		if(!isset(self::$_instance['coremember']) && !is_object(self::$_instance['coremember'])){
			self::$_instance['coremember'] = new Core_Member();
		}
		return self::$_instance['coremember'];
	}
	
	//http auth验证
	public function httpauth($param){
		
		return true;
	}
	
}

?>