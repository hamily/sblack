<?php  
defined('BOYAA') or exit('Access Denied！');
/**
* Name: Facebook Login Library
*
* Author: dulu 
*/
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookCanvasLoginHelper;

class Facebooksdk4_NewApi
{
    //记录请求信息
    private $helper;

	//canvashelper
	private $canvashelper;
	
    //记录session
    private $session;

    //记录access_token
    private $access_token;

    //游戏默认授权项
    private $facebook_default_scope = array("user_friends","email","public_profile");

    //保存类信息
    protected static $_instance = array();

    /**
     * 创建一个实例
     *
     * @author dulu
     *
     * @return object Facebookv4_NewApi
     */
    public static function factory()
    {
        if(!is_object(self::$_instance['facebooksdk4_newapi']))
        {
            self::$_instance['facebooksdk4_newapi'] = new Facebooksdk4_NewApi();
        }
        if (!isset($_SESSION)) 
        {
            session_start();
        }
        return self::$_instance['facebooksdk4_newapi'];
    }

    /**
     * 初始化游戏平台相关信息
     *
     * @author dulu
     *
     * @return object Facebookv4_NewApi
     */  
    public function __construct()
    {
        FacebookSession::setDefaultApplication(Core_Game::$appid, Core_Game::$secret);
        $this->helper = new FacebookRedirectLoginHelper(Core_Game::$appUrl);
    }
	/**
	 * 获得access_token
	**/
	public function getAccessToken(){
		return $this->session->getToken();
	}
	/**
	 * 设置accesss_token
	**/
	public function setAccessToken($token){
		$this->session = new FacebookSession($token);
	}
    /**
     * 获取用户ID
     *
     * @author dulu
     * @modify by wyj 2014-12-30
     * @return [string_type] 平台ID 
     */
    public function getUid()
    {
		$this->canvashelper = new FacebookCanvasLoginHelper();
		$this->session = $this->canvashelper->getSession();
		
		if ($this->session) {
			$this->access_token = $this->session->getToken();
			// Logged in.
			return $this->session->getUserId();
		} else {
			$this->resetLogin();
			return null;
		}	
    }

    /**
     * [resetLogin 令牌过期,重新登录,生成令牌]
     *
     * @author dulu
     * 
     * @return [void] 获得access_token信息
     */
    private function resetLogin()
    {
        // no session exists
        try {
             $this->helper->disableSessionStatusCheck();
             $this->session = $this->helper->getSessionFromRedirect();
			 if(!isset($this->session) || $this->session === null) {
                $this->toAuth($this->facebook_default_scope);
             } 
        } catch(FacebookRequestException $ex) {
            // When Facebook returns an error
            // handle this better in production code
            Logs::factory()->debug("facebookapi", $ex->getMessage());
			//exit($ex->getMessage());
        } catch(Exception $ex) {
            // When validation fails or other local issues
            // handle this better in production code
            Logs::factory()->debug("facebookapi2", $ex->getMessage());
			//exit($ex->getMessage());
        }
    }

    /**
     * [toAuth 显示授权页面授权]
     * 
     * @author dulu
     *
     * @param [array_type] 
     * 
     * @return [array_type] [显示授权页面授权]
     */
    private function toAuth($perms)
    {
        $loginUrl = $this->helper->getLoginUrl($perms);
        echo "<script type=\"text/javascript\">top.location.href=\"{$loginUrl}\"</script>";
    }
	/**
	 * 
	 * 上传照片
	 * @param ArrayObject $params
	 */
	public function uploadPhotos($filepath,$message='') {
		//检查授权
      $perms = $this->getUserPerms();
      if(!in_array("user_photos", $perms))
      {
          //授权
          $this->toAuth(array("user_photos"));
      }

      //创建相簿
      $graphObject = array();
      if(isset($this->session))
      {
          $request = new FacebookRequest($this->session, 'POST', '/me/photos', array(
                'source' => '@'.$file,
                'message' => $message
          ));
          $response = $request->execute();
          $graphObject = $response->getGraphObject()->asArray();
      }
      else
      {
          $this->resetLogin();
      }

      return $graphObject;
	}
    /**
     * [getMe 获取用户信息]
     * 
     * @author dulu
     * 
     * @return [array] [用户平台信息]
     */
    public function getMe()
    {
        $userInfo = array();
        $graphObject = array();
        if(isset($this->session))
        {
            $request = new FacebookRequest($this->session, 'GET', '/me');
            $response = $request->execute();

            // get response
            $graphObject = $response->getGraphObject()->asArray();
        }
        else
        {
            $this->resetLogin();
        }
		
        if(!empty($graphObject) && isset($graphObject['id']))
        {
            $userInfo['sitemid'] = $graphObject['id'];
            $userInfo['name'] = $graphObject['name'];
            $userInfo['mnick'] = $graphObject['name'];
            $userInfo['gender'] = $graphObject['gender'];
            $userInfo['muchid'] = $graphObject['timezone'];
            $userInfo['profile'] = $graphObject['link'];
            $userInfo['location'] = $graphObject['location'];
            $userInfo['hometown'] = $graphObject['hometown'];
			$userInfo['email'] = $graphObject['email'];
			$userInfo['birthday'] = $graphObject['birthday'];
        }
        return $userInfo;        
    }

