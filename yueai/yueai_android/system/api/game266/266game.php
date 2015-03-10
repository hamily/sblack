<?php
!defined('BOYAA') AND exit('Access Denied!');

/**
 * PHP SDK for Boyaa 266game Open API
 *
 * @version 1.0.0
 * @author Boyaa Interactive
 * @copyright ? 2011, Boyaa Interactive.
 */
 
 /**
 * 如果您的 PHP 没有安装 cURL 扩展，请先安装 
 */
if (!function_exists('curl_init'))
{
	throw new Exception('Boyaa 266 game API needs the cURL PHP extension.');
}

/**
 * 如果您的 PHP 不支持JSON，请升级到 PHP 5.2.x 以上版本
 */
if (!function_exists('json_decode'))
{
	throw new Exception('Boyaa 266 game API needs the JSON PHP extension.');
}

/**
 * 错误码定义
 */
define('BY_ERROR_REQUIRED_PARAMETER_EMPTY', -20001); // 参数为空
define('BY_ERROR_REQUIRED_PARAMETER_INVALID', -20002); // 参数格式错误
define('BY_ERROR_RESPONSE_DATA_INVALID', -20003); // 返回包格式错误
define('BY_ERROR_CURL', -30000); // 网络错误, 偏移量30000, 详见 http://curl.haxx.se/libcurl/c/libcurl-errors.html

/**
 * 提供访问博雅266游戏平台 OpenAPI 的接口
 */
 
class BoyaaGame
{
	/**
	 * SDK 版本号
	 *
	 * @var string
	 */
	 const VERSION = '1.0.0';

	/**
	 * 游戏的唯一ID
	 *
	 * @var string
	 */
	 private $appId;
	 
	 /**
	 * 游戏的密钥，用于验证游戏的合法性
	 * 
	 * @var string
	 */
	private $appKey;

	/**
	 * 266游戏平台 用户的session id 
	 *
	 * @var string
	 */
	private $skey;
	/**
	 * 266游戏平台 OpenAPI 服务器的域名
	 *
	 * @var string
	 */
	private $severUrl = 'http://game.266.com';
	 
	/**
	 * 构造函数，初始化一个266游戏平台 游戏
	 *
	 * @param string $appId 游戏的ID
	 * @param string $appKey 游戏的密钥
	 */ 
	public function __construct($appId , $appKey)
	{
		$this->appId = $appId;   
		$this->appKey = $appKey;
		$this->skey = isset($_REQUEST['266_skey']) ? $_REQUEST['266_skey'] : '';
	}

	/**
	 * 设置游戏ID
	 *
	 * @param int $appId 游戏的唯一ID
	 */
	public function setAppId($appId)
	{
		$this->appId = $appId;
	}

	/**
	 * 获取游戏ID
	 *
	 * @return int 游戏的唯一ID
	 */
	public function getAppId()
	{
		return $this->appId;
	}

	/**
	 * 设置游戏密钥
	 *
	 * @param string $appKey 游戏密钥
	 */
	public function setAppKey($appKey)
	{
		$this->appKey = $appKey;
	}

	/**
	 * 获取游戏密钥
	 *
	 * @return string 游戏密钥
	 */
	public function getAppKey()
	{
		return $this->appKey;
	}

	/**
	 * 设置API服务器的域名
	 *
	 * @param string $server_name  Boyaa game OpenAPI 服务器的域名
	 */
	public function setseverUrl($server_name)
	{
		$this->severUrl = $server_name;
	}

	/**
	 * 获取API服务器的域名
	 *
	 * @return string  Boyaa game OpenAPI 服务器的域名
	 */
	public function getseverUrl()
	{
		return $this->severUrl;
	}

	/**
	 * 生成游戏签名，用于266游戏平台验证是否为游戏商发出的请求
	 *
	 * @return array 签名信息
	 */	
	private function makeSign()
	{
		$sign_params = array(
			'266_appid' => $this->appId,
			'266_appkey' => $this->appKey,
			'266_skey' => $this->skey,
			'266_time' => time()
		);
		ksort($sign_params);
		$sign_params['266_sign'] = md5(implode('_', $sign_params));
		unset($sign_params['266_appkey']);
		return $sign_params;
	}
	 
	/**
	 * 执行API调用，返回结果数组
	 *
	 * @param string $mod 调用的API的mod名
	 * @param string $act 调用的API的act名
	 * @param array $params 调用API时带的参数
	 * @return array 结果数组
	 */ 
	public function api($mod, $act, $params)
	{
		$sign_params = $this->makeSign();
		$params = array_merge($sign_params, $params);
		$url = $this->severUrl . "/json.php?mod={$mod}&act={$act}";
		return $this->makeRequest($url, $params);			
	}

