<?php !defined('BOYAA') AND exit('Access Denied!');
error_reporting(7);
require_once('WeiyouxiClient.php');

class Weibo_Api extends WeiyouxiClient
{
    protected $me;
    
    protected $coveredMe = array();
    
    protected $coverFields = array();
    
    protected static $_instance = array();

        
    /**
     * 创建一个单例
     *
     * @author 黄国星
     *
     * @return Weibo_Api
     */
    public static function factory()
    {
        if(!is_object(self::$_instance['weibo_api']))
        {
            self::$_instance['weibo_api'] = new Weibo_Api(Core_Game::$key, Core_Game::$secret);
            if ($_REQUEST['session_key'])
            {
                    self::$_instance['weibo_api']->setAndCheckSessionKey($_REQUEST['session_key']);
            }
        }
        return self::$_instance['weibo_api'];
    }
    
    /**
     * 获取用户平台ID
     *
     * @author 黄国星
     *
     * @return int
     */
    public function getUid()
    {
        return $this->getUserId();
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
        $this->me = $this->get('user/show');
        
        $this->setCoverFields($fields);
        
        $this->coverMe();
        
        return $this->coveredMe;
    }
    
    /**
     * 获取好友列表
     * 
     * @author 孙进辉
     *
     * @return array()
     */
    public function getContact()
    {
        $data = array();
        
        $app_uids = $this->get('user/app_friend_ids');
        $x_tmp = $this->get('user/friends', array('count' => '200'));

        foreach((array)$x_tmp['users'] as $x) {
            $_is_apped = 0;

            if( in_array($x['id'], $app_uids ) ){
                $_is_apped = 1;
            }
            
            $data[] = array(
                'sitemid' => $x['id'], 
                'name' => $x['name'],
                'is_app' => $_is_apped, 
                'icon' => $x['profile_image_url']
            );
        }
        /*
        //未上正式前模拟数据
        if(empty($data) || !is_array($data)){
            $data = array(
                array('sitemid' => '1253419733', 'name' => 'TinyDark60', 'is_app' => 1, 'icon' => 'http://tp2.sinaimg.cn/1253419733/50/5611375228/1'),
                array('sitemid' => '1359719107', 'name' => 's_jinhui', 'is_app' => 0, 'icon' => 'http://tp4.sinaimg.cn/1359719107/50/0/1'),
                array('sitemid' => '2159386767', 'name' => '黄-国-星', 'is_app' => 1, 'icon' => 'http://tp4.sinaimg.cn/2159386767/50/5608189684/1'),
            );
        }
	*/
        return $data;
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
     * @return void
     */
    protected function setCoverFields(array $fields)
    {
        $this->coverFields = array_unique(array_merge($this->coverFields, $fields));
    }
    
    /**
     * 检查是为是粉丝
     *
     * @author 黄国星
     *
     * @return bool 是粉丝返回TRUE,否则返回FALSE
     */
    public function isFans()
    {
        $result = $this->get('application/is_fan');

        return (bool)$result['flag'];        
    }
    
}