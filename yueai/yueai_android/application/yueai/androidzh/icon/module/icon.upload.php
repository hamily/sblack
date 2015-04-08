<?php
defined('YUEAI') or exit( 'Access Denied！');
/*
define('CDN_IMG_URL','https://fbpoker.boyaagame.com/fb/kingslave/images/head/');
define('CDN_IMG_PATH','/disk1/wwwroot/static/facebook/fb/kingslave/images/head/');
define('CDN_POST_URL','http://208.43.166.73/fb/kingslave/iconuploadcdn.php');
*/
class icon_upload {
	public $imgType = array("jpg","jpeg","png","gif");	//允許上傳的圖片類型
	public $size = 2097152;								//允許上傳圖片大小 1024 * 1024 * 2 2 M
	protected $domainUrl;								//圖片域名
	protected $uploadPath;								//上傳路徑
	protected static $_instance = array();
	
	/**
	 * 对象生产器
	 */
	public static function factory(){
		if(!is_object(self::$_instance['upload'])){
			self::$_instance['upload'] = new icon_upload();
		}
		return self::$_instance['upload'];
	}
	/**
	 * 构造函数
	 */
	public function __construct() {
		$this->uploadPath = "/alidata/www/default/usericon";
		$this->domainUrl = Core_System::$appUrl . "usericon/";
	}
	/**
	 * 判断上传文件类型
	 * @param  $ext
	 * @return bool(true/false)
	 */
	public function checkext($ext) {
		return in_array($ext,$this->imgType) ? true : false;
	}
	/**
	 * 判断大小
	 * @param $size
	 * @return bool(true/false)
	 */
	public function checksize($size){
		return ($size <= $this->size) ? true : false;
	}
	/**
	 * 上传图片
	 * @param $param
	 */
	public function upload($mid,$files) { 
		//判斷用戶
		if(! ( $mid = Helper::uint( $mid)) || empty($files)){
			return array("flag"=>0,"msg"=>Core_Error::getError(-1));
		}
		//判斷圖片類型
		$ext = strtolower(trim(substr(strrchr($files["icon"]["name"], '.'), 1)));
		if(!$this->checkext($ext)){
			return array("flag"=>0,"msg"=>Core_Error::getError(-5));
		}
		//判斷大小
		$size = Helper::uint( $files['icon']['size']);
		if(!$this->checksize( $size)){
			return array("flag"=>0,"msg"=>Core_Error::getError(-6));
		}
		//按用戶mid分子目錄
		$subname = $mid % 100;

		if(!is_dir($this->uploadPath)) mkdir($this->uploadPath);
		if(!is_dir($this->uploadPath . "/" . $subname . '/')) mkdir($this->uploadPath . "/" . $subname  . '/');
		
		$new_name = $this->uploadPath . "/" . $subname  . '/' . $mid . '.jpg';
		$tmp_name = $files["icon"]["tmp_name"];
		if(@copy($tmp_name, $new_name)) {
			@unlink($tmp_name);
		} elseif((function_exists('move_uploaded_file') && @move_uploaded_file($tmp_name, $new_name))) {
		} elseif(@rename($tmp_name, $new_name)) {
		} else {
			return array("flag"=>0,"msg"=>Core_Error::getError(-7));
		}
		$timebefore = $this->setIconTime($mid,0);
		$icon_before_image = $mid . $timebefore . "_icon.jpg";
		$middle_before_image = $mid . $timebefore . "_middle.jpg";
		$big_before_image = $mid . $timebefore. "_big.jpg";
		if($timebefore>0){
			//@unlink($this->uploadPath . $subname  . '/' .$icon_before_image);
			@unlink($this->uploadPath . $subname  . '/' .$middle_before_image);
			@unlink($this->uploadPath . $subname  . '/' .$big_before_image);
		}
		
		//製作縮略圖
		$time = time();
		$icon_image = $mid . "_icon.jpg";
		$middle_image = $mid . "_middle.jpg";
		$big_image = $mid . "_big.jpg";		
		$big_new_image = $this->uploadPath . $subname  . '/' . $middle_image;
		$icon_new_image = $this->uploadPath . $subname  . '/' . $icon_image;
		
		$this->makethumb( $this->uploadPath . '/' . $subname  . '/' . $mid . '.jpg', 30, 30, $this->uploadPath . "/" . $subname  . '/' .$icon_image );
		$this->makethumb( $this->uploadPath . '/' . $subname  . '/' . $mid . '.jpg', 50, 50, $this->uploadPath . "/" . $subname  . '/' . $middle_image );
		$this->makethumb( $this->uploadPath . '/' . $subname  . '/' . $mid . '.jpg', 100, 100, $this->uploadPath . "/" . $subname  . '/' . $big_image );
		
		//$time = time();
		$info['icon'] = $this->domainUrl . $subname . '/' . $icon_image . '?v=' . $time;
		$info['middle'] = $this->domainUrl . $subname . '/' . $middle_image . '?v=' . $time;
		$info['big'] = $this->domainUrl . $subname . '/' . $big_image . '?v=' . $time;
		
		$this->setIconTime($mid,1,$time);
		
		/*
		$cmd = "chmod -R 775 " . $this->uploadPath . $subname;
		exec($cmd,$text);
		//同步上传头像到CDN
		$ret = $this->CurlImg($mid,$big_new_image,$icon_new_image);
		echo $ret;	
			
		*/
		return array("flag"=>1,'info'=>$info);
	}
	/**
	 * 把上传头像传以CDN
	 *
	 * @param unknown_type $mid
	 * @param unknown_type $big_img_path
	 * @param unknown_type $icon_img_path
	 * @return unknown
	 */
	public function CurlImg($mid, $big_img_path, $icon_img_path)
	{
		$subname = $mid % 100;

		$path = CDN_IMG_PATH . $subname;
		$post_data = array(
			'img_path' => $path,
			"big_img"  => "@".realpath($big_img_path),
			"icon_img"  => "@".realpath($icon_img_path)
		);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, CDN_POST_URL);
		curl_setopt($curl, CURLOPT_POST, 1 );
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($curl, CURLOPT_TIMEOUT, 15);
		//curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/4.0");
		$result = curl_exec($curl);
		$error = curl_error($curl);
		curl_close($curl);

