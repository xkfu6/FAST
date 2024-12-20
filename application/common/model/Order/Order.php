<?php

namespace app\common\model\Order;

use think\Model;
use traits\model\SoftDelete;

class Order extends Model
{
    use SoftDelete;

    // 表名
    protected $name = 'order';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    //自动过滤掉不存在的字段
    protected $field = true;

    // 追加属性
    protected $append = [
        'status_text',
        'createtime_text',
        'pay_text'
    ];

    // 订单状态数据
    public function statuslist()
    {
        return [
            '0' => __('未支付'),
            '1' => __('已支付'),
            '2' => __('已发货'),
            '3' => __('已收货'),
            '4' => __('已完成'),
            '-1' => __('仅退款'),
            '-2' => __('退款退货'),
            '-3' => __('售后中'),
            '-4' => __('退货成功'),
            '-5' => __('退货失败')
        ];
    }

    // 订单状态的获取器
    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->statuslist();
        return isset($list[$value]) ? $list[$value] : '';
    }

    //支付方式
    public function paylist()
    {
        return [
            'money' => __('余额支付'),
            'zfb' => __('支付宝支付'),
            'wx' => __('微信支付'),
        ];
    }
    public function getStatusList()
    {
        return ['1' => __('未支付'), '2' => __('已支付'), '3' => __('已发货'), '4' => __('已完成'), '-1' => __('仅退款'), '-2' => __('退货退款'), '-3' => __('售后中（退货中）'), '-4' => __('退货成功'), '-5' => __('退货失败')];
    }

    public function getPaytextAttr($value, $data)
    {
        $pay = isset($data['pay']) ? $data['pay'] : 'money';

        return $this->paylist()[$pay];
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? trim($data['createtime']) : "";

        if (empty($createtime)) {
            return '暂无下单时间';
        }

        return date("Y-m-d", $createtime);
    }

    // 关联物流
    public function express()
    {
        return $this->belongsTo('app\common\model\Express', 'expressid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联用户
    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联用户收货地址
    public function address()
    {
        return $this->belongsTo('app\common\model\Business\Address', 'businessaddrid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 销售员
    public function sale()
    {
        return $this->belongsTo('app\common\model\Admin', 'adminid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 审核员
    public function review()
    {
        return $this->belongsTo('app\common\model\Admin', 'checkmanid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 发货员
    public function dispatched()
    {
        return $this->belongsTo('app\common\model\Admin', 'shipmanid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 关联查询订单商品
    public function orderProduct()
    {
        return $this->belongsTo('app\common\model\Product\order\Product', 'orderid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
