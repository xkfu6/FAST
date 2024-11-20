<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;

/**
 * 客户回访记录管理
 *
 * @icon fa fa-circle-o
 */
class Visit extends Backend
{

    protected $relationSearch = true;
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('common/Business/Visit');
    }

    public function index($ids = null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $AuthWhere = [];

            if ($ids) //找客户的回访记录
            {
                $AuthWhere = ['visit.busid' => $ids];
            } else //找管理员的全部回访记录
            {
                $AuthWhere = ['visit.adminid' => $this->auth->id];
            }

            $total = $this->model
                ->with(['admin', 'business'])
                ->where($where)
                ->where($AuthWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count();

            $list = $this->model
                ->with(['admin', 'business'])
                ->where($where)
                ->where($AuthWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }

    public function add($ids = null)
    {
        if ($this->request->isPOST()) {
            $params = $this->request->param('row/a');
            $params['busid'] = $ids;
            $params['adminid'] = $this->auth->id;
            $result = $this->model->validate('common/Business/Visit')->save($params);

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success();
                exit;
            }
        }
        return $this->view->fetch();
    }
}
