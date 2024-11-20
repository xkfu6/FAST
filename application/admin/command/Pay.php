<?php

namespace app\admin\command;

use app\admin\command\Api\library\Builder;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\Db;

use app\home\controller\Pay as PayController;

class Pay extends Command
{
    protected $result = [
        'msg' => '',
        'result' => false
    ];

    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = new \app\common\model\Business\Business();
        $this->RecordModel = new \app\common\model\Business\Record();
        $this->PayModel = new \app\common\model\Pay\Pay();
        $this->SubjectModel = new \app\common\model\Subject\Subject();
        $this->LiveProductModel = new \app\common\model\Live\Product();
        $this->OrderModel = new \app\common\model\Subject\Order();
        $this->OrderProductModel = new \app\common\model\Order\Product();
        $this->ReceiveModel = new \app\common\model\Coupon\Receive();
        $this->CommissionModel = new \app\common\model\Business\Commission();
        $this->ProductModel = new \app\common\model\Product\Product();
    }

    protected function configure()
    {
        $this->setName('pay')->setDescription('更新支付订单状态为【已支付】'); //命令的描述
    }

    protected function execute(Input $input, Output $output)
    {
        $list = Db::name('pay')->where(['status' => 0])->select();

        if (!$list) {
            $output->info('目前暂无待支付的订单');
            return false;
        }

        foreach ($list as $item) {
            if ($item['name'] == "余额充值") {
                $success = $this->business($item['id'], $output);
                $output->info($success['msg']);
            } else if ($item['name'] == "课程购买") {
                $success = $this->subject($item['id'], $output);
                $output->info($success['msg']);
            } else if ($item['name'] == "商品订单") {
                $success = $this->shop($item['id'], $output);
                $output->info($success['msg']);
            }
        }

        $output->info('执行完毕');
        return false;
    }

    // 用户充值
    public function business($ids = 0, Output $output)
    {
        //查询充值记录
        $row = $this->PayModel->find($ids);

        if (!$row) {
            $this->result['msg'] = '支付订单不存在';
            $this->result['result'] = false;
            return $this->result;
        }

        // 充值的金额
        $total = isset($row['total']) ? $row['total'] : 0;
        $total = floatval($total);

        // 第三方参数(可多参数)
        $third = isset($row['third']) ? $row['third'] : '';

        // json字符串转换数组
        $third = json_decode($third, true);

        // 从数组获取充值的用户id
        $busid = isset($third['busid']) ? $third['busid'] : 0;

        // 支付方式
        $type = isset($row['type']) ? $row['type'] : 'zfb';
        $payment = $type == "zfb" ? "支付宝" : "微信支付";

        //判断用户是否存在
        $business = $this->BusinessModel->where(['id' => $busid])->find();

        if (!$business) {
            $this->result['msg'] = '充值用户不存在';
            $this->result['result'] = false;
            return $this->result;
        }

        //判断充值金额
        if ($total <= 0) {
            $this->result['msg'] = '充值金额不能为0';
            $this->result['result'] = false;
            return $this->result;
        }

        // 开启事务
        $this->BusinessModel->startTrans();
        $this->RecordModel->startTrans();
        $this->PayModel->startTrans();

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
                'money' => ['number', '>=:0'],
            ],
            [
                'money.number' => '余额必须是数字类型',
                'money.>=' => '余额必须大于等于0元'
            ]
        ];

        $BusinessStatus = $this->BusinessModel->validate(...$validate)->isUpdate(true)->save($BusinessData);

        if ($BusinessStatus === FALSE) {
            $this->result['msg'] = $this->BusinessModel->getError();
            $this->result['result'] = false;
            return $this->result;
        }

        // 封装插入消费记录的数据
        $RecordData = [
            'total' => $total, //变动金额数
            'content' => "{$payment}充值了 $total 元",
            'busid' => $business['id']
        ];

        // 插入
        $RecordStatus = $this->RecordModel->validate('common/Business/Record')->save($RecordData);

        if ($RecordStatus === FALSE) {
            $this->BusinessModel->rollback();
            $this->result['msg'] = $this->RecordModel->getError();
            $this->result['result'] = false;
            return $this->result;
        }

        //将未支付的订单  修改为已支付的状态
        $PayData = [
            'status' => 1,
            'paytime' => time()
        ];

        $PayStatus = $this->PayModel->where(['id' => $row['id']])->update($PayData);

        if ($PayStatus === FALSE) {
            $this->RecordModel->rollback();
            $this->BusinessModel->rollback();
            $this->result['msg'] = '支付订单更新状态失败';
            $this->result['result'] = false;
            return $this->result;
        }


        if ($BusinessStatus === FALSE || $RecordStatus === FALSE || $PayStatus === FALSE) {
            $this->PayModel->rollback();
            $this->RecordModel->rollback();
            $this->BusinessModel->rollback();

            $this->result['msg'] = '充值失败';
            $this->result['result'] = false;
            return $this->result;
        } else {
            $this->BusinessModel->commit();
            $this->RecordModel->commit();
            $this->PayModel->commit();

            $this->result['msg'] = '充值成功';
            $this->result['result'] = true;
            return $this->result;
        }
    }

    //课程购买
    public function subject($ids = 0, Output $output)
    {
        //判断支付订单是否存在
        $pay = $this->PayModel->find($ids);

        if (!$pay) {
            $this->result['msg'] = '支付订单不存在';
            $this->result['result'] = false;
            return $this->result;
        }

        //消费金额
        $total = isset($pay['total']) ? $pay['total'] : 0;
        $total = floatval($total);

        //判断消费金额
        if ($total < 0) {
            $this->result['msg'] = '消费金额为0';
            $this->result['result'] = false;
            return $this->result;
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

        if (!$business) {
            $this->result['msg'] = '用户不存在';
            $this->result['result'] = false;
            return $this->result;
        }

        //判断课程是否存在
        $subject = $this->SubjectModel->find($subid);

        if (!$subject) {
            $this->result['msg'] = '课程不存在';
            $this->result['result'] = false;
            return $this->result;
        }

        //查询是否有直播价格
        $where = ['live.status' => '1', 'type' => 'subject', 'relation' => $subid];
        $live = $this->LiveProductModel->with(['live'])->where($where)->find();
        if ($live && $live['stock'] > 0) {
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

        if ($OrderStatus === FALSE) {
            $this->result['msg'] = $this->OrderModel->getError();
            $this->result['result'] = false;
            return $this->result;
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

        if ($RecordStatus === FALSE) {
            $this->OrderModel->rollback();
            $this->result['msg'] = $this->RecordModel->getError();
            $this->result['result'] = false;
            return $this->result;
        }


        //查询是否有选择优惠券
        $where = ['receive.id' => $couid, 'receive.status' => '1'];
        $coupon = $this->ReceiveModel->with(['coupon'])->where($where)->find();

        // 如果有选择优惠券那么就要更新优惠券使用状态
        if ($coupon) {
            $ReceiveData = [
                'id' => $couid,
                'status' => '0' //改为已使用了
            ];

            $ReceiveStatus = $this->ReceiveModel->isUpdate(true)->save($ReceiveData);

            if ($ReceiveStatus === FALSE) {
                $this->RecordModel->rollback();
                $this->OrderModel->rollback();
                $this->result['msg'] = '优惠券状态更新失败';
                $this->result['result'] = false;
                return $this->result;
            }
        }


        //判断是否有推荐人可以得到佣金
        $parentid = $business['parentid'] ? trim($business['parentid']) : 0;
        $parent = $this->BusinessModel->find($parentid);
        if ($parent) {
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

            if ($CommissionStatus === FALSE) {
                $this->ReceiveModel->rollback();
                $this->RecordModel->rollback();
                $this->OrderModel->rollback();
                $this->result['msg'] = '推荐信息存储失败';
                $this->result['result'] = false;
                return $this->result;
            }
        }


        //如果是在直播间购买的，就减直播库存
        if ($live && $live['stock'] > 0) {
            $stock = intval($live['stock']);
            $stock = $stock <= 0 ? 0 : --$stock;
            $LiveData = [
                'id' => $live['id'],
                'stock' => $stock
            ];

            $LiveStatus = $this->LiveProductModel->isUpdate(true)->save($LiveData);

            if ($LiveStatus === FALSE) {
                $this->CommissionModel->rollback();
                $this->ReceiveModel->rollback();
                $this->RecordModel->rollback();
                $this->OrderModel->rollback();
                $this->result['msg'] = '直播间库存更新失败';
                $this->result['result'] = false;
                return $this->result;
            }
        }


        //将未支付的订单  修改为已支付的状态
        $PayData = [
            'status' => 1,
            'paytime' => time()
        ];

        $PayStatus = $this->PayModel->where(['id' => $pay['id']])->update($PayData);

        if ($PayStatus === FALSE) {
            $this->LiveProductModel->rollback();
            $this->CommissionModel->rollback();
            $this->ReceiveModel->rollback();
            $this->RecordModel->rollback();
            $this->OrderModel->rollback();
            $this->result['msg'] = '支付订单更新状态失败';
            $this->result['result'] = false;
            return $this->result;
        }


        if ($OrderStatus === FALSE || $RecordStatus === FALSE) {
            $this->PayModel->rollback();
            $this->LiveProductModel->rollback();
            $this->CommissionModel->rollback();
            $this->ReceiveModel->rollback();
            $this->RecordModel->rollback();
            $this->OrderModel->rollback();
            $this->result['msg'] = '购买课程失败';
            $this->result['result'] = false;
            return $this->result;
        } else {
            $this->OrderModel->commit();
            $this->RecordModel->commit();
            $this->ReceiveModel->commit();
            $this->CommissionModel->commit();
            $this->LiveProductModel->commit();
            $this->PayModel->commit();
            $this->result['msg'] = '购买课程成功';
            $this->result['result'] = true;
            return $this->result;
        }
    }

    //商品购买
    public function shop($ids = 0, Output $output)
    {
        $this->OrderModel = new \app\common\model\Order\Order();

        //判断支付订单是否存在
        $pay = $this->PayModel->find($ids);

        if (!$pay) {
            $this->result['msg'] = '支付订单不存在';
            $this->result['result'] = false;
            return $this->result;
        }

        //消费金额
        $total = isset($pay['total']) ? $pay['total'] : 0;
        $total = floatval($total);

        //判断消费金额
        if ($total < 0) {
            $this->result['msg'] = '消费金额为0';
            $this->result['result'] = false;
            return $this->result;
        }

        // 第三方参数(可多参数)
        $third = isset($pay['third']) ? $pay['third'] : '';

        // json字符串转换数组
        $third = json_decode($third, true);

        // 从数组获取充值的用户id
        $busid = isset($third['busid']) ? $third['busid'] : 0;
        $orderid = isset($third['orderid']) ? $third['orderid'] : 0;

        //判断用户是否存在
        $business = $this->BusinessModel->find($busid);

        if (!$business) {
            $this->result['msg'] = '用户不存在';
            $this->result['result'] = false;
            return $this->result;
        }

        //判断订单是否存在
        $order = $this->OrderModel->find($orderid);

        if (!$order) {
            $this->result['msg'] = '订单不存在';
            $this->result['result'] = false;
            return $this->result;
        }

        //开启事务
        $this->ProductModel->startTrans(); //更新库存
        $this->RecordModel->startTrans(); //插入消费记录
        $this->OrderModel->startTrans(); //更新订单支付状态
        $this->PayModel->startTrans(); //支付表
        $this->CommissionModel->startTrans();

        //查询订单商品
        $prolist = $this->OrderProductModel->where(['orderid' => $orderid])->select();


        if ($prolist) {
            //更新库存商品
            $stock = $ProductData = [];

            foreach ($prolist as $item) {
                if (isset($stock[$item['proid']])) {
                    $stock[$item['proid']] = bcadd($stock[$item['proid']], $item['pronum']);
                } else {
                    $stock[$item['proid']] = $item['pronum'];
                }
            }

            if (!empty($stock)) {
                foreach ($stock as $key => $item) {
                    $product = $this->ProductModel->field('id,stock,name')->where(['id' => $key])->find();

                    $UpdateStock = bcsub($product['stock'], $item);
                    $UpdateStock = $UpdateStock <= 0 ? 0 : $UpdateStock;

                    $ProductData[] = [
                        'id' => $product['id'],
                        'stock' => $UpdateStock
                    ];
                }
            }

            if (!empty($ProductData)) {
                //产品表库存更新
                $ProductStatus = $this->ProductModel->isUpdate(true)->saveAll($ProductData);

                if ($ProductStatus === FALSE) {
                    $this->result['msg'] = '更新商品库存失败';
                    $this->result['result'] = false;
                    return $this->result;
                }
            }
        }

        //用户消费记录
        $code = $order['code'];
        $amount = $order['amount'];
        $RecordData = [
            'total' => "-$amount",
            'busid' => $busid,
            'content' => "商城订单号：$code ---消费的金额：$amount 元",
        ];

        //插入语句
        $RecordStatus = $this->RecordModel->validate('common/Business/Record')->save($RecordData);

        if ($RecordStatus === FALSE) {
            $this->ProductModel->rollback();
            $this->result['msg'] = '消费记录插入失败';
            $this->result['result'] = false;
            return $this->result;
        }

        //更新订单状态
        $OrderStatus = $this->OrderModel->where(['id' => $order['id']])->update(['status' => '1']);

        if ($OrderStatus === FALSE) {
            $this->RecordModel->rollback();
            $this->ProductModel->rollback();
            $this->result['msg'] = '更新订单状态失败';
            $this->result['result'] = false;
            return $this->result;
        }

        //支付表更新
        $PayStatus = $this->PayModel->where(['id' => $ids])->update(['status' => '1']);

        if ($PayStatus === FALSE) {
            $this->OrderModel->rollback();
            $this->RecordModel->rollback();
            $this->ProductModel->rollback();
            $this->result['msg'] = '更新支付状态失败';
            $this->result['result'] = false;
            return $this->result;
        }

        //判断是否有推荐人可以得到佣金
        $parentid = $business['parentid'] ? trim($business['parentid']) : 0;
        $parent = $this->BusinessModel->find($parentid);
        if ($parent) {
            //佣金比率
            $AmountRate = config('site.AmountRate') ? config('site.AmountRate') : 0.05;
            //消费金额*佣金比率 = 转给推荐人
            $conamount = bcmul($amount, $AmountRate, 2);
            //插入佣金记录
            $CommissionData = [
                'orderid' => $orderid,
                'busid' => $busid,
                'parentid' => $parentid,
                'type' => 'product', //买课程的佣金
                'stauts' => '0', //未提现
                'amount' => $conamount, //佣金
            ];
            $CommissionStatus = $this->CommissionModel->save($CommissionData);
            if ($CommissionStatus === FALSE) {
                $this->PayModel->rollback();
                $this->OrderModel->rollback();
                $this->RecordModel->rollback();
                $this->ProductModel->rollback();
                return json(['code' => 0, 'msg' => '推荐信息存储失败', 'data' => null]);
            }
        }


        if ($RecordStatus === FALSE || $OrderStatus === FALSE || $PayStatus === FALSE || $CommissionStatus === FALSE) {
            $this->CommissionModel->rollback();
            $this->PayModel->rollback();
            $this->OrderModel->rollback();
            $this->RecordModel->rollback();
            $this->ProductModel->rollback();
            $this->result['msg'] = '支付失败';
            $this->result['result'] = false;
            return $this->result;
        } else {
            $this->ProductModel->commit();
            $this->RecordModel->commit();
            $this->OrderModel->commit();
            $this->PayModel->commit();
            $this->CommissionModel->commit();
            $this->result['msg'] = '支付成功';
            $this->result['result'] = false;
            return $this->result;
        }
    }
}
