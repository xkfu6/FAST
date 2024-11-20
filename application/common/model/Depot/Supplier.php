<?php

namespace app\common\model\Depot;

use think\Model;


class Supplier extends Model
{
    // 表名
    protected $name = 'depot_supplier';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;

    // 追加属性
    protected $append = [
        'region_text', //地区字符串,
    ];

    // 关联查询
    public function provinces()
    {
        return $this->belongsTo('app\common\model\Region','province','code',[],'LEFT')->setEagerlyType(0);
    }

    public function citys()
    {
        return $this->belongsTo('app\common\model\Region','city','code',[],'LEFT')->setEagerlyType(0);
    }

    public function districts()
    {
        return $this->belongsTo('app\common\model\Region','district','code',[],'LEFT')->setEagerlyType(0);
    }

    public function getRegionTextAttr($value, $data)
    {
        $region = '';

        // 省
        $province = empty($data['province']) ? '' : $data['province'];
        if ($province) 
        {
            $province_text = model('Region')::where('code', $province)->value('name');
            $region = $province_text . "-";
        }

        // 市
        $city = empty($data['city']) ? '' : $data['city'];
        if ($city) 
        {
            $city_text = model('Region')::where('code', $city)->value('name');
            $region .= $city_text . "-";
        }

        // 区
        $district = empty($data['district']) ? '' : $data['district'];
        if ($district) 
        {
            $district_text = model('Region')::where('code', $district)->value('name');
            $region .= $district_text;
        }

        //广东省-广州市-海珠区
        return $region;
    }
}
