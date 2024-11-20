<?php

namespace app\common\validate\Coupon;

use think\Validate;

class Receive extends Validate
{
    /**
         * 定义验证规则
         * 格式：'字段名'	=>	['规则1','规则2'...]
         *
         * @var array
         */
    	protected $rule = [
            'cid' => ['require', 'number'],
            'busid' => ['require', 'number'],
            'status' => ['number', 'in:0,1'],
        ];

        /**
         * 定义错误信息
         * 格式：'字段名.规则名'	=>	'错误信息'
         *
         * @var array
         */
        protected $message = [
            'cid.require' => '优惠活动未知',
            'cid.number' => '优惠活动ID参数有误',
            'busid.require' => '会员信息未知',
            'busid.number' => '会员信息ID参数有误',
            'status.number' => '领取状态有误',
            'status.in' => '领取状态参数有误',
        ];

        /**
         * 验证场景定义
         * 格式：'场景名称'	=>	['字段1','字段2'...]
         *
         * @var array
         */
        protected $scene = [

        ];
}
