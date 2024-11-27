<?php

namespace app\home\controller;

use app\common\controller\Home;
use PhpOffice\PhpSpreadsheet\Reader\Xls\MD5;

class Index extends Home
{
    public $NoLogin = ['*'];

    public function __construct()
    {

        // 调用父类的构造
        parent::__construct();

        $this->BusinessModel = model('common/Business/Business');
        $this->SourceModel = model('common/Business/Source');
        $this->SubjectModel = model('common/Subject/Subject');
        $this->TeacherModel = model('common/Subject/Teacher/Teacher');
        $this->OrderModel = model('common/Subject/Order');
        $this->CategoryModel = model('common/Subject/Category');
        $this->CommentModel = model('common/Subject/Comment');

        $this->LiveModel = model('Live.Live');
        $this->LiveProductModel = model('Live.Product');
    }

    // 主页
    public function index()
    {
        // 课程推荐
        $subject = $this->SubjectModel->group('cateid')->limit(6)->select();
        // 教师推荐
        $teacher = $this->TeacherModel->limit(6)->select();

        $this->assign([
            'teacher' => $teacher,
            'subject' => $subject
        ]);
        //模板渲染显示
        return $this->view->fetch();
    }
    // 课程表单
    public function ranking()
    {
        if ($this->request->isAjax()) {
            $pare = $this->request->param('page', 1, 'trim');
            $limit = 10; //每页显示的个数
            $start = ($pare - 1) * $limit; //分页起始位置
            $field = ['count(subid)' => 'total'];
            // 查询条数
            $count = $this->OrderModel
                ->field($field)
                ->group('subid')
                ->select();
            $count = count($count);


            // 查询课程数据
            $list = $this->OrderModel
                ->with(['subject'])
                ->field($field)
                ->group('subid')
                ->order('total', 'del')
                ->limit($start, $limit)
                ->select();
            if ($list) {
                foreach ($list as $item) {
                    $cateid = isset($item['subject']['cateid']) ? trim($item['subject']['cateid']) : 0;
                    $teacherid = isset($item['subject']['teacherid']) ? trim($item['subject']['teacherid']) : 0;
                    $item['cate'] = $this->CategoryModel->where(['id' => $cateid])->value('name');
                    $item['teacher'] = $this->TeacherModel->where(['id' => $teacherid])->value('name');
                }
            }

            // var_dump(collection($list)->toArray());

            if ($list) {
                $this->success('返回榜单数据成功', null, ['list' => $list, 'count' => $count]);
                exit;
            } else {
                $this->error('暂无数据');
            }
            exit;
        }
        return $this->view->fetch();
    }
    // 登录和注册
    public function login()
    {
        //判断一下是否已经登录了
        $busid = cookie('busid') ? cookie('busid') : 0;
        $mobile = cookie('mobile') ? cookie('mobile') : '';
        $parentid = NUll;
        $tame = $this->request->param('tame', '', 'trim');
        !empty($tame) && cookie('tame', $tame, 86400);
        $redurl = \think\Cookie::get('redurl', 'back_') ? \think\Cookie::get('redurl', 'back_') : url('home/business/index');
        // 100 1888
        if ($busid && $mobile) {
            //调用判断登录的方法
            $result = $this->IsLogin(false);

            //有cookie而且还能找到用户
            if ($result) {
                $this->success('您已登录无需重复登录', url('home/business/index'));
                exit;
            }
        }

        if ($this->request->isPost()) {
            $mobile = $this->request->param('mobile', '', 'trim');
            $password = $this->request->param('password', '', 'trim');

            $tame = cookie('tame');
            if (!empty($tame)) {
                $business_user = $this->BusinessModel->select();
                foreach ($business_user as $item) {
                    $istame = md5($item['id'] . $item['mobile']);
                    if ($istame == $tame) {
                        $parentid = $item['id'];
                    }
                }
                if (empty($parentid)) {
                    $this->error('邀请链接有误');
                }
            }
            if (empty($mobile)) {
                $this->error('请填写手机号');
                exit;
            }
            if (empty($password)) {
                $this->error('请填写密码');
                exit;
            }
            $resl = $this->BusinessModel->where(['mobile' => $mobile])->find();

            if ($resl) {
                $salt = $resl['salt'];
                $password = md5($password . $salt);
                if ($resl['password'] != $password) {
                    $this->error('密码不正确');
                    exit;
                }
                cookie('busid', $resl['id']);
                cookie('mobile', $resl['mobile']);
                $this->success('登录成功', $redurl);
                exit;
            } else {
                $salt = build_randstr();
                $password = md5($password . $salt);
                $sourceid = $this->SourceModel->where(['name' => ['LIKE', "%云课堂%"]])->value('id');
                $pata = [
                    'mobile' => $mobile,
                    'nickname' => build_encrypt($mobile),
                    'password' => $password,
                    'salt' => $salt,
                    'gender' => 0,
                    'sourceid' => $sourceid,
                    'deal' => '0',
                    'money' => '0',
                    'auth' => '0',
                    'parentid' => $parentid
                ];
                // $result = $User->validate('Member')->save($data);    
                // 调用验证器 
                $result = $this->BusinessModel->validate('common/Business/Business')->save($pata);
                if (false === $result) {
                    // 验证失败 输出错误信息
                    $this->error($this->BusinessModel->getError());
                    exit;
                } else {
                    //存储cookie
                    cookie('busid', $this->BusinessModel->id); //模型会自动返回最新插入的ID
                    cookie('mobile', $mobile);
                    $this->success('注册并登陆成功', $redurl);
                    exit;
                }
            }
        }

        return $this->view->fetch();
    }
    public function comment_list()
    {
        if ($this->request->isAjax()) {
            $page = $this->request->param('page', 1, 'trim');
            $limit = 10;
            $start = ($page - 1) * $limit;
            // echo $start;
            // 条数
            $count = $this->CommentModel->count();
            // 查询数据
            $list = $this->CommentModel
                ->with(['business', 'subject'])
                ->limit($start, $limit)
                ->select();

            if ($list) {
                $this->success('查询成功', null, ['list' => $list, 'count' => $count, 'page' => $page, 'start' => $start]);
                exit;
            } else {
                $this->error('暂时没有更多数据');
                exit;
            }
            exit;
        }
        return $this->view->fetch();
    }
    // 退出
    public function outlogin()
    {
        cookie(null, 'fa_');
        $this->redirect(url('home/index/login'));
    }

    //课程直播
    public function live()
    {
        //查找正在直播的记录
        $live = $this->LiveModel->where(['status' => '1'])->find();

        if (!$live) {
            $this->error('暂无在线直播');
            exit;
        }

        //直播中关联热卖的商品
        $product = $this->LiveProductModel->with(['subjects', 'products'])->where(['liveid' => $live['id'],'type'=>'subject'])->select();

        $this->assign([
            'live' => $live,
            'product' => $product
        ]);
        return $this->view->fetch();
    }
}
