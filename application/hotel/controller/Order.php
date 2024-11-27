<?php

namespace app\hotel\controller;

use think\Controller;
use think\Request;


class Order extends Controller
{
    public function __construct()
    {
        parent::__construct();   

        $this->BusinessModel = model('Business.Business');
        $this->PayModel = model('common/Pay/Pay');

        $this->CouponModel = model('Coupon.Coupon');
        $this->ReceiveModel = model('Coupon.Receive');
        $this->GuestModel = model('Hotel.Guest');
        $this->OrderModel = model('Hotel.Order');
        
        $this->model = model('Hotel.Room');
    }

    //订单支付方法
    public function pay()
    {
        if($this->request->isPost())
        {
            $orderid = $this->request->param('orderid', 0, 'trim');

            $order = $this->OrderModel->find($orderid);

            if(!$order)
            {
                $this->error('订单不存在');
                exit;
            }

            //判断当前用户是否存在
            $busid = $this->request->param('busid', 0, 'trim');

            $business = $this->BusinessModel->find($busid);

            if(!$business)
            {
                $this->error('用户不存在');
                exit;
            }
            
            $third = json_encode(['orderid' => $orderid]);


            //组装参数
            $data = [
                'name' => '酒店订单', //标题
                'third' => $third, //传递的第三方的参数
                'total' => $order['price'], //订单原价充值的价格
                'type' => $order['type'], //支付方式
                'cashier' => 0, //不需要收银台界面
                'jump' => "/order/info?orderid=$orderid", //订单支付完成后跳转的界面
                'notice' => '/hotel/order/callback',  //异步回调地址
            ];

            //调用模型中的支付方法
            $result = $this->PayModel->payment($data);

            if($result['code'])
            {
                $this->success('创建支付订单成功', null, $result['data']);
                exit;
            }else
            {
                $this->error('创建支付订单失败');
                exit;
            }
        }
    }

    //查询支付订单是否支付完成
    public function query()
    {
        if($this->request->isPost())
        {
            //判断当前用户是否存在
            $busid = $this->request->param('busid', 0, 'trim');

            $business = $this->BusinessModel->find($busid);

            if(!$business)
            {
                $this->error('用户不存在');
                exit;
            }

            $payid = $this->request->param('payid', 0, 'trim');

            $pay = $this->PayModel->find($payid);

            if(!$pay)
            {
                $this->error('支付记录不存在');
                exit;
            }

            //获取域名部分
            $host = Request::instance()->domain();
            $host = trim($host, '/');

            //完整的请求接口地址
            $api = $host."/pay/index/status";

            //发起请求
            $result = httpRequest($api, ['payid'=>$payid]);

            //将json转换为php数组
            $result = json_decode($result, true);

            if(isset($result['code']) && $result['code'] == 0)
            {
                $this->error($result['msg']);
                exit;
            }else
            {
                $status = isset($result['data']['status']) ? $result['data']['status'] : 0;
                $reurl = isset($result['data']['reurl']) ? $result['data']['reurl'] : '';
                $this->success('查询支付状态', null, ['status' => $status, 'reurl' => $reurl]);
                exit;
            }
        }
    }

