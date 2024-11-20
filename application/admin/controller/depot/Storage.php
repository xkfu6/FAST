<?php

namespace app\admin\controller\depot;

use app\common\controller\Backend;

/**
 * 入库管理
 *
 * @icon fa fa-circle-o
 */
class Storage extends Backend
{

    /**
     * Storage模型对象
     * @var \app\admin\model\depot\Storage
     */
    protected $model = null;
    protected $relationSearch = true;


    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Depot.Storage.Storage');
        $this->ModelStorageProduct = model('Depot.Storage.Product');
        $this->SupplierModel = model('Depot.Supplier');

        $this->ProductModel = model('Product.Product');

        $this->view->assign("typeList", $this->model->typelist());
        $this->view->assign("statusList", $this->model->statuslist());
    }

    // 入库列表
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->with(['supplier'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->count();

            $list = $this->model
                ->with(['supplier'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }

        return $this->view->fetch();
    }
    //添加
    public function add()
    {

        if ($this->request->isPost()) {
            $params = $this->request->post();

            if ($params) {
                // 入库插入事务
                $this->model->startTrans();
                $this->ModelStorageProduct->startTrans();
                //循环入库商品
                $prolist = isset($params['prolist']) ? $params['prolist'] : [];
                $nums = isset($params['nums']) ? $params['nums'] : [];
                $price = isset($params['price']) ? $params['price'] : [];
                $total = isset($params['total']) ? $params['total'] : [];
                if (count($prolist) <= 0) {
                    $this->error(__('未选择入库商品，请重新选择'));
                    exit;
                }

                if (count($nums) <= 0) {
                    $this->error(__('未填写入库商品数量，请重新填写'));
                    exit;
                }

                if (count($price) <= 0) {
                    $this->error(__('未填写入库商品单价，请重新填写'));
                    exit;
                }

                if (count($total) <= 0) {
                    $this->error(__('未填写入库商品总价，请重新填写'));
                    exit;
                }

                //商品个数与数量的个数不相等
                if (count($prolist) != count($nums)) {
                    $this->error(__('入库商品的个数与数量不相等，请重新填写'));
                    exit;
                }

                //商品与价格的个数不相等
                if (count($prolist) != count($price)) {
                    $this->error(__('入库商品的个数与商品价格个数不相等，请重新填写'));
                    exit;
                }

                if (count($prolist) != count($total)) {
                    $this->error(__('入库商品的个数与商品总价个数不相等，请重新填写'));
                    exit;
                }
                //查找供应商
                $supplierid = isset($params['supplierid']) ? trim($params['supplierid']) : 0;
                $suppliername = model('Depot.Supplier')->where(['id' => $supplierid])->value('name');
                $params['code'] = build_code("ST");
                $params['status'] = 0;
                $params['adminid'] = $this->auth->id;
                //插入入库记录
                $StorageStatus = $this->model->validate("Common/Depot/Storage/Storage")->save($params);
                if ($StorageStatus === FALSE) {
                    $this->error($this->model->getError());
                    exit;
                }
                $stprageProduct = [];
                foreach ($prolist as $key => $item) {
                    $stprageProduct[] = [
                        "storageid" => $this->model->id,
                        "proid" => $item,
                        "nums" => $nums[$key] > 0 ? $nums[$key] : 1,
                        "price" => $price[$key] >= 0 ? $price[$key] : 0,
                        "total" => $total[$key] >= 0 ? $total[$key] : 0,
                    ];
                }
                //插入入库商品关系表
                $StorageProductStatus = $this->ModelStorageProduct->validate("Common/Depot/Storage/Product")->insertAll($stprageProduct);

                if ($StorageProductStatus === FALSE) {
                    $this->model->rollBack();
                    $this->error($this->ModelStorageProduct->getError());
                    exit;
                }

                //提交事务
                if ($StorageStatus === FALSE || $StorageProductStatus === FALSE) {
                    $this->model->rollBack();
                    $this->ModelStorageProduct->rollBack();
                    $this->error(__('添加入库失败'));
                    exit;
                } else {
                    $this->model->commit();
                    $this->ModelStorageProduct->commit();
                    $this->success();
                    exit;
                }
            }
        }
        $this->view->assign('typelist', build_select('type', model('Depot.Storage.Storage')->typelist(), [], ['class' => 'form-control selectpicker']));
        return $this->view->fetch();
    }
    // 修改
    public function edit($ids = null)
    {
        $row = $this->model->with(['supplier'])->find($ids);
        $product = $this->ModelStorageProduct->with(['products'])->where(['storageid' => $ids])->select();
        //提取所有的入库商品id
        $proids = array_column($product, 'proid');

        // 修改操作
        if ($this->request->isPost()) {
            $params = $this->request->post();

            if ($params) {
                $params['id'] = $ids;
                // 入库插入事务
                $this->model->startTrans();
                $this->ModelStorageProduct->startTrans();
                //循环入库商品
                $prolist = isset($params['prolist']) ? $params['prolist'] : [];
                $nums = isset($params['nums']) ? $params['nums'] : [];
                $price = isset($params['price']) ? $params['price'] : [];
                $total = isset($params['total']) ? $params['total'] : [];
                if (count($prolist) <= 0) {
                    $this->error(__('未选择入库商品，请重新选择'));
                    exit;
                }

                if (count($nums) <= 0) {
                    $this->error(__('未填写入库商品数量，请重新填写'));
                    exit;
                }

                if (count($price) <= 0) {
                    $this->error(__('未填写入库商品单价，请重新填写'));
                    exit;
                }

                if (count($total) <= 0) {
                    $this->error(__('未填写入库商品总价，请重新填写'));
                    exit;
                }

                //商品个数与数量的个数不相等
                if (count($prolist) != count($nums)) {
                    $this->error(__('入库商品的个数与数量不相等，请重新填写'));
                    exit;
                }

                //商品与价格的个数不相等
                if (count($prolist) != count($price)) {
                    $this->error(__('入库商品的个数与商品价格个数不相等，请重新填写'));
                    exit;
                }

                if (count($prolist) != count($total)) {
                    $this->error(__('入库商品的个数与商品总价个数不相等，请重新填写'));
                    exit;
                }
                //查找供应商
                $supplierid = isset($params['supplierid']) ? trim($params['supplierid']) : 0;
                $suppliername = model('Depot.Supplier')->where(['id' => $supplierid])->value('name');
                $params['code'] = build_code("ST");
                $params['status'] = 0;
                $params['adminid'] = $this->auth->id;
                //更新入库记录
                $StorageStatus = $this->model->validate("Common/Depot/Storage/Storage")->isUpdate(true)->save($params);
                if ($StorageStatus === FALSE) {
                    $this->error($this->model->getError());
                    exit;
                }

                //入库商品
                $StorageProductAdd = [];
                $StorageProductUpdate = [];
                foreach ($prolist as $key => $item) {
                    $exist = $this->ModelStorageProduct->where(['storageid' => $row['id'], 'proid' => $item])->find();
                    if ($exist) {
                        // 找到对应的索引
                        $pos = array_search($item, $proids);
                        $pos = ($pos === FALSE) ? -1 : $pos;
                        // 移除
                        unset($proids[$pos]);

                        $StorageProductUpdate[] = [
                            "id" => $exist['id'],
                            "storageid" => $row['id'],
                            "proid" => $item,
                            "nums" => $nums[$key] > 0 ? $nums[$key] : 1,
                            "price" => $price[$key] >= 0 ? $price[$key] : 0,
                            "total" => $total[$key] >= 0 ? $total[$key] : 0,
                        ];
                    } else {
                        $StorageProductAdd[] = [
                            "storageid" => $row['id'],
                            "proid" => $item,
                            "nums" => $nums[$key] > 0 ? $nums[$key] : 1,
                            "price" => $price[$key] >= 0 ? $price[$key] : 0,
                            "total" => $total[$key] >= 0 ? $total[$key] : 0,
                        ];
                    }
                }
                //插入入库商品关系表
                if (!empty($StorageProductAdd)) {
                    $StorageProductAddStatus = $this->ModelStorageProduct->validate("Common/Depot/Storage/Product")->insertAll($StorageProductAdd);

                    if ($StorageProductAddStatus === FALSE) {
                        $this->model->rollBack();
                        $this->error($this->ModelStorageProduct->getError());
                        exit;
                    }
                }
                //更新入库商品关系表
                if (!empty($StorageProductUpdate)) {
                    $StorageProductAddStatus = $this->ModelStorageProduct->validate("Common/Depot/Storage/Product")->isUpdate(true)->saveAll($StorageProductUpdate);

                    if ($StorageProductAddStatus === FALSE) {
                        $this->model->rollBack();
                        $this->error($this->ModelStorageProduct->getError());
                        exit;
                    }
                }

                //提交事务
                if ($StorageStatus === FALSE) {
                    $this->model->rollBack();
                    $this->ModelStorageProduct->rollBack();
                    $this->error(__('编辑入库失败'));
                    exit;
                } else {
                    $this->model->commit();
                    $this->ModelStorageProduct->commit();
                    $this->success();
                    exit;
                }
            }
        }
        $this->view->assign('row', $row);
        $this->view->assign('product', $product);
        $this->view->assign('typelist', build_select('type', model('Depot.Storage.Storage')->typelist(), [], ['class' => 'form-control selectpicker']));
        return $this->view->fetch();
    }
    // 入库详情
    public function info($ids = null)
    {
        $row = $this->model->withTrashed()->with(['admin', 'reviewer'])->find($ids);

        $supplier = $this->SupplierModel->with(['provinces', 'citys', 'districts'])->where('id', $row['supplierid'])->find();

        $list = $this->ModelStorageProduct->where(['storageid' => $row['id']])->select();

        $prolist = [];

        foreach ($list as $item) {
            $product = model('Product.Product')->with(['type', 'unit'])->find($item['proid']);

            $prolist[] = [
                'id' => $item['id'],
                'price' => $item['price'],
                'nums' => $item['nums'],
                'total' => $item['total'],
                'product' => $product
            ];
        }

        $this->view->assign([
            'row' => $row,
            'supplier' => $supplier,
            'prolist' => $prolist
        ]);

        return $this->view->fetch();
    }
    // 同意审核
    public function agree($ids = null)
    {
        $row = $this->model->select($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }
        $params = [
            'id' => $ids,
            'status' => 2,
            'reviewerid' => $this->auth->id
        ];
        $result = $this->model->isUpdate(true)->save($params);
        if ($result) {
            $this->success('通过成功');
            exit;
        } else {
            $this->error('通过失败');
            exit;
        }
    }
    // 拒绝审核
    public function reject($ids = null)
    {
        $row = $this->model->select($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }
        $params = [
            'id' => $ids,
            'status' => 1,
            'reviewerid' => $this->auth->id
        ];
        $result = $this->model->isUpdate(true)->save($params);
        if ($result) {
            $this->success('拒绝成功');
            exit;
        } else {
            $this->error('拒绝失败');
            exit;
        }
    }
    // 撤销审核
    public function revoke($ids = null)
    {
        $row = $this->model->select($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }
        $params = [
            'id' => $ids,
            'status' => 0,
            'reviewerid' => null
        ];
        $result = $this->model->isUpdate(true)->save($params);
        if ($result) {
            $this->success('拒绝成功');
            exit;
        } else {
            $this->error('拒绝失败');
            exit;
        }
    }
    // 确认入库
    public function receipt($ids = null)
    {
        $row = $this->model->select($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }
        $this->model->startTrans();
        $this->ProductModel->startTrans();
        // 更新入库表
        $StorageData = [
            'id' => $ids,
            'status' => 3,
            'reviewerid' => $this->auth->id
        ];
        $StorageStatus = $this->model->isUpdate(true)->save($StorageData);
        if ($StorageStatus === FALSE) {
            $this->error($this->model->getError());
            exit;
        }

        $ProductData = [];
        $productList = $this->ModelStorageProduct->where(['storageid' => $ids])->select();
        foreach ($productList as $item) {
            $product = $this->ProductModel->find($item['proid']);
            if (!$product) {
                continue;
            }
            $ProductData[] = [
                'id' => $product['id'],
                'stock' => bcadd($product['stock'], $item['nums'])
            ];
        }
        $ProductStatus = $this->ProductModel->isUpdate(true)->saveAll($ProductData);
        if ($ProductStatus === FALSE) {
            $this->model->rollback();
            $this->error($this->ProductModel->getError());
            exit;
        }

        if ($StorageStatus === FALSE || $ProductStatus === FALSE) {
            $this->ProductModel->rollback();
            $this->model->rollback();
            $this->error('确认入库失败');
            exit;
        } else {
            $this->model->commit();
            $this->ProductModel->commit();
            $this->success('确认入库成功');
            exit;
        }
    }
}
