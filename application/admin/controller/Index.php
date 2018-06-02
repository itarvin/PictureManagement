<?php
namespace app\admin\controller;
use think\Controller;
use think\paginator\driver\Bootstrap;
use think\facade\Cache;
class Index extends Controller
{
    public function index()
    {
        return $this->fetch('index/index');
    }

    public function wel()
    {
        return $this->fetch('index/wel');
    }

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

        $total = $input['total'] - 1;

        return ['code' => 200, 'total' => $total, 'deal' => $deal];
    }

    public function selfplay($path)
    {
        $dirPic = $path ? $path : './images/';

        $data = cache($dirPic);

        if(!$data){
            $dirs = $this->excavateDir($dirPic);

            $data['dir'] = $this->dealDir($dirs);

            $data['files'] = $this->getfile($dirPic);

            $data['url'] = input('path') ? $this->enalUrl(input('path')) : $this->dealUrl('./images/');

            cache($dirPic,$data, 3600*24);
        }
    }

    public function play()
    {
        $dirPic = input('path') ? $this->enalUrl(input('path')) : './images/';

        $data = cache($dirPic);
        if(!$data){
            $dirs = $this->excavateDir($dirPic);

            $data['dir'] = $this->dealDir($dirs);

            $data['files'] = $this->getfile($dirPic);

            $data['url'] = input('path') ? $this->enalUrl(input('path')) : $this->dealUrl('./images/');

            cache($dirPic,$data, 3600*24);
        }
        return json(['code' => '200', 'info' => 'OK', 'data' => $data]);
    }

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

    // 同级新建文件夹
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

    public function mkDir($path)
    {
        if(!file_exists($path)) {

            if(mkdir($path,0777,true)) {

                return ['code' => 200];
            }else{

                return ['code' => 400];
            }
        } else {

            return ['code' => 500];
        }
    }


    // 处理链接
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

    // 拼接链接
    private function enalUrl($url)
    {
        $url = str_replace('it',"/",'.'.$url);
        return $url;
    }

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

    private function getfile($path)
    {
        $links = [];
        $file = $this->getAllFile($path);
        foreach ($file as $key => $value) {
            $links[] = 'http://'.$_SERVER['SERVER_NAME'].substr($value,1);
        }
        $data = $this->loadInfo($links);
        return $data;
    }

    // 根据路径获取文件
    private function getAllFile($path)
    {
        $data = [];
        $hadle = opendir($path);
        while($file = readdir($hadle) )
        {
            $file = iconv('gb2312','UTF-8',$file);
            if(!in_array($file,array('.','..')) )
            {
                if(!is_dir($path.$file)){
                    array_push($data,$path.$file);
                }
            }
        }
        return $data;
    }

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

    // private function play()
    // {
    //     $curpage = input('page') ? input('page') : 1;
    //
    //     $curUrl = config('selfDealDir');
    //
    //     $dirPic = cache('dirPic');
    //     if(!$dirPic){
    //         $dirPic = $this->my_dir($curUrl);
    //         cache('dirPic', $dirPic, 3600*24*30);
    //     }
    //
    //     $url = 'http://'.$_SERVER['SERVER_NAME'].'/images';
    //
    //     $links = cache('links');
    //
    //     if(!$links){
    //         $links = $this->deepLink($dirPic, $url);
    //         cache('links', $links, 3600 * 24 *30);
    //     }
    //
    //     $result = $this->makeList($links,$curpage);
    //
    //     var_dump($result);die;
    //
    //     $this->assign([
    //         'list' => $result['list'],
    //         'plistpage' => $result['plistpage'],
    //     ]);
    //     return $this->fetch('Index/play');
    // }

    private function makeList($links,$curpage)
    {

        $listRow = 100;//每页30行记录

        $showdata = array_slice($links, ($curpage - 1) * $listRow, $listRow,true);

        $p = Bootstrap::make($showdata, $listRow, $curpage, count($links), false, [
            'var_page' => 'page',
            'path'     => url('Index/play'),
            'query'    => [],
            'fragment' => '',
        ]);

        $p->appends($_GET);
        return [
            'list' => $p,
            'plistpage' => $p->render(),
        ];
    }

    private function remote_filectime($url_file){

        $headInf = get_headers($url_file,1);

        return strtotime($headInf['Last-Modified']);
    }

    /**
     * 按符号截取字符串的指定部分
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


    private function loadInfo($links)
    {
        $result = [];

        foreach ($links as $key => $value) {

            if(is_array($value)){

                $result[$key] = $this->loadInfo($value);
            }else {

                $re = getimagesize($value);

                $re['size'] = round($this->getsize(get_headers($value,true)['Content-Length'],'kb'),1).'k';

                $re['url'] = $value;
                $re['name'] = $this->cut_str($value,'/',-1);
                // $re['name'] = $this->cut_str($this->cut_str($value,'/',-1),'.',0);

                $re['last_edit'] = date('Y-m-d H:i:s',$this->remote_filectime($value));

                $result[] = $re;
            }
        }

        return $result;
    }

    private function deepLink($dirPic, $imgUrl)
    {
        $result = [];
        // 判定数据下面是否还存在子文件夹
        if(is_array($dirPic)){
            foreach ($dirPic as $key => $value) {
                if(is_numeric($key)){
                    $result[$key] = $imgUrl.'/'.$value;
                }else {
                    $result[$key] = $this->deepLink($value,$imgUrl.'/'.$key);
                }
            }
        }else {
            $result = $imgUrl.'/'.$dirPic;
        }
        return $result;
    }

    // 安检

    private function my_dir($dir) {

        $files = [];

        if(@$handle = opendir($dir)) { //注意这里要加一个@，不然会有warning错误提示：）
        while(($file = readdir($handle)) != false) {
            if($file != ".." && $file != ".") { //排除根目录；
                $file = iconv('gb2312','UTF-8',$file);
                if(is_dir($dir."/".$file)) { //如果是子文件夹，就进行递归
                    $files[$file] = $this->my_dir($dir."/".$file);
                }
            }
        }
        closedir($handle);
        return $files;
        }
    }

    // 计算文件大小
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

            }else {

                $content = "<?php $variable=".var_export($array, true).";?>";

                file_put_contents("./en_zh/".$file.".php",$content);

                return ['code' => 200];
            }
        }
    }
}
