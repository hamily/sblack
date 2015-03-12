<?php 
defined('YUEAI') or exit( 'Access Denied！' );

class Values{
    static function getmb(){ //单记录所有值
        return array(
                    0 => 'mid',
                    1 => 'mnick', //昵称
                    2 => 'name', //用户真实姓名
                    3 => 'sid', //站点名
                    4 => 'unid',
                    5 => 'exp', //经验值
                    6 => 'money',	//金币
                    7 => 'bccoins',//博雅币
                    8 => 'level', //用户等级
                    9 => 'vip',
                    10 => 'viptime',
                    11 => 'mstatus', //用户状态
                    12 => 'wintimes', //赢的次数
                    13 => 'losetimes', //输的次数
                    14 => 'mactivetime', //最后登录时间
                    15 => 'mtaskcount',
                    16 => 'mentercount', //用户登录的次数
                    17 => 'sitemid',
                    18 => 'sex',
                    19 => 'mcommend',
                    20 => 'mtime',      //进入时间
                    21 => 'location',    //現居地
                    22 => 'hometown',   //家鄉
                    23 => 'matchstatus',  //比赛状态
                    24 => 'clubtime',	
                    25 => 'club',		
                    26 => 'facetime',	//表情包过期时间
                    27 => 'dbl_exp'		//双倍经验值
                );
    }
    static function getsave(){	//金币经信息
    	return array(
    				0	=>	'mid',
    				1	=>	'money',
    				2	=>	'exp',
    				3	=>	'level',
    				4	=>	'wintimes',
    				5	=>	'losetimes',
    				6	=>	'bccoins'
    			);
    }
    public static function getVip(){	//腾讯 用户vip信息
    	return array(
    				0	=>	'mid',
    				1	=>	'is_yellow_vip',
    				2	=>	'is_year_vip',
    				3	=>	'vip_level'
    	);
    }
    /**
     * king queen people slave
     */
    public static function getWin(){
    	return array(
    				0	=>	'mid',
    				1	=>	'king',
    				2	=>	'queen',
    				3	=>	'people',
    				4	=>	'slave'
    	);
    }
	/**
	 * 玩家支付次数统计
	 */
	public static function getPayinfo(){
    	return array(
    				0 => 'mid',
    				1 => 'paytimes',
    				2 => 'firstptime',
    				3 => 'lastptime'
    	);
    }
    /**
     * 把对应的值压入数组
     */
    static function combine($aKey, $aValue){
        foreach ((array)$aKey as $key => $value){
            $aTemp[$key] = $aValue[$value];
        }
        return $aTemp;
    }
    /**
     * 反转数组
     */
    static function uncombine($aKey, $aValue){
        foreach ((array)$aKey as $key => $value){
//            $aTemp[$value] = $aValue[$key];
            $aTemp[$value] = isset( $aValue[$key] ) ? $aValue[$key] : '';    //为解决iPhone问题加上的
        }
        return $aTemp;
    }
}