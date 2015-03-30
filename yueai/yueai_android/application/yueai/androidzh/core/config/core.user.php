<?php
/**
 * 此类为用户相关配置
**/
defined('YUEAI') or exit('Access Denied！');

class Core_User {
	
	//星座
	private static $star = array(
							1	=>	'白羊座',
							2	=>	'金牛座',
							3	=>	'双子座',
							4	=>	'巨蟹座',
							5	=>	'狮子座',
							6	=>	'处女座',
							7	=>	'天秤座',
							8	=>	'天蝎座',
							8	=>	'射手座',
							10	=>	'摩羯座',
							11	=>	'水瓶座',
							12	=>	'双鱼座',
	);
	
	//血型
	private static $blood = array(
							1	=>	'A型',
							2	=>	'B型',
							3	=>	'AB型',
							4	=>	'O型',
	);
	
	//职业
	private static $perfession = array(
							1	=>	'上班族',
							2	=>	'私营老板'
							3	=>	'公务员',
	);
	
	//收入
	private static $money = array(
							1	=>	'2000以下',
							2	=>	'2000-5000',
							3	=>	'5000-10000',
							4	=>	'10000-20000',
							5	=>	'20000-50000',
							6	=>	'50000-100000',
							7	=>	'100000以上'
	);
	
	//魅力部位
	private static $part = array(
							1	=>	'脸部',
							2	=>	'胸部',
							3	=>	'臀部',
							4	=>	'手部',
							5	=>	'头部',
							6	=>	'四肢',
	);
	
	//marry
	private static $marry = array(
							0	=> 	'未婚',
							1	=>	'已婚',
							2	=>	'保密',
	);
	
	//兴趣受好
	private static $interest = array(
							1	=>	'购物',
							2	=>	'上网',
							3	=>	'骑车',
							4	=>	'旅游',
							5	=>	'徒步',
							6	=>	'交友',
							7	=>	'听音乐',
							8	=>	'游泳',
							9	=>	'跑步',
							10	=>	'看书',
	);
	
	//个人类型
	private static $style = array(
							1	=>	'淑女',
							2	=>	'霸女',
							3	=>	'真性情',
							4	=>	'无敌破坏',
	);
	
	//中国省市
	private static $province = array(
							1	=>	'北京市（京）', 
							2	=>	'天津市（津）', 
							3	=>	'上海市（沪）', 
							4	=>	'重庆市（渝）', 
							5	=>	'河北省（冀）', 
							6	=>	'河南省（豫） ',
							7	=>	'云南省（云） ',
							8	=>	'辽宁省（辽） ',
							9	=>	'黑龙江省（黑） ',
							10	=>	'湖南省（湘） ',
							11	=>	'安徽省（皖） ',
							12	=>	'山东省（鲁） ',
							13	=>	'新疆维吾尔（新） ',
							14	=>	'江苏省（苏） ',
							15	=>	'浙江省（浙） ',
							16	=>	'江西省（赣）', 
							17	=>	'湖北省（鄂） ',
							18	=>	'广西壮族（桂）', 
							19	=>	'甘肃省（甘）', 
							20	=>	'山西省（晋） ',
							21	=>	'内蒙古（蒙） ',
							22	=>	'陕西省（陕） ',
							23	=>	'吉林省（吉） ',
							24	=>	'福建省（闽） ',
							25	=>	'贵州省（贵） ',
							26	=>	'广东省（粤） ',
							27	=>	'青海省（青）', 
							28	=>	'西藏（藏） ',
							29	=>	'四川省（川） ',
							30	=>	'宁夏回族（宁） ',
							31	=>	'海南省（琼）',
							32	=>	'台湾省（台）',
							33	=>	'香港特别行政区',
							34	=>	'澳门特别行政区',
	);
	//通过公共函数返回上面用户相关信息资料
	public static function getUserOtherInfo($type=0){
		$info = $return = array();
		$info['star'] = self::$star;
		$info['blood'] = self::$blood;
		$info['perfession'] = self::$perfession;
		$info['money'] = self::$money;
		$info['part'] = self::$part;
		$info['marry'] = self::$marry;
		$info['interest'] = self::$interest;
		$info['style'] = self::$style;
		$info['province'] = self::$province;
		
		switch($type){
			case "1":
				$return = $info['star'];
				break;
			case "2":
				$return = $info['blood'];
				break;
			case "3":
				$return = $info['perfession'];
				break;
			case "4":
				$return = $info['money'];
				break;
			case "5":
				$return = $info['part'];
				break;
			case "6":
				$return = $info['marry'];
				break;
			case "7":
				$return = $info['interest'];
				break;
			case "8":
				$return = $info['style'];
				break;
			case "9":
				$return = $info['province'];
				break;
			default:
				$return = $info;
				break;
			
		}
		
		return $return;
		
	}
}
?>