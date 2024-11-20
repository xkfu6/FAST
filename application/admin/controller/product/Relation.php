<?php

namespace app\admin\controller\product;

use app\common\controller\Backend;
use think\console\output\descriptor\Console;

/**
 * 商品属性关联管理
 *
 * @icon fa fa-circle-o
 */
class Relation extends Backend
{

    protected $relationSearch = true;
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Product.Relation');
        $this->ProductModel = model('Product.Prop');
    }
    public function index($ids = null)
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $iswhere = ['proid' => $ids];

            $count = $this->model
                ->with(['prop', 'product'])
                ->where($where)
                ->where($iswhere)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['prop', 'product'])
                ->where($where)
                ->where($iswhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    // 当选项有改变时就执行
    public function select($ids = null)
    {
        $row = $this->ProductModel->where(['id' => $ids])->value('value');
        if (!$row) {
            $this->error(__('No Results were found'));
        } else {
            //第一个返回的正确信息，第二个网址，第三个返回的值
            $this->success(null, null, $row);
            exit;
        }
    }
    public function add($ids = null)
    {
        // if ($this->request->isPost()) 
        // {
        //     $params = $this->request->post('row/a');

        //     if ($params) 
        //     {
        //         $prop = isset($params['prop']) ? trim($params['prop']) : "";
        //         $prop = json_decode($prop, true);

        //         if(!$prop)
        //         {
        //             $this->error('未选择属性数据');
        //             exit;
        //         }

        //         foreach($prop as &$item)
        //         {
        //             if(empty($item['price']))
        //             {
        //                 unset($item);
        //             }else
        //             {
        //                 $item['proid'] = $ids;
        //                 $item['propid'] = $params['propid'];
        //             }
        //         }

        //         $result = $this->model->validate("common/Product/Relation")->saveAll($prop);

        //         if ($result === false) 
        //         {
        //             $this->error($this->model->getError());
        //             exit;
        //         }

        //         $this->success();
        //         exit;
        //     }
        // }
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            if ($params) {
                $prop = isset($params['prop']) ? trim($params['prop']) : "";
                $prop = json_decode($prop, true);

                if (!$prop) {
                    $this->error('未选择属性数据');
                    exit;
                }

                foreach ($prop as &$item) {
                    if (empty($item['price'])) {
                        unset($item);
                    } else {
                        $item['proid'] = $ids;
                        $item['propid'] = $params['propid'];
                    }
                }

                $result = $this->model->validate("common/Product/Relation")->saveAll($prop);

                if ($result === false) {
                    $this->error($this->model->getError());
                    exit;
                }

                $this->success();
                exit;
            }
        }
        $proplist = $this->ProductModel->column('id,title');
        $first = array_keys($proplist);
        $first = isset($first[0]) ? $first[0] : 0;
        $textarea = $this->ProductModel->where(['id' => $first])->value('value');
        if ($textarea) {
            $textarea = json_decode($textarea); //json格式转换为php数组
            $list = [];
            foreach ($textarea as $item) {
                $list[] = ['value' => $item, 'price' => ''];
            }

            $textarea = json_encode($list); //在转换为josn格式
        }
        $this->view->assign('textarea', $textarea);
        $this->assign('prod', build_select('row[propid]', $proplist, [], ['class' => 'form-control selectpicker', 'id' => 'propid']));
        return $this->view->fetch();
    }
    public function edit($ids = null)
    {
        $relation = $this->model->where(['id' => $ids])->find();
        if ($this->request->isPOST()) {
            $param = $this->request->param('row/a');
            $param['id'] = $ids;
            $result = $this->model->validate("common/Product/Relation")->where(['id' => $ids])->update($param);
            if ($result) {
                $this->success('操作成功');
                exit;
            } else {
                $this->error('操作失败');
                exit;
            }
        }
        $this->assign('relation', $relation);
        return $this->view->fetch();
    }
}
