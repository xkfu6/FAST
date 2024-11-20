<?php

namespace app\home\controller;

use app\common\controller\Home;

class Team extends Home
{
    public function __construct()
    {
        parent::__construct();
        $this->CommissionModel = model('common/Business/Commission');
        $this->BusinessModel = model('common/Business/Business');
    }
    // 推广视图
    public function poster()
    {
        $id = trim($this->view->AutoLogin['id']);
        $mobile = trim($this->view->AutoLogin['mobile']);
        $tabk = md5($id . $mobile);
        $link = url('home/index/login', ['tame' => $tabk], true, true);
        $poster = trim(config('site.poster'), '/'); //底图
        $avatar = trim($this->view->AutoLogin['avatar_text'], '/');
        $img = $this->view->AutoLogin;
        if ($this->request->isAjax()) {
            //生成海报
            $qrcode = new \dh2y\qrcode\QRcode();

            $level = ['L', 'M'];
            $index = array_rand($level);
            // 生成二维码
            $Qrcode = $qrcode->png($link, false, 16, $level[$index])
                ->logo($avatar)
                ->getPath();

            $Qrcode = trim($Qrcode, '\/');
            // 做海报，创建图片的实例
            $PosterIMG = imagecreatefromstring(file_get_contents($poster)); //底图
            $AvatarIMG = imagecreatefromstring(file_get_contents($avatar)); //头像
            $QrcodeIMG = imagecreatefromstring(file_get_contents($Qrcode)); //二维码
            // 头像缩略图
            // 创建一个新的头像并设置宽和高
            // imagecreatetruecolor(宽，高)
            $AvatarSmall = imagecreatetruecolor(100, 100);
            imagecopyresampled($AvatarSmall, $AvatarIMG, 0, 0, 0, 0, 100, 100, imagesx($AvatarIMG), imagesy($AvatarIMG));
            // imagecopyresampled($AvatarThumb, $AvatarImg, 0, 0, 0, 0, 100, 100, imagesx($AvatarImg), imagesy($AvatarImg));
            // 获取到二维码图片的宽高
            // list(接受指定图片宽的变量名, 接受指定图片高的变量名) = getimagesize(图像);
            list($QrcodeW, $QrcodeH) = getimagesize($Qrcode);

            // 拼装把要拼到底图的放上去
            imagecopymerge($PosterIMG, $QrcodeIMG, 50, 250, 0, 0, $QrcodeW, $QrcodeH, 100);
            imagecopymerge($PosterIMG, $AvatarSmall, 50, 40, 0, 0, 100, 100, 100);

            // 添加字体
            // imagecolorallocate
            $black = imagecolorallocate($PosterIMG, 0, 0, 0);
            imagettftext($PosterIMG, 20, 0, 180, 70, $black, 'D:\phpstudy_pro\WWW\xm\fast\public\assets\fonts\captcha.ttf', $this->view->AutoLogin['nickname']);

            // 保存合并好的图片路径
            $PosterUrl = "uploads/qrcode/{$tabk}.jpg";
            $relt = imagejpeg($PosterIMG, $PosterUrl);

            // 释放资源
            is_file($Qrcode) && @unlink($Qrcode);
            imagedestroy($PosterIMG);
            imagedestroy($AvatarIMG);
            imagedestroy($QrcodeIMG);
            imagedestroy($AvatarSmall);

            if (!$relt) {
                $this->error('海报上传错误');
                exit;
            }


            $data = [
                'id' => $this->view->AutoLogin['id'],
                'poster' => "/" . $PosterUrl

            ];

            // 更新
            $result = $this->businessModel->isUpdate(true)->save($data);
            if ($result === FALSE) {
                //将保留下来二维码海报删除掉
                @is_file($PosterUrl) && @unlink($PosterUrl);
                $this->error('更新海报失败');
                exit;
            } else {
                //跟头像一样的道理，将旧的删掉
                $this->success('更新海报成功', null, "/" . $PosterUrl);
                exit;
            }
        }
        $this->assign('link', $link);
        return $this->view->fetch();
    }
    // 我的团队视图
    public function index()
    {

        if ($this->request->isAjax()) {
            $page = $this->request->param('page', 1, 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $limit = 10; //每页显示的个数
            $start = ($page - 1) * $limit; //分页的起始位置
            $where = ['parentid' => $this->view->AutoLogin['id']];

            if (!empty($keywords)) {
                $where['nickname|mobile'] = ['LIKE', "%$keywords%"];
            }
            //查总条数
            $count = $this->businessModel
                ->where($where)
                ->count();

            //会员列表
            $list = $this->businessModel
                ->field(['id', 'nickname', 'mobile', 'createtime']) //field定义要查询的字段
                ->where($where)
                ->order('id', 'desc')
                ->limit($start, $limit) //限制查询的次数
                ->select();

            if ($list) {
                $this->success('查询成功', null, ['list' => $list, 'count' => $count]);
            } else {
                $this->error('没有更多的数据');
                exit;
            }
        }
        return $this->view->fetch();
    }
    // 提现
    public function money()
    {
        if ($this->request->isAjax()) {

            $page = $this->request->param('page', 1, 'trim');
            $cid = $this->request->param('cid', 0, 'trim');
            $limit = 10;
            $start = ($page - 1) * $limit;
            $where = ['parentid' => $this->view->AutoLogin['id']];
            $where2 = ['commission.parentid' => $this->view->AutoLogin['id']];
            if ($cid == 1) {
                // $where['status'] = 0;
                $where2['status'] = 1;
            } elseif ($cid == 2) {
                // $where['status'] = 1;
                $where2['status'] = 0;
            }
            $count = $this->CommissionModel
                ->with(['business', 'order', 'parentid'])
                ->where($where2)
                ->count();

            $list = $this->CommissionModel
                ->with(['business', 'order', 'parentid'])
                ->where($where2)
                ->limit($start, $limit)
                ->select();
            if ($list) {
                foreach ($list as $item) {
                    $subid = isset($item['order']['subid']) ? trim($item['order']['subid']) : 0;
                    $item['subject'] = $this->SubjectModel->where(['id' => $subid])->value('title');
                    $item['thumbs'] = $this->SubjectModel->where(['id' => $subid])->find();
                }
            }
            if ($list) {
                $this->success('查询成功', null, ['list' => $list, 'count' => $count]);
                exit;
            } else {
                $this->error('没有更多数据');
                exit;
            }
        }
        return $this->view->fetch();
    }
    public function gmoney()
    {
        if ($this->request->isAjax()) {;
            $amount = $this->request->param('amount', 0, 'trim');
            $money = $this->request->param('money', 0, 'trim');
            $id = $this->request->param('id', 0, 'trim');
            if ($amount == 0) {
                $this->error('金额数量不能为0');
            }
            $add = $money + $amount;

            $this->CommissionModel->startTrans();
            $this->BusinessModel->startTrans();


            $Commissionstatus = $this->CommissionModel
                ->where(['id' => $id])
                ->update(['status' => '1']);

            if ($Commissionstatus === FALSE) {
                $this->error('佣金表更新失败');
                exit;
            }


            $Businessstatus = $this->BusinessModel
                ->where(['id' => $this->view->AutoLogin['id']])
                ->update(['money' => $add]);
            if ($Businessstatus === FALSE) {
                $this->CommissionModel->rollback();
                $this->error('更新余额失败');
                exit;
            }
            if ($Commissionstatus === FALSE || $Businessstatus === FALSE) {
                $this->Businessstatus->rollback();
                $this->CommissionModel->rollback();
                $this->success('提现失败');
            } else {
                $this->CommissionModel->commit();
                $this->BusinessModel->commit();
                $this->success('提现成功');
            }
        }
    }
}
