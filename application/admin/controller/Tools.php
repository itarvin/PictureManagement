<?php
namespace app\admin\controller;
use think\Controller;
class Tools extends Controller
{
    public function index()
    {
        $re = $this->solution('itarvin');
        var_dump($re);die;
    }
    // 给定一个字符串s，你可以从中删除一些字符，使得剩下的串是一个回文串。如何删除才能使得回文串最长呢？输出需要删除的字符个数
    public function solution ($str){
        $res=strrev($str);

        $len=strlen($str);

        $data=array();
        // 初始化为0
        for ($i = 0; $i < $len; $i++)
            for($j=0 ; $j< $len;$j++){
                    $data[$i][$j]= 0;
                }
                //开始查找

        for($i=1;$i<$len+1;$i++){
            for($j=1;$j<$len+1;$j++){
                // 若顺序的第一个值等于倒序的第一个
                if($str[$i-1] == $res[$j-1]){
                    //
                    $data[$i][$j]=$data[$i-1][$j-1] +1;

                }else if($data[$i-1][$j] >= $data[$i][$j-1]){

                    $data[$i][$j] = $data[$i-1][$j];

                }else{

                    $data[$i][$j] = $data[$i][$j-1];
                }

            }
        }
        print_r($data);
        //其中$data[$len][$len]j即为最大子串
        return $len - $data[$len][$len];
    }
    //echo  solution('google');
}
