<?php
//包头默认值
define("JW_SOH", "B");		//消息开始标记
define("JW_EOT", "Y");
define("JW_MAIN_VER", 1);	//主版本号 1个字节
define("JW_SUB_VER", 1);	//子版本号 1个字节
define("JW_SOURCE", 0);		//来源 1个字节

//server协议
define("CLIENT_COMMAND_PHP_SEND_GIFE",0x1015);	//宝箱通知
define("CLIENT_COMMAND_PHP_ADD_MONEY",0x1014);	//操作金币
define("SERVER_COMMAND_SERVER_GIFT",0x2028);	//宝箱server返回
define("SERVER_COMMAND_SERVER_MONEY",0x2029);	//金币server返回

//早期定的消息头里面的类型只有4种 ，现已经废弃，改成号码段区间，分配给WEB这边的是32-63之间
define("JW_SOURCE_TYPE_IM_WEB", 32);
define("JW_HEADER_LEN", 9);
define("JW_SOCKET_BACK_LEN",1);	//返回的包体的长度

//返回类型值
define("SERVER_SOCKET_BACK_CODE_A",1);	//成功返回
define("SERVER_SOCKET_BACK_CODE_B",2);	//server加金币成功，流水失败 php写流水
define("SERVER_SOCKET_BACK_CODE_C",3);	//全部失败，不操作
//字段类型
define("JW_MSG_BODY_LOOP", 0);
define("JW_MSG_BODY_NUM", 1);
define("JW_MSG_BODY_CHAR", 2);
define("JW_MSG_BODY_WORD", 3);
define("JW_MSG_BODY_DWORD", 4);
define("JW_MSG_BODY_STRING", 5);
define("JW_MSG_BODY_QWORD", 7);
//新字段类型
define("API_LOOP", 0);//结构控制
define("API_NUM", 1);
define("API_CHAR", 2);
define("API_WORD", 3);
define("API_DWORD", 4);
define("API_STRING", 5);
define("API_BIN", 6);//只用于解包
define("API_QWORD", 7);
define("API_INT", 8);
//策略文件
define("PLIOCY",'<policy-file-request/> ');

class Packer{
	private $_body = "";
	private $_head = "";
	//包头结构体
	public static $headertpl = array(
					array("boyaa",API_CHAR,1),
					array("boyaa1",API_CHAR,1),
					array("mainversion",API_NUM,1),
					array("subversion",API_NUM,1),
					array("cmd",API_WORD,2),
					array("checkcode",API_NUM,1)	
	);
	//加钱结构体
	public static $sendmoneytpl = array(
				array("mid",API_INT,4),
				array("money",API_INT,4),
				array("actid",API_WORD,2),
				array("bpid",API_NUM,1)
	);
	//socket返回结构体
	public static $backtpl = array(
			array("flag",API_NUM,1)	
	);
		
	protected static $_instance = array();
	
	public static function factory(){
		if(!is_object(self::$_instance['packer'])){
			self::$_instance['packer'] = new Packer();
		}
		return self::$_instance['packer'];
	}

	public function PackByteNum($v){
		return pack("C", $v);
	}
	public function PackIntLitter($v){
		return pack("V", $v);
	}
	public function PackByteChar($v){
		$len = strlen($v);
		return pack("a{$len}", $v);
	}
	public function Packint($v){ 
		return pack("N",$v);	
	}
	
	public function PackWord($v){
		return pack("v", $v);	//n 大端 v 小端 unsigned short
	}
	public function PackWordn($v){
		return pack("n", $v);	//n 大端 v 小端 unsigned short
	}

	public function PackDWord($v){
		return pack("N", $v);
	}

	public function PackQWord($v){
		$vv = explode("_", $v);
		return pack("N2", $vv[0], $vv[1]);
	}

	public function PackString($v) {
		$len = strlen($v) + 1;
		return pack("a{$len}", $v);
	}
	//返回全部数据
	public function getPackage(){
		return $this->_head.$this->_body;
	}
	//返回头部数据
	public function getHeader(){
		return $this->_head;
	}
	//返回包体数据
	public function getBody(){
		return $this->_body;
	}
	public function resetBody($data){
		$this->_body = $data;
	}
	public function getBodyLen($bodyArr){
		if(!is_array($bodyArr)) return 0;
		$len = 0;
		foreach($bodyArr as $item){
			switch($item[1]){
				case API_CHAR:$len += 1;break;
				case API_NUM:$len += 1;break;
				case API_INT:$len += 4;break;
				case API_WORD:$len += 2;break;
				case API_DWORD:$len += 4;break;
				case API_QWORD:$len += 8;break;
				case API_STRING:$len += strlen($item[0])+1;break;
			}
		}
		return $len;
	}

