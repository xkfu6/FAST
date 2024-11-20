<?php

namespace app\admin\controller\subject;

use app\common\controller\Backend;

/**
 * 课程名师管理
 *
 * @icon fa fa-circle-o
 */
class Teacher extends Backend
{
    // 关联查询
    protected $relationSearch = true;

    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('common/Subject/Teacher/Teacher');
    }
    public function index()
    {
        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
        if ($this->request->isAjax()) {
            $count = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    public function follow($ids = null)
    {
        $this->model = model('Subject.Teacher.Follow');

        //设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $DataWhere = ['teacherid' => $ids];

            $total = $this->model
                ->with(['business'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count();  //查询总数

            $list = $this->model
                ->with(['business'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();  //查询数据

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    public function subject($ids = null)
    {
        $this->model = model('Subject.Subject');

        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $DataWhere = ['teacherid' => $ids];

            $total = $this->model
                ->with(['category'])
                ->where($DataWhere)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count();

            $list = $this->model
                ->with(['category'])
                ->where($DataWhere)
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
