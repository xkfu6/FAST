<?php

namespace app\common\model\Order;

use think\Model;

class Product extends Model
{
    // 数据表
    protected $name = 'order_product';

    //自动过滤掉不存在的字段
    protected $field = true;

    protected $append = [
        'query_time_text',
        'query_qrcord_text',
        'comtime_text',
        'thumbs_text'
    ];

    public function getQueryQrcordTextAttr($value, $data)
    {
        //获取域名部分
        $domain = request()->domain();
        $domain = trim($domain, '/');

        $query_qrcord = isset($data['query_qrcord']) ? $data['query_qrcord'] : '';

        //如果为空就给一个默认图片地址
        if (empty($query_qrcord) || !is_file("." . $query_qrcord)) {
            $query_qrcord = "/assets/img/cover.png";
        }

        return $domain . $query_qrcord;
    }

    public function getQueryTimeTextAttr($value, $data)
    {
        $QueryTime = isset($data['query_time']) ? trim($data['query_time']) : "";

        if (empty($QueryTime)) {
            return '暂无上架时间';
        }

        return date("Y-m-d", $QueryTime);
    }

    public function getComtimeTextAttr($value, $data)
    {
        $comtime = isset($data['comtime']) ? $data['comtime'] : 0;
        return date('Y-m-d H:i', $comtime);
    }
    public function getThumbsTextAttr($value, $data)
    {
        //获取域名部分
        $domain = request()->domain();
        $domain = trim($domain, '/');

        $thumbs = isset($data['thumbs']) ? $data['thumbs'] : '';

        if (empty($thumbs)) {
            return [];
        }

        $thumbs = explode(',', $thumbs);

        //去空
        $thumbs = array_filter($thumbs);
        $thumbs = array_unique($thumbs);
        foreach ($thumbs as &$item) {
            if (is_file('.' . $item)) {
                $item = $domain . $item;
            } else {
                unset($item);
            }
        }

        return $thumbs;
    }

    // 关联商品查询
    public function products()
    {
        return $this->belongsTo('app\common\model\Product\Product', 'proid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    // 关联用户查询
    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function order()
    {
        return $this->belongsTo('app\common\model\Order\Order', 'orderid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