	/**
	 * @param 	int             $bodylen    
	 * @param  hex-int      	$maincmd
	 * @param  int             from_uin
	 * @param  int             $to_uin
	 * @param  hex-int         $subcmd
	 * @param 	int				$source_type
	 */
	public function setHeader($bodylen, $maincmd=CLIENT_COMMAND_PHP_ADD_MONEY){
		$this->_head .= $this->PackWordn(JW_HEADER_LEN + $bodylen - 2); //2
		$this->_head .= $this->PackByteChar(JW_SOH);	//1
		$this->_head .= $this->PackByteChar(JW_EOT);	//1	
		$this->_head .= $this->PackByteNum(JW_MAIN_VER);	//1
		$this->_head .= $this->PackByteNum(JW_SUB_VER);	//1
		$this->_head .= $this->PackWordn($maincmd);	//2
	}
	/**
	 * 设置checkcode
	**/
	public function setCheckcode($checkcode){
		$this->_head .= $this->PackByteNum($checkcode);		//1	
		//$this->_head .= $this->PackWordn(JW_SUB_CMD);	//2
		//$this->_head .= $this->PackWordn(JW_SEQ);	//2
		//$this->_head .= $this->PackByteNum(JW_SOURCE);	//1
	}

	public function setBody($bodyArr){
		if(!is_array($bodyArr)) return;
		$len = count($bodyArr);
		for($i=0; $i<$len; $i++){
			switch($bodyArr[$i][1]){
				case API_CHAR:
					$this->_body .= $this->PackByteChar($bodyArr[$i][0]);
					break;
				case API_NUM:
					$this->_body .= $this->PackByteNum($bodyArr[$i][0]);
					break;
				case API_DWORD:
					$this->_body .= $this->PackDWord($bodyArr[$i][0]);
					break;
				case API_STRING:
					$this->_body .= $this->PackString($bodyArr[$i][0]);
					break;
				case API_WORD:
					$this->_body .= $this->PackWordn($bodyArr[$i][0]);
					break;
				case API_QWORD:
					$this->_body .= $this->PackQWord($bodyArr[$i][0]);
					break;
				case API_INT:
					//echo $bodyArr[$i][0];
					$this->_body .= $this->Packint($bodyArr[$i][0]);
					break;
				default:
					break;
			}
		}
	}
	/**
	 * 解包体
	**/
	public function jw_unpack($binstr,$tplArr){
		if($binstr == "" || empty($tplArr)) return array();
		$retArr = array();
		$field_tag = "";
		$start = JW_HEADER_LEN;
		$retArr = $this->unpack_segment($binstr, $start, $tplArr);		
		return $retArr;
	}
	/**
	 * 解包头
	**/
	public function jw_unpack_header($binstr,$tplArr){
		if($binstr == "" || empty($tplArr)) return array();
		$retArr = array();
		$field_tag = "";
		$start = 2 ;
		$retArr = $this->unpack_segment($binstr, $start, $tplArr);		
		return $retArr;
	}
	/**
	 * 解包体实现
	**/
	private function unpack_segment($binstr, &$offset, $tplArr){
		$_offset = $offset;
		$loopArrName = "";
		$loopfield = "";
		$field_tag = "";
		$loopTplArr = array();
		$qfieldArr = array();//记录字QWORD段名		
		foreach($tplArr as $field){
			switch($field[1]){
				case API_CHAR:
				case API_NUM:
					$field_tag .= "/C".$field[0];
					$offset += 1;
					break;
				case API_WORD:
					$field_tag .= "/n".$field[0];
					$offset += 2;
					break;
				case API_DWORD:
					$field_tag .= "/N".$field[0];
					$offset += 4;
					break;
				case API_STRING:
					$tmpArr = @unpack("@". $offset ."/a*tmpstr", $binstr);
					$ttArr = explode("\0", $tmpArr['tmpstr']);
					$ttlen = strlen($ttArr[0]) + 1;
					$field_tag .= "/a". $ttlen . $field[0];
					$offset += $ttlen;
					break;
				case API_BIN:
					$field_tag .= "/C". $field[2] . $field[0];
					$offset += $field[2];
					break;
				case API_QWORD:
					$field_tag .= "/N2". $field[0];
					$offset += 8;
					$qfieldArr[] = $field[0];
					break;
				case API_LOOP:
					$loopArrName = $field[0];
					$loopTplArr = $field[2];
					$loopfield = $field[3];
					break;
				case API_INT: 
					$field_tag .= "/N".$field[0];
					$offset += 4;
					break;
				default:
					break;
			}
		}
		//print_r($tplArr);
		$format = "@". $_offset . $field_tag;
		$retArr = @unpack($format, $binstr);
		return $retArr;
	}
	
