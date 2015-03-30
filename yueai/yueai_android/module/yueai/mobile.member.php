<?php
! defined ( 'BOYAA' ) and exit ( 'Access Denied!' );

/**
 * 移动游客登陆核心业务   
 * 经历了ios 版本的不断变迁  勿喷坑了好几代人了 
 * ios6前是靠 deviceno 设备号作为游客登陆标识
 * ios6版本 由于设备号取不到 	换为macid 作为游客登陆标识 
 * 			(注:此macid有2个版本 老macid和macaddress 后面是用macaddress覆盖macid的)
 * ios7版本 由于设备号和macid地址都被屏蔽 换做vendorid作为游客登陆标识
 * 			(注:此时客户端的macid参数会统一的传32字符 有的版本没有)
 * 注:每次变更前都会对下一代唯一标识做收集预埋
 * 请勿随意改动
 * @author HuXiaowei,HarryYi,MikePeng  出门请带刀
 * 
 */
class Mobile_Member extends Core_Table {
	/*实例化对象*/
	protected static $_instance;
	/*程序错误日志上报*/
	const ISOPENLOGS = false;
	/**  缓存过期天数  **/
    const GUESTTIMEPUTDAY = 7;
	/** IOS客户端标识   **/
	const IOSCLIENTTYPE = 1;
	/** android 客户端标识 **/
	const ANDROIDCLIENTTYPE = 2;
	/** cache类型标识  **/
	private $aCacheKeyType = array();
	/**安卓游客字段**/
	private static $ANDROIDGUESTKEY = array('sitemid','device_no','macid');
	/**ios游客字段**/
	private static $IOSGUESTKEY = array('sitemid','device_no','macid','openudid','vendorid','adsid');
	/** 选取的游客cache字段标识 **/
	private static $CACHETYPE = array('device_no'=>1,'macid'=>2,'macaddress'=>3,'vendorid'=>4);
	/** 需要屏蔽的macid值 **/
	private static $macid_blacklist = array(
					'macid'		=>array('74be16979710d4c4e7c6647856088456',	//md5(md5(''))
										'dcfcd07e645d245babe887e5e2daa016', //md5(md5('0'))
										'c7be0870d36ee31f35cb9cf7aedc6b8c', //md5(md5('020000000000'))
									),
					'macaddress'=>array('d41d8cd98f00b204e9800998ecf8427e',	//md5('')
										'cfcd208495d565ef66e7dff9f98764da',	//md5('0')
										'0f607264fc6318a92b9e13c65db7cd3c', //md5('02:00:00:00:00:00')
									),
				);
	/** 需要屏蔽掉的vendorid值 **/
	private static $vendorid_blacklist = array(	'cfcd208495d565ef66e7dff9f98764da',//md5('0')
												'd41d8cd98f00b204e9800998ecf8427e',//md5('')
												'9f89c84a559f573636a47ff8daed0d33',
												);
	/**
	 * 创建一个实例
	 *
	 *        
	 * @return object Mobile_Member
	 */
	public static function factory() {
		if (! is_object ( self::$_instance ['mobile_member'] )) {
			self::$_instance ['mobile_member'] = new Mobile_Member ();
		}
		
		return self::$_instance ['mobile_member'];
	}

	/******************  新游客登录 start  2013/04/11 **************************/
	
