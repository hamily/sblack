<?php
defined('YUEAI') or exit('Access Denied!');
if($_GET['debug']==1){
	ini_set('display_errors',1);
	error_reporting(E_ALL);
}
//ijBTM3pvJ3rIhuen1uTasD7PhitBOGNb_o8949aoPNGEsWjScWTisCa-zEpbok9jKtiGO3ih5FxbIwJhfqz3xw==
$cmd = $_GET['cmd'];
$mid = $_GET['mid'];
switch($cmd){
	case "userinfo":
		$info = Member::factory()->getOneById($mid);
		print_R($info);
		break;
	case "test":
		echo "hello world";
		break;
	case "redis":
		$cachekey = 'sblacktest';
		var_dump(Loader_Redis::Redis()->set($cachekey,1,false,false,300));
		echo Loader_Redis::Redis()->get($cachekey,false,false);
		print_r(Loader_Redis::Redis()->info());
		break;
	case "memcache":
		Loader_Memcached::cache()->set('sb',2,600);
		echo Loader_Memcached::cache()->get('sb');
		break;
	default:
		exit('you are son of bitch');
}

?>
