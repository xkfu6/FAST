<?php
namespace app\common\model\Live;

// 引入模块
use think\Model;

/**
 * 直播商品模型
 */
class Product extends Model
{
    protected $name = 'live_product';

    protected $autoWriteTimestamp = false;
    protected $createTime = false;
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;

    protected $append = [
        'type_text'
    ];

    public function GetTypeList()
    {
        return ['subject' => __('课程'), 'product' => __('商品')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->GetTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    // 直播记录关联查询
    public function live()
    {
        return $this->belongsTo('app\common\model\Live\Live','liveid', 'id',[],'LEFT')->setEagerlyType(0);
    }

    public function subjects()
    {
        return $this->belongsTo('app\common\model\Subject\Subject', 'relation', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function products()
    {
        return $this->belongsTo('app\common\model\Product\Product', 'relation', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}