	/**
	 * ios游客登陆
	 * @param int		$api	                   客户端类型
	 * @param string	$version       当前版本号
	 * @param string	$deviceno      设备号
	 * @param array 	$guestios      array(sitemid,deviceno,macid,openudid,vendorid,adsid)
	 * @return array()|array(sitemid,deviceno,macid,openudid,vendorid,adsid)
	 */
	public function iosGuestLogin( $api,$version,$guestios)
	{
		$api = Helper::uint( $api );
		$version = Loader_Mysql::DBMaster()->escape( $version );
	
		if( empty($api)||empty($version) ){
			$this->error_log('ios_guest','api or version not find!',$_REQUEST ['api']);
			return false;
		}
		
		$result  = array();
		$deviceno = $macid = $openudid = $macaddress = $boyaaopenudid = $vendorid = $adsid = $sitemid = '';
		
		extract($guestios);

		$deviceno = Loader_Mysql::DBMaster()->escape( $deviceno ); //设备号
		$macid	  = empty( $macid ) ? '' : md5( $macid );		   //老的macid，ios传的是无冒号分割的MD5，Android明文传递
		$openudid = empty( $openudid ) ? '' : md5( $openudid );	  //老的openudid，ios传的是错误的
		/*ios6以前使用deviceno ios6 使用macid ios7使用vendorid*/
		if(	empty($deviceno)&&empty($macid)&&empty($vendorid) ){
			$this->error_log('ios_guest','deviceno|macid|vendorid not find!',$_REQUEST ['api']);
			return false;
		}
		$flag_ver = $this->GuestCheckVersion( $api,$version ); //判断是不是最新的游客流程版本
		if( $flag_ver ){//是	
			
			$macaddress		= empty($macaddress) ? '' : Loader_Mysql::DBMaster()->escape($macaddress);		 //new macid地址，ios客户端都已经MD5	
			$boyaaopenudid	= empty($boyaaopenudid) ? '' : Loader_Mysql::DBMaster()->escape($boyaaopenudid); //new openudid，ios客户端都已经MD5		
			$vendorid		= empty($vendorid) ? '' : Loader_Mysql::DBMaster()->escape($vendorid);			 //厂商id，ios客户端都已经MD5
			$adsid			= empty($adsid) ? '' : Loader_Mysql::DBMaster()->escape($adsid);				 //广告id，ios客户端都已经MD5

			if(empty($boyaaopenudid)||empty($vendorid) ){ //广告id有可能获取不到
				$this->error_log('ios_guest','vendorid is not find!',$_REQUEST ['api']);
				return false;
			}
		}
		/**********  客户端会传一个md5空值过来  *************/
		if(!empty($macid) && in_array($macid, self::$macid_blacklist['macid']))
			unset($macid);
		if(!empty($macaddress) && in_array($macaddress, self::$macid_blacklist['macaddress']))
			unset($macaddress);
		if (!empty($vendorid) && in_array($vendorid, self::$vendorid_blacklist)) 
			unset($vendorid);
		/**********    end   ********************************/
		/**************   各种唯一值查找sitemid start ************/
		if( !empty($deviceno) && (strlen($deviceno)==32) ) //设备号存在，则取设备号
			$result = $this->getSitemidByDevice( $deviceno ,self::IOSCLIENTTYPE);
	
		if( (!empty($macid) || !empty($macaddress)) && empty($result) ) //取macid来找
			$result = $this->getSitemidByMacid( $macid,$macaddress,self::IOSCLIENTTYPE);
		
		if ( !empty($vendorid) && empty($result) ) //取vendorid来找
			$result = $this->getSitemidByVendorid( $vendorid,self::IOSCLIENTTYPE);
		/*************** 各种唯一值查找sitemid  end   ************/

		/********************** 新建游客 start *****************/
		if( empty($result) ){
			$sitemid = Global_ID::factory()->Global_SITEMID_ID_Generator();
			
			if( empty($sitemid)){ //注册失败
				$this->error_log('ios_guest','sitemid create error!');
				return array ();
			}
			$macid	= empty($macid) ? $sitemid : $macid;
			$query_insert = " INSERT INTO {$this->guestios}
											SET sitemid='{$sitemid}',device_no='{$deviceno}',macid='{$macid}',openudid='{$openudid}',vendorid='{$vendorid}',adsid = '{$adsid}' ";
			if( $flag_ver ){
				//ios7客户端会传一个MD5('')为空的值
				$macaddress		= empty($macaddress) ? $sitemid : $macaddress;
				$query_insert = " INSERT INTO {$this->guestios}
											SET sitemid='{$sitemid}', device_no='{$deviceno}',macid='{$macaddress}',openudid='{$boyaaopenudid}',vendorid='{$vendorid}',adsid='{$adsid}' ";	
			}
			
			Loader_Mysql::DBMaster()->query($query_insert);
			$flag = (int)Loader_Mysql::DBMaster()->affectedRows();
			
			if(empty($flag)){//注册失败
				$this->error_log('ios_guest','Failed to perform create guest sql!',$query_insert);
				return array ();
			}

			//注册成功返回信息
			return array(	
						'sitemid' =>$sitemid,	'device_no'=>$deviceno,
						'macid'   =>$macid,		'openudid' =>$openudid,
						'vendorid'=>$vendorid,	'adsid'    =>$adsid
					);
		}
		/********************** 新建游客 end *****************/

		//找到了数据，再来看要不要更新
		if( $flag_ver ){
			/*取不到macid地址的 用sitemid填充过不需要更新*/
			$needupdate = ( ( ($result['macid']!=$macaddress) && ($result['macid']!=$result['sitemid']) ) && $macaddress )
			 ||( ($result['openudid']!=$boyaaopenudid) && $boyaaopenudid )
			 ||( $result['vendorid']=='' )
			 ||( $result['adsid']=='' );
		}else{
			/*取不到macid地址的 用sitemid填充过不需要更新*/
			$needupdate = ( (($result['macid']!=$macid) && ($result['macid']!=$result['sitemid']) ) && $macid);
		}
	
		if( $needupdate ){ //需要更新
			
			if( $flag_ver ){
				$macid = $macaddress;
				$macid = $macid ? $macid : $result['sitemid'];	//用sitemid填充
				$openudid = $boyaaopenudid;
				$updatequery = " UPDATE {$this->guestios} SET macid='{$macid}',openudid='{$boyaaopenudid}',vendorid='{$vendorid}',adsid ='{$adsid}' WHERE sitemid='{$result['sitemid']}' LIMIT 1 ";	
			}else {
				$updatequery = " UPDATE {$this->guestios} SET macid='{$macid}',openudid='{$openudid}',vendorid='{$vendorid}',adsid = '{$adsid}' WHERE sitemid='{$result['sitemid']}' LIMIT 1 ";
			}	

			Loader_Mysql::DBMaster()->query($updatequery);
			
			if (Loader_Mysql::DBMaster()->affectedRows())
			{
				$result['macid'] = $macid;
				$result['openudid'] = $openudid;
				$result['vendorid'] = $vendorid;
				$result['adsid'] = $adsid;
				!empty($this->aCacheKeyType) && $this->setGuestCache(self::IOSCLIENTTYPE, $this->aCacheKeyType['key'], $this->aCacheKeyType['value'], $result);
			}
		}
		return is_array($result) ? $result : array();
	}
	
