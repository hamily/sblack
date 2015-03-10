<?php !defined('BOYAA') AND exit('Access Denied!');

class Lib_Data{
	
	/**
	 * 新的统计中心统计函数
	 * Enter description here ...
	 * @param int $bid 应用id b40 iphone b45 ipad
	 * @param int $actid 统计类型id
	 * @param int $mid
	 * @param array $arr
	 */
    public static function tj_send_loclog( $bid, $actid, $mid, $arr=array() )
	{
 
	    $str = "";
	    foreach( $arr as $v )
	    {
	        $v   = str_replace( "|", "", $v );
	        $str = $str."|".$v;
	    }
	    $socket = @socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );
	    if ( empty( $socket ) )
	    {
	        return;
	    }
	    @socket_set_option( $socket, SOL_SOCKET, SO_SNDTIMEO, array("sec"=>1, "usec"=>0) );
	    socket_sendto( $socket, "{$bid}{$actid}|".time()."|{$mid}".$str."\r\n", 1024, 0, "127.0.0.1", '11011' );
	    @socket_close( $socket );
	}
	/**
	 * tj_onlineres($bid, $dpid, $_GET['onlineres'], $_GET['playres'], $_GET['joinres']);
	 * 上报在玩在线
	 * @param int $bid  产品应用id
	 * @param int $dpid   值设20
	 * @param int $onlineres 在线人数
	 * @param int $playres  在玩人数
	 * @param int $joinres  旁观人数（不存在则报0）
	 */
	public static function tj_onlineres($bid=0, $dpid=0, $onlineres, $playres, $joinres){
		$url = 'http://data.boyaa.com/tj/onlineres.php';
		$fieldstring = "appid={$bid}&dpid={$dpid}&onlineres={$onlineres}&playres={$playres}&joinres={$joinres}";
		$ch = curl_init() ;
		curl_setopt($ch, CURLOPT_URL, $url) ;
		curl_setopt($ch, CURLOPT_POST, 5) ;
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldstring) ;
		$result = curl_exec($ch) ;
		curl_close($ch) ;
	}
	
	
}//end-class