    /**
     * 获取非游戏好友列表
     *
     * @author dulu
     *
     * @return array
     */
    public function getNoAppFriendList() 
    {
        $perms = $this->getUserPerms();
        if(!in_array("user_friends", $perms))
        {
            //授权
            $this->toAuth(array("user_friends"));
        }

        $friends = array();
        $friendInfo = array();
        $i = 0;
        if(isset($this->session))
        {
            $request = new FacebookRequest($this->session, 'GET', "/me/invitable_friends");
            $response = $request->execute();

            // get response
            $friends = $response->getGraphObject()->asArray();
        }
        else
        {
            $this->resetLogin();
        }

        if(!empty($friends) && isset($friends['data']) && is_array($friends['data']))
        {
            foreach ((array)$friends['data'] as $key => $value) 
            {
                $friendInfo[$i]['id'] = $value->id;
                $friendInfo[$i]['name'] = $value->name;
                $friendInfo[$i]['url'] = $value->picture->data->url;
                $i++;
            }
        }
        else
        {
            $friendInfo = array();
        }
        return $friendInfo;
    }

    /**
     * 获取游戏好友列表
     *
     * @author dulu
     *
     * @return array
     */
    public function getAppFriendList() 
    {
        $perms = $this->getUserPerms();
        if(!in_array("user_friends", $perms))
        {
            //授权
            $this->toAuth(array("user_friends"));
        }

        $friends = array();
        $friendInfo = array();
        $i = 0;
        if(isset($this->session))
        {
            $request = new FacebookRequest($this->session, 'GET', "/me/friends");
            $response = $request->execute();

            // get response
            $friends = $response->getGraphObject()->asArray();
        }
        else
        {
            $this->resetLogin();
        }

        if(!empty($friends) && isset($friends['data']) && is_array($friends['data']))
        {
            foreach ((array)$friends['data'] as $key => $value) 
            {
                $friendInfo[$i]['id'] = $value->id;
                $friendInfo[$i]['name'] = $value->name;
                $i++;
            }
        }
        else
        {
            $friendInfo = array();
        }
        return $friendInfo;
    }

    /**
     * 获取所有好友列表
     *
     * @author dulu
     *
     * @return array
     */
    public function getAllFriendList() 
    {
        $perms = $this->getUserPerms();
        if(!in_array("user_friends", $perms))
        {
            //授权
            $this->toAuth(array("user_friends"));
        }

        $friends = array();
        $friendInfo = array();
        $i = 0;
        if(isset($this->session))
        {
            $request = new FacebookRequest($this->session, 'GET', "/me/taggable_friends");
            $response = $request->execute();

            // get response
            $friends = $response->getGraphObject()->asArray();
        }
        else
        {
            $this->resetLogin();
        }

        if(!empty($friends) && isset($friends['data']) && is_array($friends['data']))
        {
            foreach ((array)$friends['data'] as $key => $value) 
            {
                $friendInfo[$i]['id'] = $value->id;
                $friendInfo[$i]['name'] = $value->name;
                $friendInfo[$i]['url'] = $value->picture->data->url;
                $i++;
            }
        }
        else
        {
            $friendInfo = array();
        }
        return $friendInfo;
    }

    /**
     * [getUserPerms 获取用户授权信息]
     *
     * @author dulu
     *
     * @return [type]      [description]
     */
    public function getUserPerms()
    {
        $permissions = array();
        $perms = array();
        if(isset($this->session))
        {
            $request = new FacebookRequest($this->session, 'GET', "/me/permissions");
            $response = $request->execute();

            // get response
            $permissions = $response->getGraphObject()->asArray();
        }
        else
        {
            $this->resetLogin();
        }

        if(!empty($permissions) && is_array($permissions))
        {
            foreach ($permissions as $key => $value) 
            {
                if($value->status == "granted")
                {
                    array_push($perms, $value->permission);
                }
            }
        }
        return $perms;
    }

