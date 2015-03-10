<?php !defined('BOYAA') AND exit('Access Denied!');

require_once('facebook.php');
/**
 * 
 * @return Facebook_Api
 * 
 *
 */
class Facebook_Api extends Facebook
{
    public $me = array();
	
	protected $coveredMe   = array();
	protected $coverFields = array();
    
	protected $extPerm = array('user_about_me'  => 'user_about_me',
	                           'publish_actions' => 'publish_actions',
							   'email'          => 'email');
							   
	protected $urlMap  = array( 'authen'       => 'https://www.facebook.com/dialog/oauth',
                                'redirect_uri' => '');
	
    protected static $_instance = array();
	
    /**
	 * 创建一个实例
	 *
	 * @author 黄国星
	 *
	 * @return Facebook_Api
	 */
    public static function factory()
	{
	    if(!is_object(self::$_instance['facebook_api']))
		{
		    self::$_instance['facebook_api'] = new Facebook_Api;
		}
	
		return self::$_instance['facebook_api'];
	}
	
    /**
     * 初始化接口
     *
     * @return Facebook_Api
     */	 
	public function __construct()
	{
	    parent::__construct(array( 'appId'  	=> Core_Game::$appid,
		                           'secret' 	=> Core_Game::$secret);
		
		$this->setRedUri(Core_Game::$appUrl); //设置跳转地址
	}
	
	/**
	 * 获取用户ID
	 *
	 * @author 黄国星
	 *
	 * @return string
	 */
	public function getUid($redirect=TRUE)
	{
	    $uid = $this->getUser();

		if(!$uid && $redirect)
        {
		    $this->toAuth();
		}
        
        return $uid;		
	}
	
	/**
	 * 获取用户资料 
	 *
	 * @author 黄国星
	 *
	 * @param {array} $fields
	 *
	 * @return array
	 */
	public function getMe(array $fields=array())
	{
	    $this->setCoverFields($fields);
		
		$fields = $this->getFields();
		
		$fields = $fields ? "?fields={$fields}" : "";
		
		try
		{
		    $this->me = $this->api('/me' . $fields);
		}
		catch(Exception $e)
		{
		    $this->me = array();
		}
		
        $this->coverMe();
        
        return $this->coveredMe;    
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
        
        if(!$this->coveredMe AND $this->me)
        {
            $this->coveredMe = $this->me;
        }
    }
	
	/**
	 * 设置要转换的信息
	 *
	 * @author 黄国星
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
	 * @author 黄国星
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
	 * @author 黄国星
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
	 * @author 黄国星
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
	 * @author 黄国星
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
	 * @author 黄国星
	 *
	 * @retirm void
	 */
    public function setExtPerm($perm)
    {
	    if(is_array($perm) AND $perm)
		{
		    foreach($perm as $item)
			{
			    $key = strtolower($item);
				$this->extPerm[$key] = $item;
			}
		}
		else if($perm = trim($perm))
		{
		    $key = strtolower($perm);
			
			$this->extPerm[$key] = $perm;
		}
	}
    
	/**
	 * 移除权限
	 *
	 * @author 黄国星
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
			    $key = strtolower($item);
				unset($this->extPerm[$key]);
			}
		}
		else if($perm = trim($perm))
		{
		    $key = strtolower($perm);
			unset($this->extPerm[$key]);
		}
	}
	
	/**
	 * 获取非游戏好友列表
	 *
	 * @author gaifyyang
	 *
	 * @param string $uid 用户ID
	 *
	 * @return array
	 */
	public function getNoAppFriendList($uid) {
		$uid = (string)$uid;
		$query = "SELECT uid, name FROM user WHERE is_app_user=0 AND uid IN (SELECT uid2 FROM friend WHERE uid1='$uid')";

		try {
			$result = $this->api(array( 'method' => 'fql.query',
       								    'query' => $query));
		} catch (Exception $e) {
			$result = array();
		}
		$aList = array();
		foreach ((array)$result as $row){
			$aList[] = array('sitemid'=>$row['uid'],'name'=>$row['name']);
		}
		return $aList;
	}
	/**
	 * 获取非游戏好友列表
	 *
	 * @author wyj
	 *
	 * @param string $uid 用户ID
	 *
	 * @return Array
(
    [data] => Array
        (
            [0] => Array
                (
                    [uid] => 501301880
                    [name] => Gneheix Il
                )

            [1] => Array
                (
                    [uid] => 505886710
                    [name] => David Zhang
                )

	 */
	public function getNoAppFriendListNew($uid) {
		$uid = (string)$uid;
		$query = "SELECT uid, name FROM user WHERE is_app_user=0 AND uid IN (SELECT uid2 FROM friend WHERE uid1='$uid')";

		try { 
			$param = array("q"=>$query);
			$ret = $this->api("/fql","GET",$param);
			$result = $ret['data'];
		} catch (Exception $e) {
			$result = array();
		}
		$aList = array();
		foreach ((array)$result['data'] as $row){
			$aList[] = array('sitemid'=>$row['uid'],'name'=>$row['name']);
		}
		return $aList;
	}

    /**
	 * 获取游戏好友列表
	 *
	 * @author 张奎
	 *
	 * @param string $uid 用户ID
	 *
	 * @return array
	 */
	public function getAppFriendList($uid) {
		$uid = (string)$uid;
		$query = "SELECT uid FROM user WHERE is_app_user=1 AND uid IN (SELECT uid2 FROM friend WHERE uid1='$uid')";
		try {
			$result = $this->api(array(
        							'method' => 'fql.query',
       								'query' => $query));
		} catch (Exception $e) {
			$result = array();
		}
		$aList = array();
		foreach ((array)$result as $row){
			$aList[] = $row['uid'];
		}
		return $aList;
	}
	/**
	 * 获取游戏好友列表
	 *
	 * @author wyj
	 *
	 * @param string $uid 用户ID
	 *
	 * @return array
	 */
	public function getAppFriendListNew($uid) {
		$uid = (string)$uid;
		$query = "SELECT uid FROM user WHERE is_app_user=1 AND uid IN (SELECT uid2 FROM friend WHERE uid1='$uid')";
		try {
			$param = array("q"=>$query);
			$ret = $this->api("/fql","GET",$param);
			$result = $ret['data'];
		} catch (Exception $e) {
			$result = array();
		}
		$aList = array();
		foreach ((array)$result as $row){
			$aList[] = $row['uid'];
		}
		return $aList;
	}
	/**
	 *判断用户是否是粉丝
    * @author wyj 2012-10-22
    * @param $uid 用户站点sitemid 可为空，默认为me
   **/
   public function getUserPerms($uid = null) {
	   $uid = empty($uid) ? "me()" : $uid;
	   $fql = "select uid,bookmarked,status_update, publish_stream,email,user_birthday,offline_access,xmpp_login from permissions where uid = $uid";
	   try{
			$result = $this->api(array(
						"method"	=>	"fql.query",
						"query"		=>	$fql
						));
	   }catch (Exception $e) {
			$result = array();
	   }
	 return $result;	
   }
	 /**
	 * 发送FEED
	 * @author wyj 2012-10-22 https://developers.facebook.com/docs/reference/api/ message, picture, link, name, caption, description, source, place, tags
	 * @param $linkurl 连接地址
	 * @param $feedpic 图片地址
	 * @param $name	   the name of the link
	 * @param $caption the caption of the link
	 * @param $desc    the desc of the link
	 * @param $message the msg of the link 
	 * @param $properties = array() array of objects containing the name and text
	 */
	public function sendfeed($param){
		$permissions = $this->getUserPerms();
		if ($permissions["publish_stream"] == 0){ //沒有權限
			$this->getLoginUrl(array("scope"=>"publish_stream"));
		}
		try{
			$param = array(	'message' 	=> $param['message'], 
							'link' 		=> $param['linkurl'],
							'picture' 	=> $param['imgurl'],
							'name' 		=> $param['name'],
							'caption'	=> $param['caption'],
							'description'=> $param['desc'],
							'properties'=> $param['properties']
						);
    		$result = $this->api("/me/feed", "POST", $param);
		}catch(FacebookApiException $e){
			$result = $e->getResult();			
		}
		return isset($result["id"]) ? true : false;
	}
	/**
	 * 
	 * 上传照片
	 * @param ArrayObject $params
	 * @author Daniel Luo
	 */
	public function uploadPhotos($params) {
		$uid = $this->getUser();
		if ( !$uid ){ //沒有權限
			$this->getLoginUrl(array('scope' => 'photo_upload'));
		}
		$this->setFileUploadSupport(true);
		$photo = $params['photo'];//local image file
		$message = $params['message'];
		$link = $params['link'];
		$caption = $message . $link;
		try{
			$result = $this->api('/me/photos', 'POST', array(
                                         'source' 	=> '@' . $photo,
										 'caption'	=> $caption,
                                         )
                                      );
		}catch (FacebookApiException $e) {
			$result = $e->getMessage();
		}
		return $result;
	}
	/**
	 * FB发关邮件
	 * @author wyj
	 * @param unknown_type $recipients
	 * @param unknown_type $subject
	 * @param unknown_type $content
	 * @return unknown
	 */
	public function sendEmail($recipients,$subject,$content){
		try{
			$this->api(array(
			'method'     => 'notifications.sendEmail',
			'recipients' => $recipients, //发送对象
			'subject'    => $subject,	//标题
			'fbml'       => $content,	//内容
			'text'      => 'qudlandlord'));
			return true;
		}catch(FacebookApiException $e){
			Logs::factory()->debug('email/emailerror',$e);
			return false;
		}
	}
	/**
	 * 设置access_token
	 * @author wyj
	 * @param unknown_type $secret
	 * @return unknown
	 */
	public function setAccessTokenn($secret){ 
		try{
			$result = $this->setAccessToken($secret);
			return true;
		}catch(FacebookApiException $e){
			Logs::factory()->debug('email/emailerror',$e);
			return false;
		}
	}
	/**
    * 创建一个指定名称的相册
    * @param $name 想册名称
    * @param $message 相册描述
    */
   public function createAlbum($name,$message='') {
   		$fql = "select object_id from album where owner=me() and name='".$name."'";
   		try{
   			$result = $this->api(array(
   				"method"	=>	"fql.query",
   				"query"		=>	$fql
   			));
   			if(count($result)>0) {
   				$albumid = $result[0]["object_id"];
   			} else {
   				$param = array("name"=>$name,"message"=>$message);
   				$album = $this->api("/me/albums","POST",$param);
   				$albumid = $album["id"];
   			}
   			return $albumid;
   		}catch(FacebookApiException $e) {
   			return false;
   		}
   }
   /**
	 * 根据相册的object_id找到相册的ID, 主要是结合上面的createAlbum使用，其它情况下使用fql语句是可以直接得到aid的。
	 * @param string $object_id
	 */
	public function getAlbumId($object_id){
		$albumid = false;
		$fql = "select aid from album where owner=me() and object_id='".$object_id."'";
		try{
			$result = $this->api(array("method"=>"fql.query","query"=>$fql));
			if (count($result) > 0) {
				$album = $result[0];
				$albumid = $album['aid'];
			}
			return $albumid;
		} catch(FacebookApiException $e) {
			return false;
		}		
	}
	/**
	 * 判断用户是否某个页面的like
	 *
	 * @param unknown_type $uid
	 * @param unknown_type $pageId
	 * @author wyj
	 */
	public function isFans($pageId,$uid=''){ 
		$uid = empty($uid) ? "me()" : $uid;
		$fql = "select created_time,page_id,profile_section,type,uid from page_fan where uid={$uid} and page_id= " . Core_Game::$pageId;
		//Logs::factory()->debug("fql",$fql,$pageId,$uid);
		try{
			$result = $this->api(array("method"=>"fql.query","query"=>$fql));
			return $result;
		} catch(FacebookApiException $e) {
			return false;
		}	
	}
	/**
	 * 判断用户是否某个页面的like
	 *
	 * @param unknown_type $uid
	 * @param unknown_type $pageId
	 * @author wyj
	 */
	public function isFansNew($pageId,$uid=''){ 
		$uid = empty($uid) ? "me()" : $uid;
		$fql = "select created_time,page_id,profile_section,type,uid from page_fan where uid={$uid} and page_id= " . Core_Game::$pageId;
		try{
			$param = array("q"=>$fql);
			$result = $this->api("/fql","GET",$param);
			return $result['data'][0];
		} catch(FacebookApiException $e) {
			return false;
		}	
	}
	/**
	 * 
	 * Facebook通知
	 * @author LuoZong
	 * @param unknown_type $sitemid	平台id
	 * @param unknown_type $href	链接参数：  ?notice_id=#$%@
	 * @param unknown_type $template	消息内容
	 * @param unknown_type $ref	 * 
	 */
	public function sendNotice($sitemid, $href, $template, $ref='') {
		$sitemid = $sitemid;
		$param['href'] = $href;
		$param['template'] = $template;
		$param['ref'] = $ref;
		return $this->api("/{$sitemid}/notifications", "POST" , $param);
	}
	/**
	 * 获得用户本地化货币设置币种
	 * @author wyj
	**/
	public function getUserLocalCurrency(){
		$local = "USD"; //默认值为USD取不到的时候
		try{
			$currency = $this->api("/me?fields=currency");
			$local = isset($currency["currency"]["user_currency"]) ? $currency["currency"]["user_currency"] : $local;
		} catch(FacebookApiException $e){
			//do nothing
		}
		return $local;
	}
}