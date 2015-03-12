<?php 
defined('YUEAI') or exit( 'Access Denied！' );
/**
 * DB對象處理類
 */
class Loader_Mysql
{
    protected static $_instance = array();
    
    /**
     * 主庫DB對象類實例化
     */
    public static function dbmaster() {
    	if( !is_object( self::$_instance['dbmaster'])){
    		self::$_instance['dbmaster'] = new Lib_Mysql( Core_Common::$dbmaster);
    	}
    	return self::$_instance['dbmaster'];
    }
    
    /**
     * 日誌庫DB對象實例化
     */
    public static function dbslave() {
    	if( !is_object( self::$_instance['dbslave'])){
    		self::$_instance['dbslave'] = new Lib_Mysql( Core_Common::$dbslave);
    	}
    	return self::$_instance['dbslave'];
    }
    
    /**
     * 
     * 商城数据库
     * @return Lib_Mysql
     */
    public static function dbshop(){
    	if( !is_object( self::$_instance['dbshop'])){
    		self::$_instance['dbshop'] = new Lib_Mysql( Core_Common::$dbshop);
    	}
    	return self::$_instance['dbshop'];
    }
    
    /**
     * 
     * 簽到數據庫
     * @return Lib_Mysql
     */
    public static function dbactivity(){
    	if ( !is_object(self::$_instance['activity']) ) {
    		self::$_instance['activity'] = new Lib_Mysql( Core_Common::$dbactivity);
    	}
    	return self::$_instance['activity'];
    }
	/**
	 * 
	 * 关闭所有连接
	 * @return Lib_Mysql
	 */
	public static function close(){ 
		foreach ((array)self::$_instance as $db){ 
			$db->close();
		}
	}
}
?>