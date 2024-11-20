<?php

namespace app\common\model\Subject;

use think\Model;
use think\Request;

class Chapter extends Model
{
    // 章节表
    protected $name= "subject_chapter";

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = true; 

    //设置字段的名字
    protected $createTime = "createtime"; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;

    // 追加属性
    protected $append = [
       'url_text'
    ];

    public function getUrlTextAttr($value, $data)
    {
        //获取域名部分
        $domain = Request::instance()->domain();
        $domain = trim($domain, '/');

        $url = isset($data['url']) ? $data['url'] : '';

        //如果为空就给一个默认图片地址
        if(empty($url) || !is_file(".".$url))
        {
            $url = "/assets/home/images/video.jpg";
        }
        
        return $domain.$url;
    }

    public function subject()
    {
        return $this->belongsTo('app\common\model\Subject\Subject', 'subid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
