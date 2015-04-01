<?php 
defined('YUEAI') or exit('Access Denied!');

/**
 * 移动端专用
 *
*/

class Lib_Mobile{

    /**
	 *ios客户端传过来的参数处理
	 *
	 * @param  json格式  $post 客户端参数 $_POST['api']
	 */
	 public static function decode( $post )
	 {
		if( get_magic_quotes_gpc() )
		{
			$post = stripslashes( $post );
		}
		if( ! isset( $post ))
		{
		    $ret = array('code'=>'-1',
			             'msg' =>'require arguments.');
			
			self::jsonRet($ret, 0);
		}
		if( ! $param = json_decode( $post, true ))
		{

			if(!($s = file_get_contents("php://input"))) {		//另外一种途径获取post信息（没有值的话）
					
				$ret = array('code'=>'-2','msg' =>'bad format arguments.');
					
				self::jsonRet($ret, 0);
			
			} else {
			
				$s = preg_replace('/p=facebook&/','',$s);
			
				$s = preg_replace('/p=boyaa&/','',$s);
			
				$name = substr($s, 0,3);
					
				$_POST[$name] = substr($s, 4);
			
				if(!$param = json_decode(substr($s, 4),true)) {			//值不是json格式的话
						
					$ret = array('code'=>'-2','msg' =>'bad format arguments.');
			
				}
			}
        }
		
		return $param;
	 }
	/**
	 * 验证传过来的数据
	 
	 * @param array $params 客户端参数
	 
	 * @return bool 0  失败，1 成功
	 */

	public static function auth( $params ){
		$sig = $params['sig'];
		unset( $params['sig']);
		
		$genSig = self::joins( $params, $params['mtkey'], (int)$params['api']);
		
		$flag = $sig == md5($genSig);

        return true;//PRODUCTION_SERVER ? $flag : true;
	}

	/**
	 * 验证
	 *
	 *
	 * @param array $param
	 *
	 * @return array|false|void
	 */
	public static function httpAuth( $api, $version )
	{

		$version = empty($version) ? '0.0.1' : $version;
		
		if( self::isUseXtunnelVerify($api, $version))
		{
		    $decryptData = self::getXTunnelVerify($api);
			
			if(empty($decryptData))
			{
			    $ret = array('code' => 403,
				             'msg'  => 'http header error');
			    self::jsonRet($ret, 0);
			}
			
			return $decryptData;
		}
		
		return false;
	}
		
	
	
	/**
	 * 串起来客户端传过来的数据
	 * @param araay $arg 客户端传过来的原始数据
	 * @param string $mtkey 进入房间的mtkey
	 * @param  int $api
	 * @return string $str
	 */
	private static function joins( $arg, $mtkey, $api){
		static $joins_counter = 0;
	    if (++$joins_counter > 500){
	       die( 'possible deep recursion attack!' );
	    }
	    $turnapi = self::turnApi( $api );
		$aGen = array('B','P','M','T','Y','K','U','G','X','L','G','H','Q');
		$str = $aGen[$turnapi];
		if ( ! is_object( $arg )) {
			if( is_null( $arg) || is_bool( $arg) ){
				$str .= '';
			}elseif( is_string( $arg) || is_numeric( $arg) ){
				$str .= 'T' . $mtkey . preg_replace('/[^0-9a-z]/i', '', $arg, -1);
			}else{
				ksort( $arg, SORT_STRING );
				foreach( $arg as $key => $value ){
					$str .= ($key . '=' . self::joins( $value, $mtkey, $api));
				}
			}
		}
		return $str;
	}

	/**
	 * api映射到sig验证所取字符
	 * @param int $api
	 * @return int $array[$api]
	 */
	
	private static function turnApi( $api )
	{
	    $array = array(
	                    1   =>1,
	                    100 =>1,
	                    101 =>1,
	                    102 =>1,
	                    116 =>1,
	                    106 =>5,
					    110 =>1,
					    111 =>1,
					    112 =>1,
					    2   =>2,
					    103 =>2,
					    104 =>2,
					    105 =>2,
					    113 =>2,
					    114 =>2,
					    115 =>2,
					);
	    return intval( $array[$api] );
	}
	
	/**
	 * 制作返回给ios的参数
	 * @param json $ret
	 * @param int $flag
	 * @echo json
	 */
	public static function jsonRet($ret, $flag = 1 )
	{
	    $rets['flag'] = $flag;
		
		$rets['time'] = date("Y-m-d"); //系统时间
		
		if($flag)
		{
		    $rets['success'] = $ret;
		}
		else
		{
		    $rets['error'] = $ret;
		}
		
		die( json_encode( $rets ) );
	}

	
	/**
	 * 判断客户端是使用http头x-tunnel-verify方式验证数据
	 *
	 * @param int $api 接口(应用)类型
	 * @param string $version 版本
	 * @return bool 是否使用
	 */
	public static function isUseXTunnelVerify($api, $version){
		$httpArr = Core_Game::$clientHttpVerify;
		if(
			empty($api) || empty($version) ||
			empty( $httpArr ) ||
			!isset(  $httpArr[$api] )
		)
		{
			return false;
		}
		$currInfo = explode('.', $version);
		$latestInfo = explode('.', $httpArr[$api][0]);
		
		$current =  intval($currInfo[0]).intval($currInfo[1]).intval($currInfo[2]);
		$config  =  intval($latestInfo[0]).intval($latestInfo[1]).($latestInfo[2]);

		return ($current >= $config);
	}