    /**
     * 发送通知小地球（用这个）
     * @from  https://developers.facebook.com/docs/games/notifications/ 官方文档
     * https://developers.facebook.com/docs/graph-api/reference/v2.2/user/notifications?locale=zh_CN
     *
     * @author dulu
     * 
     * @param array $uid FB用户ID集合
     * @param string $content 内容
     * @param string $href 链接地址 &用%26来代替
     * @return array
     */
    public function sendNotifications($uid, $content, $href='?faction=notifx')
    {
        if(empty($uid)) 
        {
            return array();
        }
        if(!is_array($uid))
        {
           $uid = array($uid);
        }

        $appid =  oo::$config['facebookAppid'];
        $secret = oo::$config['facebookAppsecret'];
        $access_token = $appid."|".$secret;
        $batchs = array();
        $sitemid = $uid[0];
        foreach ($uid as $u)
        {
          $msg = str_replace('{sitemid}', "@[{$u}]", $content);
          $batch = array();
          $batch['method'] = 'POST';
          $batch['relative_url'] = "{$u}/notifications";
          $batch['body'] = 'template='.urlencode($msg).'&href='.$href.'%26sent='.date('Ymd');
          $batchs[] = $batch;
          $sitemid = $u;
        }
        if(empty($batchs))
        {
            return array('error'=>'param error.');
        }
        else
        {
            $batchs = json_encode($batchs);
        }
       
        $ret = array('error'=>'send query exception.');
        
        try
        {
            $session = new FacebookSession($access_token);
            $request = new FacebookRequest($session, 'POST', "/{$sitemid}/notifications", array(
                'access_token' => $access_token,
                'batch' => $batchs,
            ));
            $response = $request->execute();
            // get response
            $ret = $response->getGraphObject()->asArray();
        }
        catch(Exception $e)
        {
            $ret = array('error'=>$e);
        }

        if(isset($ret['error']))
        {
            return $ret;
        }
        else
        {
            //统计成功发送的个数
            $successNum = 0;
            $loseNum = 0;
            foreach ($ret as $key => $value) 
            {
                //获取返回值
                $info = json_decode($value->body, true);
                if(isset($info['success']) && $info['success'] == 'true')
                {
                    //成功发送
                    $successNum++;
                }
                else
                {
                    //发送失败
                    $loseNum = 0;
                }
            }
            return array("success"=>"true");
        }
    }

  /**
   * 判断用户是否某个页面的like
   *
   * @author dulu
   *
   * @param string_type $uid     平台ID
   * @param string_type $pageId  页面ID
   */
  public function isFans($uid, $pageId)
  { 
      $graphObject = array();
      if(isset($this->session))
      {
          $request = new FacebookRequest(
            $this->session,
            'GET',
            "/{$uid}/likes/{$pageId}"
          );
          $response = $request->execute();
          $graphObject = $response->getGraphObject()->asArray();
      }
      else
      {
          $this->resetLogin();
      }

      if(empty($graphObject))
      {
          return 0;
      }
      else
      {
          return 1;
      }
  }

  /**
   * 获得用户本地化货币设置币种
   * 
   * @author dulu
   *
   * @return string_type $local 本地币种
  **/
  public function getUserLocalCurrency()
  {
      $local = "USD"; //默认值为USD取不到的时候
      $graphObject = array();
      if(isset($this->session))
      {
          $request = new FacebookRequest(
            $this->session,
            'GET',
            "/me?fields=currency"
          );
          $response = $request->execute();
          $graphObject = $response->getGraphObject()->asArray();
      }
      else
      {
          $this->resetLogin();
      }

      if(!empty($graphObject))
      {
          $local = $graphObject['currency']->user_currency;
      }
      return $local;
  }

  /**
   * 上传一张图片(URL)
   *
   * @author dulu
   * 
   * @param string $file          图片在服务器的文件绝对地址
   * @param string $picMessage    图片的说明
   */
  public function uploadPictureByUrl($file, $picMessage='good')
  {
      //检查授权
      $perms = $this->getUserPerms();
      if(!in_array("user_photos", $perms))
      {
          //授权
          $this->toAuth(array("user_photos"));
      }

      //创建相簿
      $graphObject = array();
      if(isset($this->session))
      {
          $request = new FacebookRequest($this->session, 'POST', '/me/photos', array(
                'url' => $file,
                'message' => $picMessage,
                'value' => 'EVERYONE'
          ));
          $response = $request->execute();
          $graphObject = $response->getGraphObject()->asArray();
      }
      else
      {
          $this->resetLogin();
      }

      return $graphObject;
  }

