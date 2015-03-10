<?php !defined('BOYAA') AND exit('Access Denied!');

require_once('TwitterAPIExchange.php');

class Twitter_Api extends TwitterAPIExchange
{
    public $me = array();
	
	protected $coveredMe   = array();
	protected $coverFields = array();

	//授权配置  
	protected $settings = array(
				    'oauth_access_token' => "1853206890-Tx5JvyIYs7DzvbJebhTFLmKbopZrdIMt91optvS",
				    'oauth_access_token_secret' => "3p6g5M0NPVrAtl2snF4VMIUyHVZ3d0VmdTPpvxrOSCp1z",
				    'consumer_key' => "JZft4hDE5cJ7Cty6JxOT8gLI8",
				    'consumer_secret' => "6RgCpMJj36wu1PmZudqQO9k44xRHv1Eu768qxFcF4AfqgQ17EQ"
				);
	
    protected static $_instance = array();
	
    /**
	 * 创建一个实例
	 *
	 *
	 * @return object Twitter_Api
	 */
    public static function factory()
	{
	    if(!is_object(self::$_instance['Twitter_Api']))
		{
		    self::$_instance['Twitter_Api'] = new Twitter_Api;
		}
	
		return self::$_instance['Twitter_Api'];
	}
	
    /**
     * 初始化接口
     *
     * @return object Facebook_Api
     */	 
	public function __construct()
	{
	    $this->twitter = new TwitterAPIExchange($this->settings);

	}
	
	/**
	 * 获取用户资料 
	 *
	 *
	 * @param {array} $fields
	 *
	 * @return array
	 */
	public function getMe($screenName,array $fields=array())
	{
		$url = 'https://api.twitter.com/1.1/users/show.json';	
		$getfield = '?screen_name='.$screenName;
		
		$requestMethod = 'GET';
		$tInfo = $this->twitter->setGetfield($getfield)
             ->buildOauth($url, $requestMethod)
             ->performRequest();
		
		$this->me = json_decode($tInfo,true);
		$this->coverFields = $fields;

        $this->coverMe();
        
        return $this->coveredMe;    
	}

    /**
     * 用户信息转换
     * 
     *
     * @return void
     */    
    protected function coverMe()
    {
        foreach($this->coverFields as $original=>$newField)
        {
            if(!is_numeric($original))
            {
                $this->coveredMe[$newField] = $this->me[$original];
                
                continue;
            }
            
            $this->coveredMe[$newField] = $this->me[$newField];
        }
        
        if(!$this->coveredMe AND $this->me)
        {
            $this->coveredMe = $this->me;
        }
    }
	
	/**
	 * 设置要转换的信息
	 *
	 *
	 * @param {array} $fields 要转换的项
	 * 
	 * @return void
	 */
	protected function setCoverFields(array $fields)
	{
	    $this->coverFields = array_unique(array_merge($this->coverFields, $fields));
	}
	
	/**
	 * 获取要从平台上获取的信息
	 *
	 *
	 * @param {boolean} $implode 是否返回字符串  默认返回字符串
	 *
	 * @return array
	 */
	protected function getFields($implode=TRUE)
	{
	    foreach($this->coverFields as $original=>$newField)
        {
		    if(!is_numeric($original))
			{
			    $fields[] = $original;
				
				continue;
			}
			
		    $fields[] = $newField;
		}
		
        return $fields ? ($implode ? implode(',', array_unique($fields)) : array_unique($fields)) : array();		
	}
    
	/**
	 * 认证授权
	 *
	 *
	 * @return void
	 */
    public function toAuth()
    {
	    $param['scope'] = $this->getExtPerm();
        $param['redirect_uri'] = $this->urlMap['redirect_uri'] . '?' . http_build_query($_REQUEST);	//跳转地址	
	    $authUri = $this->getLoginUrl($param);

		echo "<script type=\"text/javascript\">top.location.href=\"{$authUri}\"</script>";
	}
    
	/**
	 * 获取权限列表
	 *
	 *
	 * @param {boolean} 是否返回字符串
	 *
	 * @return string|array
	 */
    public function getExtPerm($implode=TRUE)
    {
	    return $implode ? implode(',', $this->extPerm) : $this->extPerm;
	}
    
	/**
	 * 设置跳转地址
	 *
	 *
	 * @return void
	 */
    public function setRedUri($uri)
    {
	    $this->urlMap['redirect_uri'] = $uri;
	}
    
	/**
	 * 设置授权项
	 *
	 *
	 * @retirm void
	 */
    public function setExtPerm($perm)
    {
	    if(is_array($perm) AND $perm)
		{
		    foreach($perm as $item)
			{
			    $key = strtolow($item);
				$this->extPerm[$key] = $item;
			}
		}
		else if($perm = trim($perm))
		{
		    $key = strtolow($perm);
			
			$this->extPerm[$key] = $perm;
		}
	}
    
	/**
	 * 移除权限
	 *
	 *
	 * @param mix $perm
	 *
	 * @return void
	 */
    public function removeExtPerm($perm)
    {
	    if(is_array($perm) AND $perm)
		{
		    foreach($perm as $item)
			{
			    $key = strtolow($item);
				unset($this->extPerm[$key]);
			}
		}
		else if($perm = trim($perm))
		{
		    $key = strtolow($perm);
			unset($this->extPerm[$key]);
		}
	}
}