<?php

namespace app\admin\controller\depot\async;

use app\common\controller\Backend;

/**
 * 退货单异步管理
 *
 * @icon fa fa-circle-o
 */
class Storage extends Backend
{

    /**
     * Back模型对象
     * @var \app\admin\model\depot\Back
     */
    protected $model = null;
    protected $relationSearch = true;
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Depot.Storage.Storage');
    }
    /**
     * 返回当前选中供应商
     */
    public function supplier($ids = NULL)
    {
        $row = model("Depot.Supplier")->find(['id' => $ids]);

        if (!$row) {
            $this->error(__('No Results were found'));
        } else {
            $this->success(null, null, $row);
        }
    }
    public function product($ids = NUll)
    {
        $row = model("Product.Product")->find(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        } else {
            $this->success(null, null, $row);
        }
    }
}
