<?php
/**
 * Myzone PHP SDK
 * Version: 1.0
 * Designed by YajigoLi
 * lijitaoccnu@126.com
 */
    
class Myzone {
	public $gameid = ""; //应用ID
	public $gamekey = ""; //应用私钥
	public $sessionkey = ""; //用户登录session
	public $user = ""; //当前登录用户ID(MyzoneID)
	
	public function __construct( $gameid, $gamekey ) {
		$this->gameid = $gameid;
		$this->gamekey = $gamekey;
	}
	
	/**
	 * 获取用户登录session
	 * @return String
	 */
	public function getSessionkey() {
		if ( ! $this->sessionkey ) {
			if ( $_GET["sessionkey"] ) {
				$sessionkey = $_GET["sessionkey"];
			} elseif ( $_COOKIE["myzone_sessionkey"] ) {
				$sessionkey = $_COOKIE["myzone_sessionkey"];
			}
			$sessionkey && $this->setSessionkey( $sessionkey );
		}
		return $this->sessionkey;
	}
	
	/**
	 * 设置用户登录session
	 */
	public function setSessionkey( $sessionkey ) {
		$this->sessionkey = $sessionkey;
	}
	
	/**
	 * 获取API地址
	 * @return String
	 */
	public function getApiUrl( $method ) {
		$aUrl = array(
			"profile" => "http://192.168.10.3/Portal/profile.php",
			"consume" => "http://192.168.10.3/Portal/consume.php", 
		);
		return array_key_exists($method,$aUrl) ? $aUrl[$method] : "";
	}
	
	/**
	 * 设置当前登录用户
	 */
	public function setUser( $user ) {
		$this->user = $user;
	}
	
	/**
	 * 获取当前登录用户的ID（MyzoneID）
	 */
	public function getUser() {
		if ( $this->user ) {
			return $this->user;
		}
		if ( $_GET["uid"] && $_GET["sessionkey"] && $_GET["sig"] ) {
			list( $token["uid"], $token["sessionkey"], $token["sig"] ) = array( $_GET["uid"], $_GET["sessionkey"], $_GET["sig"] );
		} elseif ( $_COOKIE["myzone_uid"] && $_COOKIE["myzone_sessionkey"] && $_COOKIE["myzone_sig"] ) {
			list( $token["uid"], $token["sessionkey"], $token["sig"] ) = array( $_COOKIE["myzone_uid"], $_COOKIE["myzone_sessionkey"], $_COOKIE["myzone_sig"] );
		}
		if ( $this->authAccessToken( $token ) ) {
			$this->setUser( $token["uid"] );
		} else {
			//$this->error_print("The access token is invalid");
		}
		return $this->user;
	}
	
	/**
	 * 验证应用传入参数
	 * @return Bool
	 */
	public function authAccessToken( $token ) {
		if ( is_array($token) && $token["uid"] && $token["sessionkey"] && $token["sig"] ) {
			if ( $token["sig"] == md5( $token["uid"] . $token["sessionkey"] . $this->gamekey ) ) {
				setcookie( "myzone_uid", $token["uid"], 0, "/" );
				setcookie( "myzone_sessionkey", $token["sessionkey"], 0, "/" );
				setcookie( "myzone_sig", $token["sig"], 0, "/" );
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 获取用户资料
	 * @return Array [uid,username,...]
	 */
	public function getUserInfo() {
		$params = array( "fldlist" => array("uid") );
		$result = $this->api( "profile", $params );
		if ( $result["result"] != 1 ) {
			$this->error_print( $result["description"] );
		} elseif ( ! is_array($result["fldlist"]) || ! $result["fldlist"]  ) {
			$this->error_print("Something wrong with the result of the 'profile' api.");
		}
		return (array)$result["fldlist"];
	}
	
	/**
	 * 用户支付扣费(扣MY币)
	 * @return String 交易号
	 */
	public function payCost( $params ) {
		if ( ! $params["fee"] || ! $params["feename"] || ! $params["feedescription"] ) {
			return false;
		}
	
		$result = $this->api( "consume", $params );
		if ( $result["result"] != 1 ) {
			$this->error_print( $result["description"] );
		} elseif ( ! $result["feeticket"] ) {
			$this->error_print("Something wrong with the result of the 'consume' api.");
		}
		
		return $result;
	}
	
	/**
	 * 调用Myzone官方接口
	 * @return Array
	 */
	public function api( $method, $params ) {
		if ( ! $this->getSessionkey() ) {
			$this->error_print("The sessionkey is invalid.");
		}
		if ( ! $api_url = $this->getApiUrl($method) ) {
			$this->error_print("The method '{$method}' is not supported.");
		}
		if ( ! is_array($params) ) {
			$this->error_print("The params must be an array.");
		}
		if ( ! function_exists("curl_init") ) {
		  	$this->error_print("This api needs the CURL PHP extension.");
		}
		if ( ! function_exists("json_decode") ) {
		 	$this->error_print("This api needs the JSON PHP extension.");
		}
		
		$params["sessionkey"] = $this->getSessionkey();
		$params["gameid"] = $this->gameid;

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $api_url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		$result = curl_exec( $ch );
		curl_close( $ch );
		
		if ( $result === false ) {
			$this->error_print("This api may be not ready, or check the api url.");
		} else {
			$array = json_decode( $result, true );
			if ( $array === NULL ) {
				$this->error_print("The result of the '{$method}' api is not the normal josn-format.<br />Result: {$result}");
			}
			return $array;
		}
	}
	
	/**
	 * 致命错误中断
	 */
	public function error_print( $description ) {
		Logs::factory()->debug(json_encode(array("Error: {$description}",'time'=>date("Y-m-d",time()),'sitemid'=>$this->getUser() )),"pay.ErrorDebug" );
	}
}