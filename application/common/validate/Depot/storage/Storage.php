<?php

namespace app\common\validate\Depot\Storage;

use think\Validate;

class Storage extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'code' => ['require','unique:depot_storage'],
        'supplierid' => ['require'],
        'type' => ['require','in:1,2'],
        'amount' => ['require'],
        'status' => ['require','in:0,1,2,3']
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'code.require' => '订单号必填',
        'code.unique' => '订单号已存在，请重新输入',
        'supplierid.require' => '供应商必填',
        'type.require' => '入库类型必填',
        'type.in' => '入库类型未知',
        'amount.require' => '总价必填',
        'status.require' => '入库状态必填',
        'status.in' => '入库状态未知',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => ['supplierid','type','amount','status'],
        'back_edit' => ['type','amount','status'],
        'back' => ['code','type','amount','status'],
    ];
    
}
