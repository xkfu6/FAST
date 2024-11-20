<?php

namespace app\admin\controller\depot;

use app\common\controller\Backend;

/**
 * 退货管理
 *
 * @icon fa fa-circle-o
 */
class Back extends Backend
{

    /**
     * Back模型对象
     * @var \app\admin\model\depot\Back
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Depot.Back.Back');
        $this->BackProductModel = model('Depot.Back.Product');
        $this->OrderModel = model('Order.Order');
        $this->ExpressModel = model('Express');
        $this->StorageModel = model('Depot.Storage.Storage');
        $this->StorageProductModel = model('Depot.Storage.Product');
        $this->view->assign("statusList", $this->model->statuslist());
        $this->BusinessModel= model('Business.Business');
    }

    // 添加退货单
    public function add()
    {

        if ($this->request->isPost()) {
            $params = $this->request->param();

            $orderid = $this->request->param('orderid', 0, 'trim');
            $addrid = $this->request->param('addrid', 0, 'trim');
            //判断退货订单是否存在
            $order = model('Order.Order')->find($orderid);
            if (!$order) {
                $this->error('退货的订单不存在');
                exit;
            }
            //判断选择的收货地址是否存在
            $busid = isset($order['busid']) ? trim($order['busid']) : 0;
            $address = model('Business.Address')->where(['busid' => $busid, 'id' => $addrid])->find();

            if (!$address) {
                $this->error('退货的订单地址不存在');
                exit;
            }
            //判断订单商品是否存在
            $OrderProduct = model('Order.Product')->where(['orderid' => $order['id']])->select();

            if (!$OrderProduct) {
                $this->error(__('订单商品不存在'));
                exit;
            }
            // 开启事务
            $this->model->startTrans();
            $this->BackProductModel->startTrans();
            $this->OrderModel->startTrans();

            // 封装退货单数据
            $BackData = [
                'code' => build_code('BK'),
                'busid' => $order['busid'],
                'ordercode' => $order['code'],
                'remark' => $params['remark'],
                'expressid' => $params['expressid'],
                'expresscode' => $params['expresscode'],
                'amount' => $order['amount'],
                'status' => '0',
                'adminid' => $this->auth->id,
                'contact' => $address['consignee'],
                'phone' => $address['mobile'],
                'address' => $address['address'],
                'province' => $address['province'],
                'city' => $address['city'],
                'district' => $address['district'],
            ];
            $BackStatus = $this->model->validate('common/Depot/Back/Back')->save($BackData);

            if ($BackStatus === FALSE) {
                $this->error(__($this->model->getError()));
                exit;
            }

            // 封装退货商品
            $BackProductData = [];

            foreach ($OrderProduct as $item) {
                $BackProductData[] = [
                    'backid' => $this->model->id,
                    'proid' => $item['proid'],
                    'nums' => $item['pronum'],
                    'price' => $item['price'],
                    'total' => $item['total']
                ];
            }

            $BackProductStatus = $this->BackProductModel->validate('common/Depot/Back/Product')->saveAll($BackProductData);

            if ($BackProductStatus === FALSE) {
                $this->model->rollback();
                $this->error($this->BackProductModel->getError());
                exit;
            }
            $Orderdata = [
                'id' => $orderid,
                'status' => -3,
            ];
            $OrderStatus = $this->OrderModel->isUpdate(true)->save($Orderdata);

            if ($BackProductStatus === FALSE) {
                $this->model->rollback();
                $this->BackProductModel->rollback();
                $this->error($this->BackProductModel->getError());
                exit;
            }
            if ($BackProductStatus === FALSE || $BackStatus === FALSE) {
                $this->OrderModel->rollback();
                $this->BackProductModel->rollback();
                $this->model->rollback();
                $this->error("添加失败");
                exit;
            } else {
                $this->model->commit();
                $this->BackProductModel->commit();
                $this->OrderModel->commit();
                $this->success();
                exit;
            }
        }
        $this->assign('express', build_select('expressid', $this->ExpressModel->column('id,name'), '', ['class' => 'form-control selectpicker']));
        return $this->view->fetch();
    }
    // 退货单详情
    public function info($ids = NULL)
    {
        $back = $this->model->find($ids);
        $BackProductList = $this->BackProductModel->with(['products'])->where(['backid' => $ids])->select();
        $this->assign('row', $back);
        $this->assign('BackProductList', $BackProductList);
        return $this->view->fetch();
    }

    // 退货单修改
    public function edit($ids = null)
    {
        $back = $this->model->find($ids);
        if (!$back) {
            $this->error(__('No Results were found'));
            exit;
        }

        $product = $this->BackProductModel->with(['products'])->where(['backid' => $back['id']])->select();

        if (!$product) {
            $this->error('无退货商品');
            exit;
        }

        // 修改提交
        if ($this->request->isPOSt()) {
            $params = $this->request->param();
            $params['id'] = $ids;
            $result = $this->model->isUpdate(true)->save($params);

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('修改成功');
                exit;
            }
        }


        $this->assign('back', $back);
        $this->assign('product', $product);
        $this->assign('express', build_select('expressid', $this->ExpressModel->column('id,name'), $back['expressid'], ['class' => 'form-control selectpicker']));
        return $this->view->fetch();
    }

    // 同意审核
    public function agree($ids = null)
    {
        $row = $this->model->select($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }
        $params = [
            'id' => $ids,
            'status' => "1",
            'reviewerid' => $this->auth->id,
        ];
        $result = $this->model->isUpdate(true)->save($params);
        if ($result) {
            $this->success('审核成功');
            exit;
        } else {
            $this->error('审核失败');
            exit;
        }
    }
    // 拒绝审核
    public function reject($ids = null)
    {
        $row = $this->model->select($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }
        $params = [
            'id' => $ids,
            'status' => "-1",
            'reviewerid' => $this->auth->id,
        ];
        $result = $this->model->isUpdate(true)->save($params);
        if ($result) {
            $this->success('拒绝成功');
            exit;
        } else {
            $this->error('拒绝失败');
            exit;
        }
    }
    // 撤销审核
    public function revoke($ids = null)
    {
        $row = $this->model->select($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }
        $params = [
            'id' => $ids,
            'status' => "0",
            'reviewerid' => NUll
        ];
        $result = $this->model->isUpdate(true)->save($params);
        if ($result) {
            $this->success('撤销成功');
            exit;
        } else {
            $this->error('撤销失败');
            exit;
        }
    }
    // 确认入库
    public function receipt($ids = null)
    {
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }
        $BackProductList = $this->BackProductModel->where(['backid' => $ids])->select();

        if (!$BackProductList) {
            $this->error(__('退货商品不存在'));
            exit;
        }
        // 查询订单
        $order = $this->OrderModel->where(['code' => $row['ordercode']])->find();

        if (!$order) {
            $this->error('商品订单不存在');
            exit;
        }

        $business = $this->BusinessModel->find($order['busid']);

        if (!$business) {
            $this->error('用户不存在');
            exit;
        }

        // 开启事务
        $this->BusinessModel->startTrans();
        $this->model->startTrans();
        $this->OrderModel->startTrans();
        $this->StorageModel->startTrans();
        $this->StorageProductModel->startTrans();
        // 更新用户的余额
        $BusinessData = [
            'id' => $business['id'],
            'money' => bcadd($order['amount'], $business['money'], 2)
        ];

        $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

        if ($BusinessStatus === false) {
            $this->error('更新用户余额失败');
            exit;
        }

        // 更新订单的状态
        $OrderData = [
            'id' => $ids,
            'status' => -4,
        ];

        $OrderStatus = $this->OrderModel->isUpdate(true)->save($OrderData);

        if ($OrderStatus === false) {
            $this->BusinessModel->rollback();
            $this->error('更新订单状态失败');
        }
        // 生成入库记录
        $StorageData = [
            'code' => build_code('ST'),
            'type' => 2,
            'amount' => $row['amount'],
            'status' => 0
        ];
        $StorageStatus = $this->StorageModel->save($StorageData);

        if ($StorageStatus === FALSE) {
            $this->BusinessModel->rollback();
            $this->OrderModel->rollback();
            $this->error($this->StorageModel->getError());
            exit;
        }
        // 存放封装好的商品数据
        $ProductData = [];

        foreach ($BackProductList as $item) {
            $ProductData[] = [
                'storageid' => $this->StorageModel->id,
                'proid' => $item['proid'],
                'nums' => $item['nums'],
                'price' => $item['price'],
                'total' => $item['total'],
            ];
        }
        // 验证数据
        $ProductStatus = $this->StorageProductModel->validate('common/Depot/Storage/Product')->saveAll($ProductData);

        if ($ProductStatus === FALSE) {
            $this->StorageModel->rollback();
            $this->OrderModel->rollback();
            $this->BusinessModel->rollback();
            $this->error($this->StorageProductModel->getError());
            exit;
        }

        // 更新退货单状态与入库外键
        $BackData = [
            'id' => $row['id'],
            'status' => '2',
            'stromanid' => $this->auth->id,
            'storageid' => $this->StorageModel->id
        ];
        $BackStatus = $this->model->isUpdate(true)->save($BackData);

        if ($BackStatus === FALSE) {
            $this->StorageProductModel->rollback();
            $this->StorageModel->rollback();
            $this->OrderModel->rollback();
            $this->BusinessModel->rollback();
            $this->error('修改收货状态失败');
            exit;
        }

        if ($BusinessStatus === false || $OrderStatus === false || $StorageStatus === false || $ProductStatus === false || $BackStatus === false) {
            $this->model->rollback();
            $this->StorageProductModel->rollback();
            $this->StorageModel->rollback();
            $this->OrderModel->rollback();
            $this->BusinessModel->rollback();
            $this->error('确认收货入库失败');
            exit;
        } else {
            $this->BusinessModel->commit();
            $this->OrderModel->commit();
            $this->StorageModel->commit();
            $this->StorageProductModel->commit();
            $this->model->commit();
            $this->success('确认收货入库成功');
            exit;
        }
    }
}
