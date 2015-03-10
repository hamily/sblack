<?php !defined('BOYAA') AND exit('Access Denied!');

include_once 'SocketPacket.php';
 
/**
 * 此处涉及四种Server的通讯,加密方式不尽相同:
 * 1: 打牌Server(需要指定IP和端口,用于控制房间里面的: 踢人,发广播,发重置命令,发暂停等)
 * 2: 存钱Server(利用配置文件指定IP和端口,用于创建用户重要信息如金币,台费值等)
 * 3: 存钱备份Server(利用配置文件指定IP和端口,用于备份用户的金币情况,每半小时切换一个文件)
 * 4: 淘金赛Server(告知Server开赛时间等)
 */
class Cserver_Api{
	const CMD_CREATE_RECORD = 0x1001;	//创建记录
	const CMD_UPDATE_RECORD = 0x1002;	//更新记录
	const CMD_DELETE_RECORD = 0x1003;	//删除记录
	const CMD_GET_RECORD 	= 0x1004;	//获取记录
	const CMD_REPORT_ID		= 0x1005;	//上报身份
	const CMD_GET_ALLRECORD = 0x1006;	//取所有数据
	const CMD_GET_UPDATEALLRECORD  = 0x1007;	//更新数据
	const CMD_GET_UPDATERECORD  = 0x1009;
	//const CMD_GET_LOGRECORD  = 0x1009;	//日志更新数据
	//const CMD_SWITCH_LOG_FILE = 0x1008;	//写日志开关
 
	
	private $MemDataServer = array(); //
	
	private $MemDataBakServer = array(); //
	
	private $aSockets = array(); //暂存各Socket连接.防止一个脚本多次连同一个Server. [$ip][$port] = socket;
	private $checkcode; //新版淘金赛与server通信用的验证吗

	public $aSeria =  array( //PHP与Server的映射关系
					   'mid',
                       'money',
					   'exp',
					   'level',
					   'wintimes',
				       'losetimes',
					   'activetime',
					   'tid',
					   'svid',
					   );
	public $KeyArr =  array( //PHP与Server的映射关系
                       'money'=>1,
					   'exp'=>2,
					   'level'=>3,
					   'wintimes'=>4,
					   'losetimes'=>5,
					   'activetime'=>6,
					   'tid'=>7,
					   'svid'=>8,
					  );
 
	 protected static $_instance = array();
    /**
	 * 创建一个实例
	 *
	 * @author 黄国星
	 *
	 * @return object Cserver_Api
	 */
    public static function factory()
	{
	    if(!is_object(self::$_instance['cserver_api']))
		{
		    self::$_instance['cserver_api'] = new Cserver_Api;
		}
	
		return self::$_instance['cserver_api'];
	}

	/*
	 *实例化
	 */
    public function __construct( ){

		$this->MemDataServer = array( Core_Game::$CServer['ip'],Core_Game::$CServer['port'] );

	}
	
	/**********************以下方法供存钱Server通讯***********************************/
	/**
	 * 创建用户记录,字段为id,钱数,经验值,积分
	 * @param int $mid
	 * @param Array $aInfo = array('money'=> 2943681,'exp'=> 16, 'level' => 65149, 'wintime' => 32 ...)
	 *  CreateRecord(1, $aInfo );
	 * @time 2012年6月7日18:48:12
	 */
	public function CreateRecord($mid, $aInfo){

        
		if(! $mid = Helper::uint( $mid)){ //非法用户
			return false;
		}
	    /* 如果记录已经存在，则无需再次初始化*/
        if( $this->GetRecord($mid) ){
        	return false;
        }
        /* end*/
		$packet = new SocketPacket();
		$packet->WriteBegin( self::CMD_CREATE_RECORD);

		$packet->WriteInt( $mid); //用户mid
		foreach( $aInfo  as $key => $value ){
          if( $key=='tid' || $key == 'svid'){ //tid 和 svid 是 short类型。例外
			 $packet->WriteShort($value);
		  }else{
             $packet->WriteInt( $value );
		  }
		}
		$packet->WriteEnd();
 
		if(! $this->SendData($this->MemDataServer[0], $this->MemDataServer[1], $packet, true)){
			return false;
		}
 
		if(($recvLen = @socket_recv($this->aSockets[$this->MemDataServer[0]][$this->MemDataServer[1]], $data, 4096, 0)) === false){
			Logs::factory()->debug(@implode('-', array(socket_strerror(socket_last_error()), __LINE__, $mid)), 'C_creat.txt');
			return false;
		}
 
		$retPacket = new SocketPacket();
		$retPacket->SetRecvPacketBuffer($data, $recvLen);
 
		return $retPacket->ParsePacket() === 0 ? true : false;
	}

