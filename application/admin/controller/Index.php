<?php
namespace app\admin\controller;
use think\Controller;
use think\paginator\driver\Bootstrap;
use think\facade\Cache;
class Index extends Controller
{
    /**
     * 应用场景：首页
     * @author itarvin[itarvin@163.com]
     * @return view
     */
    public function index()
    {
        return $this->fetch('Index/index');
    }

    /**
     * 应用场景：主页
     * @author itarvin[itarvin@163.com]
     * @return view
     */
    public function wel()
    {
        return $this->fetch('Index/wel');
    }

    // 测
    public function dir()
    {
        $parame = input('path');
        $url = ltrim($this->enalUrl($parame),'.');
        $arr = explode("/",$url);
        include "./en_zh/en_zh.php";

        foreach ($arr as $key => $value) {
            if($value != ""){
                $data[] = ($value== "images") ? '根目录' : $en_zh[$value];
            }
        }
        $datas = '';
        foreach ($data as $key => $value) {
            $datas .= $value.'/';
        }
        return json($datas);
    }
    /**
     * 应用场景：按文件搜索所有对应文件
     * @author itarvin[itarvin@163.com]
     * @return json
     */
    public function searchname()
    {
        $filename = input('filename');

        if(!empty($filename)){

            if(!file_exists('./en_zh/file_url.php')){

                // 重新生成一份文件
                $dirPic = './images/';

                $paths = $this->excavateDir($dirPic);

                // 追加当前的目录
                $paths[] = $dirPic;

                $files = [];

                foreach($paths as $k => $path){

                    $pathFiles = $this->getAllFile($path);

                    $files = array_merge($files, $pathFiles);
                }
                $all = $this->getFileName($files);

                $this->makefile('$file_url',$all,'file_url');
            }
            include "./en_zh/file_url.php";

            $names = [];

            foreach ($file_url as $key => $value) {

                $names[] = $key;
            }
            if(in_array($filename, $names)){

                $data['dir'] = [];

                $file = 'http://'.$_SERVER['SERVER_NAME'].substr($file_url[$filename],1);
                // $file = './'.substr($file_url[$filename],1);

                $re = getimagesize($file);

                $re['size'] = round($this->getsize(get_headers($file,true)['Content-Length'],'kb'),1).'k';

                $re['url'] = $file;
                $re['name'] = $this->cut_str($file,'/',-1);

                $re['last_edit'] = date('Y-m-d H:i:s',$this->remote_filectime($file));

                $data['files'] = $re;

                $data['url'] = $this->dealUrl('./images/');

                return json(['code' => '200', 'info' => 'OK', 'data' => $data]);
            }else {
                return json(['code' => '404', 'info' => 'NO', 'data' => []]);
            }
        }
    }

    /**
     * 应用场景：更新缓存
     * @author itarvin[itarvin@163.com]
     * @return json
     */
    public function getcache()
    {
        $input = input();

        $links = cookie('link');

        if(count($links) > 0){

            $firstPath = array_shift($links);

            $this->selfplay($firstPath);
            // 重新存
            cookie('link',$links);
        }

        $deal = $input['deal'] + 1;

        $total = $input['total'];

        return ['code' => 200, 'total' => $total, 'deal' => $deal];
    }

    /**
     * 应用场景：自询更新
     * @author itarvin[itarvin@163.com]
     */
    private function selfplay($path)
    {
        $dirPic = $path ? $path : './images/';

        $data = cache($dirPic);

        if(!$data){
            $dirs = $this->excavateDir($dirPic);

            $data['dir'] = $this->dealDir($dirs);

            $data['files'] = $this->getfile($dirPic);

            $data['url'] = $path ? $this->dealUrl($path) : $this->dealUrl('./images/');

            $data['enurl'] = $path ? $this->cutDir($path) : $this->cutDir('./images/');

            cache($dirPic,$data, 3600*24);
        }
    }

