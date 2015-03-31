<?php
/**
 * 用戶資料處理類
 */
class Member extends Core_Table {
	protected static $_instance = array();
	const TRYTIMES = 3;
	
	/**
	 * 返回類的實例對象
	 * @return obj/member
	 */
	public static function factory(){
		if(!is_object( self::$_instance['members'])){
			self::$_instance['members'] = new Member();
		}
		return self::$_instance['members'];
	}
	/**
	 * android游客登录自动产生ID
	**/
	public static function createSiteMid(){
		$time = time();
		$sitemid = $i = 0;
		$sql = "INSERT INTO {$this->membersitemid} SET ctime={$time}";
		while($i<self::TRYTIMES){
			Loader_Mysql::dbmaster()->query($sql);
			$sitemid = Loader_Mysql::dbmaster()->insertID();
			if($sitemid>0){
				break;
			}
		}
		return intval($sitemid);
	}
	/**
     * 根据平台ID与站点ID获取用户信息
     *
     * @param {string} $sitemid 用户平台ID
     * @param {int}    $sid     站点ID
     * @param {bool}   $inCache 是否从缓存中获取
     *
     * @return array 
     */
    public function getOneBySitemid($sitemid, $sid=100, $inCache=false)
    {           
        $sid     = Helper::uint($sid);    
        $sitemid = Loader_Mysql::dbmaster()->escape($sitemid);
        if(!$sitemid || !$sid){ 
            return array();
        }
        
        $mid = 0;
        
        $cacheKey = Core_Keys::getOneBySitemid($sitemid, $sid);
        //fb平台用memcache保存mid
        $mid = (int)Loader_Memcached::stocache()->get($cacheKey);
        if( !$mid )
        {
            $where = ($sid == 100 ? " AND sid>=100 " : " AND sid=$sid ");
            
            $query = " SELECT mid FROM $this->members WHERE sitemid='$sitemid' $where LIMIT 1 " ;
                       
            $result = Loader_Mysql::dbmaster()->getOne($query, MYSQL_ASSOC);
			
            $mid = isset($result['mid']) ? (int)$result['mid'] : 0;
            
            $mid && Loader_Memcached::stocache()->set( $cacheKey, $mid, 30*24*60*60);
            
        }
        return $mid ? $this->getOneById($mid,$inCache) : array();
    }
/**
     * 根据用户的游戏ID获取用户信息
     *
     * @param {int}  $mid      用户游戏ID
     * @param {bool} $inCache  是否取出金币，经验等信息
     *
     * @return array
     */
    public function getOneById($mid, $inCache=TRUE)
    {
        if(! $mid = Helper::uint($mid))
        {
            return array();
        }
        $cacheKey = Core_Keys::getOneById($mid);
        $userInfo = Loader_Memcached::minfo($mid)->get($cacheKey);
        $userInfo = is_array($userInfo) ? Values::uncombine(values::getmb(), $userInfo) : array();
        
        //缓存中不存在
        if($userInfo['mid'] != $mid) {
        	$sql = "SELECT *,a.mid FROM $this->members a LEFT JOIN $this->memberfield b ON a.mid=b.mid WHERE a.mid=".$mid." LIMIT 1";			
            $userInfo = Loader_Mysql::dbmaster()->getOne($sql, MYSQL_ASSOC);
            //设置缓存
            $userInfo['mid'] == $mid && Loader_Memcached::minfo($mid)->set($cacheKey, Values::combine(Values::getmb(), $userInfo), 10*24*3600);
        }
        //如果要取出金币，经验等信息
        if( $inCache)
        {
        	$aSave = $this->getUserMpSave( $mid);      	
            if( !empty( $aSave)){
            	$userInfo = array_merge($userInfo, $aSave);
            	$userInfo['mid'] == $mid && Loader_Memcached::minfo($mid)->set($cacheKey, Values::combine(Values::getmb(), $userInfo), 10*24*3600);
            }
        } 
        if($userInfo['mid']==$mid){
        	 //頭像個人地址等信息
	       //if($userInfo['sid']==13){	//FB平台 一定要限制，不然移動端這裡會出錯，除非移去端這個類裡面也有這個函數
	        $aIcon = Core_Member::factory()->getIcon($userInfo['sitemid'],$userInfo['sid'],$userInfo['unid'],$userInfo['sex'],$mid);
	        $userInfo = array_merge($userInfo, $aIcon);	//合並數組
	        
	        
	        $aKinfo = $this->getkinginfo( $mid);	//获得king queen people slave
	        $userInfo = array_merge($userInfo, $aKinfo);
	       //}
        }
       
        return $userInfo['mid'] == $mid ? $userInfo : array();
    }
    /**
     * 获取用户金币，经验，等级等信息
     * @param $mid 用户mid
     */
    public function getUserMpSave( $mid){
    	if( !( $mid = Helper::uint( $mid))){
    		return array();
    	}
    	//可以考虑用缓存储，但是存在服务器从数据库直接修改玩家金币的情况
    	$sql = "SELECT mid,IFNULL(money,0) AS money,IFNULL(exp,0) AS exp,level,IFNULL(wintimes,0) AS wintimes,IFNULL(losetimes,0) AS losetimes,IFNULL(bccoins,0) AS bccoins FROM $this->memberinfo WHERE mid=".$mid." LIMIT 1";
    	Loader_Mysql::dbmaster()->close();
    	$aInfo = Loader_Mysql::dbmaster()->getOne( $sql, MYSQL_ASSOC);
    	return empty($aInfo) ? array() : $aInfo;
    }
    /**
     * 新用户注册
	 *
     * @param {array} $userInfo 用户信息
     *
     * @return array 返回用户游戏ID
     */
    public function insert($userInfo)
    {
	    if(empty($userInfo)) {
		    return array();
		}
		Logs::factory()->debug('registerinfo',$userInfo);
        $time = time();
        $userDetail['sitemid']  = Loader_Mysql::dbmaster()->escape($userInfo['sitemid']);	//平台用户ID
        $userDetail['mnick']    = Loader_Mysql::dbmaster()->escape($userInfo['name']);		//用户昵称
        $userDetail['name']     = Loader_Mysql::dbmaster()->escape($userInfo['username']);		//用户姓名
        $userDetail['sid']      = isset($userInfo['sid']) ? Helper::uint($userInfo['sid']) : PLATFORM_ID;							//站点SID
        
        $userDetail['unid']     = Helper::uint($userInfo['unid']);							//地区ID
        $userDetail['muchid']   = Helper::uint($userInfo['muchid']);        
        
        $userDetail['exp']      = 0;
        $userDetail['level']    = 1;
        $userDetail['money']    = 0;
        
        $userDetail['icon']     = '';//Loader_Mysql::dbmaster()->escape($userInfo['icon']);
        $userDetail['middle']   = '';//Loader_Mysql::dbmaster()->escape($userInfo['middle']);
        $userDetail['big']      = '';//Loader_Mysql::dbmaster()->escape($userInfo['big']);
        
        $userDetail['profile']  = Loader_Mysql::dbmaster()->escape($userInfo['profile']);
        $location = isset($userInfo['location']['name']) ? addslashes($userInfo['location']['name']) : addslashes($userInfo['location']);
        $userDetail['location'] = iconv('GB2312','UTF-8',$location);
        $hometown = isset($userInfo['hometown']['name']) ? addslashes($userInfo['hometown']['name']) : addslashes($userInfo['hometown']);
        $userDetail['hometown'] = iconv('GB2312','UTF-8',$hometown);
        
        $userDetail['sex']         = Core_Member::factory()->getGender($userInfo['gender']);//Helper::uint($userInfo['sex']);
        $userDetail['mtime']       = $time;
        $userDetail['mstatus']	= 10;	//用户默认状态
        $userDetail['mentercount'] = 0;
        $userDetail['mcommend']  = Helper::uint($userInfo['ivitermid']);
        $userDetail['muchid']  = Helper::uint($userInfo['muchid']);
        
        extract($userDetail);
        //插入landlord_members
        $query = "INSERT INTO $this->members SET sitemid='{$sitemid}',mnick='{$mnick}',name='{$name}',sid=$sid,icon='{$icon}',middle='{$middle}',big='{$big}',mstatus={$mstatus},mactivetime={$time},mentercount={$mentercount} ON DUPLICATE KEY UPDATE mentercount=mentercount + 1";
        Loader_Mysql::dbmaster()->query( $query);
        $mid = $userDetail['mid'] = Loader_Mysql::dbmaster()->insertID();
        if($mid){
        	//插入landlord_memberfield
        	$sql = "INSERT IGNORE INTO $this->memberfield SET mid=$mid,sex=$sex,location='{$location}',hometown='{$hometown}',mcommend=$mcommend,mtime=$mtime,muchid=$muchid";
        	Loader_Mysql::dbmaster()->query( $sql);
        	
        	//初始化用户金币等信息便于下面添加金币操作
        	$sqlcoin = "INSERT IGNORE INTO $this->memberinfo SET mid=$mid,level=1";
        	Loader_Mysql::dbmaster()->query($sqlcoin);
        	
        	unset($query, $sql, $sqlcoin);
        	
        	//用户注册赠送金币
        	$chips = Core_Coin::$chips;
        	$firstin = Helper::uint($chips['firstin']);
        	$afield = array(array(0,0,$firstin));
        	$aRet = $this->addCoin($mid, 1, $afield);
        	
        	if($aRet['mid']==$mid){
        		$userDetail['money'] = $firstin;
        	} else {
        		return array();
        	}
        	//设置用户信息缓存
        	$cacheKey = Core_Keys::getOneById( $mid);
        	Loader_Memcached::minfo($mid)->set($cacheKey, Values::combine(Values::getmb(), $userDetail), 10*24*3600);
        	
        	return $userDetail;
        } else {
        	return array();
        }        
    }
    /**
     * 检查用户是否自己操作
     * @param $mid
     * @return bool(true/false)
     */
    public static function isUserSelf($mid) {
        $userInfo = Helper::getCookie('userInfo');
        if(!empty($userInfo)){	//避免某些取不到cookie
        	return ($userInfo['mid']!=$mid) ? false : true;	
        }
        return true;        
    }
    /**
	 * 用户金币操作
	 * @param $mid 用户mid
	 * @param $mode 操作类型
	 * @param $afield 金币数组 支持一次操作多个 array(array('币种类型','操作标识','操作值'))币种类型 0 金币 1 博雅币 2 经验值 操作标识 0 加 1 减 2 附值
	 * @param $remark 
	 */
	public function addCoin($mid, $mode, $afield, $remark='',$desc=''){
		$mid = Helper::uint( $mid);
		$mode = Helper::uint( $mode);
		if( !$mid || (empty($afield))){
			return false;
		}
		//取出玩家目前拥有金币等信息
		$aSave = $this->getUserMpSave( $mid);
		$time = time();
		$accountType = array(0=>"money", 1=>"bccoins", 2=>"exp");
		foreach((array)$afield as $coins){
			$lwmode = Helper::uint($coins[0]);	//操作类型
			$lflag = Helper::uint($coins[1]);	//操作标识
			$lvalue = Helper::uint($coins[2]);	//操作值
			if( !$lvalue){	
				return false;
			}
			$update = '';
			$changeInfo = array();	//記錄用戶金幣變勸信息
			$bcoinsInfo = array();	//記錄博雅幣信息
			$changemoney = $changebccoins = $changeexp = 0;			
			$field = $accountType[$lwmode];	//操作字段
			switch ($lflag) {
				case "0":	//加操作
					$aSave[$field] = $aSave[$field] + $lvalue;
					$update .= ",$field=$field + $lvalue";
					if($lwmode==0) {	//金币
						$changechips = "+$lvalue"; 
						//寫金幣變動日誌
						$changeInfo[] = array("mid"=>$mid,"wmode"=>$mode,"wflag"=>$lflag,"wchips"=>$lvalue,"wtime"=>$time,"wremark"=>$remark,"wdesc"=>$desc);
					
						//检测用户是否在破产状态下有金币加操作
						$isbank = 0;
						$abank = Bankrupt_Bankrupt::getbanktime( $mid);
						if($abank['btime']>0){
							$isbank = 1;
						}
				
						//判断用户是否在破产状态下加钱，如果是，判断是否大于200，如果大于清掉破产时间值
						if($isbank==1){
							
							if($aSave[$field] >= Bankrupt_Common::$maxbank){
								Bankrupt_Bankrupt::clearBankruptTime( $mid);
							}
						}
					}
					if($lwmode==1){		//博雅币
						$changebccoins = "+$lvalue";
						//寫博雅幣變動日誌
						$bcoinsInfo[] = array("mid"=>$mid,"wmode"=>$mode,"wflag"=>$lflag,"wchips"=>$lvalue,"wtime"=>$time,"wremark"=>$remark,"wdesc"=>$desc);
					}
					if($lwmode==2){		//经验
						$changeexp = "+$lvalue";
					}
					break;
				case "1":	//减操作
					$aSave[$field] = $aSave[$field] - $lvalue;
					$update .= ",$field=$field - $lvalue";
					if($lwmode==0) {	//金币
						$changechips = "-$lvalue"; 
						//寫金幣變動日誌
						$changeInfo[] = array("mid"=>$mid,"wmode"=>$mode,"wflag"=>$lflag,"wchips"=>$lvalue,"wtime"=>$time,"wremark"=>$remark,"wdesc"=>$desc);
					}
					if($lwmode==1){		//博雅币
						$changebccoins = "-$lvalue";
						//寫博雅幣變動日誌
						$bcoinsInfo[] = array("mid"=>$mid,"wmode"=>$mode,"wflag"=>$lflag,"wchips"=>$lvalue,"wtime"=>$time,"wremark"=>$remark,"wdesc"=>$desc);
					}
					if($lwmode==2){		//经验
						$changeexp = "-$lvalue";
					}
					break;
				case "2":	//直接附值
					$update .= ",$field = $lvalue";
					if($lwmode==0) {	//金币
						$changechips = $lvalue; 
					}
					if($lwmode==1){		//博雅币
						$changebccoins = $lvalue;
					}
					if($lwmode==2){		//经验
						$changeexp = $lvalue;
					}
					break;
			}
		}
		if($aSave["money"]<0 || $aSave["bccoins"]<0){	//出现负值
			Logs::factory()->debug("asave/forbidden",$aSave);
			return false;
		}
		if($update){	//需要更新 
			$onplay = Logs::factory()->isplay($mid);
			if(is_array($onplay) && isset($onplay['port']) && !empty($changeInfo)){ //在玩且操作是金币	
				$ret = Logs::factory()->setWinLog($mid,$changeInfo,$aSave,$onplay['port']);
				if($ret==0){
					return false;
				}
			} else {
				$update = substr($update, 1);//去掉第一个逗号
				$sql = "UPDATE $this->memberinfo SET {$update} WHERE mid=$mid LIMIT 1";
				Loader_Mysql::dbmaster()->query( $sql);
				if(!Loader_Mysql::dbmaster()->affectedRows()){
					//写日志
					Logs::factory()->debug("addmoney",func_get_args(),$time);
					return false;
				}
				//寫金幣上報日誌
				if(!empty($changeInfo)){ 
					Logs::factory()->setWinLog($mid,$changeInfo,$aSave,0);
				}
				//寫博雅幣日誌
				if(!empty($bcoinsInfo)){
					Logs::factory()->setBcoinsLog($mid,$bcoinsInfo);
				}
			}
			//返回最新值
			$ainfo['mid'] = $mid;
			$ainfo['money'] = $aSave["money"];
			$ainfo['bccoins'] = $aSave["bccoins"];
			$ainfo['exp'] = $aSave['exp'];

			return $ainfo;
			
		} else {
			return false;
		}
	}
	/**
	 * 用户登录之后更新信息
	 * 
	 * 例如，上次登录时间，VIP,会员等信息
	 * @param $userinfo array()
	 * @return bool(true/false)
	 */
	public function updateLogin( &$userinfo){
		$mid = $userinfo['mid'];
		if(! $mid = Helper::uint( $mid)){
			return false;
		}
		$flag = false;
		$today = strtotime('today');
		$now = time();
		$update = "";
		//上次登录时间 小于今天则为今天第一次登录
		if($userinfo['mactivetime'] < $today){
			$userinfo['firstLogin']	= 1;
			$update .= ",mactivetime=$now,mentercount=mentercount+1";
		} else {
			$userinfo['firstLogin']	= 0;
		}
		if($userinfo['vip']>=1 && $now>$userinfo['viptime']){	//VIP过期
			$update .= ",vip=0,viptime=0";
			$vip = 0;
			$viptime = 0;
		}
		if($userinfo['club']==1 && $now>$userinfo['clubtime']){	//表情包過期
			$update .= ",club=0,clubtime=0";
			$club = 0;
			$clubtime = 0;
		}
		if($now>$userinfo['facetime']){	//表情包过期
			$update .= ",facetime=0";
			$facetime = 0;
		}
		if($update){
			$update = substr($update,1);
		
			//有别的字段需要更新再加
			$sql = "UPDATE $this->members SET {$update} where mid=$mid LIMIT 1";
			Loader_Mysql::dbmaster()->query( $sql);
			if(Loader_Mysql::dbmaster()->affectedRows()){
				//更新 cache
				$aUser = $this->getOneById($mid,false);
				$aUser['mactivetime'] = $now;
				$aUser['vip'] = isset($vip) ? $vip : $userinfo['vip'];
				$aUser['viptime'] = isset($viptime) ? $viptime : $userinfo['viptime'];
				$aUser['club']  = isset($clubtime) ? $club : $userinfo['club'];
				$aUser['clubtime']  = isset($clubtime) ? $clubtime : $userinfo['clubtime'];
				$aUser['facetime']  = isset($facetime) ? $facetime : $userinfo['facetime'];
				if($userinfo['firstLogin']==1){
					$aUser['mentercount'] = $userinfo['mentercount'] + 1;	
				}				
				$cacheKey = Core_Keys::getOneById($mid);
				$flag = Loader_Memcached::minfo($mid)->set($cacheKey, Values::combine(Values::getmb(), $aUser), 10*24*3600);
			} else {
				$flag = false;
			}
		}
		return $flag;
	}
	/**
	 * 设置一条在线信息
	 *
	 * @param {array} $userinfo
	 * @param $bid 統計中心ID
	 * @return void
	 */
    public function setOnline(&$userinfo, $bid='')
    {
        $time = time();
        $userinfo['mtkey'] = md5($time . $userinfo['mid'] . '$#@!^');
        
		$query = " INSERT INTO {$this->membertable} 
		           SET mtkey  = '{$userinfo['mtkey']}',
				       mid    = '{$userinfo['mid']}', 
					   mttime = '{$time}',
					   bid	  = '{$bid}'  
				   ON DUPLICATE KEY 
				   UPDATE mtkey  = '{$userinfo['mtkey']}', 
				          mttime = '{$time}',bid='{$bid}' ";
        Loader_Mysql::dbmaster()->query( $query);
        
        /*
        $query = "INSERT INTO $this->memberactivetime SET mid={$userinfo['mid']},mttime=$time,bid='{$bid}' ON DUPLICATE KEY UPDATE mttime=$time,bid='{$bid}'";
        Loader_Mysql::dbmaster()->query( $query);
        */
		$query = " SELECT mtkey, 
		                  tid, 
						  svid, 
						  mtstatus
				   FROM {$this->membertable} 
				   WHERE mid='{$userinfo["mid"]}' 
				   LIMIT 1 ";
        $aInfo = Loader_Mysql::dbmaster()->getOne( $query, MYSQL_ASSOC);

        $userinfo['mtkey']    = $aInfo['mtkey'];
		$userinfo['tid']      = (int)$aInfo['tid'];
        $userinfo['svid']     = (int)$aInfo['svid'];
        $userinfo['mtstatus'] = (int)$aInfo['mtstatus'];
    }
    
