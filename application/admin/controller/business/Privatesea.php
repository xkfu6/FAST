<?php

namespace app\admin\controller\business;

use app\common\controller\Backend;
use think\Db;

class Privatesea extends Backend
{
    //覆盖重写 设置关联查询
    protected $relationSearch = true;
    protected $dataLimit = 'personal'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'adminid'; //数据关联字段,当前控制器对应的模型表中必须存在该字段
    public function _initialize()
    {
        parent::_initialize();

        $this->model = model('common/Business/Business');
        $this->SourceModel = model('common/Business/Source');
        $this->ReceiveModel = model('Business.Receive');
        $this->RegionModel = model('Region');
    }
    public function index()
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //调用公共控制器基类里面的方法，生成出表格的筛选参数
            list($where, $sort, $order, $offect, $limit) = $this->buildparams();
            $count = $this->model
                ->with(['source', 'admin'])
                ->where($where)
                ->where(['adminid' => ['<>', '']])
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->with(['source', 'admin'])
                ->where($where)
                ->where(['adminid' => ['<>', '']])
                ->order($sort, $order)
                ->limit($offect, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            // 密码加密
            $salt = build_randstr();
            $password = md5($params['mobile'] . $salt);

            //组装数据
            $data = [
                'nickname' => $params['nickname'],
                'mobile' => $params['mobile'],
                'gender' => $params['gender'],
                'sourceid' => $params['sourceid'],
                'email' => $params['email'],
                'deal' => $params['deal'],
                'adminid' => $this->auth->id,
                'password' => $password,
                'salt' => $salt,
                'auth' => $params['auth'],
                'money' => $params['money'],
                'province' => $params['province'],
                'city' => $params['city'],
                'district' => $params['district'],
                'avatar' => $params['avatar'],
            ];

            // 开启事务
            $this->model->startTrans();
            $this->ReceiveModel->startTrans();

            $BusinessStatus = $this->model->validate("common/Business/Business")->save($data);

            if ($BusinessStatus === FALSE) {
                $this->error($this->model->getError());
                exit;
            }

            // 封装领取数据
            $ReceiveData = [
                'applyid' => $this->auth->id,
                'status' => 'allot',
                'busid' => $this->model->id
            ];

            // 插入领取表
            $ReceiveStatus = $this->ReceiveModel->validate('common/Business/Receive')->save($ReceiveData);

            if ($ReceiveStatus === FALSE) {
                $this->model->rollback();
                $this->error($this->ReceiveModel->getError());
                exit;
            }

            if ($ReceiveStatus === FALSE || $BusinessStatus === FALSE) {
                $this->ReceiveModel->rollback();
                $this->model->rollback();
                $this->error('添加失败');
                exit;
            } else {
                $this->model->commit();
                $this->ReceiveModel->commit();
                $this->success('添加成功');
                exit;
            }
        }
        // $name：下拉列表的名称。
        // $options：下拉列表的选项，是一个关联数组，键是选项的值，值是选项的标签。
        // $selected：默认选中的选项，可以是一个值，也可以是一个值的数组。
        // $attrs：下拉列表的HTML属性，是一个关联数组，键是属性名，值是属性值。
        $this->view->assign('deallist', build_select('row[deal]', model('Business.Business')->getDealList(), [], ['class' => 'form-control selectpicker']));
        $this->view->assign('genderlist', build_select('row[gender]', model('Business.Business')->getGenderList(), [], ['class' => 'form-control selectpicker']));
        $this->view->assign('authlist', build_select('row[auth]', model('Business.Business')->getAuthList(), [], ['class' => 'form-control selectpicker']));
        $this->view->assign('sourcelist', build_select('row[sourceid]', model('Business.Source')->column('id,name'), [], ['class' => 'form-control selectpicker']));
        return $this->view->fetch();
    }
    public function recycle($ids = null)
    {
        $row = $this->model->select($ids);
        if (!$row) {
            $this->error(__('No results were found'));
            exit;
        }
        $list = $this->model->where(['id' => ['IN', $ids]])->update(['adminid' => NULL]);
        if ($list) {
            $this->success('回收资源成功');
            exit;
        } else {
            $this->error('回收资源失败');
            exit;
        }
    }
    public function del($ids = null)
    {
        $row = $this->model->select($ids);
        if (!$row) {
            $this->error(__('No results were found'));
            exit;
        }
        $list = $this->model->destroy($ids);
        if ($list) {
            $this->success('删除成功');
            exit;
        } else {
            $this->error('删除失败');
            exit;
        }
    }
    // 编辑
    public function edit($ids = null)
    {
        $row = $this->model->find($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
            exit;
        }

        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');

            //组装数据
            $data = [
                'id' => $ids,
                'nickname' => $params['nickname'],
                'mobile' => $params['mobile'],
                'gender' => $params['gender'],
                'sourceid' => $params['sourceid'],
                'email' => $params['email'],
                'deal' => $params['deal'],
                'adminid' => $this->auth->id,
                'auth' => $params['auth'],
                'money' => $params['money'],
                'province' => $params['province'],
                'city' => $params['city'],
                'district' => $params['district'],
                'avatar' => $params['avatar'],
            ];

            //判断是否有修改密码
            $password = empty($params['password']) ? '' : trim($params['password']);

            if (!empty($password)) {
                // 密码加密
                $salt = build_randstr();
                $data['password'] = md5($password . $salt);
                $data['salt'] = $salt;
            }

            $result = $this->model->validate("common/Business/Business")->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                //判断是否有上传新图片 
                if ($data['avatar'] != $row['avatar']) {
                    //不相等就说明有换图片了，就删除掉旧图片
                    @is_file("." . $row['avatar']) && @unlink("." . $row['avatar']);
                }

                $this->success();
                exit;
            }
        }

        $this->view->assign('row', $row);
        $this->view->assign('deallist', build_select('row[deal]', model('Business.Business')->getDealList(), $row['deal'], ['class' => 'form-control selectpicker']));
        $this->view->assign('genderlist', build_select('row[gender]', model('Business.Business')->getGenderList(), $row['gender'], ['class' => 'form-control selectpicker']));
        $this->view->assign('authlist', build_select('row[auth]', model('Business.Business')->getAuthList(), $row['auth'], ['class' => 'form-control selectpicker']));
        $this->view->assign('sourcelist', build_select('row[sourceid]', model('Business.Source')->column('id,name'), $row['sourceid'], ['class' => 'form-control selectpicker']));

        return $this->view->fetch();
    }
}
