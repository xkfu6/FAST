<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;

class Info extends Backend
{

    //当前控制器下面的所有方法都不需要权限验证
    protected $noNeedRight = ['*'];

    //开启关联查询
    protected $relationSearch = true;

    public function __construct()
    {
        parent::__construct();

        $this->model = model('common/Business/Business');
    }
    public function index($ids = NULL)
    {
        $row = $this->model->with(['source', 'admin'])->find($ids);

        if (!$row) {
            $this->error(__('No results were found'));
            exit;
        }

        $this->assign('row', $row);

        return $this->view->fetch();
        return $this->view->fetch();
    }
    //领取记录 方法
    public function receive($ids = null)
    {
        //覆盖主模型
        $this->model = model('common/Business/Receive');
        if ($this->request->isAjax()) {
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $DataWhere = ['busid' => $ids];
            //总数
            $total = $this->model
                ->with(['admin'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->count();

            //列表
            $list = $this->model
                ->with(['admin'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            //组装数据
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }
    }
    public function record($ids = null)
    {
        if ($this->request->isAjax()) {
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            $this->model = model('common/Business/Record');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $DataWhere = ['busid' => $ids];
            //总数

            $total = $this->model
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->count();
            //列表
            $list = $this->model
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            //组装数据
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }
    }
    public function subject($ids = null)
    {
        if ($this->request->isAjax()) {
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            $this->model = model('common/Subject/Order');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $DataWhere = ['busid' => $ids];
            //总数

            $total = $this->model
                ->with('subject')
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->count();
            //列表
            $list = $this->model
                ->with('subject')
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            //组装数据
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }
    }
    public function coupon($ids = null)
    {
        if ($this->request->isAjax()) {
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            $this->model = model('common/Coupon/Receive');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $DataWhere = ['parentid' => $ids];
            //总数

            $total = $this->model
                ->with(['business'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->count();
            //列表
            $list = $this->model
                ->with(['business'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            //组装数据
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }
    }
    public function commission($ids = null)
    {
        if ($this->request->isAjax()) {
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            $this->model = model('common/Business/Commission');
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $DataWhere = ['commission.parentid' => $ids];
            //总数

            $total = $this->model
                ->with(['business'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->count();
            //列表
            $list = $this->model
                ->with(['business'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            //组装数据
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }
    }
}
