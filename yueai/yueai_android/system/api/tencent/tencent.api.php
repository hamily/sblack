<?php !defined('BOYAA') AND exit('Access Denied!');

require_once('openapi_v3.php');

class Tencent_Api extends OpenAPIV3
{
    protected $me;

    protected $coveredMe = array();
    
    protected $coverFields = array();
    
    protected static $_instance = array();
    
    protected $openid;
    
    protected $openkey;
	
	protected $error = array();
    
    /**
     * 创建一个实例
     *
     * @author 黄国星
     *
     * @return Tencent_Api
     */
    public static function factory()
    {
        if(!is_object(self::$_instance['tencent_api']))
        {
            self::$_instance['tencent_api'] = new Tencent_Api(Core_Game::$key, Core_Game::$secret);    
        }
        
        return self::$_instance['tencent_api'];
    }
    
    /**
     * 初始化微博接口
     *
     * @author 黄国星
     *
     * @param {str} $appkey
     * @param {str} $secret
     *
     * return object tencent_api
     */
    public function __construct($appkey, $secret)
    {    
        $this->openid  = $_REQUEST['openid'];
        $this->openkey = $_REQUEST['openkey'];
        
        parent::__construct($appkey, $secret);
        
        $this->setServerName(Core_Game::$apiServer);
        
        return $this;
    }
    
    /**
     * 设置参数
     *
     * @author 黄国星
     *
     * @param {array} $param
     *
     * @return array
     */
    public function setParams(array $param=array())
    {
        $params  = array(
            'openid'    => $this->openid,
            'openkey'   => $this->openkey,
            'appid'     => Core_Game::$key,
            'pf'        => Core_Game::$platform,
            'reqtime'   => time(),
            'wbversion' => 1            
        );
        
        return array_merge($params, $param);        
    }
    
    /**
     * 获取用户信息
     *
     * @author 黄国星
     *
     * @param {array} $fields 要获取的信息项，或要转换的信息项
     *
     * @return array
     */
    public function getMe(array $fields=array())
    {    
        $params  = $this->setParams();

        $result   = $this->api('user/info', $params);
        $this->me = $result['data'];
        
        $this->setCoverFields($fields);
        $this->coverMe();
        
        return $this->coveredMe;
    }
    
	/**
     * 获取其它用户信息
     *
     * @author sjh
     *
     * @param {array} $fields 要获取的信息项，或要转换的信息项
     *
     * @return array
     */
    public function getOther($fopenid = '', $fname = '', array $fields)
    {    
    	if(!empty($fopenid))
		{
    		$params = array('fopenid'=> $fopenid);
    	}	
    	if(!empty($fname))
		{
    		$params = array('name'=> $fname);
		}
		
        $params  = $this->setParams($params);

        $result   = $this->api('user/other_info', $params, 'get');
        $this->me = $result['data'];
        
        $this->setCoverFields($fields);
        $this->coverMe();
        
        return $this->coveredMe;
    }
    
	/**
	 * 获取多个用户信息
	 *
	 * @author 黄国星
	 *
	 * @param {array} $fopenid 要获取的用户openid
	 *
	 * @return array
	 */
	public function getMulti(array $fopenids)
	{
	    if(!$fopenids)
		{
		    return array();
		}
		
		$params = array('fopenids' => implode('_', $fopenids));
        $params = $this->setParams($params);

        $result = $this->api('user/infos', $params, 'get');

        return (array)$result['data']['info'];		
	}
	
    /**
     * 用户信息转换
     * 
     * @author 黄国星
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
        
        if(empty($this->coveredMe) AND $this->me)
        {
            $this->coveredMe = $this->me;
        }
    }
    
    /**
     * 设置要转换的信息
     *
     * @author 黄国星
     *
     * @param {array} $fields 要转换的信息
     *
     * @return void
     */    
    protected function setCoverFields(array $fields)
    {
        $this->coverFields = array_unique(array_merge($this->coverFields, $fields));
    }
    
    /**
     * 发微博
     *
     * @author 黄国星
     * 
     * @param {str} $content 内容
     *
     * @return array
     */
    public function publishWeibo($content)
    {
        $params = array(
            'content' => $content,
        );
        
        $params = $this->setParams($params);
        
        return $this->api('t/add', $params, 'post');
    }
    
