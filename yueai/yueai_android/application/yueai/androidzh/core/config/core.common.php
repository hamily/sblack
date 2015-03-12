<?php
defined('BOYAA') or exit( 'Access Denied！');
/**
 * 四人鬥地主通用配置文件 以下信息全部為暫設置使用
 */
class Core_Common {
	//主數據庫配置
	public static $dbmaster = array(array('10.66.46.156:3388', 'kingslave', 'umsDccgarwsAlr7rc6o', 'kslave'));
	
	//日誌數據庫 未用

	public static $dbslave = array(array('10.66.46.156:3388', 'kingslave', 'umsDccgarwsAlr7rc6o', 'kslave_log'));
	//商城数据库
	public static $dbshop = array(array('10.66.46.156:3388', 'kingslave', 'umsDccgarwsAlr7rc6o', 'kslave_shop'));
	
	public static $dbactivity = array(array('10.66.46.156:3388', 'kingslave', 'umsDccgarwsAlr7rc6o', 'kslave_activity'));
	
	//memcache配置
	public static $memcache = array(array(array('10.66.46.174', '11211', 100)));	
	
	//用戶信息分存存儲memcache
	public static $memcacheMinfo = array(
										0=>array(array(array('10.66.46.174', '11212', 100))),
										1=>array(array(array('10.66.46.174', '11213', 100))),
										2=>array(array(array('10.66.46.174', '11214', 100))),
										);
	//用户额外信息存储
	public static $stomemcache = array(array(array('10.66.46.174', '11215', 100)));
	
	//好友信息存储
	public static $friendmemcache = array(array(array('10.66.46.174', '11215', 100)));
	
	//redis配置
	public static $redis = array('10.66.46.174', '4502');
	
	//bid映射
	public static $redisbid = array('10.66.46.174', '4501');
	
	//tt配置
	public static $tokyoTyrant = array('10.66.46.174', '4401');
	
	//任务redis和个人档
	public static $taskredis = array('10.66.46.174', '4503');
	
}
?>
