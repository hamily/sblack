<?php
defined('YUEAI') or exit( 'Access Denied！' );
/**
 * redis對象操作處理類
 */
class Loader_Redis {
	//對象存儲數組
	protected static $_instance = array();
	
	/**
	 * 通用redis
	 * @return Lib_Redis
	 */
	public static function redis() {
		if( !is_object( self::$_instance['redis'])) {
			self::$_instance['redis'] = new Lib_Redis( Core_Common::$redis);
		}
		return self::$_instance['redis'];
	}
	/**
	 * redis映射
	 */
	public static function redisbid(){
		if(!is_object(self::$_instance['redisbid'])){
			self::$_instance['redisbid'] = new Lib_Redis( Core_Common::$redisbid);
		}
		return self::$_instance['redisbid'];
	}
	/**
	 * 个人档和任务redis
	 * @return Lib_Redis
	 */
	public static function redistask(){
		if(!is_object(self::$_instance['redistask'])){
			self::$_instance['redistask'] = new Lib_Redis( Core_Common::$taskredis);
		}
		return self::$_instance['redistask'];
	}
}
?>