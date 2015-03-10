<?php !defined('BOYAA') AND exit('Access Denied!');

include_once 'EncryptDecrypt.php';

class SocketPacket {
	const SERVER_PACEKTVER = 1;
	const SERVER_SUBPACKETVER = 1; //2
	const PACKET_BUFFER_SIZE = 8192;
	const PACKET_HEADER_SIZE = 9;//9
	
	private $m_packetBuffer;
	private $m_packetSize;
	private $m_CmdType;
	private $m_cbCheckCode;
	private $m_version;
	private $m_subVersion;

	public function __construct(){
		$this->m_packetSize = 0;
		$this->m_packetBuffer = "";
		$this->m_version = self::SERVER_PACEKTVER;
		$this->m_subVersion = self::SERVER_SUBPACKETVER;
	}
	
	public function WriteBegin($CmdType, $version = self::SERVER_PACEKTVER, $subVersion = self::SERVER_SUBPACKETVER){
		$this->m_CmdType = $CmdType;
		$this->m_version = $version;
		$this->m_subVersion = $subVersion;
	}
	
	public function WriteEnd(){
		$EncryptObj = new EncryptDecrypt();
 		$code = 0;//$EncryptObj->EncryptBuffer($this->m_packetBuffer, 0, $this->m_packetSize);
     
		$content .= pack("s", $this->m_packetSize +7 );			//len
		$content .= "BY";
		$content .= pack("c", $this->m_version);			//ver
		$content .= pack("c", $this->m_subVersion);			//subver
		$content .= pack("s", $this->m_CmdType);			//cmd
		$content .= pack("c", $code);						//
		$this->m_packetBuffer = $content . $this->m_packetBuffer;

	}
		
	public function GetPacketBuffer(){
		return $this->m_packetBuffer;
	}
	
	public function GetPacketSize(){
		return $this->m_packetSize + self::PACKET_HEADER_SIZE ;
	}
	
	public function WriteInt($value){
		$this->m_packetBuffer .= pack("i", $value);
		$this->m_packetSize += 4;
	}
	
	public function WriteUInt($value){
		$this->m_packetBuffer .= pack("I", $value);
		$this->m_packetSize += 4;
	}
	
	public function WriteByte($value){
		$this->m_packetBuffer .= pack("C", $value);
		$this->m_packetSize += 1;
	}
	
	public function WriteShort($value){
		$this->m_packetBuffer .= pack("s", $value);
		$this->m_packetSize += 2;
	}
	
	public function WriteString($value){

		$len = strlen($value)+1;
		$this->m_packetBuffer .= pack("i", $len);
		$this->m_packetBuffer .= $value;
		$this->m_packetBuffer .= pack("C", 0);
		$this->m_packetSize += $len+4;
	}
	
	public function ParsePacket(){
		if( $this->m_packetSize < self::PACKET_HEADER_SIZE ){
			return false;
		}

		$header = substr($this->m_packetBuffer, 0, 9);
		$arr = unpack("sLen/c2Iden/sCmdType/cVer/cSubVer/cCode", $header);
 
		if($arr['Iden1'] != ord('B') || $arr['Iden2'] != ord('Y')){
			return -1;
		}
		if ($arr['Ver'] != $this->m_version || $arr['SubVer'] != $this->m_subVersion){
			//return -2;
		}
		if($arr['CmdType'] <= 0 || $arr['CmdType'] >= 32000){
			return -3;
		}
		if($arr['Len'] >= 0 && $arr['Len'] > self::PACKET_BUFFER_SIZE - self::PACKET_HEADER_SIZE ){
			return -4;
		}
	 	$this->m_cbCheckCode  = $arr['Code'];
		$this->m_packetBuffer = substr($this->m_packetBuffer, 9);

	//  $DecryptObj = new EncryptDecrypt();
	//  $code = $DecryptObj->DecryptBuffer($this->m_packetBuffer, $arr['Len'], $this->m_cbCheckCode);

		return 0;
	}
	
	public function SetRecvPacketBuffer($packet_buff, $packet_size){
		$this->m_packetBuffer = $packet_buff;
		$this->m_packetSize  = $packet_size;
	}

	public function ReadInt(){
		$temp = substr($this->m_packetBuffer, 0, 4);
		$value = unpack("i", $temp);
		$this->m_packetBuffer = substr($this->m_packetBuffer, 4);
		return $value[1];
	}
	
	public function ReadUInt(){
		$temp = substr($this->m_packetBuffer, 0, 4);
		list(,$var_unsigned)=unpack("L",$temp);
		return floatval(sprintf("%u",$var_unsigned));
	}

	public function ReadShort(){
		$temp = substr($this->m_packetBuffer, 0, 2);
		$value = unpack("s", $temp);
		$this->m_packetBuffer = substr($this->m_packetBuffer, 2);
		return $value[1];
	}
	
	public function ReadString(){
		$len = $this->ReadInt();
		$value = substr($this->m_packetBuffer, 0, $len);
		$this->m_packetBuffer = substr($this->m_packetBuffer, $len);
		return $value;
	}
	
	public function ReadByte(){
		$temp = substr($this->m_packetBuffer, 0, 1);
		$value = unpack("C", $temp);
		$this->m_packetBuffer = substr($this->m_packetBuffer, 1);
		return $value[1];
	}
}//end-class