<?php

namespace app\common\validate\Subject\Teacher;

use think\Validate;

class Teacher extends Validate
{
    /**
         * 定义验证规则
         * 格式：'字段名'	=>	['规则1','规则2'...]
         *
         * @var array
         */
    	protected $rule = [
            'name' => ['require'],
        ];

        /**
         * 定义错误信息
         * 格式：'字段名.规则名'	=>	'错误信息'
         *
         * @var array
         */
        protected $message = [
            'name.require' => '请输入名称',
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
