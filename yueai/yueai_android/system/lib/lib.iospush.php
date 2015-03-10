<?php !defined('YUEAI') AND exit('Access Denied!');


/**
 * 移动端 - 推送 - 类
 * Enter description here ...
 * @author HuXiaowei
 *
 */
class Lib_Iospush{
	
	 /*
      *  推送配置
      *$proUrl  正式环境地址
      *$testUrl 测试环境地址
      *$sound   声音
      *$badge   小气泡显示
	  */
	 protected $_proUrl  = 'ssl://gateway.push.apple.com:2195';
	 protected $_testUrl = 'ssl://gateway.sandbox.push.apple.com:2195';
	 protected $_sound   = 'default';
	 protected $_badge   = 1;
	 public $_pempath;
 
	public static $instance;
	
	/**
	 * 獲取單例
	 * @return Activities_Thanksgiving
	 */
	public static function factory() {
		if(!(self::$instance instanceof self)) {
			self::$instance = new self();
		}
	
		return self::$instance;
	}

	 /**
	  *设置证书的路径
	  *
	  */
     public function setPemPath(){
     		
     	$this->_pempath = ROOTPATH . 'pushpem/';
     	
        return $this->_pempath;
     }
     
	/**
	 * 发送推送
	 *
	 * @param array $deviceArr 推送token
	 * @param int $pushId      文案id
	 * @param int $ruleId      推送类型
	 * @param string $alert    推送信息
	 * @param bool $pro        是否是测试
	 * @param string $apnsCert 推送证书
	 */
	public function pushApns( $deviceArr, $pushId, $ruleId, $alert='', $pro=TRUE, $apnsCert ){
	 	 
		 $notification = array();
		 $this->_pempath = self::setPemPath();
		 
		 if( strlen($alert) > 0 ) {
			$notification['alert'] = $alert;
		 }
		 if( strlen( $pushId ) > 0 ) {
			$notification['pushid'] = $pushId;
		 }
		 if( strlen( $ruleId ) > 0 ) {
			$notification['rulid'] = $ruleId;
		 }
		 $notification['sound'] = $this->_sound;
		 $notification['badge'] = $this->_badge;
	 	 
	     $body['aps'] = $notification;
		
		 $APPLE_SERVER = $pro? $this->_proUrl:$this->_testUrl;
	 
	 	 $streamContext = stream_context_create(array('ssl' => array( 'local_cert' => $this->_pempath.$apnsCert )));
	
		 $apns = @stream_socket_client( $APPLE_SERVER, $nError, $sError, 2, STREAM_CLIENT_CONNECT, $streamContext);
	
		 stream_set_blocking( $apns, 0);
	     stream_set_write_buffer( $apns, 0);
	
		 if( !$apns ) {
		    print "Failed to connect $nError $sError\n";
			return;
		 }
	 	 
		 foreach( $deviceArr as $v ) {
			$device = mysql_escape_string( $v['device']);
			$payload = json_encode( $body );
			echo $payload;
			// format the message
			$msg = chr(0) . chr(0) . chr(32) . pack('H*', $device) . chr(0) . chr(strlen($payload)) . $payload;
			
			echo $msg;
			
			fwrite( $apns, $msg );
		 }
	
	     @socket_close( $apns );
		 fclose( $apns );
		 
		 var_dump($apns);
		
	}//end-func
	
	
}//end-class