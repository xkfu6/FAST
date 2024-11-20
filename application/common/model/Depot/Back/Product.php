<?php

namespace app\common\model\Depot\Back;

use think\Model;

class Product extends Model
{
    protected $name = 'depot_back_product';

    //自动过滤掉不存在的字段
    protected $field = true;

    public function products()
    {
        return $this->belongsTo('app\common\model\Product\Product','proid','id',[],'LEFT')->setEagerlyType(0);
    }
}
