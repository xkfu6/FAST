<?php

namespace app\admin\controller\subject;

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

        $this->model = model('common/Subject/Subject');
    }
    public function index($ids = null)
    {
        if ($this->request->isAjax()) {
            $this->model = model('common/Subject/Order');
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $DataWhere = ['subid' => $ids];
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
        return $this->view->fetch();
    }
    public function comment($ids = null)
    {
        if ($this->request->isAjax()) {
            $this->model = model('common/Subject/comment');
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $DataWhere = ['subid' => $ids];
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
        return $this->view->fetch();
    }
}
