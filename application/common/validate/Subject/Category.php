<?php

namespace app\common\validate\Subject;

use think\Validate;

class Category extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name'   => ['require', 'unique:subject_category'],
        'weight'   => ['require', 'unique:subject_category'],
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '课程分类名称必填',
        'name.unique' => '课程分类名称已存在，请重新输入',
        'weight.require' => '课程分类权重必填',
        'weight.unique' => '课程分类权重已存在，请重新输入',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}
