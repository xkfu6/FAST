<?php

namespace app\shop\controller;

use think\Controller;

class Live extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->LiveModel = model('Live.Live');
        $this->LiveProductModel = model('Live.Product');
    }

    public function index()
    {
        if ($this->request->isPost()) {
            //查找正在直播的记录
            $live = $this->LiveModel->where(['status' => '1'])->find();

            if (!$live) {
                $this->error('暂无在线直播');
                exit;
            }

            //直播中关联热卖的商品
            $product = $this->LiveProductModel->with(['subjects', 'products'])->where(['liveid' => $live['id'], 'type' => 'product'])->select();

            $data = [
                'live' => $live['url_text'],
                'product' => $product
            ];
            $this->success('查询成功', null, $data);
        }
    }
}
