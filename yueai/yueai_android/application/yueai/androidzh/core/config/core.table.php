<?php
defined('YUEAI') or exit('Access Denied！');

class Core_Table {
	
	//用户资料表
	public $memberinfo = 'yueai.memberinfo';
	
	//用户设备表
	public $guestandroid = 'yueai.memberdevice';
	
	//sitemid生成表
	public $membersitemid = 'yueai.membersitemid';
	
	//iphone设备表
	public $guestios = 'yueai.memberiphone';
	
	//用户登录log表
	public $loginlog = 'yueai_log.loginlog';
	
	//用户在线表
	public $membertable = 'yueai.membertable';
}

?>