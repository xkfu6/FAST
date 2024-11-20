<?php

namespace app\common\validate\Live;

use think\Validate;

class Product extends Validate
{
    	protected $rule = [
            'liveid' => ['require'],
            'relation' => ['require'],
            'stock' => ['number', '>:0'],
            'price' => ['number', '>=:0'],
            'type' => ['require', 'in:subject,product'],
        ];

        protected $message = [
            'liveid.require'      => '请确认直播记录',
            'relation.require'      => '请选择对应的关联商品或课程',
            'stock.number'      => '库存数量必须是数字',
            'stock.>'      => '库存数量必须大于0',
            'price.number'      => '价格必须是数字',
            'price.>='      => '价格必须大于等于0',
            'type.require'      => '请选择类型',
            'type.in'      => '类型状态有误',
        ];

        protected $scene = [

        ];
}
