<?php

namespace app\common\validate\product;

use think\Validate;

/**
 * 商品属性验证器
 */
class Prop extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'title' => 'require|unique:product_prop',
        'value'=>'require',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'title.require' => '属性名称必填',
        'title.unique'  => '属性已存在',
        'value.require' => '属性值必填',
    ];

    /**
     * 字段描述
     */
    protected $field = [
    ];

    /**
     * 验证场景
     */
    protected $scene = [
    ];

}
