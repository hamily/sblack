<?php
defined('YUEAI') or exit( 'Access Denied！');
/**
 * 通用配置文件 以下信息全部為暫設置使用
 */
class Core_Common {
	//主數據庫配置
	public static $dbmaster = array(array('10.171.194.32:8080', 'root', 'together123abc', 'yueai'));
	
	//日誌數據庫 未用
	public static $dbslave = array(array('10.171.194.32:8080', 'root', 'together123abc', 'yueai_log'));
	
	//memcache配置
	public static $memcache = array(array(array('10.171.194.32', '11211', 100)));	
	
	//用戶信息分存存儲memcache
	public static $memcacheMinfo = array(
										0=>array(array(array('10.171.194.32', '11211', 100))),
										1=>array(array(array('10.171.194.32', '11211', 100))),
										2=>array(array(array('10.171.194.32', '11211', 100))),
										);
	//用户额外信息存储
	public static $stomemcache = array(array(array('10.171.194.32', '11211', 100)));
	
	//好友信息存储
	public static $friendmemcache = array(array(array('10.171.194.32', '11211', 100)));
	
	//redis配置
	public static $redis = array('10.171.194.32', '4501');
	
	//bid映射
	public static $redisbid = array('10.171.194.32', '4501');
	
	//tt配置
	public static $tokyoTyrant = array('10.171.194.32', '4501');
	
	//任务redis和个人档
	public static $taskredis = array('10.171.194.32', '4501');
	
}
?>
