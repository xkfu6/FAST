<?php

namespace app\shop\controller;

use think\Controller;

class Pay extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->ProductModel = model('Product.Product');
        $this->CartModel = model('Product.Cart');
        $this->BusinessModel = model('Business.Business');
        $this->PayModel = model('common/Pay/Pay');
    }

    // 分销会员
    public function info()
    {
        if ($this->request->isPost()) {
            $payid = $this->request->param('payid', 0, 'trim');

            $pay = $this->PayModel->find($payid);

            if (!$pay) {
                $this->error('记录不存在');
                exit;
            }

            $this->success('返回支付记录', null, $pay);
            exit;
        }
    }
}