    /**
     * 发带图片的微博(图片必须以enctype="multipart/form-data"方式发送)
     *
     * @author 黄国星
     *
     * @param {str} $content 内容
     * @param {str} $pic     图片
     *
     * @return array
     */
    public function sharePhoto($content, $pic)
    {
        $params = array(
            'pic'     => $pic,     
            'content' => $content,        
        );
        
        $params = $this->setParams($params);
        $result = $this->api('t/add_pic', $params, 'post');
        
        return $result;
    }

    /**
     * 发带图片的微博(只提供图片URL即可)
     */
    public function sharePhotoByUrl($content, $pic_url){
    	$params = array(
    		'pic_url' => $pic_url,
    		'content' => $content . '?'.time() //防TX缓存，保证每次都发成功
    	);
    	
    	$params = $this->setParams($params);
    	$result = $this->api('t/add_pic_url', $params, 'post');
    	return $result;
    }
    
	/**
	 * 我收听的人
	 *
	 * @author 黄国星
	 *
	 * @param {int} $offset
	 * @param {int} $limit
	 * 
	 * @return array
	 */
	public function idollist($offset=0, $limit=30)
	{
	    $params = array(
		    'pic'     => $offset,
			'content' => $limit,
		);
		
		$list   = array();
		
		$params = $this->setParams($params);
		
		$result = $this->api('friends/idollist', $params, 'get');
		
		if(!empty($result['data']['info']))
		{
		    $list = $result['data']['info'];    
		}
		
		return $list;
	}
	
    /**
     * 获取互粉列表
     *
     * @author 黄国星
     *
     * @param {int} $offset 起始位置
     * @param {int} $limit  记录数
     *
     * @return array
     */
    public function getBothFans($offset=0, $limit=30)
    {
        $params = array(
            'startindex' => $offset,
            'reqnum'     => $limit,
        );
        
        $friends = array();
        $params  = $this->setParams($params);
        $result  = $this->api('friends/mutual_list', $params, 'get');

        if( is_array( $result ) && !empty( $result['data'] ) )
		{
            $friends = $result['data']['info'];
        }
		
        return $friends;
    }
    
    /**
     * 检测是否我的听众或收听的人
     *
     * @author sjh
     *
     * @param {str} $name   用户名称(限制30个)
     *
     * @return array
     */
    public function checkFans(array $names, $limit=30)
	{
    	if(!$names)
            return FALSE;
        
        $cfans	  = array();
        $params   = array('names' => implode(',', array_slice($names, 0, $limit)), 'flag' => 1);

        $params   = $this->setParams($params);
        $result   = $this->api('friends/check', $params, 'get');
    	
        if( is_array( $result ) && !empty( $result['data'] ) )
		{
            $cfans = $result['data'];
        }
        
        return $cfans;
    }
    
    /**
     * 收听某些用户
     *
     * @author 黄国星
     *
     * @param {array} $fopenids 用户openid列表
     *
     * @return boolean 收听成功返回TRUE，否则返回FALSE
     */
    public function add( array $fopenids )
    {
        if(!$fopenids)
        {
            return FALSE;
        }
        
        $params = array('fopenids' => implode('_', $fopenids));
        $params = $this->setParams($params);
        
        $result = $this->api('friends/add', $params);
        
        return (bool)!$result['ret'];
    }
    
    /**
     * 取消收听某个用户
     *
     * @author 黄国星
     *
     * @param {str} $fopenid 用户openid
     *
     * @return boolean 取消成功返回TRUE，否则返回FALSE
     */
    public function del( $fopenid )
    {
        if(!$fopenid)
        {
            return FALSE;
        }
        
        $params = array('fopenid' => $fopenid);
        $params = $this->setParams($params);

        $result = $this->api('friends/del', $params);

        return (bool)!$result['ret'];        
    }
	
	/**
	 * 设置错误信息
	 *
	 * @author 黄国星
	 *
	 * @param {string} $api   API名称
	 * @param {array}  $error 错误信息
	 *
	 * @return void
	 */
	protected function setError($api, $error)
	{
	    $this->error[$api]= $error;
	}
	
	/**
	 * 获取错误信息
	 *
	 * @author 黄国星
	 *
	 * @param {string} $api   API名称
	 *
	 * @return array
	 */
	public function getError($api=NULL)
	{
	    if($api)
		{
		    return $this->error[$api];
		}
		else
		{
		    return $this->error;
		}
	}
}