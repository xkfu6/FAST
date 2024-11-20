<?php

namespace app\common\validate\Product;

use think\Validate;

class Type extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => 'require|unique:product_type',
        'weigh' => 'require',
        'thumb' => 'require'
    ];
    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '商品分类名称必填',
        'name.unique' => '该商品分类名称已存在，请重新填写',
        'weigh.require' => '权重必填',
        'thumb.require' => '请上传分类图片'
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => [],
        'edit' => [],
    ];
    
}