    //支付回调
    public function callback()
    {
        // 判断是否有post请求过来
        if ($this->request->isPost()) 
        {
            // 获取到所有的数据
            $params = $this->request->param();

            // 第三方参数(可多参数)
            $third = isset($params['third']) ? $params['third'] : '';

            // json字符串转换数组
            $third = json_decode($third, true);

            // 从数组获取充值的用户id
            $orderid = isset($third['orderid']) ? $third['orderid'] : 0;
            // $orderid = 37;

            // 支付方式
            $paytype = isset($params['paytype']) ? $params['paytype'] : 0;

            // 支付订单id
            $payid = isset($params['id']) ? $params['id'] : 0;
            // $payid = 206;

            $pay = $this->PayModel->find($payid);

            if(!$pay)
            {
                $this->error('支付订单不存在');
                exit;
            }

            $payment = '';

            switch ($paytype) {
                case 0:
                    $payment = '微信支付';
                    break;
                case 1:
                    $payment = '支付宝支付';
                    break;
            }

            // 加载模型
            $RecordModel = model('Business.Record');
            $OrderModel = model('Hotel.Order');

            // 开启事务
            $RecordModel->startTrans();
            $OrderModel->startTrans();

            //先查询酒店预订订单是否存在
            $order = $OrderModel->find($orderid);

            if(!$order)
            {
                $this->error('预约订单不存在');
                exit;
            }

            $price = $order['price'];

            // 封装插入消费记录的数据
            $RecordData = [
                'total' => $price,
                'content' => "{$payment} 酒店预订消费了 $price 元",
                'busid' => $order['busid']
            ];

            // 插入
            $RecordStatus = $RecordModel->validate('common/Business/Record')->save($RecordData);

            if($RecordStatus === false)
            {
                // return json(['code' => 0, 'msg' => $RecordModel->getError(), 'data' => null]);
                $this->error($RecordModel->getError());
                exit;
            }

            //改变订单状态
            $OrderData = [
                'id' => $orderid,
                'status' => '1'
            ];

            $OrderStatus = $OrderModel->isUpdate(true)->save($OrderData);

            if($OrderStatus === FALSE)
            {
                $this->RecordModel->rollback();
                $this->error('订单支付状态修改失败');
                exit;
            }

            if($OrderStatus === false || $RecordStatus === false)
            {
                $RecordModel->rollback();
                $OrderModel->rollback();
                $this->error('支付回调失败');
                exit;
            }else
            {
                $RecordModel->commit();
                $OrderModel->commit();
                $this->success('订单支付成功');
                exit;
            }
        }
    }

    public function index()
    {
        if($this->request->isPost())
        {
            $page = $this->request->param('page', '1', 'trim');
            $status = $this->request->param('status', '', 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $limit = 10;
            $start = ($page-1)*$limit;

            $where = ['busid' => $busid];

            if(!empty($status) || $status == "0")
            {
                $where['status'] = $status;
            }

            $list = $this->OrderModel
                ->with(['room'])
                ->where($where)
                ->limit($start, $limit)
                ->select();

            if($list)
            {
                $this->success('返回列表', null, $list);
                exit;
            }else
            {
                $this->error('暂无数据');
                exit;
            }
        }
    }

    public function info()
    {
        if($this->request->isPost())
        {
            $orderid = $this->request->param('orderid', 0, 'trim');

            //查询房间是否存在
            $order = $this->OrderModel->with(['room'])->find($orderid);

            //查询住客信息
            $guestids = empty($order['guest']) ? "" : trim($order['guest']);

            $guest = $this->GuestModel->where(['id'=> ['IN',$guestids]])->select();

            $data = [
                'order'=>$order,
                'guest'=>$guest
            ];

            if($order)
            {
                $this->success('返回订单信息', null, $data);
                exit;
            }else
            {
                $this->error('暂无订单信息');
                exit;
            }

        

            
        }
    }

    public function comment()
    {
        if($this->request->isPost())
        {
            $orderid = $this->request->param('orderid', 0, 'trim');
            $comment = $this->request->param('comment', '', 'trim');
            $rate = $this->request->param('rate', 5, 'trim');

            $order = $this->OrderModel->find($orderid);

            if(!$order)
            {
                $this->error('订单不存在');
                exit;
            }

            if($order['status'] == '4')
            {
                $this->error('无须重复评价');
                exit;
            }else if($order['status'] != '3')
            {
                $this->error('状态有误，暂时无法评价');
                exit;
            }

            //更新语句
            $data = [
                'id' => $orderid,
                'status' => '4',
                'comment' => $comment,
                'rate' => $rate
            ];

            $result = $this->OrderModel->isUpdate(true)->save($data);

            if($result === FALSE)
            {
                $this->error('评价失败');
                exit;
            }else
            {
                $this->success('评论成功');
                exit;
            }
        }
    }

    public function apply()
    {
        if($this->request->isAjax())
        {
            $orderid = $this->request->param('orderid', '0', 'trim');

            $order = $this->OrderModel->find($orderid);

            if(!$order)
            {
                $this->error('订单不存在');
                exit;
            }

            if($order['status'] != '1')
            {
                $this->error('无法申请退款');
                exit;
            }

            //申请退款 -> 用户修改状态 -1 然后 在由后台进行审核 -2审核通过(退钱) -3审核失败
            $data = [
                'id' => $orderid,
                'status' => '-1',
            ];

            $result = $this->OrderModel->isUpdate(true)->save($data);

            if($result === FALSE)
            {
                $this->error('申请退款失败');
                exit;
            }else
            {
                $this->success('申请退款成功');
                exit;
            }
        }
    }
   
}
