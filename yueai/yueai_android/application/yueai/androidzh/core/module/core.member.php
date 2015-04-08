<?php
/**
 * 此文件为处理用户相关功能
**/
defined('YUEAI') or exit('Access Denied！');
class Core_Member extends Core_Table{
	
	protected static $_instance = array();
	
	//单实便对象
	public static function factory(){
		if(!isset(self::$_instance['coremember']) && !is_object(self::$_instance['coremember'])){
			self::$_instance['coremember'] = new Core_Member();
		}
		return self::$_instance['coremember'];
	}
	
	//http auth验证
	public function httpauth($param){
		
		return true;
	}
	
	//登录初始化
	public function loadinit(&$aUser,$api=1,$version="1.0.0"){
		//首次登录
		if($aUser['firstLogin']==1){
			//设置mid缓存
			$cacheKey = Core_Keys::getOneBySitemid($aUser['sitemid'], $aUser['sid']);
			Loader_Memcached::stocache()->set($cacheKey,$mid,3*24*3600);
			
			//设置在线信息
			Member::factory()->setOnline( $aUser);
		}
		
		$aUser['sigRequest'] = Logs::encrypt(json_encode(array("mid"=>$aUser['mid'],"sitemid"=>$aUser['sitemid'],"api"=>$api,"sid"=>$aUser['sid'],'version'=>$version)));
		
		return (array)$aUser;
	}
	
	/**
	 * 取用户头像信息
	 * 用户头像信息统一放在根目录/alidata/www/default/userIcon/mid%100/mid_icon.jpg mid_middle.jpg mid_big.jpg
	 * @return array()
	**/
	public function getIcon( $mid){
		$aIcon = array();
		$mod = $mid % 100;
		$appUrl = Core_System::$appUrl;
		$timebefore = icon_upload::factory()->setIconTime($mid,0);
		
		$aIcon['icon'] = $appUrl . "usericon/" . $mod . "/" . $mid . "_icon.jpg?v=" . $timebefore;
		$aIcon['middle'] = $appUrl . "usericon/" . $mod . "/" . $mid . "_middle.jpg?v=" . $timebefore;
		$aIcon['big'] = $appUrl . "usericon/" . $mod . "/" . $mid . "_big.jpg?v=" . $timebefore;
		
		return $aIcon;
	}
	
	/**
	 * 用户性别
	**/
	public function getGender($gender){
		$aMale = array('男','male');
		$aFemale = array('女','female');
		
		return in_array($gender,$aMale,true) ? 0 : (in_array($gender,$aFemail,true) ? 1 : 2);
	}
	/**
	 * 格式化用户信息输出
	**/
	public function formatUserInfo( &$userinfo){
		$ainfo = Core_User::getUserOtherInfo();
		$userinfo['star'] = $ainfo['star'][intval($userinfo['star'])];
		$userinfo['blood'] = $ainfo['blood'][intval($userinfo['blood'])];
		$userinfo['perfession'] = $ainfo['star'][intval($userinfo['perfession'])];
		$userinfo['money'] = $ainfo['money'][intval($userinfo['money'])];
		$userinfo['part'] = $ainfo['part'][intval($userinfo['part'])];
		$userinfo['marry'] = $ainfo['marry'][intval($userinfo['marry'])];
		$userinfo['interest'] = $ainfo['interest'][intval($userinfo['interest'])];
		$userinfo['style'] = $ainfo['style'][intval($userinfo['style'])];
		$userinfo['province'] = $ainfo['province'][intval($userinfo['province'])];
	}
	/**
	 * 用户资料更新
	 * @param int $mid
	 * @param array $fields
	**/
	public function updateInfo($mid,$fields=array()){
		$update = "";
		foreach($fields as $key=>$val){
			if(is_null($val)){
				continue;
			}
			$update .= $key."='".$val."',";
		}
		$update = substr($update,0,-1);
		$sql = "UPDATE {$this->memberinfo} SET {$update} WHERE mid={$mid} LIMIT 1";
		$flag = Loader_Mysql::dbmaster()->query($sql);
		if($flag){	//更新成功 更新缓存
			$aUser = Member::factory()->getOneById($mid,false);
			foreach($fields as $key=>$val){
				if(isset($aUser[$key])){
					$aUser[$key] = $val;
				}
			}
			//更新缓存	
			$cacheKey = Core_Keys::getOneById($mid);
			return Loader_Memcached::minfo($mid)->set($cacheKey, Values::combine(Values::getmb(), $aUser), 3*24*3600);
		} 
		return false;
	}
}

?>