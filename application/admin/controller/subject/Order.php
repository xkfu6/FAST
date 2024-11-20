<?php

namespace app\admin\controller\subject;

use app\common\controller\Backend;

/**
 * 课程购买订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{

    protected $relationSearch = true;
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('common/Subject/Order');
        $this->view->assign("payList", $this->model->getPayList());
    }

    public function index()
    {
        //调用公共控制器基类里面的方法，生成出表格的筛选参数
        list($where, $sort, $order, $offect, $limit) = $this->buildparams();
        if ($this->request->isAjax()) {
            $count = $this->model
                ->with(['subject', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['subject', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offect, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    public function recyclebin()
    {
        //调用公共控制器基类里面的方法，生成出表格的筛选参数
        list($where, $sort, $order, $offect, $limit) = $this->buildparams();

        if ($this->request->isAjax()) {
            $count = $this->model
                ->onlyTrashed()
                ->with(['subject', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->onlyTrashed()
                ->with(['subject', 'business'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offect, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
}
