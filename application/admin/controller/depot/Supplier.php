<?php

namespace app\admin\controller\depot;

use app\common\controller\Backend;

/**
 * 供应商
 *
 * @icon fa fa-circle-o
 */
class Supplier extends Backend
{


    /**
     * Supplier模型对象
     * @var \app\admin\model\depot\Supplier
     */
    protected $model = null;

    protected $relationSearch = false;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Depot.Supplier');
    }
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    //  供应商首页
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->with(['provinces', 'citys', 'districts'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count();

            $list = $this->model
                ->with(['provinces', 'citys', 'districts'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    // 添加供应商
    public function add()
    {
        if ($this->request->isPOST()) {
            $params = $this->request->param('row/a');
            if ($params) {
                $result = $this->model->validate('common/Depot/Supplier')->save($params);
                if ($result) {
                    $this->success('添加成功');
                    exit;
                } else {
                    $this->error($this->model->getError());
                    exit;
                }
            }
        }
        return $this->view->fetch();
    }
    // 修改供应商
    public function edit($ids = null)
    {
        $supplier = $this->model->find($ids);
        if ($this->request->isPOST()) {
            $params = $this->request->param('row/a');
            if ($params) {
                $params['id'] = $ids;
                $result = $this->model->validate('common/Depot/Supplier')->isUpdate(true)->save($params);
                if ($result) {
                    $this->success('修改成功');
                    exit;
                } else {
                    $this->error($this->model->getError());
                    exit;
                }
            }
        }
        $this->assign('row', $supplier);
        return $this->view->fetch();
    }
}
