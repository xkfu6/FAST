<?php

namespace app\hotel\controller;

use think\Controller;

// 住客信息
class Guest extends Controller
{
    public function __construct()
    {
        parent::__construct();   

        $this->BusinessModel = model('Business.Business');

        $this->model = model('Hotel.Guest');

        //判断当前用户是否存在
        $this->busid = $this->request->param('busid', 0, 'trim');
        
        $this->business = $this->BusinessModel->find($this->busid);

        if(!$this->business)
        {
            $this->error('用户不存在');
            exit;
        }
    }

    public function index()
    {
        if($this->request->isPost())
        {
            $page = $this->request->param('page', 1, 'trim');
            $limit = 20;
            $start = ($page-1)*$limit;

            $list = $this->model
                ->where(['busid'=>$this->busid])
                ->order('id desc')
                ->limit($start, $limit)
                ->select();

            if($list)
            {
                $this->success('住客信息', null, $list);
                exit;
            }else
            {
                $this->error('暂无住客信息');
                exit;
            }


        }
        
    }

    public function add()
    {
        if($this->request->isPost())
        {
            //获取所有的参数
            $params = $this->request->param();

            $result = $this->model->save($params);

            if($result === FALSE)
            {
                $this->error('添加住客信息失败');
                exit;
            }else
            {
                $this->success('添加成功');
                exit;
            }
        }
    }

    public function info()
    {
        if($this->request->isPost())
        {
            $id = $this->request->param('id', 0, 'trim');

            $guest = $this->model->find($id);

            if($guest === FALSE)
            {
                $this->error('暂无住客信息');
                exit;
            }else
            {
                $this->success('返回住客信息', null, $guest);
                exit;
            }
        }
    }

    public function edit()
    {
        if($this->request->isPost())
        {
            //获取所有的参数
            $params = $this->request->param();
            $id = $this->request->param('id', 0, 'trim');

            //先判断住客信息是否存在
            $guest = $this->model->find($id);

            if(!$guest)
            {
                $this->error('住客信息不存在');
                exit;
            }

            $result = $this->model->isUpdate(true)->save($params);

            if($result === FALSE)
            {
                $this->error('编辑住客信息失败');
                exit;
            }else
            {
                $this->success('编辑成功');
                exit;
            }
        }
    }

    public function del()
    {
        if($this->request->isPost())
        {
            $id = $this->request->param('id', 0, 'trim');

            $guest = $this->model->find($id);

            if($guest === FALSE)
            {
                $this->error('暂无住客信息');
                exit;
            }

            $result = $this->model->where(['id'=>$id])->delete();

            if($result === FALSE)
            {
                $this->error('删除住客信息失败');
                exit;
            }else
            {
                $this->success('删除住客信息成功');
                exit;
            }
        }
    }
}
