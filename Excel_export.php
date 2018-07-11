<?php
/**
 * Created by 厚柏科技.
 * User: 代码如风
 * Date: 2018/7/11
 * Time: 17:21
 * 这些代码是基于PHP框架Koala来写的  需要自己调配一下
 */



/**
 * 导出列表文件
 */
function Action_Creat_Excel(){
    $excelType  = empty($this -> request -> param('id')) ? 0 : $this -> request -> param('id');  //导出的类型 0：全部 1：最新的
    switch($excelType){
        case 0:   //全部的打印
            $links_list = ORM::factory('H5phonethere')
                -> find_all();
            break;
        case 1:   //最新的打印
            $links_list = ORM::factory('H5phonethere')
                -> where('isexccel','<>',1)
                -> find_all();
            break;
    }

    $ex = '';

    $objExcel = new \PHPExcel();
    $objExcel->getProperties()->setCreator("MiChao");
    $objExcel->getProperties()->setLastModifiedBy("MiChao");
    $objExcel->getProperties()->setTitle("Office 2003 XLS Test Document");
    $objExcel->getProperties()->setSubject("Office 2003 XLS Test Document");
    $objExcel->getProperties()->setDescription("Test document for Office 2003 XLS, generated using PHP classes.");
    $objExcel->getProperties()->setKeywords("office 2003 openxml php");
    $objExcel->getProperties()->setCategory("Test result file");
    $objExcel->setActiveSheetIndex(0);
    $i=0;
    //表头
    $k1="手机号码";
    $k2="提交时间";
    $objExcel->getActiveSheet()->setCellValue('a1', "$k1");
    $objExcel->getActiveSheet()->setCellValue('b1', "$k2");
    foreach($links_list as $k=>$v) {
        $u1=$i+2;
        /*----------写入内容-------------*/
        $objExcel->getActiveSheet()->setCellValue('a'.$u1, $v -> phone);
        $objExcel->getActiveSheet()->setCellValue('b'.$u1, date('Y-m-d H:i:s',$v -> addtime));
        $i++;
    }
    // 高置列的宽度
    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);

    $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
    $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');

    // 设置页方向和规模
    $objExcel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
    $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
    $objExcel->setActiveSheetIndex(0);
    $timestamp = '华大大兑现列表';
    if($ex == '2007') { //导出excel2007文档
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$timestamp.'.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
        $objWriter->save('php://output');
        $this -> changeExcel();
        exit;
    } else {  //导出excel2003文档
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$timestamp.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        $objWriter->save('php://output');
        $this -> changeExcel();
        exit;
    }
}

 function Action_list(){  //粉丝信息展示
    $thisurl= 'http://'.$_SERVER['SERVER_NAME'];
    if(empty($_POST)){
        echo json_encode(array('status' => 400));die;
    }
    $type  = empty($_POST['signType']) ? 0 : $_POST['signType'];
    $sign  = empty($_POST['sign']) ? 0 :$_POST['sign'];
    $checkToken = $this -> commoncheck($type,$sign);
    if(!$checkToken){
        echo json_encode(array('status' => 201));die;
    }
    $uid = empty($_POST['userId']) ? 0 : $_POST['userId'];
    //$uid=4;
    $value =$thisurl.'/weixin/makeCode/'.$uid; //二维码内容
    $inviteHref = $thisurl.'/weixin/makeCode/'.$uid;
    $arr=array();
    $count=ORM::factory('Inviteposter')->where('uid','=',$uid)->count_all();
    if($count>0){
        $list=ORM::factory('Inviteposter')->where('uid','=',$uid)->find_all();
        foreach ($list as $k=>$v){
            $arr[$k]['inviteId']=$v->id;
            $arr[$k]['inviteImage']='upload/'.$v->image;
            $arr[$k]['inviteHref']=$inviteHref;
        }
        echo json_encode(array('status' => 200,'result' => $arr));
    }else{
        $dbwebconfig=ORM::factory('Webconfig',array('modulename'=>'APP_SET','key'=>'APP_SHARE_IMAGE'));
        if($dbwebconfig->loaded()){

            //生成二维码图片
            $imgurl='./upload/shareposter/qrcode/'.$uid.'.png';
            $value = '';
            $selectValue = ORM::factory('Webconfig',array('modulename'=>'APP_SET','key'=>'APP_DOWNLOAD'));
            if($selectValue -> loaded()){
                $value = $selectValue -> value;
            }
            if(!file_exists($imgurl)){
                QRCode::instance()->png($value, $imgurl, QRConst::QR_ECLEVEL_H,1,1);
            }

            $imageArr=explode(',',$dbwebconfig->value);
            if(!empty($imageArr)){
                /*$listinfo=ORM::factory('Inviteposter')->where('uid','=',$uid)->find_all();
                foreach ($listinfo as $k=>$v){
                    $filename='./upload/' . $v->image;
                    if(file_exists($filename)) {
                        unlink($filename);
                    }
                }
                $invitepostersdel = DB::delete('inviteposters')
                    ->where('uid', '=', $uid)->execute();*/
                foreach ($imageArr as $v){
                    $url=$this->addActImg('./upload/'.$v,$imgurl);
                    //Imgcompress::instance()->compressImg('./upload/'.$url,1,'./upload/'.$url);
                    $dbInviteposter=ORM::factory('Inviteposter');
                    $dbInviteposter->uid=$uid;
                    $dbInviteposter->image=$url;
                    $dbInviteposter->save();
                }
                $count=ORM::factory('Inviteposter')->where('uid','=',$uid)->count_all();
                if($count>0){
                    $list=ORM::factory('Inviteposter')->where('uid','=',$uid)->find_all();
                    foreach ($list as $k=>$v){
                        $arr[$k]['inviteId']=$v->id;
                        $arr[$k]['inviteImage']='upload/'.$v->image;
                        $arr[$k]['inviteHref']=$inviteHref;
                    }
                    echo json_encode(array('status' => 200,'result' => $arr));
                }else{
                    echo json_encode(array('status' => 208));   //失败
                }
            }

        }


        //echo json_encode(array('status' => 208));   //失败
    }
    /*else{
        $count=ORM::factory('Inviteposter')->where('uid','=',$uid)->count_all();
        if($count>0){
            $list=ORM::factory('Inviteposter')->where('uid','=',$uid)->find_all();
            foreach ($list as $k=>$v){
                $arr[$k]['inviteId']=$v->id;
                $arr[$k]['inviteImage']='upload/'.$v->image;
                $arr[$k]['inviteHref']=$value;
            }
            echo json_encode(array('status' => 200,'result' => $arr));
        }else{
            echo json_encode(array('status' => 208));   //失败
        }

    }*/

}
 function addActImg($photo,$kuang){
    $kuang = $this->myImageResize($kuang, 180, 180);
    $image_1 = imagecreatefrompng($photo);
    $image_2 = imagecreatefrompng($kuang);

    $filename = strtolower(Text::random('alnum', 20)).'.png';
    $directory='shareposter/'.date('Y-m-d').'/';
//创建一个和人物图片一样大小的真彩色画布（ps：只有这样才能保证后面copy装备图片的时候不会失真）
    $image_3 = imageCreatetruecolor(imagesx($image_1),imagesy($image_1));
//为真彩色画布创建白色背景，再设置为透明
    $color = imagecolorallocate($image_3,  240, 240, 216);
    imagefill($image_3, 0, 0, $color);
    imageColorTransparent($image_3, $color);
//首先将人物画布采样copy到真彩色画布中，不会失真
    imagecopyresampled($image_3,$image_1,0,0,0,0,imagesx($image_1),imagesy($image_1),imagesx($image_1),imagesy($image_1));
//再将装备图片copy到已经具有人物图像的真彩色画布中，同样也不会失真
    imagecopymerge($image_3,$image_2, 177,481,0,0,imagesx($image_2),imagesy($image_2), 100);
    $colorbg = imagecolorallocate($image_3,  80, 80, 80);
    $font='./wechats/font/msyh.ttc';
    //imagettftext($image_3,20,0,116,950,$colorbg,$font,$nickname.$content); //加水印
    if (!is_dir('./upload/'.$directory)){
        mkdir(iconv("UTF-8", "GBK", './upload/'.$directory),0777,true);
    }


//将画布保存到指定的gif文件
    imagepng($image_3,'./upload/'.$directory.$filename);


    $img=$directory.$filename;
    // $image_2 = $this -> resizepng($photo,980,920);

    //imagedestroy($img);
    return $img;

}
/**
 * 图片缩放函数（可设置高度固定，宽度固定或者最大宽高，支持gif/jpg/png三种类型）
 * Author : Specs
 *
 * @param string $source_path 源图片
 * @param int $target_width 目标宽度
 * @param int $target_height 目标高度
 * @param string $fixed_orig 锁定宽高（可选参数 width、height或者空值）
 * @return string
 */
