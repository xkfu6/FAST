<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;

class Source extends Backend
{
    public function __construct()
    {
        parent::__construct();

        $this->SourceModel = model('common/Business/Source');
    }
    public function index()
    {
        //调用公共控制器基类里面的方法，生成出表格的筛选参数
        list($where, $sort, $order, $offect, $limit) = $this->buildparams();
        if ($this->request->isAjax()) {
            $count = $this->SourceModel
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->SourceModel
                ->where($where)
                ->order($sort, $order)
                ->limit($offect, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    public function add()
    {
        if ($this->request->isPost()) {
            //接收row前缀数组元素  以array的类型获取 row/a等于row/array
            $name = $this->request->param('row/a');
            $list = $this->SourceModel->validate('common/business/Source')->save($name);
            if ($list) {
                $this->success();
                exit;
            } else {
                $this->error($this->SourceModel->getError());
                exit;
            }
        }

        return $this->view->fetch();
    }
    public function edit($ids = null)
    {
        $sour = $this->SourceModel->find($ids);
        if (!$sour) {
            $this->error(__('No results were found'));
            exit;
        }
        if ($this->request->isPOST()) {
            $name = $this->request->param('row/a');
            if ($name) {
                $name['id'] = $ids;
                $list = $this->SourceModel->validate('common/business/Source')->isUpdate(true)->save($name);
                if ($list) {
                    $this->success();
                    exit;
                } else {
                    $this->error($this->SourceModle->getError());
                    exit;
                }
            }
        }
        $this->assign('sour', $sour);
        return $this->view->fetch();
    }
    public function del($ids = null)
    {
        $sour = $this->SourceModel->find($ids);
        $list = $this->SourceModel->where(['id' => $ids])->delete();
        if ($list) {
            $this->success();
            exit;
        } else {
            $this->error($this->SourceModle->getError());
            exit;
        }
    }
}
