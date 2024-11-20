<?php

namespace app\admin\controller\depot\async;

use app\common\controller\Backend;

/**
 * 退货单异步管理
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
    protected $relationSearch = true;
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Order.Order');
    }
    public function order()
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {

            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $DataWhere = ['status' => '-6'];
            $total = $this->model
                ->with(['express', 'business'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count();

            $list = $this->model
                ->with(['express', 'business'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    //返回退货单
    public function back($ids = null)
    {
        $order = $this->model->find($ids);
        if (!$order) {
            $this->error(__('No Results were found'));
            exit;
        }

        //查找订单商品
        $product = model('Order.Product')->with(['products'])->where(['orderid' => $order['id']])->select();
        if (!$product) {
            $this->error('暂无退货商品');
            exit;
        }
        //查找客户收货地址信息
        $busid = isset($order['busid']) ? $order['busid'] : 0;
        $address = model('Business.Address')->where(['busid' => $busid])->select();

        if (!$address) {
            $this->error('无收货地址信息');
            exit;
        }

        //组装返回数据
        $data = [
            'order' => $order,
            'address' => $address,
            'product' => $product
        ];

        $this->success('返回订单和地址信息', null, $data);
        exit;
    }
}
