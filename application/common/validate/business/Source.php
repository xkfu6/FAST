<?php

namespace app\common\validate\Business;

use think\Validate;

class Source extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name'   => ['require', 'unique:business_source'],
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '来源分类名称必填',
        'name.unique' => '来源分类名称已存在，请重新输入',
    ];
    
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}
