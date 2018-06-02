<?php

function getIP(){
	global $ip;
	if (getenv("HTTP_CLIENT_IP"))
	$ip = getenv("HTTP_CLIENT_IP");
	else if(getenv("HTTP_X_FORWARDED_FOR"))
	$ip = getenv("HTTP_X_FORWARDED_FOR");
	else if(getenv("REMOTE_ADDR"))
	$ip = getenv("REMOTE_ADDR");
	else $ip = "Unknow";
	return $ip;
}
// 计算文件大小
function getsize($size, $format = 'kb'){
    $p = 0;
    if ($format == 'kb') {
        $p = 1;
    } elseif ($format == 'mb') {
        $p = 2;
    } elseif ($format == 'gb') {
        $p = 3;
    }
    $size /= pow(1024, $p);
    return number_format($size, 3);
}
/**
 * 图片压缩类：通过缩放来压缩。
 * 如果要保持源图比例，把参数$percent保持为1即可。
 * 即使原比例压缩，也可大幅度缩小。数码相机4M图片。也可以缩为700KB左右。如果缩小比例，则体积会更小。
 *
 * 结果：可保存、可直接显示。
 */
class imgcompress{
    private $src;
    private $image;
    private $imageinfo;
    private $percent = 0.5;
    /**
     * 图片压缩
     * @param $src 源图
     * @param float $percent  压缩比例
     */
    public function __construct($src, $percent=1)
    {
        $this->src = $src;
        $this->percent = $percent;
    }
    /** 高清压缩图片
     * @param string $saveName  提供图片名（可不带扩展名，用源图扩展名）用于保存。或不提供文件名直接显示
     */
    public function compressImg($saveName='')
    {
        $this->_openImage();
        if(!empty($saveName)) $this->_saveImage($saveName);  //保存
        else $this->_showImage();
    }
    /**
     * 内部：打开图片
     */
    private function _openImage()
    {
        list($width, $height, $type, $attr) = getimagesize($this->src);
        $this->imageinfo = array(
            'width'=>$width,
            'height'=>$height,
            'type'=>image_type_to_extension($type,false),
            'attr'=>$attr
        );
        $fun = "imagecreatefrom".$this->imageinfo['type'];
        $this->image = $fun($this->src);
        $this->_thumpImage();
    }
    /**
     * 内部：操作图片
     */
    private function _thumpImage()
    {
        $new_width = $this->imageinfo['width'] * $this->percent;
        $new_height = $this->imageinfo['height'] * $this->percent;
        $image_thump = imagecreatetruecolor($new_width,$new_height);
        //将原图复制带图片载体上面，并且按照一定比例压缩,极大的保持了清晰度
        imagecopyresampled($image_thump,$this->image,0,0,0,0,$new_width,$new_height,$this->imageinfo['width'],$this->imageinfo['height']);
        imagedestroy($this->image);
        $this->image = $image_thump;
    }
    /**
     * 输出图片:保存图片则用saveImage()
     */
    private function _showImage()
    {
        header('Content-Type: image/'.$this->imageinfo['type']);
        $funcs = "image".$this->imageinfo['type'];
        $funcs($this->image);
    }
    /**
     * 保存图片到硬盘：
     * @param  string $dstImgName  1、可指定字符串不带后缀的名称，使用源图扩展名 。2、直接指定目标图片名带扩展名。
     */
    private function _saveImage($dstImgName)
    {
        if(empty($dstImgName)) return false;
        $allowImgs = ['.jpg', '.jpeg', '.png', '.bmp', '.wbmp','.gif'];   //如果目标图片名有后缀就用目标图片扩展名 后缀，如果没有，则用源图的扩展名
        $dstExt =  strrchr($dstImgName ,".");
        $sourseExt = strrchr($this->src ,".");
        if(!empty($dstExt)) $dstExt =strtolower($dstExt);
        if(!empty($sourseExt)) $sourseExt =strtolower($sourseExt);
        //有指定目标名扩展名
        if(!empty($dstExt) && in_array($dstExt,$allowImgs)){
            $dstName = $dstImgName;
        }elseif(!empty($sourseExt) && in_array($sourseExt,$allowImgs)){
            $dstName = $dstImgName.$sourseExt;
        }else{
            $dstName = $dstImgName.$this->imageinfo['type'];
        }
        $funcs = "image".$this->imageinfo['type'];
        $funcs($this->image,$dstName);
    }
    /**
     * 销毁图片
     */
    public function __destruct(){
        imagedestroy($this->image);
    }
}

$ip = getIP();
if(!in_array($ip,array('61.145.160.146','119.145.100.58'))){  
	echo '禁止访问:'.$ip;
	exit();
}

