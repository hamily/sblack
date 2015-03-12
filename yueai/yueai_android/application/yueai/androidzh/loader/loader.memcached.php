<?php  
defined('YUEAI') or exit( 'Access Denied！' );

class Loader_Memcached
{
    protected static $_instance = array();
    
    
    /**
     * 通用memcache
     */
    public static function cache(){
    	if(!is_object(self::$_instance['cache'])){
    		self::$_instance['cache'] = new Lib_Memcached(Core_Common::$memcache);
    	}
    	return self::$_instance['cache'];
    }
    /**
     * 用户额外信息存储
     *     
     * @return Lib_Memcached     
     */
    public static function stocache()
    {
        if(!is_object(self::$_instance['stocache']))
        {
            self::$_instance['stocache'] = new Lib_Memcached(Core_Common::$stomemcache);
        }
        
        return self::$_instance['stocache'];
    }
    
    /**
     * 用户信息散列
     *
     * @param {int} $mid 用户ID
     *
     * @return object Lib_Memcached
     */
    public static function minfo($mid)
    {
        $hash = isset(Core_Common::$memcacheMinfo[2]) ? intval($mid%3) : 0;
        
        if(!is_object(self::$_instance['minfo'][$hash]))
        {
            self::$_instance['minfo'][$hash] = is_array(Core_Common::$memcacheMinfo) ? new Lib_Memcached(isset(Core_Common::$memcacheMinfo[2]) ? Core_Common::$memcacheMinfo[$hash] : Core_Common::$memcacheMinfo) : self::cache();
        }
        
        return self::$_instance['minfo'][$hash];
    }    
    /**
     * 好友信息存储CACHE
     * @return Lib_Memcached
     */
    public static function friend(){
    	if(!is_object(self::$_instance['friend'])){
    		self::$_instance['friend'] = new Lib_Memcached(Core_Common::$friendmemcache);
    	}
    	return self::$_instance['friend'];
    }
    
    /**
     * 消息中心CACHE
     * @return Lib_Memcached
     */
    public static function message( ){
    	if(!is_object(self::$_instance['message'])){
    		self::$_instance['message'] = new Lib_Memcached(Messagecenter_Cache::$messagecache);
    	}
    	return self::$_instance['message'];
    }
}