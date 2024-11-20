<?php

namespace app\common\model\Coupon;

use think\Model;

class Coupon extends Model
{
    
    // 表名
    protected $name = 'coupon';

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = true; 

    //设置字段的名字
    protected $createTime = "createtime"; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = false;

    // 软删除的字段
    protected $deleteTime = false;

    // 忽略数据表不存在的字段
    protected $field = true;

    // 追加属性
    protected $append = [
        'status_text',
        'thumb_text',
        'createtime_text',
        'endtime_text',
    ];

    // 订单状态数据
    public function statuslist()
    {
        return [
            '1' => __('正在活动中'),
            '0' => __('结束活动'),
        ];
    }

    // 订单状态的获取器
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->statuslist();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getThumbTextAttr($value, $data)
    {
        //先获取到图片信息
        $cover = isset($data['thumb']) ? trim($data['thumb']) : '';

        //如果为空 或者 图片不存在 给一个默认图
        if(empty($cover) || !is_file(".".$cover)) $cover = config("site.cover");

        // 组装域名信息
        $domain = request()->domain();
        $domain = trim($domain, '/');
        return $domain.$cover;
    }

    //时间戳
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = $data['createtime'];
        
        if(empty($createtime))
        {
            return '';
        }

        return date("Y-m-d", $createtime);
    }

    //时间戳
    public function getEndtimeTextAttr($value, $data)
    {
        $endtime = $data['endtime'];
        
        if(empty($endtime))
        {
            return '';
        }

        return date("Y-m-d", $endtime);
    }
}
