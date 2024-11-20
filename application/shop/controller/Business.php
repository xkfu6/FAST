<?php

namespace app\shop\controller;

use think\Controller;

// 引入FastAdmin自带的一个邮箱发送类
use app\common\library\Email;

class Business extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->EmsModel = model('Ems');
        $this->RecordModel = model('Business.Record');
        $this->ProductModel = model('Product.Product');
        $this->ReceiveModel = model('Coupon.Receive');
        $this->PayModel = model('common/Pay/Pay');
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $product = $this->ProductModel->where(['flag' => '3'])->limit(4)->select();
            if ($product) {
                $this->success("热销商品", null, $product);
                exit;
            } else {
                $this->error('暂无热销商品');
                exit;
            }
        }
    }

    public function login()
    {
        // 判断是否为POST请求
        if ($this->request->isPost()) {
            // 接收参数
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            // 手机号是否为空
            if (empty($mobile)) {
                $this->error('请填写手机号');
                exit;
            }

            // 密码是否为空
            if (empty($password)) {
                $this->error('请填写密码');
                exit;
            }

            $business = $this->BusinessModel->where(['mobile' => $mobile])->find();

            // 通过该手机号查找客户是否存在，如果存在就登录，不存在就注册
            if ($business) {
                // 业务走到这里说明该手机号是已经注册过了

                // 获取查询出来的密码盐
                $salt = $business['salt'];

                // 拼接客户输入的密码和查询出来的密码盐
                $repass = md5($password . $salt);

                // 加密后密码跟查询出来的密码不一致，说明密码错误
                if ($repass != $business['password']) {
                    $this->error('密码错误');
                    exit;
                }

                // 删除不需要的信息
                unset($business['password']);
                unset($business['salt']);

                // 业务走到这里说明登录成功了，需要把客户信息返回前端
                $this->success('登录成功', '/business', $business);
                exit;
            } else {
                // 业务走到这里说明该手机号是未注册
                // 生成密码盐
                $salt = build_randstr(10);

                // 密码和密码盐拼接并且md5加密
                $repass = md5($password . $salt);

                // 查询客户来源
                $sourceid = model('Business.Source')->where(['name' => ['LIKE', "%家居商城%"]])->value('id');

                // 组装数据
                $data = [
                    'mobile' => $mobile,
                    'nickname' => build_encrypt($mobile, 3, 4), //脱敏显示
                    'salt' => $salt,
                    'password' => $repass,
                    'sourceid' => $sourceid,
                    'gender' => '0',
                    'deal' => '0',
                    'money' => '0',
                    'auth' => '0',
                ];

                // 判断是否有推荐人
                $token = $this->request->param('tabk', '', 'trim');
                if (!empty($token)) {
                    // md5(id+mobile)
                    $list = $this->BusinessModel->column('id,mobile');

                    foreach ($list as $key => $item) {
                        //找到推荐人
                        if ($token == md5($key . $item)) {
                            $data['parentid'] = $key;
                        }
                    }
                }

                // 把组装好的数据插入数据表 validate 验证器 成功返回插入的条数 失败返回false
                $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

                if ($result === FALSE) {
                    // 返回错误信息给前端
                    $this->error($this->BusinessModel->getError());
                    exit;
                } else {
                    // 通过自增ID获取刚注册的客户信息
                    $business = $this->BusinessModel->find($this->BusinessModel->id);

                    // 删除不需要的信息
                    unset($business['password']);
                    unset($business['salt']);

                    // 业务走到这里说明登录成功了，需要把客户信息返回前端
                    $this->success('注册成功', '/business', $business);
                    exit;
                }
            }
        }
    }

    public function check()
    {
        // 判断是否为POST请求
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            //查询用户是否存在
            $where = [
                'id' => $id,
                'mobile' => $mobile
            ];

            // 根据条件查询用户是否存在
            $business = $this->BusinessModel->where($where)->find();

            if (!$business) {
                $this->error('用户不存在');
                exit;
            } else {
                // 删除不需要的信息
                unset($business['password']);
                unset($business['salt']);

                $this->success('用户存在，检查登录成功', null, $business);
                exit;
            }
        }
    }

    public function profile()
    {
        if ($this->request->isPost()) {
            //接收参数
            $id = $this->request->param('id', '', 'trim');
            $nickname = $this->request->param('nickname', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');
            $gender = $this->request->param('gender', '0', 'trim');
            $email = $this->request->param('email', '', 'trim');
            $password = $this->request->param('password', '', 'trim');
            $code = $this->request->param('code', '', 'trim');

            // 先判断用户是否存在
            $business = $this->BusinessModel->find($id);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 组装数据
            $data = [
                'id' => $id,
                'nickname' => $nickname,
                'mobile' => $mobile,
                'gender' => $gender,
            ];

            // 判断是否有修改邮箱，如果修改了邮箱需要重新验证
            if ($business['email'] != $email) {
                $data['email'] = $email;
                $data['auth'] = '0';
            }

            // 判断是否有修改密码
            if (!empty($password)) {
                // 重新生成密码盐
                $salt = build_randstr(10);

                // 密码加密
                $password = md5($password . $salt);

                // 把新的密码和密码盐追加到组装数据里
                $data['salt'] = $salt;
                $data['password'] = $password;
            }

            // 判断是否有选择地区
            if (!empty($code)) {
                $parent = model('Region')->where(['code' => $code])->value('parentpath');

                if (empty($parent)) {
                    $this->error('所选地区不存在');
                    exit;
                }

                $list = explode(',', $parent);

                $data['province'] = isset($list[0]) ? $list[0] : null;
                $data['city'] = isset($list[1]) ? $list[1] : null;
                $data['district'] = isset($list[2]) ? $list[2] : null;
            }

            //判断是否有上传头像
            if (isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
                $avatar = build_upload('avatar');

                if ($avatar['code'] === 0) {
                    $this->error($avatar['msg']);
                    exit;
                }

                $data['avatar'] = $avatar['data'];
            }

            // 更新数据
            $result = $this->BusinessModel->validate('common/Business/Business')->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error($this->BusinessModel->getError());
                exit;
            }

            // 判断是否上传新头像，删除旧头像
            if (isset($data['avatar']) && $_FILES['avatar']['size'] > 0) {
                @is_file('.' . $business['avatar']) && @unlink('.' . $business['avatar']);
            }

            $this->success('修改个人资料成功');
            exit;
        }
    }

    public function email()
    {
        if ($this->request->isPost()) {
            // 接收参数
            $busid = $this->request->param('busid', 0, 'trim');
            $email = $this->request->param('email', '', 'trim');
            $action = $this->request->param('action', '', 'trim');

            // 邮箱是否为空
            if (empty($email)) {
                $this->error('邮箱地址为空，请先去基本资料填写邮箱');
                exit;
            }

            // 查询用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 是否已验证
            if ($business['auth'] == 1) {
                $this->error('您已验证，无须重复验证');
                exit;
            }

            // 查询邮箱是否真实存在
            $EmailWhere = [
                'id' => $busid,
                'email' => $email
            ];

            $receiver = $this->BusinessModel->where($emailWhere)->value('email');

            if (empty($receiver)) {
                $this->error('邮箱地址不存在');
                exit;
            }

            if ($action === 'send') {
                // 生成验证码
                $code = build_randstr(5);

                // 开启事务
                $this->EmsModel->startTrans();

                // 删除旧的验证码
                $DeleteStatus = $this->EmsModel->where(['email' => $receiver, 'event' => 'auth'])->delete();

                if ($DeleteStatus === FALSE) {
                    $this->error('删除过期验证码失败');
                    exit;
                }

                // 组装插入邮件验证码表的数据
                $data = [
                    'event' => 'auth',
                    'email' => $receiver,
                    'code' => $code,
                    'times' => 0,
                    'ip' => $this->request->ip()
                ];

                // 插入验证码
                $AddStatus = $this->EmsModel->validate('common/Business/Ems')->save($data);

                if ($AddStatus === FALSE) {
                    // 回滚操作
                    $this->EmsModel->rollback();
                    $this->error($this->EmsModel->getError());
                    exit;
                }

                // 邮件主题
                $subject = "邮箱身份验证-" . config('site.name');

                // 邮件内容
                $message = "<div>验证码为：<b>$code</b> 有限期：24小时，过期需重新发送</div>";

                // 实例化邮箱发送类
                $Mail = new Email;

                // 发送
                $result = $Mail
                    ->to($receiver)
                    ->subject($subject)
                    ->message($message)
                    ->send();

                if ($result === FALSE) {
                    // 回滚操作
                    $this->EmsModel->rollback();
                    $this->error($Mail->getError());
                    exit;
                } else {
                    //提交事务，让前面执行的动作生效
                    $this->EmsModel->commit();
                    $this->success('发送邮件成功，请注意查收');
                    exit;
                }
            }

            if ($action === 'check') {
                $vercode = $this->request->param('vercode', '', 'trim');

                if (empty($vercode)) {
                    $this->error('验证码为空');
                    exit;
                }

                // 组装查询条件
                $where = [
                    'event' => 'auth',
                    'email' => $receiver,
                    'code' => $vercode,
                ];

                $ems = $this->EmsModel->where($where)->find();

                if (!$ems) {
                    $this->error('您所输入的验证码不存在');
                    exit;
                }

                // 超过了24小时
                if (time() > strtotime($ems['createtime']) + 3600 * 24) {
                    $this->error('验证码已失效');
                    exit;
                }

                // 验证成功的话，就删除验证码 更新 business auth认证字段 1
                // Ems 删除 business 更新 开启事务

                // 开启事务
                $this->EmsModel->startTrans();
                $this->BusinessModel->startTrans();

                // 删除验证码
                $EmsStatus = $this->EmsModel->destroy($ems['id']);

                if ($EmsStatus === FALSE) {
                    $this->error('验证码删除失败');
                    exit;
                }

                // 组装更新用户数据
                $data = [
                    'id' => $busid,
                    'auth' => '1'
                ];

                // 更新用户数据
                $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($data);

                if ($BusinessStatus === FALSE) {
                    $this->EmsModel->rollback();
                    $this->error($this->BusinessModel->getError());
                    exit;
                }

                if ($EmsStatus === FALSE || $BusinessStatus === FALSE) {
                    // 回滚
                    $this->BusinessModel->rollback();
                    $this->EmsModel->rollback();
                    $this->error('更新认证失败');
                    exit;
                } else {
                    // 提交事务
                    $this->EmsModel->commit();
                    $this->BusinessModel->commit();
                    $this->success('更新认证状态成功');
                    exit;
                }
            }
        }
    }

    // 消费记录
    public function record()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $page = $this->request->param('page', 1, 'trim');
            $limit = $this->request->param('limit', 10, 'trim');
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 条件数组
            $where = [
                'busid' => $busid
            ];

            // 根据条件获取数据总条数
            $count = $this->RecordModel->where($where)->count();

            // 根据条件查询数据
            $list = $this->RecordModel->where($where)->page($page, $limit)->order('createtime desc')->select();

            // 组装返回前端数据
            $data = [
                'list' => $list,
                'count' => $count
            ];

            if ($list) {
                $this->success('查询数据成功', null, $data);
                exit;
            } else {
                $this->error('暂无消费记录');
                exit;
            }
        }
    }

    // 优惠卷
    public function coupon()
    {
        if ($this->request->isPOST()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            //要找出当前这个人所领取的优惠券
            $coupon = $this->ReceiveModel->with(['coupon'])->where(['busid' => $busid, 'receive.status' => '1'])->select();

            if ($coupon) {
                $this->success('返回优惠券', null, $coupon);
                exit;
            } else {
                $this->error('无优惠券信息');
                exit;
            }
        }
    }

    // 余额充值
    public function pay()
    {
        if ($this->request->isPOST()) {
            $money = $this->request->param('money', 0, 'trim');
            $pay = $this->request->param('pay', '', 'trim');
            $busid = $this->request->param('busid', '', 'trim');
            // 携带一个自定义的参数过去
            $third = [
                'busid' => $busid,
            ];
            // 组装参数
            $data = [
                'name' => '余额充值',
                'total' => $money,
                'type' => $pay,
                'third' => $third,
                'cashier' => 0,
                'jump' => "/#",
                'notice' => '/shop/pay/create',
            ];
            //调用模型中的支付方法
            $result = $this->PayModel->payment($data);
            if ($result['code']) {
                $this->success('创建订单成功', '/cart/cashier', $result['data']);
            }
        }
    }
}
