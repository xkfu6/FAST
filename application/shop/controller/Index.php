<?php

namespace app\shop\controller;

use think\Controller;

class Index extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->CouponModel = model('common/Coupon/Coupon');
        $this->ProductModel = model('common/Product/Product');
    }

    public function index()
    {
        //查询优惠券
        $coupon = $this->CouponModel->where(['status' => '1'])->select();

        //新品
        $news = $this->ProductModel->where(['flag' => '1', 'status' => '1'])->limit(8)->select();

        $data = [
            'coupon' => $coupon,
            'news' => $news,
        ];
        $this->success('首页数据', null, $data);
        exit;
    }
}