	/**
	 * Android游客登陆
	 * @param string	$aGuest	  游客登陆的组合参数array(deviceno,macid)
	 * @return array()|array(sitemid,deviceno,macid)
	 */	
	public function androidGusetLogin( $guestandroid )
	{
		$result = array();
		$deviceno = $macid = $sitemid = '';
		extract($guestandroid);
		$deviceno = Loader_Mysql::DBMaster()->escape( $deviceno ); //设备号
		$macid	  = empty( $macid ) ? '' : md5( $macid );		   //macid，Android明文传递,所以需要MD5

		if(	empty($deviceno)&&empty($macid) ){
			$this->error_log('android_guest','deviceno or macid not find!',$_REQUEST ['api']);
			return false;
		}
		if( !empty($deviceno) && (strlen($deviceno)==32) ) //设备号存在，则取设备号
			$result = $this->getSitemidByDevice( $deviceno ,self::ANDROIDCLIENTTYPE);

		if( empty($result) ) //取macid来找
			$result = $this->getSitemidByMacid( $macid ,"",self::ANDROIDCLIENTTYPE);
		
		if( empty($result) ){ //都没有找到，则走注册流程
			
			$sitemid = Global_ID::factory() -> Global_SITEMID_ID_Generator();
			
			if( empty($sitemid)){ //注册失败
				$this->error_log('android_guest','sitemid create failed!');
				return array ();
			}	
			$query_insert = " INSERT INTO {$this->guestandroid}
											SET sitemid='{$sitemid}', device_no='{$deviceno}',macid='{$macid}'";
			Loader_Mysql::DBMaster()->query($query_insert);
			$flag = (int)Loader_Mysql::DBMaster()->affectedRows();
		
			if( empty($flag) ){ //注册失败
				$this->error_log('android_guest','Failed to perform create guest sql!',$query_insert);
				return array ();
			}
			//注册成功返回信息
			return array(	
						'sitemid' =>$sitemid,'device_no'=>$deviceno,'macid'=>$macid,
					);
		}
		//找到了数据，再来看要不要更新
		$needupdate = $result['macid']!=$macid;
	
		if( $needupdate ){ //需要更新
			$updatequery = " UPDATE {$this->guestandroid}
									SET macid='{$macid}'
									WHERE sitemid='{$result['sitemid']}' LIMIT 1 ";	
					
			Loader_Mysql::DBMaster()->query($updatequery);
			
			if (Loader_Mysql::DBMaster()->affectedRows())
			{
				$result['macid'] = $macid;
				empty($this->aCacheKeyType) && $this->setGuestCache(self::ANDROIDCLIENTTYPE, $this->aCacheKeyType['key'], $this->aCacheKeyType['value'], $result);
			}
		}
		return is_array($result) ? $result : array();
	}
	
