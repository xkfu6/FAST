<?php

namespace app\home\controller;

use app\common\controller\Home;

//支付回调控制器
class Pay extends Home
{
    //设置不登录也可以访问
    public $NoLogin = ['*'];
    
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->PayModel = model('Pay.Pay');
        $this->OrderModel = model('Subject.Order');
        $this->ReceiveModel = model('Coupon.Receive');
        $this->RecordModel = model('Business.Record');
        $this->CommissionModel = model('Business.Commission');
        $this->SubjectModel = model('Subject.Subject');
        $this->LiveProductModel = model('Live.Product');
        $this->LiveModel = model('Live.Live');
    }

    //异步通知结果的方法
    public function business()
    {
        // 判断是否有post请求过来
        if ($this->request->isPost()) 
        {
            // 获取到所有的数据
            $params = $this->request->param();

            // 充值的金额
            $total = isset($params['total']) ? $params['total'] : 0;
            $total = floatval($total);

            // 第三方参数(可多参数)
            $third = isset($params['third']) ? $params['third'] : '';

            // json字符串转换数组
            $third = json_decode($third, true);

            // 从数组获取充值的用户id
            $busid = isset($third['busid']) ? $third['busid'] : 0;

            // 支付方式
            $type = isset($params['type']) ? $params['type'] : 'zfb';
            $payment = $type == "zfb" ? "支付宝" : "微信支付";

            // 支付订单id
            $payid = isset($params['id']) ? $params['id'] : 0;

            $pay = $this->PayModel->find($payid);

            if(!$pay)
            {
                return json(['code' => 0, 'msg' => '支付订单不存在', 'data' => null]);
            }

            //判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) 
            {
                return json(['code' => 0, 'msg' => '充值用户不存在', 'data' => null]);
            }

            //判断充值金额
            if ($total <= 0) 
            {
                return json(['code' => 0, 'msg' => '充值金额不能为0', 'data' => null]);
            }

            // 开启事务
            $this->BusinessModel->startTrans();
            $this->RecordModel->startTrans();

            // 拿出当前用户的余额并且转成浮点类型
            $money = floatval($business['money']);

            // 余额 + 充值的金额
            $UpdateMoney = bcadd($money, $total, 2);

            // 封装用户更新的数据
            $BusinessData = [
                'id' => $business['id'],
                'money' => $UpdateMoney
            ];

            // 自定义验证器
            $validate = [
                [
                    'money' => ['number','>=:0'],
                ],
                [
                    'money.number' => '余额必须是数字类型',
                    'money.>=' => '余额必须大于等于0元'
                ]
            ];

            // validate($rules, $message, $scens);
            $BusinessStatus = $this->BusinessModel->validate(...$validate)->isUpdate(true)->save($BusinessData);

            if($BusinessStatus === FALSE)
            {
                return json(['code' => 0, 'msg' => $this->BusinessModel->getError(), 'data' => null]);
            }

            // 封装插入消费记录的数据
            $RecordData = [
                'total' => $total, //变动金额数
                'content' => "{$payment}充值了 $total 元",
                'busid' => $business['id']
            ];

            // 插入
            $RecordStatus = $this->RecordModel->validate('common/Business/Record')->save($RecordData);

            if($RecordStatus === FALSE)
            {
                $this->BusinessModel->rollback();
                return json(['code' => 0, 'msg' => $this->RecordModel->getError(), 'data' => null]);
            }

            if($BusinessStatus === FALSE || $RecordStatus === FALSE)
            {
                $this->RecordModel->rollback();
                $this->BusinessModel->rollback();
                return json(['code' => 0, 'msg' => '充值失败', 'data' => null]);
            }else
            {
                $this->BusinessModel->commit();
                $this->RecordModel->commit();
                return json(['code' => 1, 'msg' => '充值成功', 'data' => null]);
            }
        }
    }

    //课程购买的支付回调
    public function subject()
    {
        // 判断是否有post请求过来
        if ($this->request->isPost()) 
        {
            // 支付订单id
            $payid = $this->request->param('id', 0, 'trim');

            //判断支付订单是否存在
            $pay = $this->PayModel->find($payid);

            if(!$pay)
            {
                return json(['code' => 0, 'msg' => '支付订单不存在', 'data' => null]);
            }

            //消费金额
            $total = isset($pay['total']) ? $pay['total'] : 0;
            $total = floatval($total);
            
            //判断消费金额
            if ($total < 0)
            {
                return json(['code' => 0, 'msg' => '消费金额为0', 'data' => null]);
            }

            // 第三方参数(可多参数)
            $third = isset($pay['third']) ? $pay['third'] : '';

            // json字符串转换数组
            $third = json_decode($third, true);

            // 从数组获取充值的用户id
            $busid = isset($third['busid']) ? $third['busid'] : 0;
            $subid = isset($third['subid']) ? $third['subid'] : 0;
            $couid = isset($third['couid']) ? $third['couid'] : 0;

            //判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) 
            {
                return json(['code' => 0, 'msg' => '用户不存在', 'data' => null]);
            }

            //判断课程是否存在
            $subject = $this->SubjectModel->find($subid);

            if(!$subject)
            {
                return json(['code' => 0, 'msg' => '课程不存在', 'data' => null]);
            }

            //查询是否有直播价格
            $where = ['live.status' => '1', 'type'=>'subject', 'relation' => $subid];
            $live = $this->LiveProductModel->with(['live'])->where($where)->find();
            if($live && $live['stock'] > 0)
            {
                $subject['live'] = $live['price'];
            }

            //开启事务逻辑
            // subject_order 用户订单表
            // business_record 用户消费记录
            // coupon_receive 优惠券领取记录表
            // business_commission 佣金表

            $this->OrderModel->startTrans();
            $this->RecordModel->startTrans();
            $this->ReceiveModel->startTrans();
            $this->CommissionModel->startTrans();
            $this->LiveProductModel->startTrans();

            //插入订单表
            $OrderData = [
                'subid' => $subid,
                'busid' => $busid,
                'pay' => $pay['type'],
                'total' => $total,
                'code' => build_code("ST"),
            ];

            //插入到订单表
            $OrderStatus = $this->OrderModel->validate('common/Subject/Order')->save($OrderData);

            if($OrderStatus === FALSE)
            {
                return json(['code' => 0, 'msg' => $this->OrderModel->getError(), 'data' => null]);
            }

            //消费记录
            $title = $subject['title'];
            $paytext = $pay['type_text'];
            $content = "购买了【{$title}】,花费了￥ $total 元（{$paytext}）";
            $RecordData = [
                'total' => "-$total",
                'content' => $content,
                'busid' => $busid
            ];

            $RecordStatus = $this->RecordModel->validate('common/Business/Record')->save($RecordData);

            if($RecordStatus === FALSE)
            {
                $this->OrderModel->rollback();
                return json(['code' => 0, 'msg' => $this->RecordModel->getError(), 'data' => null]);
            }


            //查询是否有选择优惠券
            $where = ['receive.id' => $couid, 'receive.status' => '1'];
            $coupon = $this->ReceiveModel->with(['coupon'])->where($where)->find();

            // 如果有选择优惠券那么就要更新优惠券使用状态
            if($coupon)
            {
                $ReceiveData = [
                    'id' => $couid,
                    'status' => '0' //改为已使用了
                ];
        
                $ReceiveStatus = $this->ReceiveModel->isUpdate(true)->save($ReceiveData);
        
                if($ReceiveStatus === FALSE)
                {
                    $this->RecordModel->rollback();
                    $this->OrderModel->rollback();
                    return json(['code' => 0, 'msg' => '优惠券状态更新失败', 'data' => null]);
                }
            }


            //判断是否有推荐人可以得到佣金
            $parentid = $business['parentid'] ? trim($business['parentid']) : 0;
            $parent = $this->BusinessModel->find($parentid);
            if($parent)
            {
                //佣金比率
                $AmountRate = config('site.AmountRate') ? config('site.AmountRate') : 0.05;
                
                //消费金额*佣金比率 = 转给推荐人
                $amount = bcmul($total, $AmountRate, 2);

                //插入佣金记录
                $CommissionData = [
                    'orderid' => $this->OrderModel->id, //获取插入最后一条的自增ID
                    'busid' => $busid,
                    'parentid' => $parentid,
                    'type' => 'subject', //买课程的佣金
                    'stauts' => '0', //未提现
                    'amount' => $amount, //佣金
                ];

                $CommissionStatus = $this->CommissionModel->save($CommissionData);

                if($CommissionStatus === FALSE)
                {
                    $this->ReceiveModel->rollback();
                    $this->RecordModel->rollback();
                    $this->OrderModel->rollback();
                    return json(['code' => 0, 'msg' => '推荐信息存储失败', 'data' => null]);
                }
            }


            //如果是在直播间购买的，就减直播库存
            if($live && $live['stock'] > 0)
            {
                $stock = intval($live['stock']);
                $stock = $stock <= 0 ? 0 : --$stock;
                $LiveData = [
                    'id' => $live['id'],
                    'stock' => $stock
                ];

                $LiveStatus = $this->LiveProductModel->isUpdate(true)->save($LiveData);

                if($LiveStatus === FALSE)
                {
                    $this->CommissionModel->rollback();
                    $this->ReceiveModel->rollback();
                    $this->RecordModel->rollback();
                    $this->OrderModel->rollback();
                    return json(['code' => 0, 'msg' => '直播间库存更新失败', 'data' => null]);
                    exit;
                }
            }


            if($OrderStatus === FALSE || $RecordStatus === FALSE)
            {
                $this->LiveProductModel->rollback();
                $this->CommissionModel->rollback();
                $this->ReceiveModel->rollback();
                $this->RecordModel->rollback();
                $this->OrderModel->rollback();
                return json(['code' => 0, 'msg' => '购买课程失败', 'data' => null]);
            }else
            {
                $this->OrderModel->commit();
                $this->RecordModel->commit();
                $this->ReceiveModel->commit();
                $this->CommissionModel->commit();
                $this->LiveProductModel->commit();
                return json(['code' => 1, 'msg' => '购买课程成功', 'data' => null]);
            }
        }
    }
}
