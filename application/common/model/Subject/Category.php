<?php

namespace app\common\model\Subject;

use think\Model;

class Category extends Model
{
    //模型对应的是哪张表
    protected $name = "subject_category";

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = false; 

    //设置字段的名字
    protected $createTime = false; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;
}
