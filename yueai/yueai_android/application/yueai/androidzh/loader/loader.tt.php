<?php
defined('YUEAI') or exit( 'Access Denied！' );
/**
 * tt存儲對象操作處理類
 */
class Loader_Tt {
	//對象實例化保存數組
	protected static $_instance = array();
	
	/**
	 * 通用TT
	 */
	public static function tyrant() {
		if( !is_object( self::$_instance['tyrant'])) {
			self::$_instance['tyrant'] = new Lib_Tt( Core_Common::$tokyoTyrant);
		}
		return self::$_instance['tyrant'];
	}
}
?>