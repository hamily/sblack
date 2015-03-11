<?php
/**
 * PHP SDK for  OpenAPI V3
 *
 * @version 1.2.0
 * @author dev.opensns@qq.com
 * @copyright © 2011, Tencent Corporation. All rights reserved.
 */

require_once 'sns_network.php';
require_once 'sns_sig_check.php';
 
/**
 * 如果您的 PHP 没有安装 cURL 扩展，请先安装 
 */
if (!function_exists('curl_init'))
{
    throw new Exception('Pengyou API needs the cURL PHP extension.');
}

/**
 * 如果您的 PHP 不支持JSON，请升级到 PHP 5.2.x 以上版本
 */
if (!function_exists('json_decode'))
{
    throw new Exception('Pengyou API needs the JSON PHP extension.');
}

/**
 * 错误码定义
 */
define('PYO_ERROR_REQUIRED_PARAMETER_EMPTY', 2001); // 参数为空
define('PYO_ERROR_REQUIRED_PARAMETER_INVALID', 2002); // 参数格式错误
define('PYO_ERROR_RESPONSE_DATA_INVALID', 2003); // 返回包格式错误
define('PYO_ERROR_CURL', 3000); // 网络错误, 偏移量3000, 详见 http://curl.haxx.se/libcurl/c/libcurl-errors.html

/**
 * 提供访问腾友社区开放平台 OpenAPIV3 的接口
 */
class OpenAPIV3
{
    private $appid  = 0;
    private $appkey = '';
    private $server_name = '';
    private $format = 'json';
    
    /**
     * SDK 版本号
     */
    const VERSION  = '1.2.0';
    
    /**
     * 构造函数
     *
     * @param int $appid 应用的ID
     * @param string $appkey 应用的密钥
     */
    function __construct($appid, $appkey)
    {
        $this->appid = $appid;
        $this->appkey = $appkey;
    }
    
    public function setServerName($server_name)
    {
        $this->server_name = $server_name;
    }
    
    /**
     * 执行API调用，返回结果数组
     *
     * @param array $script_name 调用的API方法
     * @param array $params 调用API时带的参数
     * @param string $method 请求方法 post / get
     * @param string $protocol 协议类型 http / https
     * @return array 结果数组
     */
    public function api($script_name, $params, $method='post', $protocol='http')
    {
        // 检查 openid 是否为空
        if (!isset($params['openid']) || empty($params['openid']))
        {
            return array(
                'ret' => PYO_ERROR_REQUIRED_PARAMETER_EMPTY,
                'msg' => 'openid is empty');
        }
        // 检查 openkey 是否为空
        if (!isset($params['openkey']) || empty($params['openkey']))
        {
            return array(
                'ret' => PYO_ERROR_REQUIRED_PARAMETER_EMPTY,
                'msg' => 'openkey is empty');
        }
        // 检查 openid 是否合法
        if (!self::isOpenId($params['openid']))
        {
            return array(
                'ret' => PYO_ERROR_REQUIRED_PARAMETER_INVALID,
                'msg' => 'openid is invalid');
        }
        
        // 无需传sig, 会自动生成
        unset($params['sig']);
        
        // 添加一些参数
        $params['appid'] = $this->appid;
        $params['format'] = $this->format;
        
        // 生成签名
        $secret = $this->appkey . '&';
        $sig = SnsSigCheck::makeSig('post', $script_name, $params, $secret);
        $params['sig'] = $sig;
    
        // 发起请求
        $url = "{$protocol}://{$this->server_name}{$script_name}";
        $cookie = array();
        $ret = SnsNetwork::makeRequest($url, $params, $cookie, $method, $protocol);
        
        if (false === $ret['result'])
        {
            return array(
                'ret' => PYO_ERROR_CURL + $ret['errno'],
                'msg' => $ret['msg'],
            );
        }
        
        $result_array = json_decode($ret['msg'], true);
        
        // 远程返回的不是 json 格式, 说明返回包有问题
        if (is_null($result_array)) {
            return array(
                'ret' => PYO_ERROR_RESPONSE_DATA_INVALID,
                'msg' => $ret['msg']);
        }
        return $result_array;
    }
    
    /**
     * 检查 openid 的格式
     *
     * @param string $openid openid
     * @return bool (true|false)
     */
    private static function isOpenId($openid)
    {
        return (0 == preg_match('/^[0-9a-fA-F]{32}$/', $openid)) ? false : true;
    }
}
?>