	/**
	 * 更新钱数,钱的加减直接传过去
	 * @param int $mid
	 * @param Array 要改的字段，请对应好 $aInfo = array('money'=> 2943681,'exp'=> 16, 'level' => 65149, 'wintime' => 32 ...)
	 * @return Boolean
	 * @time 2012年6月7日18:48:12
	 */
	public function UpdateRecord($mid, $aInfo) {
		if(! $mid = Helper::uint( $mid)){
			return false;
		}
 
		$packet = new SocketPacket();
		$packet->WriteBegin(self::CMD_UPDATE_RECORD);
 
		$packet->WriteInt( $mid); //用户mid
		$packet->WriteByte( count($aInfo) ); //要改的字段个数

		foreach( $aInfo  as $key => $value ){
             $packet->WriteInt( $this->KeyArr[$key] ); //第一个字段
             $packet->WriteInt( $value ); //要改的值
		}

		$packet->WriteEnd();

		if(! $this->SendData($this->MemDataServer[0], $this->MemDataServer[1], $packet, true)){
			return false;
		}

		if(($recvLen = @socket_recv($this->aSockets[$this->MemDataServer[0]][$this->MemDataServer[1]], $data, 4096, 0)) === false){
			Logs::factory()->debug(@implode('-', array(socket_strerror(socket_last_error()), __LINE__ , $mid)), 'C_update.txt');
			return false;
		}
 
		$retPacket = new SocketPacket();
		$retPacket->SetRecvPacketBuffer($data, $recvLen);
		$ret = $retPacket->ParsePacket();
 
		$flag = $retPacket->ReadInt();//返回的结果  0 成功 -1 失败
 
		in_array($flag, array(0)) or Logs::factory()->debug(array('mid'=>$mid, 'flag'=>$flag, 'ret'=>$ret, 'sInfo'=>$aInfo ), 'C_update_err.txt', '5');
		($ret === 0) or Logs::factory()->debug(array('mid'=>$mid, 'flag'=>$flag, 'ret'=>$ret, 'sInfo'=>$aInfo ), 'C_update_err_2.txt', '5');
		return $ret === 0 ? true : false; //返回的结果  0 成功 -1 失败
	}

	/**
	 * 清除指定mid的所有记录
	 * @param int $mid
	 * @return Boolean
	 */
	public function ClearRecord( $mid){
		if(! $mid = Helper::uint( $mid)){
			return false;
		}
		
		$packet = new SocketPacket();
		$packet->WriteBegin(self::CMD_DELETE_RECORD);
		$packet->WriteInt( $mid);
		$packet->WriteString( '');
		$packet->WriteEnd();
     
		if(! $this->SendData($this->MemDataServer[0], $this->MemDataServer[1], $packet, true)){
			return false;
		}
        
		if(($recvLen = @socket_recv($this->aSockets[$this->MemDataServer[0]][$this->MemDataServer[1]], $data, 4096, 0)) === false){
			Logs::factory()->debug(@implode('-', array(socket_strerror(socket_last_error()), __LINE__, $mid)), 'C_clear.txt');
			return false;
		}
		
		$retPacket = new SocketPacket();
		$retPacket->SetRecvPacketBuffer($data, $recvLen);
		return $retPacket->ParsePacket() === 0 ? true : false;
	}