	/**
	 * 根据设备号查找用户的信息
	 *@param string $deviceno 客户端版设备号
	 *@param int    $type     客户端类型1 ios 2 安卓
	 *@return false|array(sitemid,deviceno,macid,openudid,vendorid,adsid)
	 */
	private function getSitemidByDevice( $deviceno ,$type=1)
	{
		if( empty($deviceno)|| strlen($deviceno)!=32 )
			return false;
			
		$this->aCacheKeyType = array('key'=>'device_no','value'=>$deviceno);
		
		//获取缓存
		$result = $this->getGuestCache($type,$this->aCacheKeyType['key'], $this->aCacheKeyType['value']);
		
		if(empty($result) || !is_array($result))
		{
			$table = $this->guestios;
			
			if($type === self::ANDROIDCLIENTTYPE)
				$table = $this->guestandroid;
				
			$query  = "SELECT * FROM {$table} WHERE device_no='{$deviceno}' LIMIT 1";

			$result = Loader_Mysql::DBMaster()->getOne($query);
			
			!empty($result) && $this->setGuestCache($type,$this->aCacheKeyType['key'], $this->aCacheKeyType['value'], $result);
		}
		
		return is_array($result) ? $result : array();
	}

	/**
	 * 根据设备号查找用户的信息
	 *
	 *@param string $macid			ios为老的mac地址，Android的不区分新老
	 *@param string $macaddress		仅ios有的新mac地址
	 *@param int    $type           客户端类型1 ios 2 安卓
	 *@return false|array(sitemid,deviceno,macid,openudid,vendorid,adsid)
	 */	
	private function getSitemidByMacid( $macid,$macaddress='',$type=1 )
	{
		$macid		= Loader_Mysql::DBMaster()->escape( $macid ); //老的macid
		
		$macaddress = Loader_Mysql::DBMaster()->escape( $macaddress ); //new macid地址
		
		if( empty($macid)||strlen($macid)!=32 )
			return false;
			
		$cachetype = '';
		
		$result = array();
		 
		$ismacaddress = false;	
			
		if(!empty($macid) && (strlen($macid)==32) && empty($macaddress))
		{
			$this->aCacheKeyType = array('key'=>'macid','value'=>$macid);
			$result = $this->getGuestCache($type,$this->aCacheKeyType['key'], $this->aCacheKeyType['value']);
		}
		if ( (strlen($macid)== 32) && (strlen($macaddress) == 32))	
		{
			$ismacaddress = true;
			$this->aCacheKeyType = array('key'=>'macaddress','value'=>$macaddress);
			$result = $this->getGuestCache($type,$this->aCacheKeyType['key'], $this->aCacheKeyType['value']);
		}
	
		if (!is_array($result) || empty($result['sitemid']))	
		{
			$table = $this->guestios;
			
			if($type === self::ANDROIDCLIENTTYPE)
				$table = $this->guestandroid;
			
			$query = "SELECT * FROM {$table} WHERE macid='{$macid}' LIMIT 1";
		
			if( $ismacaddress == true )
				$query = "SELECT * FROM {$table} WHERE macid='{$macid}' OR macid='{$macaddress}' LIMIT 1";
			
			$result = Loader_Mysql::DBMaster()->getOne($query);
			
			$this->setGuestCache($type,$this->aCacheKeyType['key'], $this->aCacheKeyType['value'], $result);
		}	
			
		return is_array($result) ? $result : array();
	}

