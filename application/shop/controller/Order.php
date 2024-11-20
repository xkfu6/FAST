<?php

namespace app\shop\controller;

use think\Controller;
use app\common\library\Email;

class Order extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->OrderModel = model('Order.Order');
        $this->OrderProductModel = model('Order.Product');
        $this->RecordModel = model('Business.Record');
        $this->ProductModel = model('Product.Product');
        $this->CartModel = model('Product.Cart');
        $this->AddressModel = model('Business.Address');
        // $this->ExpressqueryModel = model('Expressquery');
        $this->BackModel = model('Depot.Back.Back');
        $this->BackProductModel = model('Depot.Back.Product');
        $this->CouponReceiveModel = model('Coupon.Receive');
        $this->CommissionModel = model('Business.Commission');
        $this->PayModel = model('common/Pay/Pay');
    }
    // 生成订单
    public function add()
    {
        $busid = $this->request->param('busid', 0, 'trim');
        $addrid = $this->request->param('addrid', 0, 'trim');
        $cartids = $this->request->param('cartids', 0, 'trim');
        $remark = $this->request->param('remark', '', 'trim');
        $couponid = $this->request->param('couponid', 0, 'trim');
        $pay = $this->request->param('pay', 'money', 'trim');

        $business = $this->BusinessModel->find($busid);

        if (!$business) {
            $this->error('该用户不存在');
            exit;
        }

        $address = $this->AddressModel->find($addrid);

        if (!$address) {
            $this->error('收件地址不存在');
            exit;
        }

        $cart = $this->CartModel
            ->with(['product'])
            ->where('cart.id', 'in', $cartids)
            ->select();

        if (!$cart) {
            $this->error('购物车商品不存在');
            exit;
        }

        //计算订单总价
        $total = array_column($cart, "total");
        $amount = array_sum($total);

        //判断是否有使用优惠券
        $coupon = $this->CouponReceiveModel->with(['coupon'])->find($couponid);

        //优惠券在可用的情况下去优惠
        if ($coupon && $coupon['status'] == '1') {
            $amount = bcmul($amount, $coupon['coupon']['rate'], 2);
        }

        //余额支付
        if ($pay == "money") {
            // 判断用户余额
            $money = bcsub($business['money'], $amount, 2);

            if ($money < 0) {
                $this->error('余额不足，请去充值');
                exit;
            }

            //开启事务
            $this->BusinessModel->startTrans(); //更新余额
            $this->OrderModel->startTrans(); //插入订单
            $this->OrderProductModel->startTrans(); //插入订单商品
            $this->RecordModel->startTrans(); //插入消费记录
            $this->ProductModel->startTrans(); //更新库存
            $this->CartModel->startTrans(); //删除购物车记录
            $this->CouponReceiveModel->startTrans(); //优惠券更新使用状态
            $this->CommissionModel->startTrans();

            // 生成订单号
            $code = build_code('SP'); //shop

            $OrderData = [
                'code' => $code,
                'busid' => $busid,
                'businessaddrid' => $addrid,
                'amount' => $amount,
                'remark' => $remark,
                'status' => '1', // 已支付
                'pay' => 'money', //余额支付
            ];

            //如果有使用优惠券就带上优惠券ID
            if ($coupon && $coupon['status'] == '1') {
                $OrderData['couponid'] = $couponid;
            }

            //订单表新增    校验器
            $OrderStatus = $this->OrderModel->save($OrderData);

            if ($OrderStatus === FALSE) {
                $this->error($this->OrderModel->getError());
                exit;
            }

            // 订单产品表的数据
            $OrderproductData = [];

            // 产品表数据
            $ProductData = [];

            $stock = [];

            //判断库存是否充足
            foreach ($cart as $item) {
                if (isset($stock[$item['proid']])) {
                    $stock[$item['proid']] = bcadd($stock[$item['proid']], $item['nums']);
                } else {
                    $stock[$item['proid']] = $item['nums'];
                }

                //组装订单商品数据
                $OrderproductData[] = [
                    'orderid' => $this->OrderModel->id, //获取到上一步自增的ID
                    'busid' => $busid,
                    'proid' => $item['proid'],
                    'pronum' => $item['nums'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                    'attrs' => $item['attrs']
                ];
            }

            //判断商品的库存是否充足
            foreach ($stock as $key => $item) {
                $pro = $this->ProductModel->field('id,stock,name')->where(['id' => $key])->find();

                $UpdateStock = bcsub($pro['stock'], $item);

                //库存不足
                if ($UpdateStock < 0) {
                    $this->error($pro['name'] . '库存不足');
                    exit;
                }

                $ProductData[] = [
                    'id' => $pro['id'],
                    'stock' => $UpdateStock
                ];
            }

            // 订单产品表的新增  
            $OrderProductStatus = $this->OrderProductModel->saveAll($OrderproductData);

            if ($OrderProductStatus === FALSE) {
                $this->OrderModel->rollback();
                $this->error($this->OrderProductModel->getError());
                exit;
            }

            // 用户余额更新
            $BusinessStatus = $this->BusinessModel->where('id', $busid)->update(['money' => $money]);

            if ($BusinessStatus === FALSE) {
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->BusinessModel->getError());
                exit;
            }

            //消费记录表
            $RecordData = [
                'total' => "-$amount",
                'busid' => $busid,
                'content' => "商城订单号：$code ---消费的金额：$amount 元",
            ];

            //插入语句
            $RecordStatus = $this->RecordModel->validate('common/Business/Record')->save($RecordData);

            if ($RecordStatus === FALSE) {
                $this->BusinessModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->RecordModel->getError());
                exit;
            }

            //判断是否有使用优惠券
            if ($coupon && $coupon['status'] == '1') {
                $CouponData = [
                    'id' => $coupon['id'],
                    'status' => 0
                ];

                $CouponStatus = $this->CouponReceiveModel->isUpdate(true)->save($CouponData);

                if ($CouponStatus === FALSE) {
                    $this->RecordModel->rollback();
                    $this->BusinessModel->rollback();
                    $this->OrderProductModel->rollback();
                    $this->OrderModel->rollback();
                    $this->error($this->CouponReceiveModel->getError());
                    exit;
                }
            }

            //产品表库存更新
            $ProductStatus = $this->ProductModel->isUpdate(true)->saveAll($ProductData);

            if ($ProductStatus === FALSE) {
                $this->CouponReceiveModel->rollback();
                $this->RecordModel->rollback();
                $this->BusinessModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->ProductModel->getError());
                exit;
            }

            // 删除购物车
            $CartStatus = $this->CartModel->destroy($cartids);

            if ($CartStatus === FALSE) {
                $this->ProductModel->rollback();
                $this->CouponReceiveModel->rollback();
                $this->RecordModel->rollback();
                $this->BusinessModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->CartModel->getError());
                exit;
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
                    'orderid' => $this->OrderModel->id, //获取插入最后一条的自增ID
                    'busid' => $busid,
                    'parentid' => $parentid,
                    'type' => 'product', //买商品的佣金
                    'stauts' => '0', //未提现
                    'amount' => $conamount, //佣金
                ];
                $CommissionStatus = $this->CommissionModel->save($CommissionData);
                if ($CommissionStatus === FALSE) {
                    $this->CartModel->rollback();
                    $this->ProductModel->rollback();
                    $this->CouponReceiveModel->rollback();
                    $this->RecordModel->rollback();
                    $this->BusinessModel->rollback();
                    $this->OrderProductModel->rollback();
                    $this->OrderModel->rollback();
                    return json(['code' => 0, 'msg' => '推荐信息存储失败', 'data' => null]);
                }
            }




            if ($OrderStatus === FALSE || $OrderProductStatus === FALSE || $BusinessStatus === FALSE || $RecordStatus === FALSE || $ProductStatus === FALSE || $CartStatus === FALSE || $CommissionStatus === FALSE) {
                $this->CommissionModel->rollback();
                $this->CartModel->rollback();
                $this->ProductModel->rollback();
                $this->CouponReceiveModel->rollback();
                $this->RecordModel->rollback();
                $this->BusinessModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error('下单失败');
                exit;
            } else {
                $this->OrderModel->commit();
                $this->OrderProductModel->commit();
                $this->BusinessModel->commit();
                $this->RecordModel->commit();
                $this->CouponReceiveModel->commit();
                $this->ProductModel->commit();
                $this->CartModel->commit();
                $this->CommissionModel->commit();
                $this->success('下单成功', "/order/info", ['orderid' => $this->OrderModel->id]);
                exit;
            }
        } else {
            //微信 和 支付宝

            $this->OrderModel->startTrans(); //插入订单
            $this->OrderProductModel->startTrans(); //插入订单商品
            $this->CouponReceiveModel->startTrans(); //优惠券更新使用状态
            $this->CartModel->startTrans(); //删除购物车记录


            // 生成订单号
            $code = build_code('SP'); //shop

            $OrderData = [
                'code' => $code,
                'busid' => $busid,
                'businessaddrid' => $addrid,
                'amount' => $amount,
                'remark' => $remark,
                'status' => '0', // 未支付
                'pay' => $pay, //微信或者是支付宝
            ];

            //如果有使用优惠券就带上优惠券ID
            if ($coupon && $coupon['status'] == '1') {
                $OrderData['couponid'] = $couponid;
            }

            //订单表新增    校验器
            $OrderStatus = $this->OrderModel->save($OrderData);

            if ($OrderStatus === FALSE) {
                $this->error($this->OrderModel->getError());
                exit;
            }

            // 订单产品表的数据
            $OrderproductData = [];

            // 产品表数据
            $ProductData = [];

            $stock = [];

            //判断库存是否充足
            foreach ($cart as $item) {
                if (isset($stock[$item['proid']])) {
                    $stock[$item['proid']] = bcadd($stock[$item['proid']], $item['nums']);
                } else {
                    $stock[$item['proid']] = $item['nums'];
                }

                //组装订单商品数据
                $OrderproductData[] = [
                    'orderid' => $this->OrderModel->id, //获取到上一步自增的ID
                    'busid' => $busid,
                    'proid' => $item['proid'],
                    'pronum' => $item['nums'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                    'attrs' => $item['attrs']
                ];
            }

            //判断商品的库存是否充足
            foreach ($stock as $key => $item) {
                $pro = $this->ProductModel->field('id,stock,name')->where(['id' => $key])->find();

                $UpdateStock = bcsub($pro['stock'], $item);

                //库存不足
                if ($UpdateStock < 0) {
                    $this->error($pro['name'] . '库存不足');
                    exit;
                }

                $ProductData[] = [
                    'id' => $pro['id'],
                    'stock' => $UpdateStock
                ];
            }

            // 订单产品表的新增  
            $OrderProductStatus = $this->OrderProductModel->saveAll($OrderproductData);

            if ($OrderProductStatus === FALSE) {
                $this->OrderModel->rollback();
                $this->error($this->OrderProductModel->getError());
                exit;
            }

            //判断是否有使用优惠券
            if ($coupon && $coupon['status'] == '1') {
                $CouponData = [
                    'id' => $coupon['id'],
                    'status' => 0
                ];

                $CouponStatus = $this->CouponReceiveModel->isUpdate(true)->save($CouponData);

                if ($CouponStatus === FALSE) {
                    $this->OrderProductModel->rollback();
                    $this->OrderModel->rollback();
                    $this->error($this->CouponReceiveModel->getError());
                    exit;
                }
            }

            // 删除购物车
            $CartStatus = $this->CartModel->destroy($cartids);


            if ($CartStatus === FALSE) {
                $this->CouponReceiveModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error($this->CartModel->getError());
                exit;
            }

            if ($OrderStatus === FALSE || $OrderProductStatus === FALSE || $CartStatus === FALSE) {
                $this->CartModel->rollback();
                $this->CouponReceiveModel->rollback();
                $this->OrderProductModel->rollback();
                $this->OrderModel->rollback();
                $this->error('生成订单失败');
                exit;
            }

            // 携带一个自定义的参数过去
            $third = [
                'busid' => $busid,
                'orderid' => $this->OrderModel->id,
            ];

            //组装参数
            $data = [
                'name' => '商品订单', //标题
                'third' => $third, //传递的第三方的参数
                'total' => $amount, //订单原价充值的价格
                'type' => $pay, //支付方式
                'cashier' => 0, //不需要收银台界面
                'jump' => "/order/info?orderid=" . $this->OrderModel->id, //订单支付完成后跳转的界面
                'notice' => '/shop/pay/create',  //异步回调地址
            ];

            //调用模型中的支付方法
            $result = $this->PayModel->payment($data);

            if ($result['code']) {
                $this->OrderModel->commit();
                $this->OrderProductModel->commit();
                $this->CouponReceiveModel->commit();
                $this->CartModel->commit();
                $this->success('创建订单成功', '/cart/cashier', $result['data']);
                exit;
            }
        }
    }
    // 订单列表
    public function index()
    {
        $busid = $this->request->param('busid', 0, 'trim');
        $status = $this->request->param('status', '', 'trim');
        $page = $this->request->param('page', 1, 'trim');
        $limit = 8;
        //偏移量
        $offset = ($page - 1) * $limit;

        $business = $this->BusinessModel->find($busid);

        if (!$business) {
            $this->error('该用户不存在');
            exit;
        }

        $where['busid'] = $busid;

        // // 订单状态
        if (!empty($status)) {
            if ($status > 0) {
                $where['status'] = $status;
            } else {
                $where['status'] = ['lt', '0'];
            }
        }

        $list = $this->OrderModel
            ->where($where)
            ->order('createtime desc')
            ->limit($offset, $limit)
            ->select();

        if ($list) {
            //给订单添加一个自定义的下标 下标存放 订单商品信息(一个商品)
            foreach ($list as &$item) {
                $item['info'] = $this->OrderProductModel->with(['products'])->where(['orderid' => $item['id']])->find();
            }

            $this->success('返回订单列表', '', $list);
            exit;
        } else {
            $this->error('暂无更多数据');
            exit;
        }
    }

    // 订单详情
    public function info()
    {
        $busid = $this->request->param('busid', 0, 'trim');
        $orderid = $this->request->param('orderid', 0, 'trim');

        $business = $this->BusinessModel->find($busid);

        if (!$business) {
            $this->error('该用户不存在');
            exit;
        }

        $order = $this->OrderModel->find($orderid);

        if (!$order) {
            $this->error('订单不存在');
            exit;
        }

        $address = $this->AddressModel->where(['id' => $order['businessaddrid']])->find();

        if (!$address) {
            $this->error('收货地址不存在');
            exit;
        }

        //判断是否有使用优惠券
        $couponid = isset($order['couponid']) ? $order['couponid'] : 0;
        $coupon = $this->CouponReceiveModel->with(['coupon'])->find($couponid);

        if ($coupon) {
            $rate = isset($coupon['coupon']['rate']) ? $coupon['coupon']['rate'] : 1;
            $coupon_name = isset($coupon['coupon']['title']) ? $coupon['coupon']['title'] : '优惠活动';

            //原价总的价格
            $total = $this->OrderProductModel->where(['orderid' => $orderid])->sum('total');

            //订单优惠后的价格
            $amount = $order['amount'];

            //优惠的金额数
            $coupon_amount = bcsub($total, $amount);

            if ($coupon_amount > 0) {
                $order['coupon_amount'] = "-￥$coupon_amount ($coupon_name)";
            } else {
                $order['coupon_amount'] = '暂无优惠金额';
            }
        } else {
            $order['coupon_amount'] = '暂无优惠金额';
        }

        $product = $this->OrderProductModel->with(['products'])->where(['orderid' => $orderid])->select();

        $data = [
            'order' => $order,
            'product' => $product,
            'address' => $address,
        ];

        $this->success('订单详情', '', $data);
        exit;
    }

    // 物流信息
    public function express()
    {
        $busid = $this->request->param('busid', 0, 'trim');
        $orderid = $this->request->param('orderid', 0, 'trim');

        $business = $this->BusinessModel->find($busid);

        if (!$business) {
            $this->error('该手机号用户不存在');
            exit;
        }
        $expresscode = '';
        // 订单物流
        if ($orderid > 0) {
            $orderinfo = $this->OrderModel->with('address')->find($orderid);
            if (!$orderinfo) {
                $this->error('订单不存在');
                exit;
            }

            $expresscode = $orderinfo['code'];
        }

        if (empty($expresscode)) {
            $this->error('暂无物流信息');
            exit;
        }
        // 物流接口地址
        $url = "http://wdexpress.market.alicloudapi.com/gxali";
        $params = [
            'n' => $expresscode,
        ];
        $paramsString = http_build_query($params);
        $appcode = "f067fec0ccc24082a8c67ebfdaaa85db"; //开通服务后 买家中心-查看AppCode
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        if (cache($expresscode)) {
            $this->success('', '', cache($expresscode));
        } else {

            // 调取物流接口
            $expressinfo = HttpRequest($url, $paramsString, 0, $headers);
            //将json转换为php数组
            $result = json_decode($expressinfo, true);

            if ($result['Success']) {
                // 设置路由缓存
                cache($expresscode, $result['Traces']);
                $this->success('查询快递信息成功', '', $result['Traces']);
            } else {
                $this->error($result['Reason']);
            }
        }
    }

    // 确认收货
    public function receiving()
    {
        if ($this->request->isPOST()) {
            $orderid = $this->request->param('orderid', 0, 'trim');
            if (!$orderid) {
                $this->error('订单不存在');
                exit;
            }
            $reuslt = $this->OrderModel->where(['id' => $orderid])->Update(['status' => '3']);
            if ($reuslt) {
                $this->success('确认收货成功');
                exit;
            } else {
                $this->error('确认收货失败');
                exit;
            }
        }
    }

    // 评论
    public function comment()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'trim');
            $comment = $this->request->param('comment', '', 'trim');
            $rate = $this->request->param('rate', 5, 'trim');
            $orderid = $this->OrderProductModel->where(['id' => $id])->value('orderid');

            if (!$orderid) {
                $this->error('订单信息不存在');
                exit;
            }
            // 组装数据（除了图片图片要处理才组装）
            $params = [
                'id' => $id,
                'comment' => $comment,
                'rate' => $rate,
                'comtime' => time()
            ];
            // $_FILES是一个包含所有文件上传的数组
            // isset判断文件是否存在
            if (isset($_FILES['thumbs'])) {
                $success = build_uploads('thumbs');
                if ($success['code'] == 0) {
                    $this->error($success['msg']);
                    exit;
                }
                $params['thumbs'] = implode(',', $success['data']);
            }
            //开始事务
            $this->OrderModel->startTrans();
            $this->OrderProductModel->startTrans();
            //更新订单商品表(评论内容)

            $ProductStatus = $this->OrderProductModel->isUpdate(true)->save($params);

            if ($ProductStatus === FALSE) {
                $this->error($this->OrderProductModel->getError());
                exit;
            }

            //更新订单表
            $OrderStatus = $this->OrderModel->where(['id' => $orderid])->update(['status' => '4']);

            if ($OrderStatus === FALSE) {
                $this->OrderProductModel->rollback();
                $this->error('订单状态更新失败');
                exit;
            }
            if ($ProductStatus === FALSE || $OrderStatus === FALSE) {
                $this->OrderModel->rollback();
                $this->OrderProductModel->rollback();
                $this->error('评论失败');
                exit;
            } else {
                $this->OrderProductModel->commit();
                $this->OrderModel->commit();
                $this->success('评论成功');
                exit;
            }
        }
    }
    // 仅退款
    public function apply()
    {
        $busid = $this->request->param('busid', 0, 'trim');
        $orderid = $this->request->param('orderid', 0, 'trim');
        $remark = $this->request->param('remark', '', 'trim');
        $business = $this->BusinessModel->find($busid);

        if (!$business) {
            $this->error('该手机号用户不存在');
            exit;
        }

        $order = $this->OrderModel->find($orderid);

        if (!$order) {
            $this->error('订单不存在');
            exit;
        }
        $OrderData = [
            'id' => $orderid,
            'refundreason' => $remark, //退款原因
        ];
        //已支付，仅退款
        if ($order['status'] == "1") {
            $OrderData['status'] = '-1';
        } else if ($order['status'] == "3") {
            //已发货，申请退货退款
            $OrderData['status'] = '-2';
            
        }
        $result = $this->OrderModel->isUpdate(true)->save($OrderData);
        if ($result === FALSE) {
            $this->error('申请失败');
            exit;
        } else {
            $this->success('申请成功，待商家审核');
            exit;
        }
    }
}
