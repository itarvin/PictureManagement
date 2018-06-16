<?php
namespace app\admin\controller;
use think\Controller;
use think\facade\Cache;
use Db;
class Wechat extends Controller
{
    /**
     * 应用场景：首页
     * @author itarvin[itarvin@163.com]
     * @return view
     */
    public function index()
    {
        $files = $this->getAllFile('./image/');
        foreach ($files as $key => $value) {
            $data['image'] = 'https://www.itarvin.info/fileadmin/public/image/'.$value;
            $data['url'] = 'https://www.itarvin.info/fileadmin/public/image/'.$value;
            $data['addtime'] = time();
            $data['content'] = 'https://www.itarvin.info/fileadmin/public/image/'.$value;
            $data['name'] = explode('.',$value)[0];
            $data['category'] = '清纯美女';
            $result[] = $data;
        }
        Db::name('meinv_content')->data($result)->limit(100)->insertAll();
        var_dump($result);
        // $page = input('p') ? input('p') : '1';
        // $data = Db::name('meinv_content')->page($page,10)->select();
        // return json(['girls' => $data]);
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
            $file = iconv('gb2312','UTF-8',$file);
            if(!in_array($file,array('.','..')) )
            {
                if(!is_dir($path.$file)){
                    array_push($data,$file);
                }
            }
        }
        return $data;
    }
}
