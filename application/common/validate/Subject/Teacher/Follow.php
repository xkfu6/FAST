<?php

namespace app\common\validate\Subject\Teacher;

use think\Validate;

class Follow extends Validate
{
    /**
         * 定义验证规则
         * 格式：'字段名'	=>	['规则1','规则2'...]
         *
         * @var array
         */
    	protected $rule = [
            'teacherid' => ['require', 'number'],
            'busid' => ['require', 'number'],
        ];

        /**
         * 定义错误信息
         * 格式：'字段名.规则名'	=>	'错误信息'
         *
         * @var array
         */
        protected $message = [
            'teacherid.require' => '老师信息未知',
            'teacherid.number' => '老师信息ID参数有误',
            'busid.require' => '客户信息未知',
            'busid.number' => '客户信息ID参数有误',
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