    /**
     * 应用场景：请求更新
     * @author itarvin[itarvin@163.com]
     * @return json
     */
    public function play()
    {
        $dirPic = input('path') ? $this->enalUrl(input('path')) : './images/';

        $data = cache($dirPic);
        if(!$data){
            $dirs = $this->excavateDir($dirPic);

            $data['dir'] = $this->dealDir($dirs);

            $data['files'] = $this->getfile($dirPic);

            $data['url'] = input('path') ? input('path') : $this->dealUrl('./images/');

            // $data['enurl'] = input('path') ? $this->cutDir($this->enalUrl(input('path'))) : $this->cutDir('./images/');
            $data['enurl'] = input('path') ? $this->dealUrl(input('path')) : './images/';

            cache($dirPic,$data, 3600*24);
        }
        return json(['code' => '200', 'info' => 'OK', 'data' => $data]);
    }


    // 截取中文路径
    public function cutDir($dir)
    {
        $deal = ltrim($dir, ".");
        $count = substr_count($deal, '/');
        $sign = $this->cut_str($deal,'/',$count-1);
        include "./en_zh/en_zh.php";
        return $trkey = ($sign== "images") ? '根目录' : $en_zh[$sign];
    }

    /**
     * 应用场景：合并目录的路径和名称
     * @author itarvin[itarvin@163.com]
     * @return array
     */
    private function dealDir($dirs)
    {
        $arr = [];

        $dir = $this->dealUrl($dirs);

        $name = $this->getdirname($dirs);

        foreach ($dir as $k1 => $dr) {

            foreach ($name as $k2 => $nm) {

                if($k1 == $k2){
                    $arr[] = ['dir' => $dr, 'name' => $nm];
                }
            }
        }
        return $arr;
    }

    /**
     * 应用场景：请求处理清空缓存并生成新的缓存
     * @author itarvin[itarvin@163.com]
     * @return json
     */
    public function cleargetdir()
    {
        // 清空所有缓存且返回所有目录供前台刷新缓存
        Cache::clear();

        $path = './images/';
        // 先缓存根目录下面的文件
        $this->selfplay($path);
        // 存链接
        cookie('link',$this->getdirglo($path));

        $total = count($this->getdirglo($path));

        return json(['code' => 200, 'total' => $total, 'deal' => 0]);
    }

    /**
     * 应用场景：获取目录名称并返回对应中文名
     * @author itarvin[itarvin@163.com]
     * @return json
     */
    private function getFileName($path)
    {
        $names = [];

        foreach ($path as $key => $value) {

            $str_count = substr_count($value,'/');

            $name = $this->cut_str($value,'/',$str_count);

            $filename = $this->cut_str($name,'.',0);

            $names[$filename] = $value;
        }
        return $names;
    }

    /**
     * 应用场景：获取目录名称并返回对应中文名
     * @author itarvin[itarvin@163.com]
     * @return array
     */
    private function getdirname($path)
    {
        $names = [];

        include "./en_zh/en_zh.php";

        foreach ($path as $key => $value) {

            $str_count = substr_count($value,'/');

            $name = $this->cut_str($value,'/',$str_count-1);

            $names[] = isset($en_zh[$name]) ? $en_zh[$name] : $name;
        }
        return $names;
    }

    /**
     * 应用场景：同级新建文件夹
     * @author itarvin[itarvin@163.com]
     * @return json
     */
    public function newdir()
    {
        $input = input();

        if($input['url'] == ''){
            return json(['code' => '400', 'info' => '请刷新页面重新请求！']);
        }
        $url = $this->enalUrl($input['url']);

        if($input['chinese'] == ''){
            return json(['code' => '400', 'info' => '请提供对应的中文名称!']);
        }
        if($input['english'] == ''){
            return json(['code' => '400', 'info' => '请提供对应的英文名称!']);
        }

        if(preg_match("/^[a-zA-Z\s]+$/",$input['english'])){

            $path = $url.$input['english'];

            $code = $this->mkDir($path);

            if($code['code'] == '200'){
                $en_zh = [$input['english'] => $input['chinese']];

                if($re = $this->makefile('$en_zh',$en_zh,'en_zh')){

                    if($re['code'] == '400'){
                        return json(['code' => '400', 'info' => $re['info']]);
                    }else {

                        // 清除缓存
                        cache($url,null);
                        return json(['code' => '200', 'info' => 'OK']);
                    }
                }
            }else if($code['code'] == '400') {

                return json(['code' => '400', 'info' => '创建文件夹失败了,刷新再试！']);
            }else if($code['code'] == '500'){

                return json(['code' => '500', 'info' => 'No']);
            }
        }else {
            return json(['code' => '400', 'info' => '英文名称中存在非英文字母']);
        }
    }

