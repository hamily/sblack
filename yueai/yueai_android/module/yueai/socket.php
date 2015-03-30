<?php
/**
 * socket 通信
**/
class Socket{
	public $sock = 0;
	public $host = '';
	public $port = 0;
	public $timeout = 2;
	public $buffer = '';
	public $eol = "";
	public $errmsg = '';
	public $connected = 0;
	public $errno = 0;

	protected static $_instance = array();
	public static function factory(){
		if(!is_object(self::$_instance['socket'])){
			self::$_instance['socket'] = new Socket();
		}
		return self::$_instance['socket'];
	}

	function set_server($host, $port, $timeout = 2){
		$this->host     = $host;
		$this->port     = $port;
		$this->timeout  = $timeout;
		return(0);
	}

	function set_eol($eol = "\r\n"){
		$this->eol = $eol;
		return(0);
	}

	function get_errmsg(){
		return $this->errno . $this->errmsg;
	}

	function connect(){ 
		//if($this->connected==0){
			$this->host = trim($this->host);
			$this->sock = @fsockopen($this->host, $this->port, $this->errno, $this->errmsg, $this->timeout);
			if(!$this->sock){
				$this->sock = @pfsockopen($this->host, $this->port, $this->errno, $this->errmsg, $this->timeout);
			}
			if($this->sock > 0){ 
				$this->connected = 1;
				stream_set_timeout($this->sock, $this->timeout);
				return(0);
			}else{
				return(-1);
			}
		//}
		
	}

	function close(){
		if($this->sock && fclose($this->sock)){ 
			$this->connected = 0;
			return 0;
		}else{
			return -1;
		}
	}
	
	function read_line_bin($offset, $len){
		if ($this->sock <= 0) return false;
		$dlen = $offset + $len;
		$this->buffer = stream_get_line($this->sock, $dlen);
		if(strlen($this->buffer) != $dlen){
			$this->errmsg = $this->host .':'. $this->port.' socket string format error';
			return false;
		}

		$bufferArr = unpack("C3byte/Ndword", $this->buffer);
		$tlen = $bufferArr['dword'];
		$this->buffer .= stream_get_contents( $this->sock, $tlen - $dlen);
		$stream_info  = stream_get_meta_data( $this->sock );
		if($stream_info['timed_out']){
			$this->errmsg = $this->host.':'.$this->port.'socket_timed_out';
			return false;
		}
		return( $this->buffer );
	}
	
	function read_line_str($len){
		if($this->sock <= 0) return false;
		$this->buffer = fread($this->sock,$len);//stream_get_line($this->sock,$len);
		//$stream_info  = stream_get_meta_data($this->sock);
		//if($stream_info['timed_out']) $this->errmsg = 'socket_timed_out';
		return $this->buffer;
	}

	function write_line($str){
		if($this->sock <= 0) return false;
		$ret = fwrite($this->sock, $str.$this->eol);
		//$stream_info  = stream_get_meta_data($this->sock);
		//if($stream_info['timed_out']) $this->errmsg = 'socket_timed_out';
		return $ret;
	}
	
	function isConnect(){
		return $this->connected;
	}
}
?>