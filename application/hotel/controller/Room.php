<?php

namespace app\hotel\controller;

use think\Controller;


class Room extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');

        $this->CouponModel = model('Coupon.Coupon');
        $this->ReceiveModel = model('Coupon.Receive');

        $this->GuestModel = model('Hotel.Guest');
        $this->OrderModel = model('Hotel.Order');
        $this->model = model('Hotel.Room');
    }

    // 首页渲染
    public function index()
    {
        if ($this->request->isPost()) {
            $page = $this->request->param('page', '1', 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $limit = 10;
            $start = ($page - 1) * $limit;

            $where = [];

            if (!empty($keywords)) {
                $where['name'] = ['LIKE', "%$keywords%"];
            }

            $room = $this->model
                ->where($where)
                ->limit($start, $limit)
                ->select();

            // echo $this->model->getLastSql();
            // exit;

            if ($room) {
                $this->success('返回列表', null, $room);
                exit;
            } else {
                $this->error('暂无数据');
                exit;
            }
        }
    }

    public function info()
    {
        if ($this->request->isPost()) {
            $rid = $this->request->param('rid', 0, 'trim');

            //查询房间是否存在
            $room = $this->model->find($rid);

            if (!$room) {
                $this->error('暂无房间信息');
                exit;
            }

            //查询一下房间的订单
            $where = [
                'roomid' => $rid,
                'status' => ['IN', ['1', '2']],
            ];

            $count = $this->OrderModel->where($where)->count();

            //在数据中插入一个自定义的属性，用来表示是否可以预订
            $room['state'] = bcsub($room['total'], $count) <= 0 ? false : true;

            //查询部分的评论数据
            $comment = $this->OrderModel->with(['business'])->where(['status' => '4', 'roomid' => $rid])->limit(3)->select();

            $data = [
                'room' => $room,
                'comment' => $comment
            ];

            $this->success('返回房间信息', null, $data);
            exit;
        }
    }

    public function guest()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');

            $list = $this->GuestModel->where(['busid' => $busid])->select();

            if ($list) {
                $this->success('返回住客信息', null, $list);
                exit;
            } else {
                $this->error('暂无住客信息');
                exit;
            }
        }
    }

    public function coupon()
    {
        if ($this->request->isPost()) {
            if ($this->request->isPost()) {
                $busid = $this->request->param('busid', 0, 'trim');

                $list = $this->ReceiveModel
                    ->with(['coupon'])
                    ->where(['busid' => $busid, 'receive.status' => '1'])
                    ->select();

                if ($list) {
                    $this->success('返回优惠券信息', null, $list);
                    exit;
                } else {
                    $this->error('暂无优惠券信息');
                    exit;
                }
            }
        }
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $roomid = $this->request->param('roomid', 0, 'trim');
            $couponid = $this->request->param('couponid', 0, 'trim');
            $starttime = $this->request->param('starttime', 0, 'trim');
            $endtime = $this->request->param('endtime', 0, 'trim');
            $guest = $this->request->param('guest', NULL, 'trim');
            $remark = $this->request->param('remark', '', 'trim');
            $pay = $this->request->param('pay', 'money', 'trim');

            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $room = $this->model->find($roomid);

            if (!$room) {
                $this->error('房间不存在');
                exit;
            }

            //房间是否可以在预约
            $where = [
                'roomid' => $roomid,
                'status' => ['IN', '1,2']
            ];

            $count = $this->OrderModel->where($where)->count();

            $update = bcsub($room['total'], $count);

            if ($update <= 0) {
                $this->error('该房型已全部预约');
                exit;
            }


            //查看优惠券是否过期
            $where = [
                'busid' => $busid,
                'receive.id' => $couponid
            ];

            $receive = $this->ReceiveModel->with(['coupon'])->where($where)->find();

            //如果存在优惠券就判断
            if ($receive) {
                if ($receive['status'] == '0') {
                    $this->error('该优惠券已失效');
                    exit;
                }
            }

            // 先计算天数 先计算出价格
            $day = intval(($endtime - $starttime) / 86400);
            $price = $origin_price = bcmul($day, $room['price']);

            if ($receive) {
                $rate = isset($receive['coupon']['rate']) ? $receive['coupon']['rate'] : 1;
                $price = bcmul($origin_price, $rate);
            }

            //判断是否是使用余额支付
            if ($pay == "money") {
                $UpdateMoney = bcsub($business['money'], $price);

                if ($UpdateMoney < 0) {
                    $this->error('余额不足，请选择其他方式支付');
                    exit;
                }
            }

            //订单表 优惠券
            $this->OrderModel->startTrans();
            $this->ReceiveModel->startTrans();
            $this->BusinessModel->startTrans();

            $OrderData = [
                'busid' => $busid,
                'roomid' => $roomid,
                'guest' => $guest,
                'origin_price' => $origin_price,
                'price' => $price,
                'starttime' => $starttime,
                'endtime' => $endtime,
                'status' => '0', //未支付
                'couponid' => $couponid ? $couponid : NULL,
                'remark' => $remark,
                'type' => $pay
            ];

            if ($pay == "money") {
                //已支付状态
                $OrderData['status'] = '1';
            }

            //插入订单表
            $OrderStatus = $this->OrderModel->save($OrderData);

            if ($OrderStatus === FALSE) {
                $this->error('预约房间订单失败');
                exit;
            }

            //判断是否有用到优惠券
            if ($OrderData['couponid']) {
                $ReceiveData = [
                    'id' => $couponid,
                    'status' => '0'
                ];

                $ReceiveStatus = $this->ReceiveModel->isUpdate(true)->save($ReceiveData);

                if ($ReceiveStatus === FALSE) {
                    $this->OrderModel->rollback();
                    $this->error('更新优惠券状态失败');
                    exit;
                }
            }

            //判断是否用余额支付
            if ($pay == "money") {
                //计算扣完之后的余额
                $UpdateMoney = bcsub($business['money'], $price);

                $BusinessData = [
                    'id' => $busid,
                    'money' => $UpdateMoney
                ];

                $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

                if ($BusinessStatus === FALSE) {
                    $this->ReceiveModel->rollback();
                    $this->OrderModel->rollback();
                    $this->error('更新用户余额失败');
                    exit;
                }
            }

            if ($OrderStatus === FALSE) {
                $this->BusinessModel->rollback();
                $this->ReceiveModel->rollback();
                $this->OrderModel->rollback();
                $this->error('预约失败');
                exit;
            } else {
                $this->OrderModel->commit();
                $this->ReceiveModel->commit();
                $this->BusinessModel->commit();

                $url = "/room/pay?orderid=" . $this->OrderModel->id;  //支付界面

                //余额支付跳转到详情界面
                if ($pay == "money") {
                    $url = "/order/info?orderid=" . $this->OrderModel->id; //订单详情
                }

                $this->success('预约成功', $url);
                exit;
            }
        }
    }

    // 评价
    public function visible()
    {
        $page = $this->request->param('page', '1', 'trim');
        $rid = $this->request->param('rid', '1', 'trim');
        $limit = 3;
        $start = ($page - 1) * $limit;
        //查询部分的评论数据
        $comment = $this->OrderModel->with(['business'])->where(['status' => '4', 'roomid' => $rid])->limit($start, $limit)->select();
        if ($comment) {
            $this->success('返回列表', null, $comment);
            exit;
        } else {
            $this->error('暂无数据');
            exit;
        }
    }
}
