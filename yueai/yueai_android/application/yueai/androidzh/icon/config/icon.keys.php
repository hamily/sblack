<?php
defined('YUEAI') or exit( 'Access Denied！');
class Icon_Keys {
	const PREFIX = "KS_";
	
	public static function mkmbicontime($mid){
		return self::PREFIX . "MKICONTIME_" . $mid;
	}
}
?>