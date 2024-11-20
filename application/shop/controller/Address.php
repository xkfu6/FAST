<?php

namespace app\shop\controller;

use think\Controller;

class Address extends Controller
{
    public function __construct()
    {
        parent::__construct();

        // 实例化模型
        $this->BusinessModel = model('Business.Business');
        $this->AddressModel = model('Business.Address');

        // 接收用户ID
        $bsuid = $this->request->param('busid', 0, 'trim');

        // 查询用户
        $business = $this->BusinessModel->find($bsuid);

        if (!$business) {
            $this->error('用户不存在');
            exit;
        }

        $this->business = $business;
    }

    public function index()
    {
        if ($this->request->isPost()) {
            // 找出当前这个人的地址
            $AddressData = $this->AddressModel->where(['busid' => $this->business['id']])->select();

            if ($AddressData) {
                $this->success('查询收货地址', null, $AddressData);
                exit;
            } else {
                $this->error('暂无地址');
                exit;
            }
        }
    }

    public function add()
    {
        if ($this->request->isPost()) {
            // 接收全部参数
            $params = $this->request->param();

            // 获取地区码
            $code = isset($params['code']) ? trim($params['code']) : '';
            $busid = isset($params['busid']) ? trim($params['busid']) : '';

            // 判断是否有地区数据
            if (!empty($code)) {
                // 查询省市区的地区码出来
                $parent = model('Region')->where(['code' => $code])->value('parentpath');

                if (empty($parent)) {
                    $this->error('所选地区不存在');
                    exit;
                }

                // 转成数组
                $list = explode(',', $parent);

                $params['province'] = isset($list[0]) ? $list[0] : null;
                $params['city'] = isset($list[1]) ? $list[1] : null;
                $params['district'] = isset($list[2]) ? $list[2] : null;
            }

            // 开启事务
            $this->AddressModel->startTrans();

            $status = isset($params['status']) ? $params['status'] : 0;

            // 判断是否选择了默认收货地址
            if ($status == 1) {
                // 直接去更新覆盖，将已有的数据变成0
                $AddressStatus = $this->AddressModel->where(['busid' => $busid])->update(['status' => '0']);

                if ($AddressStatus === FALSE) {
                    $this->error('更新默认地址状态有误');
                    exit;
                }
            }

            // 插入数据
            $result = $this->AddressModel->validate('common/Business/Address')->save($params);

            if ($result === FALSE) {
                $this->AddressModel->rollback();
                $this->error($this->AddressModel->getError());
                exit;
            } else {
                $this->AddressModel->commit();
                $this->success('添加成功');
                exit;
            }
        }
    }

    public function info()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'trim');

            $where = [
                'id' => $id,
            ];

            $address = $this->AddressModel->where($where)->find();

            if ($address) {
                $this->success('返回收货地址', null, $address);
                exit;
            } else {
                $this->error('地址不存在');
                exit;
            }
        }
    }

    // 编辑收货地址
    public function edit()
    {
        if ($this->request->isPost()) {
            $params = $this->request->param();

            // 获取当前修改地址id
            $id = isset($params['id']) ? trim($params['id']) : 0;
            $busid = isset($params['busid']) ? trim($params['busid']) : 0;

            $where = [
                'id' => $id,
            ];

            $address = $this->AddressModel->where($where)->find();

            if (!$address) {
                $this->error('收货地址不存在');
                exit;
            }

            // 获取地区码
            $code = isset($params['code']) ? trim($params['code']) : '';

            // 判断是否有地区数据
            if (!empty($code)) {
                // 查询省市区的地区码出来
                $parent = model('Region')->where(['code' => $code])->value('parentpath');

                if (empty($parent)) {
                    $this->error('所选地区不存在');
                    exit;
                }

                // 转成数组
                $list = explode(',', $parent);

                $params['province'] = isset($list[0]) ? $list[0] : null;
                $params['city'] = isset($list[1]) ? $list[1] : null;
                $params['district'] = isset($list[2]) ? $list[2] : null;
            }

            // 开启事务
            $this->AddressModel->startTrans();

            $status = isset($params['status']) ? $params['status'] : 0;

            // 判断是否选择了默认收货地址
            if ($status == 1) {
                // 直接去更新覆盖，将已有的数据变成0
                $AddressStatus = $this->AddressModel->where(['busid' => $busid])->update(['status' => '0']);

                if ($AddressStatus === FALSE) {
                    $this->error('更新默认地址状态有误');
                    exit;
                }
            }

            // 编辑数据
            $result = $this->AddressModel->validate('common/Business/Address')->isUpdate(true)->save($params);

            if ($result === FALSE) {
                $this->AddressModel->rollback();
                $this->error($this->AddressModel->getError());
                exit;
            } else {
                $this->AddressModel->commit();
                $this->success('更新成功');
                exit;
            }
        }
    }

    // 删除收货地址
    public function del()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'trim');

            $where = [
                'id' => $id,
            ];

            $address = $this->AddressModel->where($where)->find();

            if (!$address) {
                $this->error('收货地址不存在');
                exit;
            }

            $result = $this->AddressModel->destroy($id);

            if ($result === FALSE) {
                $this->error('删除失败');
                exit;
            } else {
                $this->success('删除成功');
                exit;
            }
        }
    }

    // 切换默认地址
    public function check()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'trim');

            $where = [
                'id' => $id,
                'busid' => $this->business->id
            ];

            $address = $this->AddressModel->where($where)->find();

            if (!$address) {
                $this->error('收货地址不存在');
                exit;
            }

            if ($address['status'] == 1) {
                $this->error('已经是默认地址');
                exit;
            }

            // 先去更新默认地址
            $status = $this->AddressModel->where(['busid' => $this->business['id']])->update(['status' => '0']);

            if ($status === FALSE) {
                $this->error('取消默认地址失败');
                exit;
            }

            // 更新指定的默认地址
            $data = [
                'id' => $id,
                'status' => 1
            ];

            $result = $this->AddressModel->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error($this->AddressModel->getError());
                exit;
            } else {
                $this->success('更新默认地址成功');
                exit;
            }
        }
    }
    public function selected()
    {
        if ($this->request->isPost()) {
            // 找出当前这个人的地址
            $where = [
                'busid' => $this->business['id'],
                'status' => '1'
            ];

            $address = $this->AddressModel
                ->field('id,mobile as tel,consignee as name,province,city,district')
                ->where($where)
                ->find();

            if (!$address) {
                $where = ['busid' => $this->business['id']];

                $address = $this->AddressModel
                    ->field('id,mobile as tel,consignee as name')
                    ->where($where)
                    ->find();
            }
            if ($address) {
                $this->success('返回收获地址', '', $address);
                exit;
            } else {
                $this->error('无收获地址');
                exit;
            }
        }
    }
}
