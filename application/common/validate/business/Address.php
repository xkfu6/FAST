<?php

namespace app\common\validate\business;

use think\Validate;

/**
 * 客户收货地址
 */
class Address extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'busid' => 'require',
        'consignee'=> 'require',
        'province'=> 'require',
        'city'=> 'require',
        'address'=> 'require',
        'mobile'   => 'require',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'busid.require' => '客户信息未知',
        'consignee.require'  => '收货人必填',
        'province.require' => '省份必选',
        'city.require' => '城市必选',
        'address.require' => '详细地址必填',
        'mobile.require' => '手机号必填',
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
        //添加
        'add'=>['busid','consignee','province','city','mobile'],

        //编辑
        'edit'=>['consignee','province','city','mobile']
    ];

}
