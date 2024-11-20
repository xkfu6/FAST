<?php

namespace app\common\model\Card;

// 引入模块
use think\Model;

/**
 * 通讯录分类数据模型
 */
class Type extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    // 设置当前模型对应的完整数据表名称
    protected $name = 'card_type';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;
    protected $updateTime = false;
    protected $createTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;

    protected $append = [];
}