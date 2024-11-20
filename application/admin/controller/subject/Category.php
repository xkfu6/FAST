<?php

namespace app\admin\controller\subject;

use app\common\controller\Backend;

/**
 * 课程分类管理
 *
 * @icon fa fa-circle-o
 */
class Category extends Backend
{

    /**
     * Category模型对象
     * @var \app\admin\model\subject\Category
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('common/Subject/Category');
    }



    public function add()
    {
        $params = $this->request->param('row/a');
        if ($params) {
            $result = $this->model->validate("common/Subject/Category")->save($params);

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success("添加成功");
                exit;
            }
        }
        $weight = $this->model->max('weight');
        $weight > 0 ? ++$weight : 1;
        $this->assign('weight', $weight);
        return $this->view->fetch();
    }
}
