<?php

namespace app\home\controller;

use app\common\controller\Home;

class Teacher extends Home
{
    public $NoLogin = ['index', 'teacher'];

    protected $TeacherModel = null;
    protected $SubjectModel = null;
    protected $FollowModel = null;

    public function __construct()
    {
        parent::__construct();

        $this->TeacherModel = model('Subject.Teacher.Teacher');
        $this->FollowModel = model('Subject.Teacher.Follow');
        $this->SubjectModel = model('Subject.Subject');
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            $pare = $this->request->param('page', 1, 'trim');
            $limit = 10; //每页显示的个数
            $keywords = $this->request->param('keywords', '', 'trim');
            $start = ($pare - 1) * $limit; //分页起始位置
            $where = [];
            if (!empty($keywords)) {
                // nickname LIKE "%$keywords%" OR mobile LIKE "%$keywords%"
                $where['name'] = ['LIKE', "%$keywords%"];
            }
            $count = $this->TeacherModel
                ->where($where)
                ->count();
            $list = $this->TeacherModel
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
    public function teacher()
    {
        $teacherid = $this->request->param('teacherid', 0, 'trim');

        $teacher = $this->TeacherModel->find($teacherid);

        // if ($teacherid === 0) {
        //     $this->error('暂无该老师信息');
        // }

        // 关注状态
        $teacher['follow_status'] = false;

        $login = $this->IsLogin(false);

        if ($login) {
            $check = model('Subject.Teacher.Follow')->where(['teacherid' => $teacherid, 'busid' => $login['id']])->find() ? true : false;

            $teacher['follow_status'] = $check;
        }
        if ($this->request->isAjax()) {
            $keyword = $this->request->param('keyword', '', 'trim');
            $page = $this->request->param('page', 1, 'trim');
            $teacherid = $_SERVER['HTTP_REFERER'];
            $teacherid = ltrim($teacherid, "http://www.fast.com/home/teacher/teacher/teacher?teacherid=");
            $limit = 10;
            $start = ($page - 1) * $limit;
            $teacher = $this->TeacherModel
                ->find();
            $count = $this->SubjectModel
                ->where(['teacherid' => $teacher])
                ->count();
            $list = $this->SubjectModel
                ->with(['category'])
                ->where(['teacherid' => $teacherid])
                ->limit($start, $limit)
                ->select();
            if ($list) {
                $this->success('查询成功', null, ['list' => $list, 'count' => $count, 'teacher' => $teacher]);
                exit;
            } else {
                $this->error('暂时没有多数据');
            }
        }
        $this->assign('teacher', $teacher);
        return $this->fetch();
    }
    public function follow()
    {
        $follow = '';
        if ($this->request->isAjax()) {
            $teacherid = $this->request->param('teacherid', 0, 'trim');

            $busid = $this->view->AutoLogin['id'] ?: 0;

            $teacher = $this->TeacherModel->find($teacherid);

            if (!$teacher) {
                $this->error('该老师信息不存在');
            }

            $follow = $this->FollowModel->where(['teacherid' => $teacherid, 'busid' => $busid])->find();



            if ($follow) {
                $result = $this->FollowModel->destroy($follow['id']);
                $count = $this->FollowModel->where(['teacherid' => $teacherid])->count();
                $follow = '';
                $msg = '取消关注';
            } else {
                $result = $this->FollowModel->insert(['teacherid' => $teacherid, 'busid' => $busid]);
                $count = $this->FollowModel->where(['teacherid' => $teacherid])->count();
                $follow = 'ture';
                $msg = '关注';
            }

            if ($result === false) {
                $this->error("{$msg}失败");
            } else {
                $this->success("{$msg}成功", null, ['teacher_follow_status' => $follow, 'teacher' => $follow, 'count' => $count]);
            }
        }
        teacher();
    }
}
