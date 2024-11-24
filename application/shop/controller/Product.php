<?php

namespace app\shop\controller;

use think\Controller;

class Product extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->ProductModel = model('Product.Product');
        $this->TypeModel = model('Product.Type');
        $this->CartModel = model('Product.Cart');
        $this->BusinessModel = model('Business.Business');
        $this->OrderProductModel = model('Order.Product');
        $this->RelationModel = model('Product.Relation');
        $this->PropModel = model('Product.Prop');
        $this->CollectionModel = model('Business.Collection');
    }

    // 请求分类列表
    public function type()
    {
        if ($this->request->isPost()) {
            $list = $this->TypeModel->select();


            if ($list) {
                $this->success('分类列表', null, $list);
                exit;
            } else {
                $this->error('暂无分类');
                exit;
            }
        }
    }

    //商品数据列表
    public function index()
    {
        if ($this->request->isPost()) {
            $page = $this->request->param('page', 1, 'trim');
            $typeid = $this->request->param('typeid', 0, 'trim');
            $flag = $this->request->param('flag', '0', 'trim');
            $sort = $this->request->param('sort', 'createtime', 'trim');
            $by = $this->request->param('by', 'desc', 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $limit = 8;

            //偏移量
            $offset = ($page - 1) * $limit;

            //查询分类名称
            $TypeName = $this->TypeModel->where(['id' => $typeid])->value('name');
            $TypeName = empty($TypeName) ? '全部分类' : $TypeName;

            $where = [];

            //关键词不为空
            if (!empty($keywords)) {
                $where['name'] = ['like', "%$keywords%"];
            }

            //分类筛选
            if ($typeid) {
                $where['typeid'] = $typeid;
            }

            //标签筛选
            if ($flag != "0") {
                $where['flag'] = $flag;
            }
            //是否下架判断筛选
            $where['status'] = '1';

            $list = $this->ProductModel
                ->where($where)
                ->order($sort, $by)
                ->limit($offset, $limit)
                ->select();

            $data = [
                'TypeName' => $TypeName,
                'list' => $list
            ];

            if ($list) {
                $this->success('返回商品数据', null, $data);
                exit;
            } else {
                $this->error('暂无更多商品数据');
                exit;
            }
        }
    }

    //商品详情
    public function info()
    {
        if ($this->request->isPOST()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $proid = $this->request->param('proid', 0, 'trim');
            $product = $this->ProductModel->with(['type', 'unit'])->find($proid);

            if (!$product) {
                $this->error('商品不存在');
                exit;
            }
            $business = $this->BusinessModel->find($busid);
            if ($business) {
                $where = ['collectid' => $proid, 'busid' => $busid, 'status' => 'product'];
                $product['collection'] = $this->CollectionModel->where($where)->find() ? true : false;
                //要去找出这个人的购物车数量
                $product['cart'] = $this->CartModel->where(['busid' => $busid])->sum('nums');
            } else {
                //没有登录的话，直接就是未收藏
                $product['collection'] = false;

                //如果没有登录 购物车的数量 就是0个
                $product['cart'] = 0;
            }
            $comment = $this->OrderProductModel->with(['business', 'order'])->where(['proid' => $proid, 'rate' => ['<>', '']])->select();
            $sumrate = $this->OrderProductModel->where(['proid' => $proid])->sum('rate');
            $count = $this->OrderProductModel->with(['business', 'order'])->where(['proid' => $proid, 'rate' => ['<>', '']])->count();
            if ($count) {
                $average = round($sumrate / $count, 1);
            } else {
                $average = 0;
            }

            //Sku数据组装
            $properties = $sku = $list = [];
            // //查找商品规格属性
            $propid = $this->RelationModel->where(['proid' => $proid])->group('propid')->column('propid');
            $props = $this->PropModel->where(['id' => ['IN', $propid]])->column('id,title');
            if ($props) {
                foreach ($props as $key => $item) {
                    $properties[$key] = [
                        'k_id' => $key, //属性ID
                        'k' => $item, //规格名称
                        'is_multiple' => false, //是否可多选
                        'v' => []
                    ];
                }
            }
            // //查找商品规格列表
            $relationlist = $this->RelationModel->where(['proid' => $proid])->select();

            //重新组装sku的数据结构
            if ($properties && $relationlist) {
                foreach ($relationlist as $item) {
                    $properties[$item['propid']]['v'][] = [
                        'id' => $item['id'],
                        'name' => $item['value'],
                        'price' => (float)bcmul($item['price'], 100), //前端显示的是分单位
                        'text_status' => 1,
                    ];
                }
            }
            foreach ($properties as $item) {
                $list[] = [
                    's1' => $item['k_id'],
                    'price' => (float)bcmul($product['price'], 100), //默认的商品价格
                    'stock_num' => $product['stock'],
                ];
            }

            //组装sku
            $sku = [
                'tree' => [],
                'price' => $product['price'], //默认按照单独购买的价格来算
                'stock_num' => $product['stock'], //库存
                'list' => $list
            ];
            $data = [
                'product' => $product,
                'comment' => $comment,
                'sku' => $sku,
                'properties' => $properties,
                'average' => $average
            ];
            if ($product) {
                $this->success('返回商品数据', null, $data);
                exit;
            } else {
                $this->error('无商品数据');
                exit;
            }
        }
    }
    // 商品收藏
    public function collection()
    {
        if ($this->request->isAjax()) {
            $collectid = $this->request->param('collectid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $product = $this->ProductModel->find($collectid);
            if (!$product) {
                $this->error('商品不存在');
                exit;
            }
            $business = $this->BusinessModel->find($busid);
            if (!$business) {
                $this->error('用户不存在');
                exit;
            }
            $where = [
                'busid' => $busid,
                'collectid' => $collectid,
                'status' => 'product'
            ];
            $collect = $this->CollectionModel->where($where)->find();
            if ($collect) {
                //已收藏过,取消收藏删除
                $result = $this->CollectionModel->where(['id' => $collect['id']])->delete();

                $action = 'delete';
                $msg = '取消收藏';
            } else {
                //没收藏 插入语句
                $data = [
                    'collectid' => $collectid,
                    'busid' => $busid,
                    'status' => 'product'
                ];

                $result = $this->CollectionModel->validate('common/Business/Collection')->save($data);

                $action = 'add';
                $msg = "收藏";
            }

            if ($result === FALSE) {
                $this->error("{$msg}失败");
                exit;
            } else {
                $this->success("{$msg}成功", null, $action);
                exit;
            }
        }
    }

    // 分类表数据
    public function lode()
    {
        if ($this->request->isPOST()) {
            $id = $this->request->param('id', 0, 'trim');
            if (!$id) {
                $this->error('没有这个分类');
                exit;
            }
            $result = $this->ProductModel->where(['typeid' => $id])->select();
            if ($result) {
                $this->success('查询成功', null, $result);
                exit;
            } else {
                $this->error('没有数据');
            }
        }
    }
    // 分类右边商品
    public function commodity()
    {
        $typenum = $this->request->param('id', 0, 'trim');
        $list = $this->TypeModel->select();
        $typeid = $list[$typenum]['id'];

        $product = $this->ProductModel->where(['typeid' => $typeid, 'status' => '1'])->select();
        $data = [
            $product,
            $typenum
        ];
        if ($product) {
            $this->success('查询成功', null, $data);
            exit;
        } else {
            $this->error('没有更多数据');
            exit;
        }
    }

    // 商品排行
    public function rank()
    {
        $pare = $this->request->param('page', 1, 'trim');
        $limit = 8; //每页显示的个数
        $offset = ($pare - 1) * $limit; //分页起始位置
        $result = $this->ProductModel->limit($offset, $limit)->order('id desc')->where(['status' => '1'])->select();
        if ($result) {
            $this->success('查询成功', null, $result);
            exit;
        } else {
            $this->error('查询失败');
            exit;
        }
    }
}
