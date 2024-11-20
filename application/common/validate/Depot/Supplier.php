<?php

namespace app\common\validate\Depot;

use think\Validate;

class Supplier extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => ['require','unique:depot_supplier'],
        'mobile' => ['require','unique:depot_supplier','max:11'],
        'province' => ['require'],
        'city' => ['require'],
        'district' => ['require'],
        'address' => ['require'],
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '供应商名称必填',
        'name.unique' => '该供应商名称已存在，请重新输入',
        'mobile.require' => '供应商手机号必填',
        'mobile.unique' => '该供应商手机号已存在，请重新输入',
        'province.require' => '请选择省份',
        'city.require' => '请选择市',
        'district.require' => '请选择区',
        'address.require' => '请输入详细地址',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => [],
    ];
    
}
