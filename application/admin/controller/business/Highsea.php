<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;

class Highsea extends Backend
{
    //覆盖重写 设置关联查询
    protected $relationSearch = true;
    public function __construct()
    {
        parent::__construct();

        $this->model = model('common/Business/Business');
        $this->ReceiveModel = model('common/Business/Receive');
        $this->AdminModel = model('common/Admin');
    }
    public function index()
    {
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            list($where, $sort, $order, $offect, $limit) = $this->buildparams();
            $count = $this->model
                ->with(['source'])
                ->where($where)
                ->where(['adminid' => NULL])
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['source'])
                ->where($where)
                ->where(['adminid' => NULL])
                ->order($sort, $order)
                ->limit($offect, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    public function apply($ids = null)
    {

        $row = $this->model->select($ids);
        if (!$row) {
            $this->error(__('No results were found'));
            exit;
        }
        // 组装领取信息
        $ReceiveData = [];
        foreach ($row as $item) {
            $ReceiveData[] = [
                'applyid' => $this->auth->id,
                'status' => 'apply',
                'busid' => $item['id']
            ];
        }
        //开始事务
        $this->model->startTrans();
        $this->ReceiveModel->startTrans();

        $BusinessStart = $this->model->where(['id' => ['IN', $ids]])->update(['adminid' => $this->auth->id]);
        if ($BusinessStart === FALSE) {
            $this->error('更新领取状态失败');
            exit;
        }
        $ReceiveStart = $this->ReceiveModel->validate('common/business/receive')->saveAll($ReceiveData);
        if ($ReceiveStart === FALSE) {
            $this->model->rollback();
            $this->error("插入领取数据失败");
            exit;
        }
        if ($BusinessStart === FALSE || $ReceiveStart === FALSE) {
            $this->ReceiveModel->rollback();
            $this->model->rollback();
            $this->error('领取失败');
            exit;
        } else {
            $this->model->commit();
            $this->ReceiveModel->commit();
            $this->success('领取成功');
            exit;
        }
    }
    public function allot($ids = null)
    {
        $row = $this->model->select($ids);
        if (!$row) {
            $this->error(__('No results were found'));
            exit;
        }
        if ($this->request->isPOST()) {
            $adminid = $this->request->param('adminid', '0', 'trim');
            if (!$adminid) {
                $this->error('分配人为空');
                exit;
            }

            // 组装领取信息
            $ReceiveData = [];
            foreach ($row as $item) {
                $ReceiveData[] = [
                    'applyid' => $adminid,
                    'status' => 'allot',
                    'busid' => $item['id']
                ];
            }
            //开始事务
            $this->model->startTrans();
            $this->ReceiveModel->startTrans();

            $BusinessStart = $this->model->where(['id' => ['IN', $ids]])->update(['adminid' => $adminid]);
            if ($BusinessStart === FALSE) {
                $this->error('更新领取状态失败');
                exit;
            }
            $ReceiveStart = $this->ReceiveModel->validate('common/business/receive')->saveAll($ReceiveData);
            if ($ReceiveStart === FALSE) {
                $this->model->rollback();
                $this->error("插入领取数据失败");
                exit;
            }
            if ($BusinessStart === FALSE || $ReceiveStart === FALSE) {
                $this->ReceiveModel->rollback();
                $this->model->rollback();
                $this->error('分配失败');
                exit;
            } else {
                $this->model->commit();
                $this->ReceiveModel->commit();
                $this->success('分配资源成功');
                exit;
            }
        }
        $this->assign('row', $row);
        $admin = $this->AdminModel
            ->where(['id' => ['<>', $this->auth->id]])
            ->column('id,nickname');
        $adminlist = build_select('adminid', $admin, [], ['class' => 'selectpicken', 'reuired' => '']);
        $this->assign('adminlist', $adminlist);
        return $this->view->fetch();
    }
    public function del($ids = NULL)
    {
        $row = $this->model->select($ids);
        if (!$row) {
            $this->error(__('No results were found'));
            exit;
        }
        $result = $this->model->destroy($ids);
        if ($result === FALSE) {
            $this->error($this->SourceModel->getError());
            exit;
        } else {
            $this->success();
            exit;
        }
    }
}