    /**
     * 更新用戶資料 js取了之後發過來
     * @param $aInfo array()
     */
    public function update($aInfo){
    	$flag = 0;
    	if(empty($aInfo)){
    		return false;
    	}	
		
    	$sitemid = $aInfo['id'] ? $aInfo['id'] : $aInfo['sitemid'];
    	if(!$sitemid){
    		return $flag;
    	}
    	$time = time();
    	$sid = $aInfo['sid'] ? $aInfo['sid'] : PLATFORM_ID;
		if($aInfo['mid']){
			$userinfo = $this->getOneById($aInfo['mid'],false);
		}else{
			$userinfo = $this->getOneBySitemid($sitemid,$sid,false);
		}
    	
 		$mid = $userinfo['mid'];   	
    	
    	Logs::factory()->debug('updateuser',$aInfo['sex']);
 		$sex = $userinfo['sex'] = Core_Member::factory()->getGender($aInfo['sex']);
    	Logs::factory()->debug('updateuser',$sex);
    	$unid = $userinfo['unid'] = Helper::uint($aInfo['unid']);							//子站ID
    	$mnick = $userinfo['mnick'] =  Loader_Mysql::dbmaster()->escape( $aInfo['name']);	//用戶別名
    	$name = $userinfo['name'] =  Loader_Mysql::dbmaster()->escape( $aInfo['username']);	//用戶姓名
    	$icon = $userinfo['icon'] = Loader_Mysql::dbmaster()->escape( $aInfo['icon']);	//小頭像
    	$middle = $userinfo['middle'] =  Loader_Mysql::dbmaster()->escape( $aInfo['middle']);	//中頭像
    	$big = $userinfo['big'] =  Loader_Mysql::dbmaster()->escape( $aInfo['big']);	//大頭像
    	$location = isset($userInfo['location']['name']) ? $userInfo['location']['name'] : $userInfo['location'];
        $userDetail['location'] = iconv('GB2312','UTF-8',$location);
        $hometown = isset($userInfo['hometown']['name']) ? $userInfo['hometown']['name'] : $userInfo['hometown'];
        $userDetail['hometown'] = iconv('GB2312','UTF-8',$hometown);
    	$email = Loader_Mysql::dbmaster()->escape($aInfo['email']);									//郵件地址
    	$locale = Core_Member::factory()->getLocale($aInfo['locale']);
    	$tmpprofile = empty($aInfo['link']) ? "https://www.facebook.com/profile.php?id=".$sitemid : Loader_Mysql::dbmaster()->escape($aInfo['link']);
    	$profile = $userinfo['profile'] = $tmpprofile;	
    	
    	//添加用户email信息
    	if(!empty($email)){
    		$sql = "INSERT DELAYED INTO $this->membermail SET mid=$mid,email='{$email}',mactivetime=$time ON DUPLICATE KEY UPDATE email='{$email}',mactivetime=$time";
    		Loader_Mysql::dbmaster()->query($sql);
    	}
    	
    	//更新members表
    	$sql = "UPDATE LOW_PRIORITY $this->members SET mnick='{$mnick}',name='{$mnick}' WHERE mid=$mid LIMIT 1";
    	Loader_Mysql::dbmaster()->query($sql);
    	
    	//更新memberfield表
    	$sql = "UPDATE LOW_PRIORITY $this->memberfield SET sex=$sex,location='{$location}',hometown='{$hometown}',muchid=$locale WHERE mid=$mid LIMIT 1";
    	Logs::factory()->debug('updateuser',$sql);
    	Loader_Mysql::dbmaster()->query($sql);
    	
    
    	//更新cache
    	$cacheKey = Core_Keys::getOneById($mid);
    	$flag = Loader_Memcached::minfo($mid)->set($cacheKey, Values::combine(Values::getmb(), $userinfo), 10*24*3600);
    	return $flag;
    }
    /**
     * 获取多个用户的资料
     *
     * @param {array} $mids 用户游戏ID列表
     *
     * @return array
     */
    public function getAllByIds($mids)
    {
        if(!is_array($mids)){
            return array();
        }
        $mids = array_unique($mids);
        
        $keys = $noKeys = $list = $return = array();
        
        foreach($mids as $mid){
            $noKeys[$mid]   = $mid; //没有找到用户资料的ID
            $keys[$mid%3][] = Core_Keys::getOneById($mid); //[散列][列表] = 缓存键
        }
        
        foreach($keys as $hash=>$keys){
            $tmpList = Loader_Memcached::minfo($hash)->getMulti($keys);
            $tmpList = is_array($tmpList) ? $tmpList : array();
            
            foreach($tmpList as $key=>$user){
                $list[$user[0]] = Values::uncombine(Values::getmb(), $user);
                unset($noKeys[$user[0]]); //已经找到该用户	//注释掉。 就可以去取缓存信息
            }
        }
      	//沒有找到的，再找一遍
        foreach($noKeys as $mid){
            $list[$mid] = Member::factory()->getOneById($mid, true);
        }
        
        foreach($mids as $mid){
            empty($list[$mid]) ? '' : ($return[] = $list[$mid]);
        }
        
        return (array)$return;
    }
	
