<?php
defined('BOYAA') or exit('Access Denied');

require_once('api.php');

class Flicker_Api{
	protected static $_instance = array();
	
	const FLICKKEY = "77b1e17ccd2bbe2846e2dfe702fbe35c";
	CONST FLICKSECRET = "289d2ef6485f0fd9";
	
	protected $flickapi = '';
	
	protected $perms = 'write';//array('read','write','delete');
	
	public $me = array();
	
	//单便模式
	public static function factory(){
		if(!isset(self::$_instance['flick']) || !is_object(self::$_instance['flick'])){
			self::$_instance['flick'] = new Flicker_Api();
		}
	}
	
	public function __construct(){
		//$this->flickapi = new Phlickr_Api(self::FLICKKEY,self::FLICKSECRET);
	}
	
	//获取用户信息
	public function getMe($uid,$auth_token){
		if(empty($uid) || empty($auth_token)){
			return $this->me;
		}
		$this->flickapi = new Phlickr_Api(self::FLICKKEY,self::FLICKSECRET,$auth_token);
		$params = array(
			"api_key"		=>	self::FLICKKEY,
			"user_id"		=>	$uid,
			"format"		=>	"json",
			"nojsoncallback"=>	1,
			"auth_token"	=>	$auth_token
		);
		$ret = $this->flickapi->executeMethod("flickr.people.getInfo",$params);
		$this->me = json_decode($ret,true);
		
		return $this->me;
	}
	
	//可以用于web时调用
	public function toAuth(){
		echo "<script>top.location.href='".$this->flickapi->buildAuthUrl($this->perms)."';</script>";
		exit;
	}
}

?>