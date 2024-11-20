<?php

namespace app\admin\controller\product;

use app\common\controller\Backend;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{
    //是否是关联查询
    protected $relationSearch = true;
    protected $model = null;

    public function __construct()
    {
        parent::__construct();
        $this->model = model('Order.Order');
        $this->ProductModel = model('Order.Product');
        $this->BusinessModel = model('Business.Business');
        $this->ExpressqueryModel = model('Express');
        $this->BackModel = model('Depot.Back.Back'); //退货表
        $this->BackProductModel = model('Depot.Back.product'); //退货单商品表表
        $this->AddressModel = model('Business.Address');
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    // 商品订单页面
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->with(['express', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count();

            $list = $this->model
                ->with(['express', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }
    // 订单详情
    public function info()
    {
        $ids = $this->request->param('ids', 0, 'trim');
        $row = $this->model->with(['business'])->where(['order.id' => $ids])->find();
        $OrderProductData = $this->ProductModel->with(['products'])->where(['orderid' => $ids])->find();
        $this->assign('row', $row);
        $this->assign('OrderProductData', $OrderProductData);
        return $this->view->fetch();
    }
    // 发货方法
    public function deliver($ids = null)
    {
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error('订单不存在');
            exit;
        }
        if ($this->request->isPost()) {
            $params = $this->request->param('row/a');
            //封装数据
            $data = [
                'id' => $ids,
                'expressid' => $params['expressid'],
                'expresscode' => $params['expresscode'],
                'status' => 2,
                'shipmanid' => $this->auth->id
            ];

            // 定义验证器
            $validate = [
                [
                    'expressid' => 'require',
                    'expresscode' => 'require|unique:order'
                ],
                [
                    'expressid.require' => '配送物流未知',
                    'expresscode.unique' => '配送物流单号已存在，请重新输入',
                    'expresscode.require' => '请输入配送物流单号'
                ]
            ];
            $result = $this->model->validate(...$validate)->isUpdate(true)->save($data);
            if ($result === false) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success('发货成功');
                exit;
            }
        }

        $this->view->assign('row', $row);
        // 创建下拉
        $this->view->assign('express', build_select('row[expressid]', $this->ExpressqueryModel->column('name', 'id'), '', ['class' => 'form-control selectpicker']));

        return $this->fetch();
    }


    // 退款退货
    public function refund($ids = null)
    {
        $ids = $ids ?: $this->request->param('ids', 0, 'trim');

        $row = $this->model->with(['express', 'business'])->find($ids);

        if (!$row) {
            $this->error('订单不存在');
            exit;
        }

        // 提交动作
        if ($this->request->isPost()) {
            $params = $this->request->param('row/a');
            $business = $this->BusinessModel->find($row['busid']);
            if ($params['refund'] == 0 && empty($params['examinereason'])) {
                $this->error('请填写不同意退货的原因');
                exit;
            }

            // 同意仅退款
            if ($params['refund'] === '1' && $row['status'] === '-1') {

                if (!$business) {
                    $this->error('用户不存在');
                    exit;
                }

                // 开启事务
                $this->BusinessModel->startTrans();
                $this->model->startTrans();

                // 更新用户余额
                $BusinessData = [
                    'id' => $business['id'],
                    'money' => bcadd($row['amount'], $business['money'], 2)
                ];

                $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($BusinessData);

                if ($BusinessStatus === false) {
                    $this->error('更新用户余额失败');
                }

                // 更新订单的状态
                $OrderData = [
                    'id' => $ids,
                    'status' => -4,
                ];

                $OrderStatus = $this->model->isUpdate(true)->save($OrderData);

                if ($OrderStatus === false) {
                    $this->BusinessModel->rollback();
                    $this->error('更新订单状态失败');
                    exit;
                }

                if ($BusinessStatus === false || $OrderStatus === false) {
                    $this->model->rollback();
                    $this->BusinessModel->rollback();
                    $this->error('同意退款失败');
                    exit;
                } else {
                    $this->BusinessModel->commit();
                    $this->model->commit();
                    $this->success('同意退款成功');
                    exit;
                }
            }

            // 不同意退货
            if ($params['refund'] === '0') {
                // 封装数据
                $data = [
                    'id' => $ids,
                    'status' => -5,
                    'examinereason' => $params['examinereason']
                ];

                $result = $this->model->isUpdate(true)->save($data);

                if ($result === false) {
                    $this->error('退货审核失败');
                    exit;
                } else {
                    $this->success();
                    exit;
                }
            }

            // 同意退货退款
            if ($params['refund'] === '1' && $row['status'] === '-2') {
                // 封装数据
                $data = [
                    'id' => $ids,
                    'status' => -6,
                ];
                $result = $this->model->isUpdate(true)->save($data);

                if ($result === false) {
                    $this->error('退货失败');
                    exit;
                } else {
                    $this->success('退货成功');
                    exit;
                }
            }
        }


        $this->view->assign('row', $row);
        // 创建下拉
        $this->view->assign('express', build_select('row[expressid]', $this->ExpressqueryModel->column('name', 'id'), '', ['class' => 'form-control selectpicker']));
        return $this->fetch();
    }
}