	/**
	 * 获取单个用户最新资料
	 * @param int $mid
	 * @return Array
	 * array( $mid, $exp, $level, $wintime, $losttime, $activetime, $tid, $sid );
	 */
	public function GetRecord( $mid ) {
		if(! $mid = Helper::uint( $mid)){
			return array();
		}
 
		if(! $aInfo = Loader_Memcached::CserverCache()->get((int)$mid )){
		   return array();
		}

        $userInfo['mid'] = $mid ;

		$aInfo = explode( ',', $aInfo );
        $keyArr =  array_keys( $this->KeyArr );
		foreach( $aInfo as $key=>$v ){
			$userInfo[ $keyArr[$key] ] = $v;
		}
 
		return (array)$userInfo;
	}

    /**
	 * 根据更新时间来，批量取更新数据，同步到DB
	 * @param $updatetime 上次更新的时间
	 * @param int $num 从第一页开始，一次2000条记录
	 */
    public function GetUpdateRecord( $updatetime, $num =2000 ){
      
	   if(( !$num = functions::uint($num)) ){
           return array();
	   }
		$packet = new SocketPacket();
		$packet->WriteBegin(self::CMD_GET_UPDATERECORD);
		$packet->WriteInt($updatetime);
		$packet->WriteInt($num);
		$packet->WriteEnd();
         
		if(! $this->SendData($this->MemDataServer[0], $this->MemDataServer[1], $packet, true)){
			return false;
		}
		
		if(($recvLen = @socket_recv($this->aSockets[$this->MemDataServer[0]][$this->MemDataServer[1]], $data, 8192, 0)) === false){//4096
			Logs::factory()->debug(@implode('-', array(socket_strerror(socket_last_error()), __LINE__, $mid)), 'C_getupdate.txt');
			return false;
		}
        
		$retPacket = new SocketPacket();
		$retPacket->SetRecvPacketBuffer($data, $recvLen);
		$ret = $retPacket->ParsePacket();
 
        ($ret === 0) or Logs::factory()->debug(array('updatetime'=>$updatetime, 'num'=>$num, 'ret'=>$ret ), 'C_getupdate_err.txt' );
        $value = $retPacket->ReadInt();//返回的结果,这里的结果是 有多少个mid，就去多少次，谢谢
 
		 for( $i=1;$i<= $value; $i++ ){ //根据mid个数，获取

			$mid = $retPacket->ReadInt();//读取mid
            $midArr[] = $mid;
		 }
  
		 return $midArr; //返回mid数组

	}
 
	/**********************以下为私有方法***********************************/
	/**
	 * 连接Tcp服务器
	 * @param String $ip
	 * @param int $port
	 * @param Boolean $reuse 是否使用上一次创建好的连接.用在一个脚本中与同一个端口多次通讯.只有存钱Server支持,其他都要强制重新连接
	 * @return socket/false
	 */
	private function connect($ip, $port, $reuse=false){
		if( is_resource( $this->aSockets[$ip][$port]) && $reuse){ //已经连接并且支持...
			return $this->aSockets[$ip][$port];
		}
		
		if(($socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false){
			Logs::factory()->debug(@implode('-', array(socket_strerror(socket_last_error()), __LINE__, $ip, $port)), 'C_connect_creat.txt');
			return false;
		}
		
		@socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>1, "usec"=>0));
		@socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>1, "usec"=>0));

		if( @socket_connect($socket, $ip, $port) === false){
			Logs::factory()->debug(@implode('-', array(socket_strerror(socket_last_error()), __LINE__, $ip, $port)), 'C_connect_connect.txt');
			return false;
		}
		
		socket_set_block( $socket);
		
		return $this->aSockets[$ip][$port] = $socket;
	}
	/**
	 * 实际做的发送操作
	 * @param String $ip
	 * @param int $port
	 * @param GameSocketPacket $packet
	 * @return Boolean
	 */
	private function SendData($ip, $port, &$packet, $reuse=false){
		if(! $this->connect($ip, $port, $reuse)){
			
			return false;
		}
 
		if( @socket_write($this->aSockets[$ip][$port], $packet->GetPacketBuffer(), $packet->GetPacketSize() ) === false){
			Logs::factory()->debug(@implode('-', array(socket_strerror(socket_last_error()), __LINE__)), 'C_sendData.txt');
			return false;
		}

    	return true;
	}
	 
}//end-class