	/**
	 * 通过厂商ID查找用户sitemid
	 * @param  [type]  $vendorid [description]
	 * @param  integer $type     [description]
	 * @return [type]            [description]
	 */
	public function getSitemidByVendorid($vendorid,$type=1){

		if( empty($vendorid)|| strlen($vendorid)!=32 )
			return false;
			
		$this->aCacheKeyType = array('key'=>'vendorid','value'=>$vendorid);
		
		//获取缓存
		$result = $this->getGuestCache($type,$this->aCacheKeyType['key'], $this->aCacheKeyType['value']);
		
		if(empty($result) || !is_array($result))
		{
			$table = $this->guestios;
			
			if($type === self::ANDROIDCLIENTTYPE)
				$table = $this->guestandroid;
				
			$query  = "SELECT * FROM {$table} WHERE vendorid='{$vendorid}' LIMIT 1";

			$result = Loader_Mysql::DBMaster()->getOne($query);
			
			!empty($result) && $this->setGuestCache($type,$this->aCacheKeyType['key'], $this->aCacheKeyType['value'], $result);
		}
		
		return is_array($result) ? $result : array();		
	}
	/**
	 * 
	 * 读取游客缓存
	 * @param {int} $ctype  客户端类型
	 * @param {string} $type  缓存key值类型
	 * @param {string} $GuestValue
	 * @return {ArrayIterator} getGuestCache
	 */
	private function getGuestCache( $ctype , $strkey , $GuestValue )
	{
		if (empty($GuestValue))
			return array();

		$aGuestSysKeys = array();

		if ($ctype == self::IOSCLIENTTYPE)
			$aGuestSysKeys = self::$IOSGUESTKEY;
		elseif ($ctype == self::ANDROIDCLIENTTYPE)
			$aGuestSysKeys = self::$ANDROIDGUESTKEY;
		else
			return array();
		
		$cTypeKey = self::$CACHETYPE[$strkey];	
		
		$CacheKey = Core_keys::guestLoginMarkKey( $GuestValue ,$ctype,$cTypeKey);	

		if ($strinfo = Loader_Redis::redisGuestMember()->get($CacheKey))
		{
			$aCacheInfo = json_decode($strinfo,true);
		
			$aCacheInfo && $info = Values::uncombine($aGuestSysKeys, $aCacheInfo);
		}
		return $info ? $info : array();
	}
	
	/**
	 * 
	 * 设置游客信息缓存
	 * @param {int} $ctype  客户端类型
	 * @param {string} $type  缓存key值类型
	 * @param {string} $GuestValue
	 * @param {array} $aInfo
	 * @return {ArrayIterator} setGuestCache
	 */
	private function setGuestCache( $ctype, $strkey, $GuestValue ,$aInfo)
	{
		if (empty($GuestValue) || empty($aInfo) || !is_array($aInfo))
			return array();
			
		$aGuestSysKeys = $aCacheInfo = array();	

		if ($ctype == self::IOSCLIENTTYPE)
			$aGuestSysKeys = self::$IOSGUESTKEY;
		elseif ($ctype == self::ANDROIDCLIENTTYPE)
			$aGuestSysKeys = self::$ANDROIDGUESTKEY;
		else 
			return array();
			
		$cTypeKey = self::$CACHETYPE[$strkey];	
			
		$CacheKey = Core_keys::guestLoginMarkKey( $GuestValue ,$ctype,$cTypeKey);	
		
		$aCacheInfo = Values::combine($aGuestSysKeys, $aInfo);

		if ($aCacheInfo[0])
			Loader_Redis::redisGuestMember()->set( $CacheKey , json_encode( $aCacheInfo) , false , true , 86400*self::GUESTTIMEPUTDAY );
	
		return true;
	}
	
	/**
	 * 判断当前版本是不是
	 * 最新的游客登陆流程版本
	 * 
	 * @param int	 $api		应用api
	 * @param string $version	当前版本的版本号
	 * 
	 * @return boolean
	 */
	private function GuestCheckVersion($api,$version){
		$api	 = Helper::uint( $api );
		$version = trim( $version );
		$api_ver = array(
							110=>'3.1.0', 113 =>'3.1.0',		//锄大地繁体
							111 =>'1.6' , 114 =>'1.6',		//锄大地简体
							100 =>'3.9' , 103 =>'3.9',		//斗地主繁体
							101 =>'3.9' , 104 =>'3.9',		//斗地主简体
					);

		if( !in_array( $api,array_keys( $api_ver)) )
			return false;
	
		if(	strnatcmp( $version,$api_ver[$api] )<0 ) //<0 - 如果当前版本小于界定的版本
			return false;
		
		return true;
	}
	
