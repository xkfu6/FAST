<?php

namespace app\common\model\Pay;

use think\Model;

class Pay extends Model
{
    // 表名
    protected $name = 'pay';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'status_text',
        'paytime_text'
    ];

    public function GetPayType()
    {
        return ['wx' => __('微信支付'), 'zfb' => __('支付宝支付')];
    }

    public function GetStatusList()
    {
        return ['0' => __('待支付'), '1' => __('已支付'), '2' => __('已关闭')];
    }
    public function getTypeList()
    {
        return ['wx' => __('微信支付'), 'zfb' => __('支付宝支付')];
    }
    public function getCashierList()
    {
        return ['0' => __('Cashier 0'), '1' => __('Cashier 1')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->GetPayType();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->GetStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPaytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['paytime']) ? $data['paytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPaytimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    //支付方法！！！！！
    public function payment($data = [])
    {
        //获取域名部分
        $host = request()->domain();
        $host = trim($host, '/');

        //接口地址
        $api = $host . "/pay/index/create";

        // 订单支付完成后跳转的界面
        if (isset($data['jump']) && !empty($data['jump'])) {
            if ($data['cashier'] != 0)  //需要收银台
            {
                $data['jump'] = $host . '/' . trim($data['jump'], '/');
            }
        }

        // 回调的异步通知接口
        if (isset($data['notice']) && !empty($data['notice'])) {
            $data['notice'] = $host . '/' . trim($data['notice'], '/');
        }

        // 携带一个自定义的参数过去 转换为json类型
        if (isset($data['third']) && !empty($data['third'])) {
            $data['third'] = json_encode($data['third']);
        }

        //微信收款码
        $wxcode = config('site.wxcode') ? trim(config('site.wxcode'), '/') : '';
        if (!empty($wxcode)) {
            $data['wxcode'] = $host . '/' . $wxcode;
        }

        //支付宝收款码
        $zfbcode = config('site.zfbcode') ? trim(config('site.zfbcode'), '/') : '';
        if (!empty($zfbcode)) {
            $data['zfbcode'] = $host . '/' . $zfbcode;
        }

        // 发起请求
        $result = HttpRequest($api, $data);

        if ($data['cashier'] == "0") {
            $result = json_decode($result, true);
        }

        return $result;
    }
}
