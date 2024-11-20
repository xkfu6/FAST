<?php

namespace app\common\model\Hotel;

use think\Model;

class Order extends Model
{
    // 表名
    protected $name = 'hotel_order';

    // 追加属性
    protected $append = [
        'status_text',
        'starttime_text',
        'startday_text',
        'endtime_text',
        'endday_text',
        'order_day',
        'type_text'
    ];

    //支付方式
    public function getTypeTextAttr($value, $data)
    {
        $type = isset($data['type']) ? trim($data['type']) : '';

        switch($type)
        {
            case "money":
                return '余额支付';
            case "wx":
                return "微信支付";
            case "zfb":
                return "支付宝";
            default:
                return "未知支付方式";
        }
    }

    // 总共入住几天
    public function getOrderDayAttr($value, $data)
    {
        $starttime = $data['starttime'] ? trim($data['starttime']) : 0;
        $endtime = $data['endtime'] ? trim($data['endtime']) : 0;
        $day = intval(($endtime - $starttime) / 86400);
        return $day;
    }

    //时间戳
    public function getStarttimeTextAttr($value, $data)
    {
        $starttime = $data['starttime'];
        
        if(empty($starttime))
        {
            return '';
        }

        return date("Y-m-d", $starttime);
    }

    public function getStartdayTextAttr($value, $data)
    {
        $starttime = $data['starttime'];
        
        if(empty($starttime))
        {
            return '';
        }
        
        $key = date("w", $starttime);

        $week = ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'];

        return $week[$key];
    }

    public function getEndtimeTextAttr($value, $data)
    {
        $endtime = $data['endtime'];
        
        if(empty($endtime))
        {
            return '';
        }

        return date("Y-m-d", $endtime);
    }

    public function getEnddayTextAttr($value, $data)
    {
        $endtime = $data['endtime'];
        
        if(empty($endtime))
        {
            return '';
        }

        $key = date("w", $endtime);

        $week = ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'];

        return $week[$key];
    }

    // 订单状态数据
    public function statuslist()
    {
        return [
            '0' => __('未支付'),
            '1' => __('已支付'),
            '2' => __('已入住'),
            '3' => __('已退房'),
            '4' => __('已评价'),
            '-1' => __('申请退款'),
            '-2' => __('审核通过'),
            '-3' => __('审核不通过'),
        ];
    }

    // 订单状态的获取器
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->statuslist();
        return isset($list[$value]) ? $list[$value] : '';
    }

     // 关联用户
     public function business()
     {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
     }

     public function room()
     {
         return $this->belongsTo('app\common\model\Hotel\Room', 'roomid', 'id', [], 'LEFT')->setEagerlyType(0);
     }
}