    /**
     * 应用场景：新建文件夹
     * @author itarvin[itarvin@163.com]
     * @return json
     */
    private function mkDir($path)
    {
        if(!file_exists($path)) {

            if(mkdir($path,0777,false)) {

                return ['code' => 200];
            }else{

                return ['code' => 400];
            }
        } else {

            return ['code' => 500];
        }
    }

    /**
     * 应用场景：处理链接
     * @author itarvin[itarvin@163.com]
     * @return array|string
     */
    private function dealUrl($url)
    {
        if(is_array($url))
        {
            foreach ($url as $key => $value) {
                $url[$key] = str_replace('/',"it",substr($value , 1));
            }
        }else {
            $url = str_replace('/',"it",substr($url , 1));
        }

        return $url;
    }

    /**
     * 应用场景：拼接链接
     * @author itarvin[itarvin@163.com]
     * @return string
     */
    private function enalUrl($url)
    {
        $url = str_replace('it',"/",'.'.$url);
        return $url;
    }

    /**
     * 应用场景：拼接链接
     * @author itarvin[itarvin@163.com]
     * @return array
     */
    private function excavateDir($dir)
    {
        static $arr = [];

        if(is_dir($dir)){

            $hadle = @opendir($dir);

            while($file = readdir($hadle) )
            {
                if(!in_array($file,array('.','..')) )
                {
                    if(is_dir($dir.$file)){

                        $dirr = $dir.$file."/";

                        array_push($arr,$dirr);

                        if(is_dir($dirr)){
                            $this->getdirglo($dirr);
                        }
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 应用场景：拼装返回文件信息
     * @author itarvin[itarvin@163.com]
     * @return array
     */
    private function getfile($path)
    {
        $links = [];
        $file = $this->getAllFile($path);
        foreach ($file as $key => $value) {
            // 过滤windows系统的缩虐图db
            $values = $this->cut_str($value,'/',-1);

            if($values != 'Thumbs.db'){
                // $links[] = 'http://'.$_SERVER['SERVER_NAME'].substr($value,1);
                // $links[] = '..'.substr($value,1);
                $links[] = substr($value,1);
            }
        }
        $data = $this->loadInfo($links);
        return $data;
    }

    /**
     * 应用场景：根据路径获取文件
     * @author itarvin[itarvin@163.com]
     * @return array
     */
    private function getAllFile($path)
    {
        $data = [];
        $hadle = opendir($path);
        while($file = readdir($hadle) )
        {
            // $file = iconv('gb2312','UTF-8',$file);
            if(!in_array($file,array('.','..')) )
            {
                if(!is_dir($path.$file)){
                    array_push($data,$path.$file);
                }
            }
        }
        return $data;
    }

    /**
     * 应用场景：递归获取目录
     * @author itarvin[itarvin@163.com]
     * @return array
     */
    private function getdirglo($dir)
    {

        static $arr = [];

        if(is_dir($dir)){

            $hadle = @opendir($dir);

            while($file = readdir($hadle) )
            {
                if(!in_array($file,array('.','..')) )
                {

                    if(is_dir($dir.$file)){

                        $dirr = $dir.$file."/";
                        if(!in_array($dirr,$arr)){
                            array_push($arr,$dirr);
                        }
                        if(is_dir($dirr)){
                            $this->getdirglo($dirr);
                        }
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 应用场景：处理远程文件信息
     * @author itarvin[itarvin@163.com]
     * @return timestamp
     */
    private function remote_filectime($url_file){

        $headInf = get_headers('http://'.$_SERVER['SERVER_NAME'].$url_file,1);

        return strtotime($headInf['Last-Modified']);
    }

    /**
     * 应用场景：按符号截取字符串的指定部分
     * @author itarvin[itarvin@163.com]
     * @param string $str 需要截取的字符串
     * @param string $sign 需要截取的符号
     * @param int $number 如是正数以0为起点从左向右截  负数则从右向左截
     * @return string 返回截取的内容
     */
    private function cut_str($str,$sign,$number){
        $array = explode($sign, $str);

        $length = count($array);

        if($number < 0){

            $new_array = array_reverse($array);

            $abs_number = abs($number);

            if($abs_number>$length){

                return 'error';
            }else{

                return $new_array[$abs_number-1];
            }
        }else{

            if($number >= $length){

                return 'error';
            }else{

                return $array[$number];
            }
        }
    }

    /**
     * 应用场景：拼装文件信息
     * @author itarvin[itarvin@163.com]
     * @return array
     */
    private function loadInfo($links)
    {
        $result = [];

        foreach ($links as $key => $value) {

            if(is_array($value)){

                $result[$key] = $this->loadInfo($value);
            }else {

                $url = 'http://'.$_SERVER['SERVER_NAME'].$value;

                $re = getimagesize($url);

                $re['size'] = round($this->getsize(get_headers($url,true)['Content-Length'],'kb'),1).'k';

                $re['url'] = $value;

                $re['name'] = $this->cut_str($value,'/',-1);

                $re['nameid'] = $this->cut_str($this->cut_str($value,'/',-1),'.',0);

                $re['last_edit'] = date('Y-m-d H:i:s',$this->remote_filectime($value));

                $result[] = $re;
            }
        }

        return $result;
    }

    /**
     * 应用场景：计算文件大小
     * @author itarvin[itarvin@163.com]
     * @return string
     */
    private function getsize($size, $format = 'kb'){
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
     * 应用场景：生成文件
     * @author itarvin[itarvin@163.com]
     * @param string $variable 数组名
     * @param array $array 数组
     * @param string $file 文件名
     */
    private function makefile($variable,$array,$file)
    {
        $file_path = "./en_zh/";
        // 判断文件是否存在，未不存在，则创建文件并写入文件。如存在，则覆盖写入重新生成文件
        if(!file_exists($file_path))
        {

            //0777表示文件夹权限，windows默认已无效，但这里因为用到第三个参数，得填写；true/false表示是否可以递归创建文件夹
            mkdir($file_path,0777,false);

            $content = "<?php $variable=".var_export($array, true).";?>";

            file_put_contents("./en_zh/".$file.".php",$content);
            return ['code' => 200];
        }else{

            if(file_exists("./en_zh/".$file.".php")){

                include "./en_zh/".$file.".php";

                if($variable == '$en_zh'){

                    foreach ($array as $key => $value) {
                        if(array_key_exists($key,$en_zh)){

                            return ['code' => 400,'info' => '已存在相同英文目录名称'];
                        }else if(in_array($value, $en_zh)){

                            return ['code' => 400,'info' => '已存在相同中文目录名称'];
                        }else {
                            $newarray = array_merge($array, $en_zh);

                            $content = "<?php $variable=".var_export($newarray, true).";?>";

                            file_put_contents("./en_zh/".$file.".php",$content);

                            return ['code' => 200];
                        }
                    }
                }else if($variable == '$file_url') {
                    $newarray = array_merge($array, $file_url);

                    $content = "<?php $variable=".var_export($newarray, true).";?>";

                    file_put_contents("./en_zh/".$file.".php",$content);

                    return ['code' => 200];
                }

            }else {
                $content = "<?php $variable=".var_export($array, true).";?>";

                file_put_contents("./en_zh/".$file.".php",$content);

                return ['code' => 200];
            }
        }
    }
    // 文件上传
    public function upload()
    {
        // 这里路径应该实时获取，but前端需求无法实现
        $path = "images/";
        if($file = request()->file('file')){
            $name = iconv('utf-8','gbk',$file->getInfo()['name']);
            $info = $file->validate(['size'=>4194304,'ext'=>'jpg,png,gif'])->move($path,$name);
            if($info){
                return ['code' => 200 , 'info' => 'OK'];
            }else {
                // 上传失败获取错误信息
                return ['code' => 400 , 'info' => $file->getError()];
            }
        }else {
            return ['code' => 400 , 'info' => '请先选择上传文件！'];
        }
    }
}
