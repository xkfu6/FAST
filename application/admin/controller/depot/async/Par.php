<?php

namespace app\admin\controller\depot\async;

use app\common\controller\Backend;

/**
 * 退货单异步管理
 *
 * @icon fa fa-circle-o
 */
class Par extends Backend
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
        $this->model = model('Product.Product');
    }
    public function index()
    {
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->with(['unit'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['unit'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
