<?php

namespace app\admin\controller\live;

use app\common\controller\Backend;

/**
 * 直播记录管理
 *
 * @icon fa fa-circle-o
 */
class Live extends Backend
{

    /**
     * Live模型对象
     * @var \app\admin\model\live\Live
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Live.Live');
        $this->view->assign("statusList", $this->model->StatusList());
    }
}