  /**
   * 上传一张图片(Source)
   *
   * @author dulu
   * 
   * @param string $file 获取通过文件上传照片multipart/form-data数据然后使用源参数
   * @param string $message 照片的说明
   */
  public function uploadPictureBySource($file, $picMessage='good')
  {
      //检查授权
      $perms = $this->getUserPerms();
      if(!in_array("user_photos", $perms))
      {
          //授权
          $this->toAuth(array("user_photos"));
      }

      //创建相簿
      $graphObject = array();
      if(isset($this->session))
      {
          $request = new FacebookRequest($this->session, 'POST', '/me/photos', array(
                'url' => '@'.$file,
                'message' => $picMessage,
                'value' => 'EVERYONE'
          ));
          $response = $request->execute();
          $graphObject = $response->getGraphObject()->asArray();
      }
      else
      {
          $this->resetLogin();
      }

      return $graphObject;
  }
  
  /**
   * 创建一个指定名称的相册, 如果存在就直接返回相簿ID
   *
   * @author dulu
   * 
   * @param string $name 相册名称
   * @param string $message 相册说明
   *
   * @return string 相簿ID
   */
  public function createAlbum($name, $message='')
  {
      //检查授权
      $perms = $this->getUserPerms();
      if(!in_array("user_photos", $perms))
      {
          //授权
          $this->toAuth(array("user_photos"));
      }

      //检查是否已经存在此相簿,避免重复创建同名称相簿
      $albumInfo = $this->getAlbum();
      foreach ($albumInfo as $key => $value) 
      {
          if($name == $value['name'])
          {
              return $value['id'];
          }
      }

      //创建相簿
      $graphObject = array();
      if(isset($this->session))
      {
          $request = new FacebookRequest($this->session, 'POST', '/me/albums', array(
                'name' => $name,
                'message' => $message,
                'value' => 'EVERYONE'
          ));
          $response = $request->execute();
          $graphObject = $response->getGraphObject()->asArray();
      }
      else
      {
          $this->resetLogin();
      }

      //是否创建成功,成功返回相簿ID
      if(!empty($graphObject) && is_array($graphObject) && isset($graphObject['id']))
      {
          return $graphObject['id'];
      }
      else
      {
          return 0;
      }
  }

  /**
   * [getAlbum 获得用户所有相簿信息]
   *
   * @author dulu
   * 
   * @return [array] [相簿信息(名称，ID)]
   */
  public function getAlbum()
  {
      //检查授权
      $perms = $this->getUserPerms();
      if(!in_array("user_photos", $perms))
      {
          //授权
          $this->toAuth(array("user_photos"));
      }

      $graphObject = array();
      $photosInfo = array();
      if(isset($this->session))
      {
          $request = new FacebookRequest($this->session, 'GET', '/me/albums');
          $response = $request->execute();
          $graphObject = $response->getGraphObject()->asArray();
      }
      else
      {
          $this->resetLogin();
      }

      if(!empty($graphObject) && is_array($graphObject['data']) && isset($graphObject['data']))
      {
          foreach ($graphObject['data'] as $key => $value) 
          {
              $photosInfo[$key]['name'] = $value->name;
              $photosInfo[$key]['id'] = $value->id;
          }
      }

      return $photosInfo;
  }
  /**
   * 发送一个feed
   * @param 
  **/
  public function postLink($feed)
 {
	 //检查授权
      $perms = $this->getUserPerms();
      if(!in_array("publish_actions", $perms))
      {
          //授权
          $this->toAuth(array("publish_actions"));
      }
	if($this->session) {

	  try {
		$response = (new FacebookRequest(
		  $this->session, 'POST', '/me/feed', array(
			'link' => $feed['link'],
			'message' => $feed['msg']
		  )
		))->execute()->getGraphObject();

		$id = $response->getProperty('id');
		return true;
	  } catch(FacebookRequestException $e) {
		return false;
	  }   
	}
  }
  /**
   * 测试接口
  **/
  public function testfacebook(){
		echo __CLASS__ . "_" . __METHOD__;
  }
}