	/*******   新游客登录 end **********/
	
	/**
	 * 游客登录  已弃用 
	 *
	 * @modifer 黄国星
	 *
	 * @param {string} $deviceNo 设备号
	 * @param {string} $macid mac地址
	 * @param {string} $openudid 第三方类库的udid
	 * @return array
	 */
	public function guestLogin( $deviceNo, $macid, $openudid )
	{
	    $deviceNo = Loader_Mysql::DBMaster()->escape($deviceNo);
		$macid = empty($macid) ? '' : md5($macid);
		$openudid = empty($openudid) ? '' : md5($openudid);

        if( strlen($deviceNo) != 32 ){
              if( strlen($macid) != 32 ){
              	    if( strlen($openudid) != 32 ){
              	 		return array();
              	    }else{
              	    	$str = "openudid='{$openudid}'";
	               		$email = $openudid.'@boyaa.com';
              	    }
              }else{
	               $str = "macid='{$macid}'";
	               $email = $macid.'@boyaa.com';
              }
        }else{
              $str = "device_no='{$deviceNo}'";
              $email = $deviceNo.'@boyaa.com';
        }
        $query = " SELECT *
		           FROM {$this->iphonemembers}
				   WHERE $str
				   LIMIT 1 ";
        $info = Loader_Mysql::DBMaster()->getOne($query);
        
        if(!empty($info))
        {
        	if( empty($info['macid']) || empty($info['openudid']) ){
		        $updatequery = " UPDATE {$this->iphonemembers}
		         		   SET macid='{$macid}', openudid='{$openudid}'
						   WHERE sitemid='{$info['sitemid']}'
						   LIMIT 1 ";
		       Loader_Mysql::DBMaster()->query( $updatequery );
        	}
		    return $info;
		}
		
        $query = " INSERT INTO {$this->iphonemembers}
		           SET memail='{$email}',
   				       mpwd='NOPASS', device_no='{$deviceNo}',
					   macid='{$macid}', openudid='{$openudid}' ";
	    
		Loader_Mysql::DBMaster()->query($query);
		$sitemid = (int)Loader_Mysql::DBMaster()->insertID();
		
		if($sitemid<=0)
		{
		    return array();
		}
		
		return array('sitemid'   => $sitemid,
		             'memail'    => $email,
					 'mpwd'      => 'NOPASS',
					 'device_no' => $deviceNo);
	}

	/**
	 * 邮箱账号登录
	 * 
	 * @param String $memail 邮箱地址
	 * @param String $mpwd	 登陆密码
	 * @return int
	 */
	public function iphoneLogin( $memail, $mpwd ){
		
		if( empty($memail) || empty($mpwd) ):
			
			return 0;
		endif;
		
		//1.先从缓存中读取数据  2.读不到取DB
		if( !( $uInfo = $this->getEmailCache($memail)) ){

			//参数过滤
			$memail = Loader_Mysql::DBMaster()->escape( $memail );
			$mpwd = md5( Loader_Mysql::DBMaster()->escape( $mpwd ) );
			
			$query = "SELECT sitemid
						FROM {$this->tbmemail}
						WHERE memail='{$memail}'
						AND   mpwd='{$mpwd}'
						LIMIT 1";
		
			$uInfo = Loader_Mysql::DBMaster()->getOne($query);
			
			if( $uInfo['sitemid'] ):
				
				$this->setEmailCache($memail,$uInfo);
			endif;
				
		}
		
		return (int)$uInfo['sitemid'];
	}
	
	/**
	* 根据用户email 获取用户缓存存储数据
	* @param String $memail 邮箱地址
	*/
	private function getEmailCache($memail)
	{
		if( empty($memail) )
		{
			return array();
		}
		$cacheKey = Core_keys::PREVSTR.'e_'.$memail;
		
		Loader_Memcached::cache()->get($cacheKey);
		
	}
	
