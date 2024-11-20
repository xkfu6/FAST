<?php

namespace app\shop\controller;

use think\Controller;

class Coupon extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->CouponModel = model('Coupon.Coupon');
        $this->ReceiveModel = model('Coupon.Receive');
    }

    // 优惠卷领取界面
    public function index()
    {
        if ($this->request->isPOST()) {
            $page = $this->request->param('page', 1, 'trim');
            $limit = 8;
            //偏移量
            $offset = ($page - 1) * $limit;
            $coupon = $this->CouponModel->where(['status' => '1'])->page($offset, $limit)->select();


            if ($coupon) {
                $this->success('查询成功', null, $coupon);
                exit;
            } else {
                $this->error('没数据');
                exit;
            }
        }
    }
    // 优惠卷详情
    public function info()
    {
        if ($this->request->isPOST()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $id = $this->request->param('id', 0, 'trim');
            $usep = 0;
            if (!$id) {
                $this->error('优惠卷不存在');
                exit;
            }
            $coupon = $this->CouponModel->where(['id' => $id])->find();

            $use = $this->ReceiveModel->where(['busid' => $busid, 'cid' => $id])->find();

            if ($use) {
                $usep = 1;
            }
            $data = [
                'usep' => $usep,
                'coupon' => $coupon
            ];
            if ($coupon) {
                $this->success('查询成功', null, $data);
                exit;
            } else {
                $this->error('没有这个优惠卷');
                exit;
            }
        }
    }
    // 优惠卷领取
    public function receive()
    {
        if ($this->request->isPOST()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $id = $this->request->param('id', 0, 'trim');
            $result = $this->ReceiveModel->save(['cid' => $id, 'busid' => $busid, 'status' => '1']);
            if ($result) {
                $this->success('领取优惠卷成功');
                exit;
            } else {
                $this->error('领取优惠卷失败');
                exit;
            }
        }
    }
    public function myindex()
    {
        if ($this->request->isPOST()) {
            $page = $this->request->param('page', 1, 'trim');
            $busid = $this->request->param('busid', 1, 'trim');
            $limit = 8;
            //偏移量
            $offset = ($page - 1) * $limit;

            $coupon = $this->ReceiveModel->with('coupon')->where(['busid' => $busid])->page($offset, $limit)->select();

            if ($coupon) {
                $this->success('查询成功', null, $coupon);
                exit;
            } else {
                $this->error('没数据');
                exit;
            }
        }
    }
}
