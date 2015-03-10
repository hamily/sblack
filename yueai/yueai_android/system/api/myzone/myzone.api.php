<?php !defined('BOYAA') AND exit('Access Denied!');


require_once   dirname(__FILE__)."/myzone.php"; 
/**
 * @author PaulLi
 *
 */
class Myzone_API extends Myzone
{
	
	private static $_instance = array();
	
 	/**
     * 创建一个实例
     *
     * @return Myzone_API
     */
    public static function factory()
    {
        if(!is_object(self::$_instance['Myzone_API']))
        {
            self::$_instance['Myzone_API'] = new self(Core_Game::$gameid, Core_Game::$gamekey);    
        }
        
        return self::$_instance['Myzone_API'];
    }
	
	function __construct($gameid, $gamekey)
	{
		parent::__construct($gameid, $gamekey);
	}
	
	public function getUid()
	{
		return parent::getUser();
	}
	/**
	 * 获取用户信息
	 * 
	 */
	public function getMe()
	{
		return parent::getUserInfo();
	}
	
	public function payCost($param)
	{
		$result = parent::payCost($param);
		
		return $result['result'];
	}
	
	
	
}