	/**
	* 存储用户信息到缓存
	* @param String $memail 邮箱地址
	* @param array $data 	存储数据
	*/
	private function setEmailCache($memail,$data,$isExpire=true)
	{
		if( empty($memail) || empty($data) )
		{
			return false;
		}
		
		$cacheKey = Core_keys::PREVSTR.'e_'.$memail;

		//默认设置30天过期时间
		if($isExpire===false){
			
			Loader_Memcached::cache()->set($cacheKey,$data);
		}
		else{
			
			Loader_Memcached::cache()->set( $cacheKey,$data,86400*30 );
		}
	}
	
  /**
   * 每天第一次登录，更新用户信息
   * @uses ios锄大地
   * @param mid ,info
   * @return true
   */
   public function updateUserInfo( $mid, $info ){
   		if( !$mid = Helper::uint( $mid ) )
			return false;
			
		$sex = Helper::uint($info['sex']);
		
		$mnick = Loader_Mysql::DBMaster()->escape($info['mnick']);
		
		$icon = Loader_Mysql::DBMaster()->escape($info['icon']);
		
		$middle = Loader_Mysql::DBMaster()->escape($info['normal']);
		
		$big = Loader_Mysql::DBMaster()->escape($info['large']);
		
		$hometown = Loader_Mysql::DBMaster()->escape($info['hometown']);
		
		$tableUserInfo = $this->getUserInfo($mid);
		
		$tableGameInfo = $this->getGameInfo($mid);
		
   		//FB,266平台注册信息
		if( PLATFORM_ID == 13 || PLATFORM_ID == 226 )
		{
		    $query = "UPDATE $tableUserInfo SET mnick='$mnick', icon='$icon', middle='$middle',big='$big',sex='$sex',hometown='$hometown' WHERE mid='$mid' LIMIT 1";
		}
		//新浪平台注册信息
		else if( PLATFORM_ID == 203 )
		{
			$query = "UPDATE $tableUserInfo SET mnick='$mnick', icon='$icon', middle='$middle',big='$big' WHERE mid='$mid' LIMIT 1";
		}
		
		Loader_Mysql::DBMaster()->query( $query );
		
        $flag = Loader_Mysql::DBMaster()->affectedRows();
        
        if( $flag >= 0 && PLATFORM_ID == 203)
        {
			$queryfiel = "UPDATE $tableGameInfo SET sex='$sex', hometown='$hometown' WHERE mid='$mid' LIMIT 1";
			
			Loader_Mysql::DBMaster()->query( $queryfiel );
        }
        return true;
   }
   
    /**
     * 移动用户更改个人信息
     * @uses ios锄大地
	 * @param mid ,info
	 * @return true
     */
	public function modUserInfo( $mid,$info )
	{
		if ( !($mid = Helper::uint( $mid )) || !is_array($info) || !$info) 
			return false;
		
		$flag = 0;
		
		$cachekey = Core_Keys::getOneById($mid);
		
		$aInfo = Member::factory()->getMemberUserInfo($mid);
		
		if( ($aInfo['mid'] != $mid) || !($sitemid = $aInfo['sitemid']))  //无效用户
			return false;
		
		$aInfo['sid'] = $info['sid'] ? Helper::uint( $info['sid'] ) : $aInfo['sid'];
		
		$aInfo['mnick'] = $info['mnick'] ? Loader_Mysql::DBMaster()->escape( $info['mnick'] ) : $aInfo['mnick'];
		
		$aInfo['sex'] = Helper::uint( $info['sex'] );
		
		$aInfo['hometown'] = Loader_Mysql::DBMaster()->escape( $info['hometown'] );
		
		$tableUserInfo = $this->getUserInfo($mid);
		
		$query = "UPDATE {$tableUserInfo} SET mnick = '{$aInfo['mnick']}', hometown = '{$aInfo['hometown']}',sex = {$aInfo['sex']} WHERE mid = '{$mid}' LIMIT 1";
		
		Loader_Mysql::DBMaster()->query( $query );
		
		//if ($flag = Loader_Mysql::DBMaster()->affectedRows())
		//{
		return Loader_Memcached::minfo( $mid )->set( $cachekey, values::combine(values::getArrUsers(), $aInfo), 10*24*60*60); //加入缓存
		//} 
	}

