<?php

namespace app\common\validate\product;

use think\Validate;

/**
 * 商品属性关系验证器
 */
class Relation extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'proid' => 'require',
        'propid'=>'require',
        'value'=>'require',
        'price' => ['require','number','egt:0'],
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'proid.require' => '商品ID未知',
        'propid.require' => '属性ID未知',
        'value.require' => '属性值未知',
        'price.require' => '价格必填',
        'price.number' => '价格必须是数字',
        'price.egt' => '价格必须大于等于0'
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