function myImageResize($source_path, $target_width = 200, $target_height = 200, $fixed_orig = ''){
    $source_info = getimagesize($source_path);
    $source_width = $source_info[0];
    $source_height = $source_info[1];
    $source_mime = $source_info['mime'];
    $ratio_orig = $source_width / $source_height;
    if ($fixed_orig == 'width'){
        //宽度固定
        $target_height = $target_width / $ratio_orig;
    }elseif ($fixed_orig == 'height'){
        //高度固定
        $target_width = $target_height * $ratio_orig;
    }else{
        //最大宽或最大高
        if ($target_width / $target_height > $ratio_orig){
            $target_width = $target_height * $ratio_orig;
        }else{
            $target_height = $target_width / $ratio_orig;
        }
    }
    $source_image = imagecreatefrompng($source_path);
    $target_image = imagecreatetruecolor($target_width, $target_height);
    imagecopyresampled($target_image, $source_image, 0, 0, 0, 0, $target_width, $target_height, $source_width, $source_height);
    //header('Content-type: image/jpeg');
    $imgArr = explode('.', $source_path);
    $target_path ='.'. $imgArr[0]. $imgArr[1].'_new'.'.png';

    imagepng($target_image, $target_path);
    return $target_path;
}

/**
 * 改变图片的尺寸的大小
 * @param $imgsrc  图片地址
 * @param $imgwidth  改变的宽度
 * @param $imgheight 改变的高度
 * @return resource  返回图片的资源信息
 */