	/**
	 * 破产补贴
	 * $sid 平台ID
	 * $times 可以领取次数
	 */
	public function bankrupt( $mid, $sid, $times=1, $wmode=87 ) {

		if( !( $mid = Helper::uint( $mid )) || !( $sid = Helper::uint( $sid ) ) ){
			return false;
		}
		$cacheKey = Bankrup_Keys::mkBankrupt( $mid ,$sid );
		$data['bankruptTime'] = Loader_Memcached::cache()->get($cacheKey);

		if ($times <= $data['bankruptTime'] ) { //超过可领取次数返回false
			return false;
		}
			
		if (  $data['money'] = Bankrup_Money::$bankrup ) {
			
			$data['flag']  = Logs::factory()->addWin( $mid, $wmode, 0, $data['money'], $mid, $mid, true );
			
			if ($data['flag']) {
				
				$data['bankruptTime'] +=1;
				$saveTime = Helper::time2Seven();//获取到第二天七点的时间差
				Loader_Memcached::cache()->set( $cacheKey,$data['bankruptTime'],$saveTime );
				
				return $data;
			}
		}
	}
	   
   
   /**
    *获取游客和独立站点的登录类型的缓存
    */
   public function loginType( $mid, $type, $expire= 300 ){
   	
		$loginTypeKey = Core_Keys::mkLoginType( $mid );
   	    
        Loader_Memcached::cache()->set( $loginKey, $type, $expire );
   }
   
   /**
    * 记录用户登陆方式（b40代表iphone， b45代表 ipad）
    */
   public function loginBid( $mid, $api ){

   	   $loginVerKey = Core_Keys::mkLoginver( $mid );
   	   
   	   $bid = Core_Game::$apiBid[$api];

	   return  Loader_Redis::redisLoginVer()->set( $loginVerKey,$bid,false,false,12*3600);
   }
   	/**
   	 * 
   	 * 获取用户bid
   	 * @param unknown_type $mid
   	 */
	public function getLoginBid($mid){
		
		$loginVerKey = Core_Keys::mkLoginver( $mid );
		
		$bid = Loader_Redis::redisLoginVer()->get($loginVerKey,false,false);
		
		return $bid ? $bid : false;
   	}

	/**
	 * 业务错误日志上报
	 * @param  [type] $mode      [description]
	 * @param  [type] $error_msg [description]
	 * @return [type]            [description]
	 */
	private function error_log($mode,$errors='',$params_error=''){
		$array = array(
			'time'=>date('Y-m-d H:i:s'),
			'error'=>$errors,
			'api'=>$params_error
		);
		if ((self::ISOPENLOGS === true) && class_exists('Kkdatacenter_Module')) {
			Kkdatacenter_Module::factory()->sendNewLog('guest_error', $mode, $array );
		}else{
			Logs::factory()->debug($array , 'guest_error_'.$mode);
		}
	}
	
	
	/**
	 * boyaa账号登录
	 * @param String $boyaa_id			表字段名为memail 
	 * @param String $device_no
	 * @return int
	 */
	public function iphoneLoginNew( $boyaa_id, $device_no ){
		$boyaa_id = Loader_Mysql::dbmaster()->escape( $boyaa_id );
		$device_no = Loader_Mysql::dbmaster()->escape( $device_no ) ;
		if ( $boyaa_id ) {
			$query = "SELECT sitemid,memail 
			          FROM {$this->guestios} 
					  WHERE memail='{$boyaa_id}' 
					  LIMIT 1";		
			$aInfo = Loader_Mysql::dbmaster()->getOne($query);
		}
		if ( $aInfo ) {
			return $aInfo['sitemid'];
		}
		
		if ( empty($aInfo) ) {			//博雅帐号注册
			$sql = " NSERT INTO {$this->guestios} SET device_no='{$device_no}', memail='{$boyaa_id}'";
			Loader_Mysql::dbmaster()->query($sql);
			$aInfo['sitemid'] = Loader_Mysql::dbmaster()->insertID();
		} 
		/**
		  elseif ( $aInfo['sitemid'] && ( !is_numeric($aInfo['memail']) ) ) {		//绑定boyaa_id 
			$sql = "
				UPDATE {$this->iphonemembers}
				SET memail='{$boyaa_id}'
				WHERE sitemid={$aInfo['sitemid']}
				LIMIT 1
			";
			Loader_Mysql::dbmaster()->query($sql);
		}*/		
		return (int)$aInfo['sitemid'];
	}

}//end-class