<?php

namespace app\shop\controller;

use think\Controller;

class Cart extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->ProductModel = model('Product.Product');
        $this->CartModel = model('Product.Cart');
        $this->BusinessModel = model('Business.Business');

        // 规格属性
        $this->RelationModel = model('Product.Relation');
        $this->PropModel = model('Product.Prop');
    }
    // 添加购物车
    public function add()
    {
        $busid = $this->request->param('busid', 0, 'trim');
        $proid = $this->request->param('proid', 0, 'trim');
        $nums = $this->request->param('nums', 1, 'trim'); //加入的商品数量
        $relationids = $this->request->param('relationids', "0", 'trim'); //商品属性id值
        //根据手机号码查询用户是否存在
        $business = $this->BusinessModel->find($busid);

        if (!$business) {
            $this->error('该用户不存在');
            exit;
        }

        $product = $this->ProductModel->find($proid);

        if (!$product) {
            $this->error('商品不存在');
            exit;
        }

        if ($product['stock'] < $nums && $product['stock'] <= 0) {
            $this->error($product['name'] . '库存不足');
            exit;
        }
        //组装数据
        $data = [
            'busid' => $busid,
            'proid' => $proid,
            'nums' => $nums,
        ];
        //根据规格计算商品单价，在计算总价
        if (empty($relationids)) //无规格
        {
            $data['price'] = $product['price'];
            $data['total'] = bcmul($product['price'], $nums);
            $data['attrs'] = NULL;
        } else {
            //有规格
            $relation = $this->RelationModel->with(['prop'])->select($relationids);

            $attrs = [];
            $price = $product['price'];

            if ($relation) {
                foreach ($relation as $item) {
                    $attrs[] = [
                        'title' => isset($item['prop']['name']) ? $item['prop']['name'] : '',
                        'value' => $item['value'],
                        'price' => $item['price']
                    ];

                    $price = bcadd($price, $item['price']);
                }
            }


            $data['price'] = $price;
            $data['total'] = bcmul($price, $nums);
            $data['attrs'] = json_encode($attrs);
        }
        //查询是否存在购物车记录
        $where = ['busid' => $busid, 'proid' => $proid, 'attrs' => $data['attrs']];

        $cart = $this->CartModel->where($where)->find();


        if ($cart) {
            // 多次添加
            $nums = bcadd($cart['nums'], $nums);

            // bcmul — 两个任意精度数字乘法计算
            $total = bcmul($cart['price'], $nums, 2);

            $data = [
                'id' => $cart['id'],
                'nums' => $nums,
                'total' => $total,
                'attrs' => $cart['attrs'],
            ];

            $result = $this->CartModel->isUpdate(true)->save($data);
        } else {
            // 第一次商品添加购物车
            // 要增加校验器

            $result = $this->CartModel->validate('common/Product/Cart')->save($data);
        }

        if ($result === FALSE) {
            $this->error($this->CartModel->getError());
            exit;
        } else {
            //最新的购物车数量 求总和
            $num = $this->CartModel->where(['busid' => $busid])->sum('nums');

            // 查找购物车id
            $where = ['busid' => $busid, 'proid' => $proid, 'attrs' => $data['attrs']];
            $cartids = $this->CartModel->where($where)->value('id');

            $this->success('添加购物车成功', null, ['nums' => $nums, 'cartids' => $cartids]);
            exit;
        }
    }
    // 列表
    public function index()
    {
        $busid = $this->request->param('busid', 0, 'trim');
        $cartids = $this->request->param('cartids', 0, 'trim');
        //根据手机号码查询用户是否存在
        $business = $this->BusinessModel->find($busid);
        if (!$business) {
            $this->error('该手机号用户不存在');
            exit;
        }
        $where = [];

        $where['busid'] = $busid;
        if ($cartids != 0) {
            // 批量查询
            $where['cart.id'] = ['in', $cartids];
        }
        $data = $this->CartModel->with('product')->where($where)->select();
        $this->success('返回购物车数据', '', $data);
    }
    // 更新
    public function edit()
    {
        $cartid = $this->request->param('cartid', 0, 'trim');
        $nums = $this->request->param('nums', '', 'trim');

        $cart = $this->CartModel->with('product')->find($cartid);
        if (!$cart) {
            $this->error('购物车商品不存在');
            exit;
        }
        $stock = bcsub($cart['product']['stock'], $nums); //减

        if ($stock < 0) {
            $this->error($cart['product']['name'] . '库存不足');
            exit;
        }
        $total = bcmul($cart['price'], $nums, 2);

        $data = [
            'id' => $cartid,
            'nums' => $nums,
            'total' => $total
        ];

        $result = $this->CartModel->isUpdate()->save($data);
        if ($result === FALSE) {
            $this->error($this->CartModel->getError());
            exit;
        } else {
            $this->success('添加购物车成功');
            exit;
        }
    }
    // 删除
    public function del()
    {
        if ($this->request->isPOST()) {
            $cartid = $this->request->param('cartid', 0, 'trim');
            if (!$cartid) {
                $this->error('没有这个商品');
                exit;
            }
            $result = $this->CartModel->destroy($cartid);
            if ($result) {
                $this->success('删除购物车数据成功');
                exit;
            } else {
                $this->error('删除购物车数据失败');
                exit;
            }
        }
    }
}
