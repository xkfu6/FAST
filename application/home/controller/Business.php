<?php

namespace app\home\controller;

// 前台公共控制器
use app\common\controller\Home;
use app\common\library\Email;

class Business extends Home
{
    public function __construct()
    {
        parent::__construct();
        $this->BusinessModel = model('common/Business/Business');
        $this->RegionModel = model('common/Region');
        $this->EMSModel = model('common/Ems');
        $this->OrderModel = model('Subject.Order');
        $this->CategoryModel = model('Subject.Category');
        $this->SubjectModel = model('Subject.Subject');
        $this->CollectionModel = model('common/Business/Collection');
        $this->CollevtionModel = model('common/Business/Collevtion');
        $this->FollowModel = model('Subject.Teacher.Follow');
        $this->PayModel = model('common/Pay/Pay');
        $this->RecordModel = model('Business.Record');
    }
    // 我的
    public function index()
    {
        return $this->view->fetch();
    }
    // 联系我们
    public function contact()
    {
        //模板渲染显示
        return $this->view->fetch();
    }
    // 我的收藏
    public function collection()
    {

        if ($this->request->isAjax()) {
            $pare = $this->request->param('page', 1, 'trim');
            $limit = 10; //每页显示的个数
            $start = ($pare - 1) * $limit; //分页起始位置
            $busid = cookie('busid');
            $count = $this->CollectionModel->where(['busid' => $busid])->count();
            $list = $this->SubjectModel->with(['collection', 'teacher', 'category'])->where(['collection.busid' => $busid])->limit($start, $limit)->select();
            if ($list) {
                $this->success('查询成功', null, ['list' => $list, 'count' => $count]);
            } else {
                $this->error('暂无数据');
            }
        }
        return $this->view->fetch();
    }
    //我的收藏里取消收藏操作
    public function delcollevtion()
    {
        if ($this->request->isAjax()) {
            $subid = $this->request->param('subid', 0, 'trim');
            if (!$subid) {
                $this->error('课程不存在');
                exit;
            }
            $login = $this->Islogin(false);

            if (!$login) {
                $this->error('请登录');
                exit;
            }
            $date = [
                'collectid' => $subid,
                'busid' => $login['id'],
                'status' => 'subject'
            ];
            // 查询是否收藏了
            $collevtion = $this->CollevtionModel->where($date)->find();
            if (!$collevtion) {
                $this->error('该课程没有被收藏');
                exit;
            }

            $result = $this->CollevtionModel->where(['collectid' => $subid])->delete();
            $msg = $result === FALSE ? "取消收藏失败" : "取消收藏成功";

            if ($result === FALSE) {
                $this->error($msg, null);
                exit;
            } else {
                $this->success($msg, null);
                exit;
            }
        }
    }
    // 基本资料
    public function profile()
    {
        if ($this->request->isPost()) {
            // 获取部分不需要操作的数据
            $nickname = $this->request->param('nickname', '', 'trim');
            $mobile = $this->request->param('mobile', '', 'trim');
            $email = $this->request->param('email', '', 'trim');
            $gender = $this->request->param('gender', '0', 'trim');
            $password = $this->request->param('passwird', '', 'trim');
            $code = $this->request->param('code', '', 'trim');
            // 组装一部风数据
            $data = [
                'id' => $this->view->AutoLogin['id'],
                'nickname' => $nickname,
                'mobile' => $mobile,
                'email' => $email,
                'gender' => $gender
            ];
            if (!empty($password)) {
                // 组装密码输出
                $salt = build_randstr();
                $password = md5($password . $salt);
                $data['password'] = $password;
            }

            if ($email != $this->view->AutoLogin['email']) {
                $data['auth'] = 0;
            }

            // 地区码
            if (!empty($code)) {

                $parentpar =  model('common/Region')->where(['code' => $code])->value('parentpath');
                if (!empty($parentpar)) {
                    $arr = explode(',', $parentpar);
                    $data['province'] = isset($arr[0]) ? $arr[0] : '';
                    $data['city'] = isset($arr[1]) ? $arr[1] : '';
                    $data['district'] = isset($arr[2]) ? $arr[2] : '';
                }
            }

            // 图片
            if (isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
                $upload = build_upload('avatar');
                if ($upload['data']) {
                    $data['avatar'] = $upload['data'];
                } else {
                    $this->error('上传图片失败');
                }
            }
            // 修改数据
            $rel = $this->BusinessModel->validate('common/Business/Business')->isUpdate(true)->save($data);
            if ($rel === FALSE) {
                //让模型来返回错误信息
                $this->error($this->BusinessModel->getError());
                exit;
            }
            if ($this->view->AutoLogin['email'] != $email) {
                $data = [
                    'id' => $this->view->AutoLogin['id'],
                    'auth' => '0'
                ];
                $rest = $this->BusinessModel->isUpdate(true)->save($data);
            }
            if (isset($data['avatar'])) {
                @is_file("." . $this->view->AutoLogin['avatar']) && @unlink("." . $this->view->AutoLogin['avatar']);
            }

            $this->success('修改成功', url('home/business/index'));
            exit;
        }



        return $this->view->fetch();
    }
    // 邮箱验证
    public function email()
    {
        if ($this->request->isAjax()) {
            // 开启事务
            $yzm = build_randstr(4);
            $this->EMSModel->startTrans();
            $dleoutyzm = $this->EMSModel->where(['email' => $this->view->AutoLogin['email']])->delete();
            if ($dleoutyzm === FALSE) {
                $this->error('删除旧验证码失败');
                exit;
            }
            $data = [
                'email' => $email = $this->view->AutoLogin['email'],
                'code' => $yzm
            ];
            $addyzm = $this->EMSModel->save($data);
            if ($addyzm === FAlSE) {
                $this->error('添加验证码失败');
                exit;
            }

            $email = new Email();

            $html = "<b>【Fast】云课堂邮箱验证：$yzm</b>";
            $result = $email
                ->to($this->view->AutoLogin['email'])
                ->subject('【Fast云平台】邮件验证')
                ->message($html)
                ->send();
            if (!$result) {
                $this->error('邮件发送失败');
            }
            // 提交事务
            $this->EMSModel->commit();
            $this->success('邮箱验证发送成功');
            exit;
        }

        if ($this->request->isPOST()) {
            $isyzm =  $this->request->param('code', '', 'trim');
            $email = $this->view->AutoLogin['email'];
            $yzm = $this->EMSModel->where(['email' => $email])->find();
            $id = $this->view->AutoLogin['id'];
            if ($yzm['code'] == $isyzm && $yzm['createtime'] < $yzm['createtime'] + 3600 * 24) {
                $data = [
                    'id' => $id,
                    'auth' => "1"
                ];
                $rest = $this->BusinessModel->isUpdate(true)->save($data);
                $this->success('邮箱认证成功', url('home/business/index'));
                exit;
            } else {
                $this->error('邮箱认证失败');
            }
            exit;
        }
        return $this->view->fetch();
    }

