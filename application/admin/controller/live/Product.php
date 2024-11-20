<?php

namespace app\admin\controller\live;

use app\common\controller\Backend;

/**
 * 直播热卖商品管理
 *
 * @icon fa fa-circle-o
 */
class Product extends Backend
{

    /**
     * Product模型对象
     * @var \app\admin\model\live\Product
     */
    protected $model = null;
    protected $relationSearch = true;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Live.Product');
        $this->ProductModel = model('Product.Product');
        $this->view->assign("typeList", $this->model->getTypeList());
    }


    public function index($ids = null)
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $DataWhere = ['liveid' => $ids];

            $total = $this->model
                ->with(['subjects', 'products'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count();

            $list = $this->model
                ->with(['subjects', 'products'])
                ->where($where)
                ->where($DataWhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }
    public function add($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            if ($params) {
                $type = isset($params['type']) ? trim($params['type']) : 'subject';

                $params['relation'] = $type == 'subject' ? $params['subject'] : $params['product'];
                $params['liveid'] = $ids;

                $where = [
                    'liveid' => $ids,
                    'relation' => $params['relation']
                ];

                $check = $this->model->where($where)->find();

                if ($check) {
                    $this->error('不可以重复添加同一商品或课程');
                    exit;
                }

                $result = $this->model->validate("common/Live/Product")->save($params);

                if ($result === FALSE) {
                    $this->error($this->model->getError());
                    exit;
                }

                $this->success();
                exit;
            }
        }
        $this->assign('sublist', build_select('row[subject]', model('Subject.Subject')->column('id,title'), [], ['class' => 'selectpicker', 'required' => '']));
        $this->assign('prolist', build_select('row[product]', model('Product.Product')->column('id,name'), [], ['class' => 'selectpicker', 'required' => '']));
        $this->assign('typelist', build_select('row[type]', $this->model->GetTypeList(), [], ['class' => 'selectpicker', 'required' => '', 'id' => 'type']));
        return $this->view->fetch();
    }
    public function edit($ids = null)
    {
        $row = $this->model->find($ids);

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            if ($params) {
                $type = isset($params['type']) ? trim($params['type']) : 'subject';

                $params['relation'] = $type == 'subject' ? $params['subject'] : $params['product'];
                $params['liveid'] = $row['liveid'];
                $params['id'] = $ids;

                $where = [
                    'id' => ['<>', $ids],
                    'liveid' => $row['liveid'],
                    'relation' => $params['relation']
                ];

                $check = $this->model->where($where)->find();

                if ($check) {
                    $this->error('不可以重复添加同一商品或课程');
                    exit;
                }

                $result = $this->model->validate("common/Live/Product")->isUpdate(true)->save($params);

                if ($result === FALSE) {
                    $this->error($this->model->getError());
                    exit;
                }

                $this->success();
                exit;
            }
        }
        $this->assign('row', $row);
        $this->assign('sublist', build_select('row[subject]', model('Subject.Subject')->column('id,title'), $row['relation'], ['class' => 'selectpicker', 'required' => '']));
        $this->assign('prolist', build_select('row[product]', model('Product.Product')->column('id,name'), $row['relation'], ['class' => 'selectpicker', 'required' => '']));
        $this->assign('typelist', build_select('row[type]', $this->model->GetTypeList(), $row['type'], ['class' => 'selectpicker', 'required' => '', 'id' => 'type']));
        return $this->view->fetch();
    }
}
