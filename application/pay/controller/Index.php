<?php
namespace app\pay\controller;

use think\Controller;
use think\Log;

class Index extends Controller
{
    // 管理员模型
    protected $AdminModel = null;

    protected $PayModel = null;

    public function __construct()
    {
        parent::__construct();

        $this->AdminModel = model('Admin');

        $this->PayModel = model('Pay.Pay');
    }

    // 登录
    public function login()
    {
        // 判断请求类型是否为POST
        if ($this->request->isPost()) {
            // 接收参数
            $username = $this->request->param('username', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 根据用户名去查询数据表是否存在该用户
            $admin = $this->AdminModel->where(['username' => $username])->find();

            // 如果不存在的话就进入判断里
            if (!$admin) {
                $this->error('账号不存在');
            }

            // 匹配密码
            $password = md5(md5($password) . $admin['salt']);

            // 如果输入密码跟查询出来的密码对不上
            if ($password !== $admin['password']) {
                $this->error('密码错误');
            }

            if ($admin['status'] !== 'normal') {
                $this->error('账号已被禁用');
            }

            // 封装返回数据
            $data = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'nickname' => $admin['nickname'],
                'avatar_cdn' => $admin['avatar_text']
            ];

            $this->success('登录成功',null,$data);
        }
    }

    // 当客户端监听到收款通知后发起请求到这里获取相关的数据
    public function check()
    {
        if ($this->request->isPost()) {
            $price = $this->request->param('price', 0, 'trim');
            $adminid = $this->request->param('adminid', 0, 'trim');
            $paytime = $this->request->param('paytime', '', 'trim');

            // 查询账号是否存在
            $admin = $this->AdminModel->find($adminid);

            if (!$admin) 
            {
                $this->error('账号不存在');
                exit;
            }

            if ($admin['status'] !== 'normal') 
            {
                $this->error('账号已被禁用');
                exit;
            }

            // 查询订单
            $pay = $this->PayModel->where(['price' => $price, 'status' => 0])->find();

            if (!$pay) 
            {
                $this->error('查询不到该订单');
                exit;
            }

            // 日期格式转成时间戳
            $paytime = strtotime($paytime);

            // 封装更新数据
            $data = [
                'id' => $pay['id'],
                'status' => 1,
                'paytime' => $paytime
            ];

            // 更新数据表
            $result = $this->PayModel->isUpdate(true)->save($data);

            if ($result === FALSE) 
            {
                $this->error('更新订单状态失败');
                exit;
            }

            // 获取更新后的订单数据
            $pay = $this->PayModel->find($pay['id']);
            
            $this->success('查询成功', null, $pay);
            exit;
        }
    }

    // 创建支付订单
    public function create()
    {
        if($this->request->isPost())
        {
            // 订单原价
            $total = $this->request->param('total', 0, 'trim');
            $name = $this->request->param('name', '', 'trim');
            $third = $this->request->param('third', '', 'trim');
            $type = $this->request->param('type', 'zfb', 'trim');
            $cashier = $this->request->param('cashier', 0, 'trim');
            $jump = $this->request->param('jump', '', 'trim');
            $notice = $this->request->param('notice', '', 'trim');
            $wxcode = $this->request->param('wxcode', '', 'trim');
            $zfbcode = $this->request->param('zfbcode', '', 'trim');

            // 由于业务逻辑是通过支付金额确认订单，避免出现0
            if($total < 0)
            {
                // 写入错误日志
                Log::error('充值金额不小于1元');
                $this->error('充值金额不小于1元');
                exit;
            }

            // 查询支付表最后一次未支付记录
            $pay = $this->PayModel->where(['total' => $total, 'status' => 0])->order('id DESC')->find();

            // 获取最后一次支付的递减值
            $sub = empty($pay) ? 0.01 : bcadd(0.01, bcsub($pay['total'], $pay['price'], 2), 2);
            if($sub >= $total) $sub = 0;

            // 封装数据
            $data = [
                'code' => build_code('PAY'),
                'name' => $name,
                'third' => $third,
                'type' => $type,
                'total' => $total,
                'price' => bcsub($total, $sub, 2),
                'cashier' => $cashier,
                'jump' => $jump,
                'notice' => $notice,
                'wxcode' => $wxcode,
                'zfbcode' => $zfbcode,
                'status' => 0
            ];

            //插入支付订单记录
            $result = $this->PayModel->validate('common/Pay/Pay')->save($data);

            if($result === FALSE)
            {
                // 写入错误日志
                Log::error($this->PayModel->getError());
                $this->error($this->PayModel->getError());
                exit;
            }
            
            $pay = $this->PayModel->find($this->PayModel->id);

            if(isset($data['cashier']) && $data['cashier'] == "0")
            {
                return json(['code' => 1, 'msg' => '支付订单创建成功', 'data' => $pay]);
            }else
            {
                return $this->fetch('page', ['pay' => $pay]);
            }
        }
    }

    public function status()
    {
        if($this->request->isPost())
        {
            $payid = $this->request->param('payid', 0, 'trim');

            $pay = $this->PayModel->find($payid);

            if (!$pay) {
                // 写入日志
                Log::error('支付订单不存在');
                
                $this->error('支付订单不存在');
            }

            switch ($pay['status']) {
                case 0:
                    $this->error('订单未支付',null,$pay);
                    break;

                case 1:
                    $this->success('订单已支付',null,$pay);
                    break;

                case 2:
                    $this->error('订单已关闭',null,$pay);
                    break;
            }
        }
    }
}
