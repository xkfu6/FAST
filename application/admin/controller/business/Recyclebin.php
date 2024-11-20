<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;

class Recyclebin extends Backend
{
    //覆盖重写 设置关联查询
    protected $relationSearch = true;
    public function __construct()
    {
        parent::__construct();

        $this->model = model('common/Business/Business');
    }
    public function index()
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            list($where, $sort, $order, $offect, $limit) = $this->buildparams();
            $count = $this->model
                ->onlyTrashed()
                ->with(['source'])
                ->where($where)
                ->order($sort, $order)
                ->count();
            // echo $this->model->getLastSql();
            // exit;
            $list = $this->model
                ->onlyTrashed() //只显示软删除过的数据
                ->with(['source'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offect, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    public function restore($ids = null)
    {
        $row = $this->model->select();
        if (!$row) {
            $this->error(__('No results were found'));
            exit;
        }
        if ($this->request->isAjax()) {
            $list =  $this->model->onlyTrashed()->where(['id' => ['IN', $ids]])->update(['deletetime' => null, 'adminid' => null]);
            if ($list) {
                $this->success('还原成功');
                exit;
            } else {
                $this->error('还原失败');
                exit;
            }
        }
    }
    public function del($ids = null)
    {

        $row = $this->model->select();
        if (!$row) {
            $this->error(__('No results were found'));
            exit;
        }
        if ($this->request->isAjax()) {
            $list =  $this->model->onlyTrashed()->where(['id' => ['IN', $ids]])->delete(true);
            if ($list) {
                $this->success('销毁成功');
                exit;
            } else {
                $this->error('销毁失败');
                exit;
            }
        }
    }
}
