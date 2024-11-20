<?php

namespace app\common\validate\Pay;

use think\Validate;

class Pay extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'code'   => ['require','unique:pay'],
        'name' => ['require'],
        'type' => ['require', 'in:wx,zfb'],
        'total' => ['require','number', '>:0'],
        'price' => ['require','number', '>:0'],
        'jump' => ['require'],
        'notice' => ['require'],
        'status' => ['require','number', 'in:0,1,2'],
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'code.require' => '订单号必须填写',
        'code.unique' => '订单号已重复',
        'name.require' => '订单名称必须填写',
        'type.require' => '支付方式必须填写',
        'type.in' => '支付方式的值有误',
        'total.require' => '订单原价必须填写',
        'total.>' => '订单原价必须大于0',
        'total.number' => '订单原价的类型有误',
        'price.>' => '实际支付金额必须大于0',
        'price.require' => '实际支付金额必须填写',
        'price.number' => '实际支付金额的类型有误',
        'jump.require' => '订单支付完成后跳转的网页地址必须填写',
        'notice.require' => '回调地址必须填写',
        'status.require' => '订单状态必须填写',
        'status.number' => '订单状态的类型有误',
        'status.in' => '订单状态的值有误',
    ];

}