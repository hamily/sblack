<?php

echo $_FILES["big_img"]["name"]."|". $_FILES["icon_img"]["name"];

$img_path = $_POST['img_path'];

if(!is_dir($img_path ))  {
    mkdir($img_path, 0777 );
}
exit;
$big_img_name = $_FILES["big_img"]["name"];
$icon_img_name= $_FILES["icon_img"]["name"];

$big_img_tmp_name = $_FILES["big_img"]["tmp_name"];
$icon_img_tmp_name= $_FILES["icon_img"]["tmp_name"];


if(@copy($big_img_tmp_name, $img_path . '/' . $big_img_name)) {
    @unlink($big_img_tmp_name);
} else {
    exit($img_path . '/' .$big_img_name . "fail");
}

if(@copy($icon_img_tmp_name, $img_path . '/' . $icon_img_name)) {
    @unlink($icon_img_tmp_name);
} else {
    exit($img_path . '/' .$icon_img_name . "fail");
}

echo $icon_img_name . 'ok';


?>