	/**
	 * 执行一个 HTTP 请求, 返回结果数组。可能发生cURL错误
	 *
	 * @param string $url 执行请求的URL
	 * @param array $params 表单参数
	 * @return array 结果数组
	 */
	private function makeRequest($url, $params)
	{
		$ch = curl_init();

		$opts = array();
		$opts[CURLOPT_RETURNTRANSFER] = true;
		$opts[CURLOPT_CONNECTTIMEOUT] = 10;
		$opts[CURLOPT_TIMEOUT] = 30;
		$opts[CURLOPT_USERAGENT] = '266game-php-1.0';
		$opts[CURLOPT_POST] = 1;
		$opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
		$opts[CURLOPT_URL] = $url;

		curl_setopt_array($ch, $opts);
		
		$result = curl_exec($ch);

		if (false === $result)
		{
			// cURL 网络错误, 返回错误码为 cURL 错误码加偏移量
			// 详见 http://curl.haxx.se/libcurl/c/libcurl-errors.html
			$err = array(
				'code' => BY_ERROR_CURL + curl_errno($ch),
				'data' => curl_error($ch));
			curl_close($ch);
			return $err;
		}
		curl_close($ch);
		
		$result_array = json_decode($result, true);
		
		// 远程返回的不是 json 格式, 说明返回包有问题
		if (is_null($result_array)) {
			return array(
				'code' => BY_ERROR_RESPONSE_DATA_INVALID,
				'data' => $result);
		}
		return $result_array;		
	}
	 
	###############################################################################
	#                        以下是用户需要调用的函数接口
	############################################################################### 

	/**
	 * 验证请求是否为266游戏平台服务器发出。若不是，游戏商可选择拒绝该请求。
	 *
	 * @return bool (true|false)
	 */
	public function checkSign()
	{
		foreach($_REQUEST AS $key => $value) {
			if(substr($key, 0, 4) == '266_') {
				$params[$key] = $value;
			}
		}
		
		if(!isset($params['266_sign']) || empty($params['266_sign'])) {
			return false;
		}
		$sign = $params['266_sign'];
		unset($params['266_sign']);
		
		$params['266_appid'] = $this->appId;
		$params['266_appkey'] = $this->appKey;
		ksort($params);
		return $sign === md5(implode('_', $params));			
	}

	/**
	 * 设置游戏密钥
	 *
	 * @param string $appKey 游戏密钥
	 */
	public function setSkey($skey)
	{
		$this->skey = $skey;
	}

	/**
	 * 获取游戏密钥
	 *
	 * @return string 游戏密钥
	 */
	public function getSkey()
	{
		return $this->skey;
	}	
	
	/**
	 * 返回当前登录用户信息
	 * @return array
			- code : 返回码 (0:正确返回, [~,-10000]错误,发生错误时,data为错误信息)
			- data : array 用户信息
				- uid : 266游戏平台的id
				- name : 昵称
				- gender : 性别 0表示男，1表示女
				- locale : 地区 可能为空
				- smallpicurl : 小头像url
				- mediumpicurl : 中头像url
				- largepicurl : 大头像url
				- platform : 266用户关联的第三方平台的名字 （用户关联了第三方平台才返回此字段）
				- pid : 266用户所关联的第三方平台的id （用户关联了第三方平台才返回此字段）
	 */	
	public function getUserInfo()
	{
		// 检查 skey 是否为空
		if (!isset($this->skey) || empty($this->skey))
		{
			return array(
				'code' => BY_ERROR_REQUIRED_PARAMETER_EMPTY,
				'data' => 'skey is empty');
		}
		$result = $this->api('game', 'userInfo', array());
		return $result;
	}
	
	/**
	 * 获取266用户在第三方平台的好友列表
	 *
	 * @param array $input_array
			- platname : 第三方平台的名称
	 * @return array 好友关系链的数组
			- code : 返回码 (0:正确返回; [~, -10000]错误,错误时data为错误信息)
			- data : array 266用户在第三方平台的好友列表
				-0 第一个好友
					- name : 好友的名字
					- id : 好友的第三方平台id
	 */
	public function getPlatFriendList($input_array = array())
	{
		// 检查 skey 是否为空
		if (!isset($this->skey) || empty($this->skey))
		{
			return array(
				'code' => BY_ERROR_REQUIRED_PARAMETER_EMPTY,
				'data' => 'skey is empty');
		}
		
		if (!isset($input_array['platname']) || empty($input_array['platname']))
		{
			return array(
				'code' => BY_ERROR_REQUIRED_PARAMETER_EMPTY,
				'data' => 'platname is empty');
		}
		$result = $this->api('game', 'platFriendList', $input_array);
		return $result;
	}
	
}