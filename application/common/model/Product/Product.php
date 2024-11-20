<?php

namespace app\common\model\Product;

use think\Model;
use traits\model\SoftDelete;
use think\Request;

class Product extends Model
{
    // 软删除
    use SoftDelete;

    // 表名
    protected $name = 'product';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    //自动过滤掉不存在的字段
    protected $field = true;

    // 追加属性
    protected $append = [
        'flag_text',
        'status_text',
        'unit_text',
        'thumbs_text',
    ];

    public function getFlagList()
    {
        return ['1' => '新品', '2' => '热销', '3' => '推荐'];
    }

    public function getStatusList()
    {
        return ['0' => '下架', '1' => '已上架'];
    }
    public function getFlagTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['flag']) ? $data['flag'] : '');
        $list = $this->getFlaglist();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatuslist();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getUnitTextAttr($value, $data)
    {
        $unitid = isset($data['unitid']) ? $data['unitid'] : 0;
        return model('Product.Unit')->where(['id' => $unitid])->value('name');
    }

    public function getThumbsTextAttr($value, $data)
    {
        //获取域名部分
        $domain = Request::instance()->domain();
        $domain = trim($domain, '/');

        $thumbs = isset($data['thumbs']) ? $data['thumbs'] : '';

        //如果为空就给一个默认图片地址
        if (empty($thumbs) || !is_file("." . $thumbs)) {
            $thumbs = "/assets/home/images/video.jpg";
        }

        return $domain . $thumbs;
    }

    // 分类关联查询
    public function type()
    {
        return $this->belongsTo('app\common\model\Product\Type', 'typeid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 单位关联查询
    public function unit()
    {
        return $this->belongsTo('app\common\model\Product\Unit', 'unitid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
