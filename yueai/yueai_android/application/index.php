<?php 
/**
 * 首页加载程式
**/
define('YUEAI', TRUE);
require_once('config.yueai.php');

header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache"); 
error_reporting(E_ALL ^ E_NOTICE);

ob_start('ob_gzhandler');
date_default_timezone_set(DEFAULT_TIMEZONE); //设置时区

// 记录开始运行时间
$GLOBALS['_beginTime'] = version_compare(PHP_VERSION,"5.4.0",">=") ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(TRUE);
// 记录内存初始使用
define('MEMORY_LIMIT_ON',function_exists('memory_get_usage'));
if(MEMORY_LIMIT_ON) $GLOBALS['_startUseMems'] = memory_get_usage();
 
define('ROOTPATH', substr(dirname(__FILE__), 0, -11) );
define('SYSPATH',  ROOTPATH . 'system/');
define('APPPATH',  ROOTPATH . 'application/');

define('SYS_LIB_PATH', SYSPATH  . 'lib/');
define('SYS_HEP_PATH', SYSPATH  . 'helper/');
define('SYS_API_PATH', SYSPATH  . 'api/{module}/');

define('APP_COM_PATH',  ROOTPATH . 'module/' . GAMENAME . '/');
define('APP_GAME_PATH', APPPATH  . GAMENAME  . '/' . PLATFORM . '/');
define('APP_LOG_PATH',  APPPATH  . GAMENAME  . '/' . PLATFORM . '/logs/');
define('APP_VER_PATH',  APPPATH  . GAMENAME  . '/' . PLATFORM . '/versions/');
define('APP_LOD_PATH',  APPPATH  . GAMENAME  . '/' . PLATFORM . '/loader/');

define('APP_CFG_PATH',  APPPATH  . GAMENAME  . '/' . PLATFORM . '/{module}/config/');
define('APP_MOD_PATH',  APPPATH  . GAMENAME  . '/' . PLATFORM . '/{module}/module/');

require_once(SYSPATH.'lib/lib.yueai.php');

Yueai::init();

$mod  = $_REQUEST['m'] ? $_REQUEST['m'] : 'core';

$page = $_REQUEST['p'] ? $_REQUEST['p'] : 'index';

if (isset($_SERVER['_']) && substr($_SERVER['_'],strrpos($_SERVER['_'],"/")) == '/php') {
	//crontab 脚本模块
	$mod = $_SERVER['argv']['1'] ? $_SERVER['argv']['1'] : 'core' ;
	$page = $_SERVER['argv']['2'] ? $_SERVER['argv']['2'] : 'index';
	$file =  GAMENAME .'/' . PLATFORM . "/{$mod}/{$page}.php";
	require_once($file);exit;
}

$file =  GAMENAME .'/' . PLATFORM . "/{$mod}/{$page}.php";

if(!is_file($file))
{
    exit('file is not exists...');
}


require_once($file);