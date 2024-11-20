<?php

namespace app\common\validate\Card;

use think\Validate;

class Card extends Validate
{
    protected $rule =   [
        'nickname'   => 'require',
        'mobile'  => 'require|unique:card',
        'typeid'   => 'require',
        'busid'   => 'require',
    ];

    protected $message  =   [
        'mobile.require' => '手机号码必须填写',
        'mobile.unique'     => '手机号码必须是唯一的值',
        'nickname.require'   => '昵称必须填写',
        'typeid.require'  => '请选择分类',
        'busid.require'  => '用户身份ID未知',
    ];

    // 验证场景
    protected $scene = [];
}