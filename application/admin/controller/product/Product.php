<?php

namespace app\admin\controller\product;

use app\common\controller\Backend;

/**
 * 商品管理
 *
 * @icon fa fa-circle-o
 */
class Product extends Backend
{
    protected $relationSearch = true;
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->TypeModel = model('common/Product/Type');
        $this->UnitModel = model('common/Product/Unit');
        $this->model = model('common/Product/Product');
        $this->view->assign('flagList', build_select('row[flag]', model('Product.Product')->getFlagList(), [], ['class' => 'form-control selectpicker']));
        $this->view->assign('statusList', build_select('row[status]', model('Product.Product')->getStatusList(), [], ['class' => 'form-control selectpicker']));
        $this->view->assign('typeList', build_select('row[typeid]', $this->TypeModel->column('id,name'), [], ['class' => 'form-control selectpicker']));
        $this->view->assign('unitList', build_select('row[unitid]', $this->UnitModel->column('id,name'), [], ['class' => 'form-control selectpicker']));
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    public function index()
    {
        $this->model = model('common/Product/Product');
        if ($this->request->isAjax()) {

            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            //总数
            $total = $this->model
                ->with(['type', 'unit'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            //列表
            $list = $this->model
                ->with(['type', 'unit'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            //组装数据
            $result = ['total' => $total, 'rows' => $list];

            return json($result);
        }

        return $this->view->fetch();
    }
    public function edit($ids = null)
    {
        $product = $this->model
            ->where(['id' => $ids])
            ->find();

            if($this->request->isPOST()){
                $parmas = $this->request->param('row/a');
                $parmas['id'] = $ids;
                $result = $this->model->validate('common/Product/Product')->isUpdate(true)->save($parmas);
                if ($result) {
                    $this->success();
                    exit;
                } else {
                    $this->error($this->SourmodelceModle->getError());
                    exit;
                }
            }
        $this->view->assign('flagList', build_select('row[flag]', model('Product.Product')->getFlagList(), $product['status'], ['class' => 'form-control selectpicker']));
        $this->view->assign('typeList', build_select('row[typeid]', $this->TypeModel->column('id,name'), $product['typeid'], ['class' => 'form-control selectpicker']));
        $this->view->assign('unitList', build_select('row[unitid]', $this->UnitModel->column('id,name'), $product['unitid'], ['class' => 'form-control selectpicker']));
        $this->assign('product', $product);
        return $this->view->fetch();
    }
}
