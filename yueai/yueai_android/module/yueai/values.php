<?php 
defined('YUEAI') or exit( 'Access Denied！' );

class Values {
	//单记录所有值
    static function getmb(){ 
        return array(
                    0 => 'mid',		//用户uid
                    1 => 'sitemid', //用户站点id
                    2 => 'sid', 	//用户站点sid
                    3 => 'mnick', 	//昵称
                    4 => 'sex',		//性别
                    5 => 'locate', 	//住址
                    6 => 'birth',	//金币
                    7 => 'star',	//星座
                    8 => 'weight', 	//用户等级
                    9 => 'blood',	//血型
                    10 => 'perfession',//职业
                    11 => 'house', 	//是否有房
                    12 => 'car', 	//是否有车
                    13 => 'marry', 	//是否已婚
                    14 => 'money', 	//收入
                    15 => 'bsex',	//是否接受婚前性行为
                    16 => 'bother', //是否接受异地恋
                    17 => 'bstay',	//是否接受父母同住
                    18 => 'bchild',	//是否要小孩
                    19 => 'interst',//兴趣爱好
                    20 => 'part',   //魅力部位
                    21 => 'status', //用户状态
                    22 => 'mactivetime', //上次登录时间
					23 => 'mtime',		//注册时间
					24 => 'mentercount',//登录次数
                );
    }
	
	//用户vip信息
    public static function getVip(){
    	return array(
    				0	=>	'mid',
    				1	=>	'viplevel',
    				2	=>	'vipstime',
    				3	=>	'vipetime',
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
    public static function combine($aKey, $aValue){
        foreach ((array)$aKey as $key => $value){
            $aTemp[$key] = $aValue[$value];
        }
        return $aTemp;
    }
    /**
     * 反转数组
     */
    public static function uncombine($aKey, $aValue){
        foreach ((array)$aKey as $key => $value){
            $aTemp[$value] = isset( $aValue[$key] ) ? $aValue[$key] : ''; 
        }
        return $aTemp;
    }
}