function resizepng($imgsrc,$imgwidth,$imgheight)
{
    //$imgsrc jpg格式图像路径 $imgdst jpg格式图像保存文件名 $imgwidth要改变的宽度 $imgheight要改变的高度
    //取得图片的宽度,高度值
    $arr = getimagesize($imgsrc);
    $imgWidth = $imgwidth;
    $imgHeight = $imgheight;
    // Create image and define colors
    $imgsrc = imagecreatefrompng($imgsrc);
    $image = imagecreatetruecolor($imgWidth, $imgHeight); //创建一个彩色的底图
    imagecopyresampled($image, $imgsrc, 0, 0, 0, 0, $imgWidth, $imgHeight, $arr[0], $arr[1]);

    return $image;
}

/**
 * 合成图片的方法
 * @param $photo  原图
 * @param $title  商品标题
 * @param $price  原价
 * @param $couponprice  优惠券的价格
 * @param $goodsId  商品id
 * @param $uid   用户的id
 * @return string  返回的是需要分享的图片的地址
 */
function SynthesisImage($photo,$title,$price,$couponprice,$goodsId,$uid){       //合成图片

    $kuang =  'http://'.$_SERVER['SERVER_NAME'].'/media/bgimage/share.png';

    //增加二维码的内容
    $thisurl = 'http://'.$_SERVER['SERVER_NAME'];
    $value   =$thisurl.'/appcopyshow/Showcopy/'.$goodsId.'/2/'.$uid; //二维码内容

    //生成二维码图片地址
    $image_8='./upload/sheareqrcode/'.$goodsId.'.png';

    if(!file_exists($image_8)){
        QRCode::instance()->png($value, $image_8, QRConst::QR_ECLEVEL_H,5,1);
    }

    $image_1 = imagecreatefrompng($kuang);
    $image_2 = $this -> resizejpg($photo,980,920);
    $image_9 = imagecreatefrompng($image_8);


    $filename = $uid.'_'.$goodsId.'.png';
    $directory='taobaoPassword/';
    //创建一个和人物图片一样大小的真彩色画布（ps：只有这样才能保证后面copy装备图片的时候不会失真）
    $image_3 = imageCreatetruecolor(imagesx($image_1),imagesy($image_1));
    //为真彩色画布创建白色背景，再设置为透明
    $color = imagecolorallocate($image_3,  211, 214, 216);
    imagefill($image_3, 0, 0, $color);
    imageColorTransparent($image_3, $color);
    //首先将人物画布采样copy到真彩色画布中，不会失真
    imagecopyresampled($image_3,$image_1,0,0,0,0,imagesx($image_1),imagesy($image_1),imagesx($image_1),imagesy($image_1));
    //再将装备图片copy到已经具有人物图像的真彩色画布中，同样也不会失真
    imagecopymerge($image_3,$image_2, 0,0,0,0,imagesx($image_2),imagesy($image_2), 100);

    //设置二维码到图片上
    imagecopymerge($image_3,$image_9, 700,975,0,0,imagesx($image_9),imagesy($image_9), 100);

    $colorbg = imagecolorallocate($image_3,  0, 0, 0);
    $font='./wechats/font/msyh.ttc';

    //水印的大小
    $lineWidth = imagesx($image_1) - 10 - 300;

    $lineArr = $this -> autoLineSplit($title, $font, 30, 'utf8', $lineWidth);

    foreach ($lineArr as $k => $v) {
        imagettftext($image_3, 30, 0, 10, (980 + (40 * $k)), $colorbg, $font, $v);
    }

    //增加销售价格
    $colorbgtwo = imagecolorallocate($image_3,  80, 80, 80);
    imagettftext($image_3,20,0,40,1200,$colorbgtwo,$font,'销售价：￥'.$price); //加水印

    //增加优惠券的价格
    $colorbgthere = imagecolorallocate($image_3,  255, 105, 180);
    imagettftext($image_3,20,0,93,1290,$colorbgthere,$font,$couponprice); //加水印

    if (!is_dir('./upload/'.$directory)){
        mkdir(iconv("UTF-8", "GBK", './upload/'.$directory),0777,true);
    }

    $new_width  =  972*0.5;
    $new_height =  1332*0.5;

    $imageColer = imagecreatetruecolor($new_width,$new_height); //创建一个彩色的底图

    imagecopyresampled($imageColer,$image_3,0,0,0,0,$new_width,$new_height,972,1332);


    //将画布保存到指定的gif文件
    imagepng($imageColer,'./upload/'.$directory.$filename);

    $img=$directory.$filename;


    return $img;
}