    //我的课程
    public function order()
    {
        if ($this->request->isAjax()) {
            $pare = $this->request->param('page', 1, 'trim');
            $limit = 10; //每页显示的个数
            $start = ($pare - 1) * $limit; //分页起始位置
            $busid = cookie('busid');
            $count = $this->OrderModel->where(['busid' => $busid])->count();
            $list = $this->OrderModel
                ->with(['subject', 'comment'])
                ->where(['order.busid' => $busid])
                ->limit($start, $limit)
                ->select();
            // var_dump(collection($list)->toArray());
            foreach ($list as $key => $item) {
                $categoryid = $item['subject']['cateid'];
                $category = $this->CategoryModel->where(['id' => $categoryid])->find();
                $list[$key]['category'] = $category;
            }
            if ($list) {
                $this->success('返回数据成功', null, ['list' => $list, 'count' => $count]);
            } else {
                $this->error('暂无数据');
            }
        }
        return $this->view->fetch();
    }
    // 我的课程评价
    public function comment()
    {
        $orderid = $this->request->param('orderid', 0, 'trim');

        $order = $this->OrderModel->with(['subject', 'comment'])->find($orderid);
        if (!$order) {
            $this->error('订单不存在');
            exit;
        }
        if ($this->request->isPOST()) {
            $rate = $this->request->param('rate', 5, 'trim');
            $content = $this->request->param('content', '', 'trim');
            $data = [
                'busid' => $this->view->AutoLogin['id'],
                'subid' => $order['subid'],
                'rate' => $rate,
                'content' => $content,
                'orderid' => $order['id']
            ];
            $result = $this->CommentModel->validate('common/Subject/Comment')->save($data);
            if ($result === FALSE) {
                $this->error($this->CommentModel->getError());
                exit;
            } else {
                $this->success('评论成功', url('home/business/order'));
                exit;
            }
        }
        $this->assign('order', $order);
        return $this->view->fetch();
    }
    //我的关注
    public function concern()
    {
        if ($this->request->isAjax()) {
            $pare = $this->request->param('page', 1, 'trim');
            $limit = 10; //每页显示的个数
            $keywords = $this->request->param('keywords', '', 'trim');
            $start = ($pare - 1) * $limit; //分页起始位置
            $where = ['follow.busid' => $this->view->AutoLogin['id']];
            if (!empty($keywords)) {
                // nickname LIKE "%$keywords%" OR mobile LIKE "%$keywords%"
                $where['teacher.name'] = ['LIKE', "%$keywords%"];
            }
            $count = $this->FollowModel
                ->with('teacher')
                ->where($where)
                ->count();
            $list = $this->FollowModel
                ->with('teacher')
                ->where($where)
                ->limit($start, $limit)
                ->select();
            if ($list) {
                $this->success('返回榜单数据成功', null, ['list' => $list, 'count' => $count]);
                exit;
            } else {
                $this->error('暂无数据');
            }
        }

        return $this->fetch();
    }
    //余额
    public function recharge()
    {
        $business = $this->BusinessModel->find($this->view->AutoLogin['id']);
        if ($this->request->isPOST()) {
            $total = $this->request->param('money', 0, 'trim');
            $pay = $this->request->param('type', '', 'trim');
            if (!$total) {
                $this->error('输入金额不对');
                exit;
            }
            if (!$pay) {
                $this->error('支付方式出错');
            }

            // 购买操作
            // 微信 / 支付宝

            //携带一个自定义的参数过去
            $third = [
                'busid' => $this->view->AutoLogin['id'],
            ];

            //组装参数
            $data = [
                'name' => '余额充值', //标题
                'third' => $third, //传递的第三方的参数
                'total' => $total, //订单原价充值的价格
                'type' => $pay, //支付方式
                'cashier' => 1, //是否需要收银台界面
                'jump' => "/home/business/recharge.html", //订单支付完成后跳转的界面
                'notice' => '/home/pay/subject',  //异步回调地址
            ];

            //调用模型中的支付方法
            $result = $this->PayModel->payment($data);

        }
        $this->assign([
            'business' => $business
        ]);
        return $this->view->fetch();
    }
    // 消费记录
    public function record()
    {
        if ($this->request->isAjax()) {
            $pare = $this->request->param('page', 1, 'trim');
            $limit = 10; //每页显示的个数
            $keywords = $this->request->param('keywords', '', 'trim');
            $start = ($pare - 1) * $limit; //分页起始位置
            $where = ['busid' => $this->view->AutoLogin['id']];
            if (!empty($keywords)) {
                // nickname LIKE "%$keywords%" OR mobile LIKE "%$keywords%"
                $where['content'] = ['LIKE', "%$keywords%"];
            }
            $count = $this->RecordModel
                ->where($where)
                ->count();
            $list = $this->RecordModel
                ->where($where)
                ->limit($start, $limit)
                ->select();
            if ($list) {
                $this->success('返回榜单数据成功', null, ['list' => $list, 'count' => $count]);
                exit;
            } else {
                $this->error('暂无数据');
            }
        }
        return $this->view->fetch();
    }


}
