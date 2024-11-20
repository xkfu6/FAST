<?php
// 公共控制器
namespace app\common\controller;

use think\Controller;


// 前台公共的控制器
class Home extends Controller
{
    //不需要登录的方法，如果为空就说明所有的都需要登录,*所有都不需要登录
    public $NoLogin = [];

    public function __construct()
    {
        parent::__construct();

        $this->businessModel = model('common/Business/Business');
        $this->SubjectModel = model('common/Subject/Subject');
        $this->TeacherModel = model('common/Subject/Teacher/Teacher');
        $this->FollowModel = model('common/Subject/Teacher/Follow');
        $this->OrderModel = model('common/Subject/Order');
        $this->CategoryModel = model('common/Subject/Category');
        $this->ChapterModel = model('common/Subject/Chapter');
        $this->CommentModel = model('common/Subject/Comment');
        // 控制当前访问的控制方法
        $action = $this->request->action();

        if (!in_array($action, $this->NoLogin) && !in_array("*", $this->NoLogin)) $this->IsLogin();
    }
    /**
     * 验证登录
     * @param boolean $redirect 是否跳转 true跳转 false不跳转
     * @param string $url 跳转地址
     * @return array business用户信息
     */
    public function Islogin($redirect = true, $url = '')
    {
        //判断一下是否已经登录了
        $busid = cookie('busid') ? cookie('busid') : 0;
        $mobile = cookie('mobile') ? cookie('mobile') : '';

        $url = empty($url) ? $this->request->server('HTTP_REFERER') : $url;
        cookie('redurl', $url, ['prefix' => 'back_']);
        if (!$busid || !$mobile) {
            cookie(null, 'fa_');
            if ($redirect) {
                $this->error('请先登录', url('home/index/login'));
                exit;
            } else {
                return null;
            }
        }
        $AutoLogin = $this->businessModel->where(['id' => $busid, 'mobile' => $mobile])->find();

        if (!$AutoLogin) {
            // 清除cookie
            cookie(null, 'fa_');
            if ($redirect) {
                $this->error('非法登录', url('home/index/login'));
                exit;
            } else {
                return null;
            }
        }
        // 数据传到视图

        $this->view->assign('AutoLogin', $AutoLogin);
        return $AutoLogin;
    }
}
