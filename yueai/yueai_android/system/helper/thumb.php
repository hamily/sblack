<?php 
defined('YUEAI') OR exit('Access Denied!');

class Thumb{

	//生成缩略图
	public static function makethumb($srcfile, $width, $height, $dstfile) {
		
		//判断文件是否存在
		if (!file_exists($srcfile)) {
			return '';
		}

		//缩略图大小
		$tow = intval($width);
		$toh = intval($height);

		if($tow < 42) $tow = 42;
		if($toh < 42) $toh = 42;
	
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
	
	
}//end-class