<?php

namespace app\work\controller;

use think\Controller;

//引入微信SDK
use EasyWeChat\Factory;

class Business extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('Business.Business');
        $this->ProductModel = model('Order.Product');

        //全局配置的微信的SDK 换成自己的
        $config = [
            'app_id' => config('site.WxAppid'), ///appid 
            'secret' => config('site.WxAppSecret'), //AppSecret
            'response_type' => 'array', //调用SDK接口返回什么类型数据(数组)

            //日志等级设置
            'log' => [
                'level' => 'debug',
                'file' => __DIR__ . '/wechat.log',
            ],
        ];

        //微信小程序SDK实例对象
        $this->app = Factory::miniProgram($config);
    }

    //授权登录的方法
    public function WxLogin()
    {
        if ($this->request->isPost()) {
            //获取临时凭证
            $code = $this->request->param('code', '', 'trim');

            if (empty($code)) {
                $this->error('临时凭证获取失败');
                exit;
            }

            //请求微信公众平台 换取openid 唯一标识符
            $wxauth = $this->app->auth->session($code);
            //获取openid 
            $openid = isset($wxauth['openid']) ? trim($wxauth['openid']) : '';

            if (empty($openid)) {
                $this->error('授权openid失败');
                exit;
            }

            //查找 openid 绑定的记录
            $BusinessInfo = $this->BusinessModel->where(['openid' => $openid])->find();

            if ($BusinessInfo) {
                //有绑定的账号
                $this->success('授权登录成功', null, $BusinessInfo);
                exit;
            } else {
                //没有绑定的账号
                $this->success('授权成功请绑定账号', null, ['action' => 'bind', 'openid' => $openid]);
                exit;
            }
        }
    }

    //微信绑定账号的方法
    public function WxBind()
    {
        if ($this->request->isPost()) {
            $mobile = $this->request->param("mobile", '', 'trim');
            $password = $this->request->param("password", '', 'trim');
            $openid = $this->request->param("openid", '', 'trim');

            if (empty($mobile)) {
                $this->error('手机号码不能为空');
                exit;
            }

            if (empty($password)) {
                $this->error('密码不能为空');
                exit;
            }

            if (empty($openid)) {
                $this->error('openid不能为空');
                exit;
            }

            $BusinessInfo = $this->BusinessModel->where(['mobile' => $mobile])->find();

            if ($BusinessInfo) {
                //能找到用户意味着 他在其他的平台上注册过账号，目前是首次来到小程序，更新用户openid
                $result = $this->BusinessModel->where(['id' => $BusinessInfo['id']])->update(['openid' => $openid]);

                if ($result === FALSE) {
                    $this->error('绑定账号失败');
                    exit;
                }

                //查询一遍最新的数据
                $BusinessInfo = $this->BusinessModel->find($BusinessInfo['id']);

                $this->success('绑定账号成功', null, $BusinessInfo);
                exit;
            } else {
                //没有找到用户，意味着 他在其他的平台上没有账号，首次来到小程序
                // Insert 插入语句 顺带将openid 也一并插入进去

                //生成一个密码盐
                $salt = build_randstr();

                //加密密码
                $password = md5($password . $salt);

                //组装数据
                $data = [
                    'openid' => $openid,
                    'mobile' => $mobile,
                    'nickname' => build_encrypt($mobile, 3, 4, '*'), //脱敏显示
                    'password' => $password,
                    'salt' => $salt,
                    'gender' => '0', //性别
                    'deal' => '0', //成交状态
                    'money' => '0', //余额
                    'auth' => '0', //邮箱认证
                ];

                //查询出防伪查询的渠道来源的ID信息 数据库查询
                $data['sourceid'] = model('common/Business/Source')->where(['name' => ['LIKE', "%防伪查询%"]])->value('id');

                //执行插入 返回自增的条数
                $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

                if ($result === FALSE) {
                    //失败
                    $this->error($this->BusinessModel->getError());
                    exit;
                } else {
                    //查询出当前插入的数据记录
                    $BusinessInfo = $this->BusinessModel->find($this->BusinessModel->id);

                    //注册
                    $this->success('注册成功', null, $BusinessInfo);
                    exit;
                }
            }
        }
    }

    // H5或者APP登陆的方法
    public function WebLogin()
    {
        if ($this->request->isPost()) {
            $mobile = $this->request->param("mobile", '', 'trim');
            $password = $this->request->param("password", '', 'trim');

            if (empty($mobile)) {
                $this->error('手机号码不能为空');
                exit;
            }

            if (empty($password)) {
                $this->error('密码不能为空');
                exit;
            }

            $BusinessInfo = $this->BusinessModel->where(['mobile' => $mobile])->find();

            if ($BusinessInfo) {
                //有找到用户,验证密码是否正确
                $password = md5($password . $BusinessInfo['salt']);


                //密码不正确
                if ($BusinessInfo['password'] != $password) {
                    $this->error('密码不正确');
                    exit;
                }

                $this->success('登录成功', null, $BusinessInfo);
                exit;
            } else {
                //没有找到用户，新增用户

                //生成一个密码盐
                $salt = build_randstr();

                //加密密码
                $password = md5($password . $salt);

                //组装数据
                $data = [
                    'mobile' => $mobile,
                    'nickname' => build_encrypt($mobile, 3, 4, '*'), //脱敏显示
                    'password' => $password,
                    'salt' => $salt,
                    'gender' => '0', //性别
                    'deal' => '0', //成交状态
                    'money' => '0', //余额
                    'auth' => '0', //邮箱认证
                ];

                //查询出防伪查询的渠道来源的ID信息 数据库查询
                $data['sourceid'] = model('common/Business/Source')->where(['name' => ['LIKE', "%防伪查询%"]])->value('id');

                //执行插入 返回自增的条数
                $result = $this->BusinessModel->validate('common/Business/Business')->save($data);

                if ($result === FALSE) {
                    //失败
                    $this->error($this->BusinessModel->getError());
                    exit;
                } else {
                    //查询出当前插入的数据记录
                    $BusinessInfo = $this->BusinessModel->find($this->BusinessModel->id);

                    //注册
                    $this->success('注册成功', null, $BusinessInfo);
                    exit;
                }
            }
        }
    }

    // 解除绑定账号的方法
    public function UnBind()
    {
        if ($this->request->isPost()) {
            $mobile = $this->request->param("mobile", '', 'trim');
            $id = $this->request->param("id", 0, 'trim');

            $BusinessInfo = $this->BusinessModel->where(['mobile' => $mobile, 'id' => $id])->find();

            if (!$BusinessInfo) {
                $this->error('用户不存在');
                exit;
            }

            //解除绑定
            $result = $this->BusinessModel->where(['id' => $id])->update(['openid' => NULL]);

            if ($result === FALSE) {
                $this->error('解除绑定失败');
                exit;
            } else {
                $this->success('解除账号绑定成功');
                exit;
            }
        }
    }

    // 防伪列表
    public function ProductIndex()
    {
        if ($this->request->isPost()) {
            $page = $this->request->param('page', 1, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $keyword = $this->request->param('keyword', '', 'trim');

            $where = ['busid' => $busid];

            if (!empty($keyword)) {
                $where['products.name'] = ['LIKE', "%$keyword%"];
            }

            $ProductData = $this->ProductModel->with('products')->where($where)->order('id desc')->page($page, 10)->select();

            if ($ProductData) {
                $this->success('返回列表', null, $ProductData);
                exit;
            } else {
                $this->error('暂无数据');
                exit;
            }
        }
    }

    // 防伪详情
    public function ProductInfo()
    {
        if ($this->request->isPost()) {
            $code = $this->request->param('code', '', 'trim');

            $ProductDetails = $this->ProductModel->with('products')->where(['query_code' => $code])->find();

            if (!$ProductDetails) {
                $this->error('未查询到防伪码数据');
                exit;
            }

            $query_num = bcadd($ProductDetails['query_num'], 1);

            $data = [
                'id' => $ProductDetails['id'],
                'query_num' => $query_num,
            ];

            if (empty($info['query_time'])) {
                // 首次查询防伪时间
                $data['query_time'] = time();
            }

            $result = $this->ProductModel->isUpdate()->save($data);

            if ($result === false) {
                $this->error('更新查询次数错误');
                exit;
            }

            $ProductDetails = $this->ProductModel->with('products')->where(['query_code' => $code])->find();

            $this->success('查询防伪码成功', null, $ProductDetails);
            exit;
        }
    }

    // 生成小程序码
    public function GenerateCode()
    {
        if ($this->request->isPost()) {
            $code = $this->request->param('code', '', 'trim');

            $ProductDetails = $this->ProductModel->with('products')->where(['query_code' => $code])->find();

            if (!$ProductDetails) {
                $this->error('未查询到防伪码数据');
                exit;
            }

            $data = [
                'id' => $ProductDetails['id']
            ];

            //调用EasyWechat实例对象(小程序) 来生成小程序码
            $path = "/pages/validate/info?code=$code";

            //返回生成小程序码的一个文件的二进制流
            $response = $this->app->app_code->get($path);

            // 保存小程序码到文件
            // 判断$response是否为instanceof \EasyWeChat\Kernel\Http\StreamResponse文件
            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {

                $filename = $response->saveAs('./uploads/qrcode/', "$code.png");

                if (!$filename) {
                    $this->error('生成防伪码失败');
                    exit;
                }

                $data['query_qrcord'] = "/uploads/qrcode/$filename";
            }

            $result = $this->ProductModel->isUpdate()->save($data);

            if ($result === false) {
                $this->error('更换防伪码失败');
                exit;
            }

            $ProductDetails = $this->ProductModel->with('products')->where(['query_code' => $code])->find();

            $this->success('更换防伪码成功', null, $ProductDetails);
            exit;
        }
    }
}
