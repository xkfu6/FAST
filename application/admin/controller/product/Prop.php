<?php

namespace app\admin\controller\product;

use app\common\controller\Backend;

/**
 * 通用商品属性管理
 *
 * @icon fa fa-circle-o
 */
class Prop extends Backend
{

    /**
     * Prop模型对象
     * @var \app\admin\model\product\Prop
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Product.Prop');
    }
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->param('row/a');

            if ($params) {
                $value = isset($params['value']) ? trim($params['value']) : '';
                if (empty($value)) {
                    $this->error('请输入属性值');
                    exit;
                }
                $value = json_decode($value, true); //将json转换为php数组
                $list = isset($value[0]) ? $value[0] : [];
                $filter = array_filter($list); //过滤数组中的空值
                $filter = array_unique($filter); //去除数组中的重复元素

                if (count($list) != count($filter)) {
                    $this->error('请删除空属性，或者重复属性');
                    exit;
                }
                //中文不转义
                $params['value'] = json_encode($filter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); //转换json字符串
                $result = $this->model->validate("Common/Product/Prop")->save($params);
                if ($result === false) {
                    $this->error($this->model->getError());
                    exit;
                }

                $this->success();
                exit;
            }
        }
        return $this->view->fetch();
    }
    public function edit($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            if ($params) {
                $value = isset($params['value']) ? trim($params['value']) : '';

                if (empty($value)) {
                    $this->error('请输入属性值');
                    exit;
                }

                if ($value != $row['value']) {
                    $value = json_decode($value, true);
                    $list = isset($value[0]) ? $value[0] : [];
                    $filter = array_filter($list);
                    $filter = array_unique($filter);

                    if (count($list) != count($filter)) {
                        $this->error('请删除空属性，或者重复属性');
                        exit;
                    }

                    //中文不转义
                    $params['value'] = json_encode($filter, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }

                $params['id'] = $ids;

                $result = $this->model->validate("Common/Product/Prop")->isUpdate(true)->save($params);

                if ($result === false) {
                    $this->error($this->model->getError());
                    exit;
                }

                $this->success();
                exit;
            }
        }
        $this->assign("row", $row);
        return $this->view->fetch();
    }
}