	private function unpack_segment_loop($binstr, &$offset, $tplArr){
		$_offset = $offset;
		$field_tag = "";
		$qfieldArr = array();//记录字QWORD段名		
		foreach($tplArr as $field){
			switch($field[1]){
				case API_CHAR:
				case API_NUM:
					$field_tag .= "/C".$field[0];
					$offset += 1;
					break;
				case API_WORD:
					$field_tag .= "/n".$field[0];
					$offset += 2;
					break;
				case API_DWORD:
					$field_tag .= "/N".$field[0];
					$offset += 4;
					break;
				case API_STRING:
					$tmpArr = @unpack("@". $offset ."/a*tmpstr", $binstr);
					$ttArr = explode("\0", $tmpArr['tmpstr']);
					$ttlen = strlen($ttArr[0]) + 1;
					$field_tag .= "/a". $ttlen . $field[0];
					$offset += $ttlen;
					break;
				case API_BIN:
					$field_tag .= "/C". $field[2] . $field[0];
					$offset += $field[2];
					break;
				case API_QWORD:
					$field_tag .= "/N2". $field[0];
					$offset += 8;
					$qfieldArr[] = $field[0];
					break;
				default:
					break;
			}
		}

		$format = "@". $_offset . $field_tag;
		$retArr = @unpack($format, $binstr);

		foreach($qfieldArr as $qname){
			$retArr[$qname] = $retArr[$qname."1"]."_".$retArr[$qname."2"];
			unset($retArr[$qname."1"]);
			unset($retArr[$qname."2"]);
		}

		return $retArr;
	}
	/**
	 * pack之后是二进制
	**/
	public function encryptData($len){
		$auth = array(
				0x70,0x2F,0x40,0x5F,0x44,0x8E,0x6E,0x45,0x7E,0xAB,0x2C,0x1F,0xB4,0xAC,0x9D,0x91,
				0x0D,0x36,0x9B,0x0B,0xD4,0xC4,0x39,0x74,0xBF,0x23,0x16,0x14,0x06,0xEB,0x04,0x3E,
				0x12,0x5C,0x8B,0xBC,0x61,0x63,0xF6,0xA5,0xE1,0x65,0xD8,0xF5,0x5A,0x07,0xF0,0x13,
				0xF2,0x20,0x6B,0x4A,0x24,0x59,0x89,0x64,0xD7,0x42,0x6A,0x5E,0x3D,0x0A,0x77,0xE0,
				0x80,0x27,0xB8,0xC5,0x8C,0x0E,0xFA,0x8A,0xD5,0x29,0x56,0x57,0x6C,0x53,0x67,0x41,
				0xE8,0x00,0x1A,0xCE,0x86,0x83,0xB0,0x22,0x28,0x4D,0x3F,0x26,0x46,0x4F,0x6F,0x2B,
				0x72,0x3A,0xF1,0x8D,0x97,0x95,0x49,0x84,0xE5,0xE3,0x79,0x8F,0x51,0x10,0xA8,0x82,
				0xC6,0xDD,0xFF,0xFC,0xE4,0xCF,0xB3,0x09,0x5D,0xEA,0x9C,0x34,0xF9,0x17,0x9F,0xDA,
				0x87,0xF8,0x15,0x05,0x3C,0xD3,0xA4,0x85,0x2E,0xFB,0xEE,0x47,0x3B,0xEF,0x37,0x7F,
				0x93,0xAF,0x69,0x0C,0x71,0x31,0xDE,0x21,0x75,0xA0,0xAA,0xBA,0x7C,0x38,0x02,0xB7,
				0x81,0x01,0xFD,0xE7,0x1D,0xCC,0xCD,0xBD,0x1B,0x7A,0x2A,0xAD,0x66,0xBE,0x55,0x33,
				0x03,0xDB,0x88,0xB2,0x1E,0x4E,0xB9,0xE6,0xC2,0xF7,0xCB,0x7D,0xC9,0x62,0xC3,0xA6,
				0xDC,0xA7,0x50,0xB5,0x4B,0x94,0xC0,0x92,0x4C,0x11,0x5B,0x78,0xD9,0xB1,0xED,0x19,
				0xE9,0xA1,0x1C,0xB6,0x32,0x99,0xA3,0x76,0x9E,0x7B,0x6D,0x9A,0x30,0xD6,0xA9,0x25,
				0xC7,0xAE,0x96,0x35,0xD0,0xBB,0xD2,0xC8,0xA2,0x08,0xF3,0xD1,0x73,0xF4,0x48,0x2D,
				0x90,0xCA,0xE2,0x58,0xC1,0x18,0x52,0xFE,0xDF,0x68,0x98,0x54,0xEC,0x60,0x43,0x0F
			);
		$data = $this->_body;
		if(empty($data) || empty($len)){
			return false;
		}
		$checkcode = 0;
		for($i=0;$i<$len;$i++){ 
			$checkcode += hexdec(bin2hex($data{$i}));
			$data{$i} = chr($auth[hexdec(bin2hex($data{$i}))]);
		}
		$this->resetBody($data);
		return $checkcode = ~$checkcode+1;
	
	}
	/**
	 * 解密包体数据
	**/
	public function decryptData($buffer,$bodylen,$type='money'){ 
		$auth = array(
				0x51,0xA1,0x9E,0xB0,0x1E,0x83,0x1C,0x2D,0xE9,0x77,0x3D,0x13,0x93,0x10,0x45,0xFF,
				0x6D,0xC9,0x20,0x2F,0x1B,0x82,0x1A,0x7D,0xF5,0xCF,0x52,0xA8,0xD2,0xA4,0xB4,0x0B,
				0x31,0x97,0x57,0x19,0x34,0xDF,0x5B,0x41,0x58,0x49,0xAA,0x5F,0x0A,0xEF,0x88,0x01,
				0xDC,0x95,0xD4,0xAF,0x7B,0xE3,0x11,0x8E,0x9D,0x16,0x61,0x8C,0x84,0x3C,0x1F,0x5A,
				0x02,0x4F,0x39,0xFE,0x04,0x07,0x5C,0x8B,0xEE,0x66,0x33,0xC4,0xC8,0x59,0xB5,0x5D,
				0xC2,0x6C,0xF6,0x4D,0xFB,0xAE,0x4A,0x4B,0xF3,0x35,0x2C,0xCA,0x21,0x78,0x3B,0x03,
				0xFD,0x24,0xBD,0x25,0x37,0x29,0xAC,0x4E,0xF9,0x92,0x3A,0x32,0x4C,0xDA,0x06,0x5E,
				0x00,0x94,0x60,0xEC,0x17,0x98,0xD7,0x3E,0xCB,0x6A,0xA9,0xD9,0x9C,0xBB,0x08,0x8F,
				0x40,0xA0,0x6F,0x55,0x67,0x87,0x54,0x80,0xB2,0x36,0x47,0x22,0x44,0x63,0x05,0x6B,
				0xF0,0x0F,0xC7,0x90,0xC5,0x65,0xE2,0x64,0xFA,0xD5,0xDB,0x12,0x7A,0x0E,0xD8,0x7E,
				0x99,0xD1,0xE8,0xD6,0x86,0x27,0xBF,0xC1,0x6E,0xDE,0x9A,0x09,0x0D,0xAB,0xE1,0x91,
				0x56,0xCD,0xB3,0x76,0x0C,0xC3,0xD3,0x9F,0x42,0xB6,0x9B,0xE5,0x23,0xA7,0xAD,0x18,
				0xC6,0xF4,0xB8,0xBE,0x15,0x43,0x70,0xE0,0xE7,0xBC,0xF1,0xBA,0xA5,0xA6,0x53,0x75,
				0xE4,0xEB,0xE6,0x85,0x14,0x48,0xDD,0x38,0x2A,0xCC,0x7F,0xB1,0xC0,0x71,0x96,0xF8,
				0x3F,0x28,0xF2,0x69,0x74,0x68,0xB7,0xA3,0x50,0xD0,0x79,0x1D,0xFC,0xCE,0x8A,0x8D,
				0x2E,0x62,0x30,0xEA,0xED,0x2B,0x26,0xB9,0x81,0x7C,0x46,0x89,0x73,0xA2,0xF7,0x72	
		);
		if(empty($buffer) || empty($bodylen)){
			return false;
		}
		$header = $this->jw_unpack_header($buffer,self::$headertpl);
		$cmd = Helper::uint($header["cmd"]);
		$ret = array();
		$flag = 1;
		switch($type){
			case "money": 
				if(SERVER_COMMAND_SERVER_MONEY!=$cmd){
					$flag = 0;
				}
				break;
			case "back":
				if(SERVER_COMMAND_SERVER_GIFT!=$cmd){
					$flag = 0;
				}
				break;
		}
		if($flag==0){
			return $ret;
		}
		//解包体
		$start = JW_HEADER_LEN ;	
		$end = $start + $bodylen;
		for($i=$start;$i<$end;$i++){ 
			$buffer{$i} = chr($auth[ord($buffer{$i})]);
		}
		$body = $this->jw_unpack($buffer,self::$backtpl);
		return $body;
	}
	/**
	 * 通知server 玩家有金币改动
	 *
	 * @param unknown_type $body
	 * @param unknown_type $port
	 * @param $actid
	 * @param $bpid 1 pc 2 android 3 iphone
	 * @return unknown
	 */
	public function sendToServer($mid,$money,$port,$actid,$bpid=1){ 
		if((!$mid=Helper::uint($mid)) || (empty($money)) || (empty($port)) || (empty($actid)) || (empty($bpid))){ 
			return false;
		}
		$body = array(
					array($mid,API_INT),
					array($money,API_INT),
					array($actid,API_WORD),
					array($bpid,API_NUM)
		);
		$buffer= '';
		//获得BODY长度
		$this->_head = "";
		$this->_body = "";
		$this->setBody($body);
		$len = $this->getBodyLen($body);

		//设置头部HEADER
		$this->setHeader($len);
		$checkcode = $this->encryptData($len);
		//设置校验码
		$this->setCheckcode($checkcode);
		//获得全部数据
		$string =  Packer::factory()->getPackage(); 
		//发起socket
		//if(!Socket::factory()->isConnect()){ 
		Socket::factory()->set_server(Core_Game::$serverIP,$port);
		Socket::factory()->connect();
		Logs::factory()->debug('packer/socket',Socket::factory()->get_errmsg(),Core_Game::$serverIP,$port);
		//}		
		//发送内容
		$ret = Socket::factory()->write_line($string);
		$buffer = Socket::factory()->read_line_str(JW_HEADER_LEN + $len);
		Socket::factory()->close();
		$decrypt = $this->decryptData($buffer,JW_SOCKET_BACK_LEN,'money');
		Logs::factory()->debug('packer/money',$decrypt,$body);
		return $decrypt;
	}
	/**
	 * 单纯发头部命令
	**/
	public function sendHeader($mid,$port){ 
		if((!$mid=Helper::uint($mid)) || empty($port)){
			return false;
		}
		$body = array(
					array($mid,API_INT)
		);
		$buffer= '';
		//获得BODY长度
		$this->_head = "";
		$this->_body = "";
		$this->setBody($body);
		$len = $this->getBodyLen($body);
		//设置头部HEADER
		$this->setHeader($len,CLIENT_COMMAND_PHP_SEND_GIFE);
		$checkcode = $this->encryptData($len);
		//设置校验码
		$this->setCheckcode($checkcode);
		//获得全部数据
		$string =  Packer::factory()->getPackage();
		//发起socket
		//if(!Socket::factory()->isConnect()){ 
			Socket::factory()->set_server(Core_Game::$serverIP,$port);
			Socket::factory()->connect();	
			Logs::factory()->debug('packer/socketheader',Socket::factory()->get_errmsg(),Core_Game::$serverIP,$port);
		//}	
		//发送内容
		$ret = Socket::factory()->write_line($string);
		$buffer = Socket::factory()->read_line_str(JW_HEADER_LEN + $len);
		Socket::factory()->close();
		$decrypt = $this->decryptData($buffer,JW_SOCKET_BACK_LEN,'back');
		Logs::factory()->debug('packer/header',$decrypt);
		return $decrypt;
	}
}
?>