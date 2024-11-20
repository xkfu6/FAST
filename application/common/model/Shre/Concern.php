<?php

namespace app\common\model\Shre;

use think\Model;
use think\Request;

class Concern extends Model
{

    //模型对应的是哪张表
    protected $name = "shre_concern";
    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = true;
    //设置字段的名字
    protected $createTime = "createtime"; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = true;

    //自动过滤掉不存在的字段
    protected $field = true;
    protected $append = [
        'createtime_text',
        'thumbs_text',
    ];
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? $data['createtime'] : 0;
        return date('Y-m-d H:i', $createtime);
    }
    public function getThumbsTextAttr($value, $data)
    {
        //获取域名部分
        $domain = request()->domain();
        $domain = trim($domain, '/');

        $thumbs = isset($data['thumbs']) ? $data['thumbs'] : '';

        if (empty($thumbs)) {
            return [];
        }

        $thumbs = explode(',', $thumbs);

        //去空
        $thumbs = array_filter($thumbs);
        $thumbs = array_unique($thumbs);
        foreach ($thumbs as &$item) {
            if (is_file('.' . $item)) {
                $item = $domain . $item;
            } else {
                unset($item);
            }
        }

        return $thumbs;
    }
}
