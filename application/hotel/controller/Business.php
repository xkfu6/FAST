<?php

namespace app\hotel\controller;

use think\Controller;

// 引入FastAdmin自带的一个邮箱发送类
use app\common\library\Email;

/**
 * 用户接口
 */
class Business extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');

        $this->PayModel = model('Pay.Pay');

        $this->ReceiveModel = model('Coupon.Receive');
    }

    //注册登录
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
                $sourceid = model('Business.Source')->where(['name' => ['LIKE', "%酒店预订%"]])->value('id');

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
                $token = $this->request->param('token', '', 'trim');

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
                    $this->success('注册登录成功', '/business', $business);
                    exit;
                }
            }
        }
    }

    //判断登录用户信息是否有效
    public function check()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', '0', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');

            //查询
            $business = $this->BusinessModel->where(['id' => $id, 'mobile' => $mobile])->find();

            if ($business) {
                unset($business['password']);
                unset($business['salt']);

                $this->success("用户验证成功", null, $business);
                exit;
            } else {
                $this->error('用户不存在', '/business/login');
                exit;
            }
        }
    }

    //修改个人资料
    public function profile()
    {
        //判断是否有Post过来数据
        if ($this->request->isPost()) {
            //可以一次性接收到全部数据
            $id = $this->request->param('id', 0, 'trim');
            $nickname = $this->request->param('nickname', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');
            $email = $this->request->param('email', '', 'trim');
            $gender = $this->request->param('gender', '0', 'trim');
            $code = $this->request->param('code', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            //判断用户是否存在
            $business = $this->BusinessModel->find($id);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            // 直接组装数据
            $data = [
                'id' => $business['id'], //因为我们要执行更新语句
                'nickname' => $nickname,
                'mobile' => $mobile,
                'gender' => $gender,
            ];

            //如果密码不为空 修改密码
            if (!empty($password)) {
                //重新生成一份密码盐
                $salt = build_randstr();

                $data['salt'] = $salt;
                $data['password'] = md5($password . $salt);
            }

            //判断是否修改了邮箱 输入的邮箱 不等于 数据库存入的邮箱
            //如果邮箱改变，需要重新认证
            if ($email != $business['email']) {
                $data['email'] = $email;
                $data['auth'] = '0';
            }

            //判断是否有地区数据
            if (!empty($code)) {
                //查询省市区的地区码出来
                $parent = model('Region')->where(['code' => $code])->value('parentpath');

                if (!empty($parent)) {
                    $arr = explode(',', $parent);
                    $data['province'] = isset($arr[0]) ? $arr[0] : null;
                    $data['city'] = isset($arr[1]) ? $arr[1] : null;
                    $data['district'] = isset($arr[2]) ? $arr[2] : null;
                }
            }

            //判断是否有图片上传
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
                $success = build_upload('avatar');

                //如果上传失败，就提醒
                if (!$success['code']) {
                    $this->error($success['msg']);
                    exit;
                }

                //如果上传成功
                $data['avatar'] = $success['data'];
            }

            //执行更新语句 数据验证 -> 需要用到验证器

            //这是插入语句
            // $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

            //更新语句 如果是更新语句，需要给data提供一个主键id的值 这就是更新语句 使用验证器的场景
            $result = $this->BusinessModel->validate('common/Business/Business')->isUpdate(true)->save($data);

            if ($result === FALSE) {
                $this->error($this->BusinessModel->getError());
                exit;
            }

            //判断是否有旧图片，如果有就删除
            if (isset($data['avatar'])) {
                is_file("." . $business['avatar']) && @unlink("." . $business['avatar']);
            }

            $business = $this->BusinessModel->find($id);

            $this->success('更新资料成功', null, $business);
            exit;
        }
    }

    //邮箱认证
    public function email()
    {
        if ($this->request->isPost()) {
            //加载模型
            $EmsModel = model('common/Ems');

            //接收用户id
            $id = $this->request->param('id', 0, 'trim');

            $business = $this->BusinessModel->find($id);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            //获取用户信息
            $email = empty($business['email']) ? '' : trim($business['email']);

            if (empty($email)) {
                $this->error('邮箱地址为空');
                exit;
            }

            $action = $this->request->param('action', '', 'trim');

            //发送验证码
            if ($action == "send") {
                //生成一个验证码
                $code = build_ranstr(5);

                //开启事务
                $EmsModel->startTrans();

                //删除掉之前旧的验证码
                $EmsModel->where(['email' => $email])->delete();

                //把验证码插入到数据库表中
                $data = [
                    'event' => 'auth',
                    'email' => $email,
                    'code' => $code,
                    'times' => 0,
                ];

                //插入数据
                $ems = $EmsModel->save($data);

                if ($ems === FALSE) {
                    $this->error('邮件插入失败');
                    exit;
                }

                //邮件主题
                $name = config('site.name');
                $subject = "【{$name}】邮箱验证";

                //组装文字信息
                $message = "<div>感谢您的使用，您的邮箱验证码为：<b>$code</b></div>";

                //实例化邮箱验证类
                $PhpMailer = new Email;

                //邮箱发送有规律，不可以发送关键词
                $result = $PhpMailer
                    ->to($email)
                    ->subject($subject)
                    ->message($message)
                    ->send();

                //检测邮箱发送成功还是失败
                if ($result) {
                    //发送验证码成功
                    //将事务提交，提交的意思就是让刚刚插入的记录真实存在到数据表中
                    $EmsModel->commit();
                    $this->success('邮件发送成功，请注意查收');
                    exit;
                } else {
                    //将刚才插入成功的验证码记录要撤销回滚
                    $EmsModel->rollback();
                    $this->error($PhpMailer->getError());
                    exit;
                }
            } else {
                $code = $this->request->param('code', '', 'trim');

                if (empty($code)) {
                    $this->error('验证码不能为空');
                    exit;
                }

                //开启事务
                $EmsModel->startTrans();
                $this->BusinessModel->startTrans();

                //查询这个验证码是否存在
                $where = ['email' => $email, 'code' => $code];
                $check = $EmsModel->where($where)->find();

                //如果没找到记录
                if (!$check) {
                    $this->error('您输入的验证码有误，请重新输入');
                    exit;
                }

                // 1、更新用户表  2、删除验证码记录

                //组装数据
                $data = [
                    'id' => $business['id'],
                    'auth' => '1'
                ];

                //执行
                $BusinessStatus = $this->BusinessModel->isUpdate(true)->save($data);

                if ($BusinessStatus === FALSE) {
                    $this->error('用户邮箱认证状态修改失败');
                    exit;
                }

                //第二条 删除验证码记录
                $EmsStatus = $EmsModel->where($where)->delete();

                if ($EmsStatus === FALSE) {
                    //先要将用户表的更新进行回滚
                    $this->BusinessModel->rollback();
                    $this->error('验证码记录删除失败');
                    exit;
                }

                if ($BusinessStatus === FALSE || $EmsStatus === FALSE) {
                    $EmsModel->rollback();
                    $this->BusinessModel->rollback();
                    $this->error('验证失败');
                    exit;
                } else {
                    //提交事务
                    $this->BusinessModel->commit();
                    $EmsModel->commit();
                    $this->success('邮箱验证成功');
                    exit;
                }
            }
        }
    }

    //充值
    public function pay()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $money = $this->request->param('money', 1, 'trim');
            $type = $this->request->param('type', 'wx', 'trim');


            //判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            if ($money <= 0) {
                $this->error('充值金额不能小于0元');
                exit;
            }


            //发送一个接口请求出去
            $host = config('site.cdnurl');
            $host = trim($host, '/');

            //完整的请求接口地址
            $api = $host . "/pay/index/create";

            //订单支付完成后跳转的界面
            $reurl = "http://shop.xdlayman.cn/#/business/pay";

            $callbackurl = $host . "/shop/business/callback";

            //携带一个自定义的参数过去 转换为json类型
            $third = json_encode(['busid' => $busid]);

            //微信收款码
            $wxcode = config('site.wxcode');
            $wxcode = $host . $wxcode;

            //支付宝收款码
            $zfbcode = config('site.zfbcode');
            $zfbcode = $host . $zfbcode;

            //充值信息
            $PayData = [
                'name' => '余额充值',
                'third' => $third,
                'originalprice' => $money,
                //微信支付
                // 'paytype' => 0,
                // 'paypage' => 1,
                //支付宝支付
                // 'paytype' => 1,
                // 'paypage' => 2,
                'paypage' => 0,
                'wxcode' => $wxcode,
                'zfbcode' => $zfbcode,
                'reurl' => $reurl,
                'callbackurl' => $callbackurl,
            ];

            //要看是哪一种支付方式
            if ($type == 'wx') {
                //微信
                $PayData['paytype'] = 0;
            } else {
                //支付宝
                $PayData['paytype'] = 1;
            }

            //发起请求
            $result = httpRequest($api, $PayData);

            //有错误
            if (isset($result['code']) && $result['code'] == 0) {
                $this->error($result['msg']);
                exit;
            }

            //将json转换为php数组
            $result = json_decode($result, true);

            $this->success('生成付款码', null, $result['data']);
            exit;
        }
    }

    //查询订单是否成功
    public function query()
    {
        if ($this->request->isPost()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $payid = $this->request->param('payid', '', 'trim');

            //判断用户是否存在
            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            if (empty($payid)) {
                $this->error('支付记录不存在');
                exit;
            }

            //发送一个接口请求出去
            $host = config('site.cdnurl');
            $host = trim($host, '/');

            //完整的请求接口地址
            $api = $host . "/pay/index/status";

            //发起请求
            $result = httpRequest($api, ['payid' => $payid]);

            //将json转换为php数组
            $result = json_decode($result, true);

            if (isset($result['code']) && $result['code'] == 0) {
                $this->error($result['msg']);
                exit;
            } else {
                $status = isset($result['data']['status']) ? $result['data']['status'] : 0;
                $this->success('查询充值状态', null, ['status' => $status]);
                exit;
            }
        }
    }

    //充值回调
    public function callback()
    {
        // 判断是否有post请求过来
        if ($this->request->isPost()) {
            // 获取到所有的数据
            $params = $this->request->param();

            // 充值的金额
            $price = isset($params['price']) ? $params['price'] : 0;
            $price = floatval($price);

            // 第三方参数(可多参数)
            $third = isset($params['third']) ? $params['third'] : '';

            // json字符串转换数组
            $third = json_decode($third, true);

            // 从数组获取充值的用户id
            $busid = isset($third['busid']) ? $third['busid'] : 0;

            // 支付方式
            $paytype = isset($params['paytype']) ? $params['paytype'] : 0;

            // 支付订单id
            $payid = isset($params['id']) ? $params['id'] : 0;

            $pay = $this->PayModel->find($payid);

            if (!$pay) {
                return json(['code' => 0, 'msg' => '支付订单不存在', 'data' => null]);
            }

            $payment = '';

            switch ($paytype) {
                case 0:
                    $payment = '微信支付';
                    break;
                case 1:
                    $payment = '支付宝支付';
                    break;
            }

            //判断充值金额
            if ($price <= 0) {
                return json(['code' => 0, 'msg' => '充值金额为0', 'data' => null]);
            }

            // 加载模型
            $BusinessModel = model('Business.Business');
            $RecordModel = model('Business.Record');

            $business = $BusinessModel->find($busid);

            if (!$business) {
                return json(['code' => 0, 'msg' => '充值用户不存在', 'data' => null]);
            }

            // 开启事务
            $BusinessModel->startTrans();
            $RecordModel->startTrans();

            // 转成浮点类型
            $money = floatval($business['money']);

            // 余额 + 充值的金额
            $updateMoney = bcadd($money, $price, 2);

            // 封装用户更新的数据
            $BusinessData = [
                'id' => $business['id'],
                'money' => $updateMoney
            ];

            // 自定义验证器
            $validate = [
                [
                    'money' => ['number', '>=:0'],
                ],
                [
                    'money.number' => '余额必须是数字类型',
                    'money.>=' => '余额必须大于等于0元'
                ]
            ];

            $BusinessStatus = $BusinessModel->validate(...$validate)->isUpdate(true)->save($BusinessData);

            if ($BusinessStatus === false) {
                return json(['code' => 0, 'msg' => $BusinessModel->getError(), 'data' => null]);
            }

            // 封装插入消费记录的数据
            $RecordData = [
                'total' => $price,
                'content' => "{$payment}充值了 $price 元",
                'busid' => $business['id']
            ];

            // 插入
            $RecordStatus = $RecordModel->validate('common/Business/Record')->save($RecordData);

            if ($RecordStatus === false) {
                $BusinessModel->rollback();
                return json(['code' => 0, 'msg' => $RecordModel->getError(), 'data' => null]);
            }

            if ($BusinessStatus === false || $RecordStatus === false) {
                $BusinessModel->rollback();
                $RecordModel->rollback();
                return json(['code' => 0, 'msg' => '充值失败', 'data' => null]);
            } else {
                $BusinessModel->commit();
                $RecordModel->commit();

                // 订单号：\r\n
                // 金额:50元
                // 支付方式：
                // 时间，
                return json(['code' => 1, 'msg' => '充值成功', 'data' => null]);
            }
        }
    }

    //优惠券
    public function coupon()
    {
        if ($this->request->isPost()) {
            $active = $this->request->param('active', 'all', 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $page = $this->request->param('page', 1, 'trim');

            $business = $this->BusinessModel->find($busid);

            if (!$business) {
                $this->error('用户不存在');
                exit;
            }

            $limit = 20;
            $start = ($page - 1) * $limit;

            $where = ['busid' => $busid];

            if ($active != "all") {
                $where['receive.status'] = $active;
            }

            $list = $this->ReceiveModel
                ->with(['coupon'])
                ->where($where)
                ->order('receive.createtime desc')
                ->limit($start, $limit)
                ->select();

            if ($list) {
                $this->success('返回优惠券', null, $list);
                exit;
            } else {
                $this->error('暂无领取记录');
                exit;
            }
        }
    }
}
