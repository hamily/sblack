<?php
defined('YUEAI') or exit('Access Denied！');
/**
 * APP中相关日志类处理函数
**/
class Logs {
	protected static $_instance = array();
	protected static $encryptkey = "encryptkey";
	/**
	 * 日志记录
	 * @access public
	 * @param $prefix 日志前缀，可以用来区分不能功能的日志文件
	 * @param $filetype .txt .php
	 */
	public static function debug($prefix='') { 
		clearstatcache();
		$file = APP_LOG_PATH  . ($prefix == "" ? "" : $prefix . "_") . date("Y-m-d") . ".txt";
	    $arr = debug_backtrace();
	    $desc = "[" . date("Y-m-d H:i:s") . "] " . "\r\n";
	    for($i=0,$count=count($arr);$i<$count;$i++) {
	    	$desc .= $arr[$i]['file'] . ":" . $arr[$i]['line'] . "\r\n";
	    }
	    $desc .= print_r(func_get_args(), true);
	    $desc .= "\r\n\r\n";
	    $size = @filesize( $file); 
		$contents = ($size ? '' : "<?php die();?>\n") . $desc . "\n";
		@file_put_contents($file, $contents, $size<64*1024 ? FILE_APPEND : null);
	}
	/**
	 * 3DES加密
	 *
	 * @param unknown_type $string
	 * @return unknown add by wyj 2014-06-16
	 */
	public static function encrypt($string){	
		$iv_size = mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size,MCRYPT_RAND);
		$crypttext = mcrypt_encrypt(MCRYPT_3DES, self::$encryptkey, $string, MCRYPT_MODE_ECB, $iv);
	    return str_replace(array('+',"/"),array('-','_'),base64_encode($crypttext));
	}
	/**
	 * 3DES解密
	 *
	 * @param unknown_type $string
	 * @return unknown add by wyj 2014-06-16
	 */
	public static function decrypt($string){
		$string = base64_decode(str_replace(array('-',"_"),array('+','/'),$string));
		$iv_size = mcrypt_get_iv_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
	    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	    $decrypttext = mcrypt_decrypt(MCRYPT_3DES, self::$encryptkey, $string, MCRYPT_MODE_ECB, $iv);
	    return trim($decrypttext);
	}
	/**
	 * 解析加密串
	 * @param string $sig
	 * @return array
	 */
	public static function getSigInfo($sig){
		if(empty($sig)){
			return false;
		}
		$jsonstr = self::decrypt($sig);
		return json_decode( $jsonstr, true);
	}
	/**
	 * 记录和统计时间（微秒）和内存使用情况
	 * 使用方法:
	 * <code>
	 * G('begin'); // 记录开始标记位
	 * // ... 区间运行代码
	 * G('end'); // 记录结束标签位
	 * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
	 * echo G('begin','end','m'); // 统计区间内存使用情况
	 * 如果end标记位没有定义，则会自动以当前作为标记位
	 * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
	 * </code>
	 * @param string $start 开始标签
	 * @param string $end 结束标签
	 * @param integer|string $dec 小数位或者m
	 * @return mixed
	 */
	function Gtime($start,$end='',$dec=4) {
		static $_info       =   array();
		static $_mem        =   array();
		if(is_float($end)) { // 记录时间
			$_info[$start]  =   $end;
		}elseif(!empty($end)){ // 统计时间和内存使用
			if(!isset($_info[$end])) $_info[$end]       =  microtime(TRUE);
			if(MEMORY_LIMIT_ON && $dec=='m'){
				if(!isset($_mem[$end])) $_mem[$end]     =  memory_get_usage();
				return number_format(($_mem[$end]-$_mem[$start])/1024);
			}else{
				return number_format(($_info[$end]-$_info[$start]),$dec);
			}

		}else{ // 记录时间和内存使用
			$_info[$start]  =  version_compare(PHP_VERSION,"5.4.0",">=") ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(TRUE);
			if(MEMORY_LIMIT_ON) $_mem[$start]           =  memory_get_usage();
		}
		return null;
	}
}


?>