	/**
	 * 根据sitemid获取多个用户信息
	 * 
	 * @param {array} $sitemids  平台ID
	 * @param {int}   $sid       
	 * @param {int}   $incache
	 *
	 * @return array
	 */
	public function getMultiBySitemids($sitemids, $sid=100, $incache=TRUE)
	{
	    if(empty($sitemids))
        {
		    return array();
		}
		
		$list = array();
		
		foreach($sitemids as $key=>$sitemid)
		{
		    $result = $this->getOneBySitemid($sitemid, $sid, $incache);
		    
			if(!empty($result))
			{
			    $list[$sitemid] = $result;
			}
		}
		
        return $list;		
	}
	/**
	 * 设置站点BID
	 * @param $mid
	 */
	public function setBid($mid){
		$cacheKey = Core_Keys::mkbid( $mid);
		Loader_Redis::redisbid()->set($cacheKey, Core_Game::$bid, false, false);		
	}
	/**
	 * 從數據庫拿數據生成排行數據
	 * @param $num 數量
	 * @param $sid 站點ID
	 * @param $type 2 某站點金幣排行 3 所有站點金幣排行 4 某站點等級排行榜 5 所有站点等级排行
	 */
	public function getTop($num,$sid=0,$type=2){
		if(!( $num = Helper::uint( $num)) || !in_array($type,array(1,2,3,4,5,6))){
			return array();
		}
		
		$cacheKey = Core_Keys::mksort( $num,$sid,$type);
		$amids = Loader_Memcached::cache()->get($cacheKey);
		if(empty($amids)){
			//金币排行
			if($sid>0){
				switch($type){
					case "2":
						$sql_and = " AND a.sid=".$sid;
						$sql_order = "  b.money DESC";
						break;	
					case "4":
						$sql_and = " AND a.sid=".$sid;
						$sql_order = "  b.level DESC";
						break;
				}				
			} else {
				switch ($type) {
					case "3":
						$sql_and = "";
						$sql_order = " b.money DESC";
						break;
					case "5":
						$sql_and = "";
						$sql_order = " b.level DESC";
						break;
				}
			}			
			$sql = "SELECT a.mid FROM $this->members a LEFT JOIN $this->memberinfo b ON a.mid=b.mid where 1=1 {$sql_and} ORDER BY {$sql_order} LIMIT {$num}";
			$aRet = Loader_Mysql::dbmaster()->getAll( $sql, MYSQL_ASSOC);
			//Logs::factory()->debug('gettop',$sql,$aRet);
			foreach((array)$aRet as $mid){
				$amids[] = $mid;
				$this->getOneById( $mid,true);
			}
			Loader_Memcached::cache()->set($cacheKey,$amids,7200);	//两個小時
		}
		return (array)$amids;
	}
	/**
	 * 用戶完成新手教程更新
	 * @param $mid
	 * @param $param 要更新的參數
	 */
	public function updateUserParam( $mid, $param=array()){
		if(!$mid = Helper::uint( $mid)){
			return false;
		}
		$update = "";
		if(isset($param['mtaskcount'])){
			$update .= ",mtaskcount=mtaskcount+1";
		}
		if($update){
			$update = substr($update,1);
			$query = "UPDATE $this->members SET {$update} WHERE mid=$mid LIMIT 1";
			Loader_Mysql::dbmaster()->query( $query);
			if(Loader_Mysql::dbmaster()->affectedRows()){
				//更新 cache
				$aUser = $this->getOneById($mid,false);
				$aUser['mtaskcount'] += 1 ;
				$cacheKey = Core_Keys::getOneById($mid);
				$flag = Loader_Memcached::minfo($mid)->set($cacheKey, Values::combine(Values::getmb(), $aUser), 10*24*3600);
			} else {
				$flag = false;
			}
		} 
		return $flag;
	}
	/**
	 *  用户升级 主要用于核实用的等级
	 */
	public function uplevel( &$aUser){
		if( empty($aUser) ){
			return false;
		}
		$aUser['level'] = Helper::uint( $aUser['level']);
		$aUser['exp'] = $aUser['wintimes'] * 2 + $aUser['losetimes'];
		
		$grade = 0; 
		//获得级别配置
		$levels = Core_Level::getLevel();
		foreach( (array)$levels as $key=>$aLevel ) {
			if( $aUser['exp'] >= $aLevel[1] ){
				$grade = $aLevel[0];	//当前级别
			} else {
				break;
			}
		}
		if( $grade > 0 && $grade <=50 ){ 
			if( $grade == $aUser['level'] ) { //如果计算出来的等级和userinfo 里的一致，则不更新
				return false;
			}			
			$query = "UPDATE $this->memberinfo SET exp={$aUser['exp']}, level=$grade WHERE mid='{$aUser['mid']}' LIMIT 1";
			Loader_Mysql::dbmaster()->query( $query);
			
			//重置userinfo里的level
			$aUser['level'] = $grade;
		}
	}
	/**
	 * 获得玩家king queen people slave胜负数量
	 *
	 * @param unknown_type $mid
	 */
	public function getkinginfo($mid){
		if(!$mid=Helper::uint( $mid)){
			return array();
		}
		$cachekey = Core_Keys::mkkqpsnum( $mid);
		$kinfo = Loader_Memcached::minfo( $mid)->get($cachekey);
		$kinfo = is_array($kinfo) ? Values::uncombine(values::getWin(), $kinfo) : array();
		if($kinfo['mid']!=$mid){	//缓存中不存在
			$sql = "SELECT mid,king,queen,people,slave FROM {$this->memkingslave} WHERE mid={$mid} LIMIT 1";
			$kinfo = Loader_Mysql::dbslave()->getOne( $sql, MYSQL_ASSOC);
			if(empty($kinfo)){
				$kinfo = array("mid"=>$mid,"king"=>0,"queen"=>0,"people"=>0,"slave"=>0);
			}
			Loader_Memcached::minfo( $mid)->set($cachekey,Values::combine(Values::getWin(),$kinfo),1800);
		}
		return $kinfo['mid']==$mid ? $kinfo : array();
	}
}
?>
