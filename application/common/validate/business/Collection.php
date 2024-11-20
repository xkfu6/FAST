<?php

namespace app\common\validate\Business;

// 引入 tp验证器
use think\Validate;

class Collection extends Validate
{
    /**
     * 验证规则
    */
    protected $rule = [
        'busid' => ['require'],
        'collectid' => ['require'],
        'status' => ['in:subject,product']
    ];
    

    /**
     * 错误信息
    */
    protected $message = [
        'busid.require' => '未知用户id',
        'collectid.require' => '收藏ID未知',
        'status.in' => '收藏状态有误'
    ];

    /**
     * 验证场景
    */
    protected $scene = [
    ];
}