		return $error ? $error : $result;
	}
	/**
	 * 生成缩略图
	 *
	 * @param unknown_type $srcfile 源图片地址
	 * @param unknown_type $width	缩略图宽度
	 * @param unknown_type $height  缩略图高度
	 * @param unknown_type $dstfile 生成目标文件地址
	 * @return unknown
	 */
	public function makethumb($srcfile, $width, $height, $dstfile) 
	{
		//判断文件是否存在
		if (!file_exists($srcfile)) {
			return '';
		}

		//缩略图大小
		$tow = intval($width);
		$toh = intval($height);
		if($tow < 30) $tow = 30;
		if($toh < 30) $toh = 30;
	
		$make_max = 0;
		$maxtow = 300;
		$maxtoh = 300;
		if($maxtow >= 300 && $maxtoh >= 300) {
			$make_max = 1;
		}
		
		//获取图片信息
		$im = '';
		if($data = getimagesize($srcfile)) {
			if($data[2] == 1) {
				$make_max = 0;//gif不处理
				if(function_exists("imagecreatefromgif")) {
					$im = imagecreatefromgif($srcfile);
				}
			} elseif($data[2] == 2) {
				if(function_exists("imagecreatefromjpeg")) {
					$im = imagecreatefromjpeg($srcfile);
				}
			} elseif($data[2] == 3) {
				if(function_exists("imagecreatefrompng")) {
					$im = imagecreatefrompng($srcfile);
				}
			}
		}
		if(!$im) return '';
		$srcw = imagesx($im);
		$srch = imagesy($im);
		
		$towh = $tow/$toh;
		$srcwh = $srcw/$srch;
		if($towh <= $srcwh){
			$ftow = $tow;
			$ftoh = $ftow*($srch/$srcw);
			
			$fmaxtow = $maxtow;
			$fmaxtoh = $fmaxtow*($srch/$srcw);
		} else {
			$ftoh = $toh;
			$ftow = $ftoh*($srcw/$srch);
			
			$fmaxtoh = $maxtoh;
			$fmaxtow = $fmaxtoh*($srcw/$srch);
		}
		if($srcw <= $maxtow && $srch <= $maxtoh) {
			$make_max = 0;//不处理
		}
		if($srcw > $tow || $srch > $toh) {
			if(function_exists("imagecreatetruecolor") && function_exists("imagecopyresampled") && @$ni = imagecreatetruecolor($ftow, $ftoh)) {
				imagecopyresampled($ni, $im, 0, 0, 0, 0, $ftow, $ftoh, $srcw, $srch);
				//大图片
				if($make_max && @$maxni = imagecreatetruecolor($fmaxtow, $fmaxtoh)) {
					imagecopyresampled($maxni, $im, 0, 0, 0, 0, $fmaxtow, $fmaxtoh, $srcw, $srch);
				}
			} elseif(function_exists("imagecreate") && function_exists("imagecopyresized") && @$ni = imagecreate($ftow, $ftoh)) {
				imagecopyresized($ni, $im, 0, 0, 0, 0, $ftow, $ftoh, $srcw, $srch);
				//大图片
				if($make_max && @$maxni = imagecreate($fmaxtow, $fmaxtoh)) {
					imagecopyresized($maxni, $im, 0, 0, 0, 0, $fmaxtow, $fmaxtoh, $srcw, $srch);
				}
			} else {
				return '';
			}
			if(function_exists('imagejpeg')) {
				imagejpeg($ni, $dstfile);
				//大图片
				if($make_max) {
					imagejpeg($maxni, $srcfile);
				}
			} elseif(function_exists('imagepng')) {
				imagepng($ni, $dstfile);
				//大图片
				if($make_max) {
					imagepng($maxni, $srcfile);
				}
			}
			imagedestroy($ni);
			if($make_max) {
				imagedestroy($maxni);
			}
		}
		
		imagedestroy($im);
	
		if(!file_exists($dstfile)) {
			return '';
		} else {
			return $dstfile;
		}
	}
	/**
	 * 设置玩家最后一台更新头像的时间
	 *
	 * @param unknown_type $mid
	 * @param unknown_type $type
	 * @param unknown_type $time
	 * @return unknown
	 */
	public function setIconTime($mid,$type=0,$time=0){
		if(!$mid=Helper::uint($mid)){
			return false;
		}
		$cachekey = Icon_Keys::mkmbicontime($mid);
		if($type==0){
			$time = (int)Loader_Redis::redistask()->get($cachekey,false,false);
			if($time==0){
				$time = mt_rand(1,999999);
				Loader_Redis::redistask()->set($cachekey,$time,false,false,90*24*3600);
			}
			return $time;
		}else{ 
			//存三个月
			return Loader_Redis::redistask()->set($cachekey,$time,false,false,90*24*3600);
		}
	}
}

?>