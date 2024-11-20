<?php

namespace app\common\model\Business;

use think\Model;

class Record extends Model
{
    //模型对应的是哪张表
    protected $name = "business_record";

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
        'createtime_text'
    ];

    // 定义日期格式
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? $data['createtime'] : 0;
        return date('Y-m-d H:i', $createtime);
    }
}
