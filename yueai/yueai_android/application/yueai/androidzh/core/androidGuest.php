<?php
defined('YUEAI') or exit('Access Denied！'); 
/**
 * 此文件为android用户登录接口文件
 * @param api={}
 * @return array()
**/

$param = json_decode(trim($_POST['api']),true);
if(empty($param)){
	
}
//auth验证
if(!Core_Member::factory()->httpauth($param)){
	
}
$api = $param['api'];

?>