	/**
	 * 新版移动客户端http数据验证
	 *
	 * @return bool 是否合法
	 */
	public static function getXTunnelVerify($api){
		static $byteMap = array(
			0x70,0x2F,0x40,0x5F,0x44,0x8E,0x6E,0x45,0x7E,0xAB,0x2C,0x1F,0xB4,0xAC,0x9D,0x91,
			0x0D,0x36,0x9B,0x0B,0xD4,0xC4,0x39,0x74,0xBF,0x23,0x16,0x14,0x06,0xEB,0x04,0x3E,
			0x12,0x5C,0x8B,0xBC,0x61,0x63,0xF6,0xA5,0xE1,0x65,0xD8,0xF5,0x5A,0x07,0xF0,0x13,
			0xF2,0x20,0x6B,0x4A,0x24,0x59,0x89,0x64,0xD7,0x42,0x6A,0x5E,0x3D,0x0A,0x77,0xE0,
			0x80,0x27,0xB8,0xC5,0x8C,0x0E,0xFA,0x8A,0xD5,0x29,0x56,0x57,0x6C,0x53,0x67,0x41,
			0xE8,0x00,0x1A,0xCE,0x86,0x83,0xB0,0x22,0x28,0x4D,0x3F,0x26,0x46,0x4F,0x6F,0x2B,
			0x72,0x3A,0xF1,0x8D,0x97,0x95,0x49,0x84,0xE5,0xE3,0x79,0x8F,0x51,0x10,0xA8,0x82,
			0xC6,0xDD,0xFF,0xFC,0xE4,0xCF,0xB3,0x09,0x5D,0xEA,0x9C,0x34,0xF9,0x17,0x9F,0xDA,
			0x87,0xF8,0x15,0x05,0x3C,0xD3,0xA4,0x85,0x2E,0xFB,0xEE,0x47,0x3B,0xEF,0x37,0x7F,
			0x93,0xAF,0x69,0x0C,0x71,0x31,0xDE,0x21,0x75,0xA0,0xAA,0xBA,0x7C,0x38,0x02,0xB7,
			0x81,0x01,0xFD,0xE7,0x1D,0xCC,0xCD,0xBD,0x1B,0x7A,0x2A,0xAD,0x66,0xBE,0x55,0x33,
			0x03,0xDB,0x88,0xB2,0x1E,0x4E,0xB9,0xE6,0xC2,0xF7,0xCB,0x7D,0xC9,0x62,0xC3,0xA6,
			0xDC,0xA7,0x50,0xB5,0x4B,0x94,0xC0,0x92,0x4C,0x11,0x5B,0x78,0xD9,0xB1,0xED,0x19,
			0xE9,0xA1,0x1C,0xB6,0x32,0x99,0xA3,0x76,0x9E,0x7B,0x6D,0x9A,0x30,0xD6,0xA9,0x25,
			0xC7,0xAE,0x96,0x35,0xD0,0xBB,0xD2,0xC8,0xA2,0x08,0xF3,0xD1,0x73,0xF4,0x48,0x2D,
			0x90,0xCA,0xE2,0x58,0xC1,0x18,0x52,0xFE,0xDF,0x68,0x98,0x54,0xEC,0x60,0x43,0x0F
		);
	    $httpArr = Core_Game::$clientHttpVerify;
	    
		if(empty($_SERVER['HTTP_X_TUNNEL_VERIFY']))
			return false;
	
		list($version, $seed, $data, $sig) = explode('&', $_SERVER['HTTP_X_TUNNEL_VERIFY']);
		if(empty($version) || empty($seed) || empty($data) || empty($sig))
			return false;
			
		$seed		= hexdec($seed) % 256;
		$data		= base64_decode($data);
		$sig		= base64_decode($sig);
		$datalen	= strlen($data);
	
		for($i=0; $i<$datalen; $i++){
			$data{$i} = chr( $byteMap[ ord($data{$i}) ^ $seed ] );
		}
	
		if(function_exists('mhash'))
			$hash = mhash(MHASH_SHA1, $data, $httpArr[$api][1]);
		elseif(function_exists('hash_hmac'))
			$hash = hash_hmac("sha1", $data, $httpArr[$api][1], true);
		else
			return false;

		if( strcmp($hash, $sig) )
			return false;
	    
		$data = json_decode($data, true);
		if(is_array($data) && !empty($data)){
			//兼容旧的appleHeaderDecrypt函数返回结果
			$data[0] = '2.0-x-tunnel-verify'; //标识新版本协议
			$data[1] = $data['macID'];
			$data[2] = $data['iOSType'];
			$data[3] = $data['iOSVer'];
			$data[4] = $data['iOSModel'];
		}else{
			$data = false;
		}

		return $data;
	}
		
	
}//end-class