/**
 * 这是把字符串通过水印宽度来进行的吧字符串转化成数组的方法
 * @param $str  字符串
 * @param $fontFamily  字体
 * @param $fontSize  字体大小
 * @param $charset   编码
 * @param $width     宽度
 * @return array   返回切割后的数组
 */
function autoLineSplit ($str, $fontFamily, $fontSize, $charset, $width) {
    $result = [];

    $len = (strlen($str) + mb_strlen($str, $charset)) / 2;

    // 计算总占宽
    $dimensions = imagettfbbox($fontSize, 0, $fontFamily, $str);
    $textWidth = abs($dimensions[4] - $dimensions[0]);

    // 计算每个字符的长度
    $singleW = $textWidth / $len;
    // 计算每行最多容纳多少个字符
    $maxCount = floor($width / $singleW);

    while ($len > $maxCount) {
        // 成功取得一行
        $result[] = mb_strimwidth($str, 0, $maxCount, '', $charset);
        // 移除上一行的字符
        $str = str_replace($result[count($result) - 1], '', $str);
        // 重新计算长度
        $len = (strlen($str) + mb_strlen($str, $charset)) / 2;
    }
    // 最后一行在循环结束时执行
    $result[] = $str;

    return $result;
}


/**
 * curl实例
 * @param $taobaoGoodsId  淘宝商品的id
 * @return mixed    返回图片数组
 */
 function getGoodsDetails($taobaoGoodsId){

    $src = 'https://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data={item_num_id:"'.$taobaoGoodsId.'"}&type=jsonp&dataType=jsonp';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $src);

    curl_setopt($curl, CURLOPT_HEADER, 1);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $data = curl_exec($curl);

    curl_close($curl);

    $arr = explode("\r\n\r\n", $data, 2);

    $classStr = $arr[1];

    $jsonStr = ltrim($classStr,'callback(');
    $jsonStr = rtrim($jsonStr,')');

    $imgarr = json_decode($jsonStr,true);


    $returnArr = $imgarr['data']['images'];

    return $returnArr;

}


