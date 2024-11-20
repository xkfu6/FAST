<?php

namespace app\common\validate\Card;

// 引入模块
use think\Validate;

/**
 * 分类验证器
 */
class Type extends Validate
{
    protected $rule =   [
        'name'  => 'require|unique:card_type',
    ];

    protected $message  =   [
        'name.require' => '分类名称必须填写',
        'name.unique'     => '分类名称必须是唯一的值',
    ];
}