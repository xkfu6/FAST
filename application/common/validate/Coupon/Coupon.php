<?php

namespace app\common\validate\Coupon;

use think\Validate;

class Coupon extends Validate
{
    /**
         * 定义验证规则
         * 格式：'字段名'	=>	['规则1','规则2'...]
         *
         * @var array
         */
    	protected $rule = [

        ];

        /**
         * 定义错误信息
         * 格式：'字段名.规则名'	=>	'错误信息'
         *
         * @var array
         */
        protected $message = [

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
