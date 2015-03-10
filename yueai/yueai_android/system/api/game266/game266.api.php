<?php !defined('BOYAA') AND exit('Access Denied!');

include '266game.php';

class GAME266_API extends BoyaaGame
{
	private static $_instance = array();
	
 	/**
     * 创建一个实例
     *
     * @return Myzone_API
     */
    public static function factory()
    {
        if(!is_object(self::$_instance['GAME266_API']))
        {
            self::$_instance['GAME266_API'] = new self(Core_Game::$appId, Core_Game::$appKey);    
        }
        return self::$_instance['GAME266_API'];
    }
	/**
	 * 获取用户信息
	 * @return	Array
	 */
	public  function getMe()
	{
		if (!parent::checkSign())
		{
			return array();
		}
		
		$info = parent::getUserInfo();

		$userInfo['sitemid'] = $info['data']['id'];
	    $userInfo['mnick'] = $info['data']['name'];
	    $userInfo['icon'] = self::fomatePic($info['data']['smallpicurl']);
	    $userInfo['middle'] = self::fomatePic($info['data']['mediumpicurl']);
	    $userInfo['big'] =self::fomatePic($info['data']['largepicurl']);
	    $userInfo['gender'] = $info['data']['gender'];
    	
	    if ($info['code'] != 0)
	    {
	    	echo $info['data'];
	    }
	    
		return $info['code'] == 0 ? $userInfo : array();
	}
	/**
	 * 获取好友列表
	 * @param	$key {string}	与平台通信的时候，有时候需要这个key，为了所有平台的flash参数保持一致 这个key统一用$_GET['openkey'];
	 * @return	Array
	 */
	public function getFriendList($key="")
	{
				
		$key && self::setSkey($key);
		
		$result = parent::getPlatFriendList(array('platname'=>'facebook'));

		$friends = array();
		
		if ($result['code'] == 0 && is_array($result['data']))
		{
			foreach ($result['data'] as $firend)
			{
				$friends[$firend['fuid']]['nick'] = $firend['uname'];
				$friends[$firend['fuid']]['icon'] = "http://game.266.com/".$firend['upic']."?1";
			}
		}
		
		return $friends;
	} 
	/**
	 * 格式化站点PIC
	 * @param unknown_type $pic
	 */
	private function fomatePic($pic)
	{
		return PLATFORM_ID == '204' ? "http://game.266.com/".$pic : $pic;
	}
}
