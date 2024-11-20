<?php

namespace app\admin\controller\coupon;

use app\common\controller\Backend;
use tests\thinkphp\library\think\buildTest;

/**
 * 优惠券管理
 *
 * @icon fa fa-circle-o
 */
class Coupon extends Backend
{
    protected $relationSearch = true;
    public function __construct()
    {
        parent::__construct();

        $this->model = model('common/Coupon/Coupon');
    }
    // 列表
    public function index()
    {
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offect, $limit) = $this->buildparams();
            $count = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offect, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    // 增加
    public function add()
    {
        if ($this->request->isPOST()) {
            $params = $this->request->post('row/a');
            // var_dump($params);
            // exit;
            if ($params) {
                $params['endtime'] = strtotime($params['endtime']); //strotime将时间转换为时间戳
                $list = $this->model->validate('common/Coupon/Coupon')->save($params);
                if ($list) {
                    $this->success('添加成功');
                    exit;
                } else {
                    $this->error('添加失败');
                    exit;
                }
            }
        }
        $this->assign('status', build_select('row[status]', model('Coupon.Coupon')->statuslist(), [], ['class' => 'form-control selectpicker']));
        return $this->view->fetch();
    }

    public function edit($ids = null)
    {
        $coupon = $this->model->find($ids);
        if (!$coupon) {
            $this->error(__('No results were found'));
            exit;
        }
        if ($this->request->isPOST()) {
            $name = $this->request->param('row/a');
            $name['endtime'] = strtotime($name['endtime']);
            if ($name) {
                $name['id'] = $ids;
                $list = $this->model->validate('common/coupon/Coupon')->isUpdate(true)->save($name);
                if ($list) {
                    $this->success();
                    exit;
                } else {
                    $this->error($this->SourmodelceModle->getError());
                    exit;
                }
            }
        }
        $this->assign('status', build_select('row[status]', model('Coupon.Coupon')->statuslist(), $coupon['status'], ['class' => 'form-control selectpicker']));
        $this->assign('coupon', $coupon);
        return $this->view->fetch();
    }
    // 删除
    public function del($ids = null)
    {
        $sour = $this->model->find($ids);
        $list = $this->model->where(['id' => $ids])->delete();
        if ($list) {
            $this->success();
            exit;
        } else {
            $this->error($this->SourceModle->getError());
            exit;
        }
    }
    public function receive($ids)
    {

        $this->model = model('common/Coupon/Receive');
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offect, $limit) = $this->buildparams();
            $iswhere = ['cid' => $ids];
            $count = $this->model
                ->with('business')
                ->where($where)
                ->where($iswhere)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with('business')
                ->where($where)
                ->where($iswhere)
                ->order($sort, $order)
                ->limit($offect, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
}