if( $_POST){//判断上传图片
	$files = $_FILES['file'];//得到传输的数据

	//得到文件名称
	$id = $_POST['id'];

	$filename = $files['name'];

	$name = $_POST['filename'];//

	// 判断文件大小
    $image_size = getimagesize($files['tmp_name']); 

    if($image_size[0] > 640){

        if(move_uploaded_file($files['tmp_name'],$name)){

            $percent = 640/$image_size[0];

            $image = (new imgcompress($name,$percent))->compressImg($name);

            $data = $id.'@'.$name."\r\n";
            
            file_put_contents("record.txt", $data, FILE_APPEND);
            echo " <script> 
            window.parent.imgok($id);
            </script>"; 
        }

    }
	if(getsize(filesize($files['tmp_name']),'kb') > 260 ){

		$percent = 1;  #原图压缩，不缩放，但体积大大降低

		$image = (new imgcompress($files['tmp_name'],$percent))->compressImg($name);

		$data = $id.'@'.$name."\r\n";
		
		file_put_contents("record.txt", $data, FILE_APPEND);
		echo " <script> 
		window.parent.imgok($id);
		</script>";

	}else {
		$path = explode('/',$name);//转为数组

		array_pop($path);//将图片名称排出

		$paths = implode('/',$path);//重新转为字符串 得到排出域名的图片路径
		
		if(!is_dir($paths)){ //检查目录是否存在，否则自动创建图片路径

			mkdir($paths,0777,true); 
		}

		$type = strtolower(substr($filename,strrpos($filename,'.')+1)); //得到文件类型，并且都转化成小写

		$allow_type = array('jpg','jpeg','gif','png'); //定义允许上传的类型
		//判断文件类型是否被允许上传

		if(!in_array($type, $allow_type)){

			//如果不被允许，则直接停止程序运行
			echo "<script>
			  window.parent.imgtype($id); 
			  </script> ";
			  return false;
		}
		//判断是否是通过HTTP POST上传的
		if(!is_uploaded_file($files['tmp_name'])){

			//如果不是通过HTTP POST上传的
			return false;
		}
		//开始移动文件到相应的文件夹
		if(move_uploaded_file($files['tmp_name'],$name)){
	   
		$data=$id.'@'.$name."\r\n";

		file_put_contents("record.txt", $data, FILE_APPEND); //以追加形式写入日志
			echo " <script> 
			window.parent.imgok($id);
			 </script>";    
	  	}else{
		 	echo " <script> 
			window.parent.imgnot($id);  
			 </script>"; 
	 	}
	}
exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>图片</title>
	<link rel="stylesheet" href="js/bootstrap.min.css">
    <script src="js/jquery-2.1.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <style>
		.imgnot:after{content:'上传失败，请重新选择...';display:block;position:absolute;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.5);color:#FFF;line-height:170px}
		.imgtype:after{content:'文件类型错误，请重新选择...';display:block;position:absolute;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.5);color:#FFF;line-height:170px}
		.imgok{ border-color:#F00;}
		.thumbnail{position:relative;}
		.thumbnail:hover{box-shadow:0 0 10px rgba(0,102,255,.5);}
		.loading:after{content:'正在加载，请稍后...';display:block;position:absolute;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.5);color:#FFF;line-height:170px}
	</style>
</head>
<body style="text-align:center;">
<div id='imgpage' class='row'>
<?php

	/**
    * 获取目录中所有文件的路径
    * @param $dir
    * @return array
    */
    function my_scandir($dir)
    {

        if(is_dir($dir)){

            $files = [];

            $child_dirs = scandir($dir);

            foreach($child_dirs as $k => $child_dir){

                //'.'和'..'是Linux系统中的当前目录和上一级目录，必须排除掉，
                //否则会进入死循环，报segmentation falt 错误
                if($child_dir != '.' && $child_dir != '..' && $child_dir != 'up'){

                    $child_dir = iconv('gb2312','UTF-8',$child_dir);

                    if(is_dir($dir.'/'.$child_dir)){

                        $files[][$child_dir] = my_scandir($dir.'/'.$child_dir);
                    }else{
                        $files[] = $child_dir;
                    }
                }
            }
            return $files;
        }else{
            return $dir;
        }
    }

    function loadLink($dirPic,$url)
    {
        $result = [];

        foreach($dirPic as $k => $v)
        {
        	if (is_array($v)) {

        		foreach ($v as $key => $value) {
        			
        			foreach ($value as $k1 => $v2) {

        				$result[] = '../..'.$url.'/'.$key.'/'.$v2;
        			}
        			
        		}
        	}  
        }
        return $result;
    }
    function writeContent($data,$filename)
    {
    	$myfile = fopen($filename, "w+") or die("Unable to open file!");
    	foreach ($data as $key => $value) {

    		fwrite($myfile, $value."\r\n");
    	}
		fclose($myfile);
    }

    date_default_timezone_set('PRC');

	$file = 'gif.txt';

	// 检测文件是否存在
	if(!file_exists($file))
	{
		$mydir = my_scandir('../../UploadFiles/');

		$myPicLink = loadLink($mydir,'/UploadFiles');

		writeContent($myPicLink,$file);

	}

	$recordFile='record.txt';//已经挑选好了的

	$array = explode("\r\n", file_get_contents($file));//将每一行的链接当做一个vallue存入数组

	$arr= array_filter(array_unique($array));//去除重复的值为空的值

	sort($arr);//重新排列元素

	$num = count($arr);

	$max = 42;

	$page = isset($_GET['page'])?$_GET['page']:1;//获取当前页数

	$p = $page - 1;

	if( is_file($recordFile)){  //已经修改好的图片 直接替换原图片

		$recordArray = explode("\r\n",file_get_contents($recordFile));

		$recordArr = array_filter(array_unique($recordArray));//为防止一张图片重复选择造成错误 清除重复值
		sort( $recordArr);
			foreach( $recordArr as $v){

				$recordPath = explode('@',$v);//转为数组

				$k = array_shift($recordPath) ;//将索引排出

				$recordPaths = implode($recordPath);//路径

				foreach($arr as $key=>$value){

					if($key==$k){

						$arr[$key]=$recordPaths;

					}
				}
			}
		}
		for ($j=$max*$p;$j<($max*$p+$max)&&$j<$num;++$j)//循环条件控制显示图片张数
    	{

        $names=trim($arr[$j],'http://www.12ky.com/');

    	 echo "<div class=\"col-sm-6 col-md-2\" >
            <div id=\"". $j ."\" class=\"thumbnail\" > 
             <img  id=\"$j\" src=\"$arr[$j]\" style='width:150px;height:120px;'>
            <div class='caption'>
                <p>第". $j ."张图片 </p>
              <form action='git.php'target=\"frameName\"class='myform' method=\"post\" enctype=\"multipart/form-data\" style='display:none;'>
                <input type=\"file\" name=\"file\"  >
                <input type='hidden' name='id' value=\"$j\">
                <input type='hidden' name='filename' value=\"$arr[$j]\">
                <input type='submit' value='提交'>
                <iframe name=\"frameName\" style=\"display:none;\"></iframe>
              </form>
            </div> 
            </div>
        </div>";
    		
    	}
        echo "</div>";

        $prev=ceil($num/$max);//末页

        $previous_page=($page-1)>=1?$page-1:1;//上一页

        $next_page=($page+1)<=$prev?$page+1:$prev;//下一页

        echo '<ul class="pagination">';
        echo "<li><a href=?page=1>首页</a></li>";
        echo "<li><a href=?page=$previous_page>上一页</a></li>";
        echo "<li><a href=?page=$next_page>下一页</a></li>";
        echo "<li><a href=?page=$prev>末页</a></li>";
        echo "<li><a href='#'>共 $prev 页</a></li>";
        echo '</ul>';
        echo  " <form action='git.php' method='get' name='pages' >
                <input type='text' name='page' style='width:30px;'id='text'>
                <button type=\"button\" class=\"btn btn-warning\" 
          data-container=\"body\" data-toggle=\"popover\" data-placement=\"right\" 
          data-content=\"您输入页码有误\">
        确认
      </button>
           </form>";
       echo "</center>
       <script>
           var pages=document.pages;
            $(\":button\").click(function(){
          var text=$(\"#text\").val();
        if(text!=false){
            if(text>=0 && text<=$prev){
              pages.submit();
            }else{
            $(\"[data-toggle='popover']\").popover();
            }
          }else{
            $(\"[data-toggle='popover']\").popover();
            
          }
            });  
         </script>";
?>
</body>
<script>
	$('#imgpage .thumbnail img').click(function(){
	  $this=$(this);

	  $file=$this.siblings('.caption').find('.myform');

	  $file.find('[type="file"]').click().change(function(){

	    $this.parent('.thumbnail').addClass('loading');

	    $file.submit()

	  });

	})
	function imgok(id){//图片上传成功
	  var $itm=$('[id="'+id+'"]')
	  $itm.removeClass('loading').addClass('imgok');
	  var src=$itm.find('input[name="filename"]').val();
	  $itm.find('img').attr({src:src+'?'+Math.random()})
}
	function imgtype(id){//图片类型不符
	  var $itm=$('[id="'+id+'"]');
	  $itm.removeClass('loading').addClass('imgtype');
	  setTimeout(function(){ 
	          $itm.removeClass('imgtype');
	          },1000) 
	}
	function imgnot(id){//上传失败
	  var $itm=$('[id="'+id+'"]')
	  $itm.removeClass('loading').addClass('imgnot');
	  setTimeout(function(){ 
	          $itm.removeClass('imgnot');
	          },1000) 
	}
</script>
</html>