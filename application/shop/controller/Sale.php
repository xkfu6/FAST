<?php

namespace app\shop\controller;

use think\Controller;

class Sale extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->BusinessModel = model('common/Business/Business');
        $this->CommissionModel = model('Business.Commission');
        $this->ProductModel = model('Product.Product');
        $this->RecordModel = model('Business.Record');
    }

    // 分销海报页面
    public function index()
    {
        $id = trim($this->request->param('id'));
        $mobile = trim($this->request->param('mobile'));
        // echo $id;
        // exit;
        $where = [
            'id' => $id,
            'mobile' => $mobile
        ];
        // 根据条件查询用户是否存在
        $business = $this->BusinessModel->where($where)->find();
        if (!$business) {
            $this->error('用户不存在');
            exit;
        }
        $tabk = md5($id . $mobile);
        $link = url('/business/login?tabk=' . $tabk, null, false, false);
        $business['link'] = trim($link, '/');
        $this->success('加密成功', null, $business);
    }

    // 分销会员
    public function business()
    {
        if ($this->request->isPOST()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $page = $this->request->param('page', 1, 'trim');
            $limit = 10;
            //偏移量
            $offset = ($page - 1) * $limit;
            if (empty($busid)) {
                $this->error('请先登录');
                exit;
            }
            $sale = $this->BusinessModel->limit($offset, $limit)->where(['parentid' => $busid, 'sourceid' => '2'])->select();
            if ($sale) {
                $this->success('查询成功', null, $sale);
                exit;
            } else {
                $this->error('查询失败');
                exit;
            }
        }
    }
    // 重新生成海报
    public function saleadd()
    {
        if ($this->request->isPOST()) {
            $busid = trim($this->request->param('busid'));
            $mobile = trim($this->request->param('mobile'));
            $avatar = trim($this->request->param('avatar'));
            $nickname = trim($this->request->param('nickname'));
            $myurl = trim($this->request->param('url'));

            $tabk = md5($busid . $mobile);
            $link = $myurl . '#/business/login?tabk=' . $tabk;
            $poster = trim(config('site.poster'), '/');
            $avatar = trim($avatar, '/');
            // echo $link;
            // exit;

            //生成海报
            $qrcode = new \dh2y\qrcode\QRcode();

            // 随机获得二维码密度
            $level = ['L', 'M'];

            $index = array_rand($level);
            // 生成二维码
            $Qrcode = $qrcode->png($link, false, 15, $level[$index])
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
            imagecopymerge($PosterIMG, $QrcodeIMG, 100, 250, 0, 0, $QrcodeW, $QrcodeH, 100);
            imagecopymerge($PosterIMG, $AvatarSmall, 50, 40, 0, 0, 100, 100, 100);

            // 添加字体
            // imagecolorallocate
            $black = imagecolorallocate($PosterIMG, 0, 0, 0);
            imagettftext($PosterIMG, 20, 0, 180, 70, $black, 'D:\phpstudy_pro\WWW\xm\fast\public\assets\fonts\captcha.ttf', $nickname);

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
                'id' => $busid,
                'poster' => "/" . $PosterUrl
            ];
            // 更新
            $result = $this->BusinessModel->isUpdate(true)->save($data);
            $avatar_text = $this->BusinessModel->find($busid);
            $avatar_text['link'] = trim($link, '/');
            if ($result === FALSE) {
                //将保留下来二维码海报删除掉
                @is_file($PosterUrl) && @unlink($PosterUrl);
                $this->error('更新海报失败');
                exit;
            } else {
                //跟头像一样的道理，将旧的删掉
                $this->success('更新海报成功', null, $avatar_text);
                exit;
            }
        }
    }

    // 提现页面
    public function money()
    {
        if ($this->request->isPOST()) {
            $parentid = $this->request->param('busid', 0, 'trim');
            $usemoney = 0;
            $nomoney = 0;
            $page = $this->request->param('page', 1, 'trim');
            $limit = 10;
            //偏移量
            $offset = ($page - 1) * $limit;
            if (!$parentid) {
                $this->error('用户不存在');
                exit;
            }
            $result = $this->CommissionModel->with(['sporder', 'orderProduct'])->limit($offset, $limit)->where(['parentid' => $parentid, 'type' => 'product'])->select(); //'type' => 'product'

            $usemoney = $this->CommissionModel->limit($offset, $limit)->where(['parentid' => $parentid, 'status' => '1'])->sum('amount'); //'type' => 'product'
            $nomoney = $this->CommissionModel->limit($offset, $limit)->where(['parentid' => $parentid, 'status' => '0'])->sum('amount'); //'type' => 'product'


            foreach ($result as $item) {
                // 商品信息
                $productid = $item['order_product']['proid'];
                $product = $this->ProductModel->limit($offset, $limit)->where(['id' => $productid])->find();
                $item['product'] = $product['name'];
                $item['pr_thumbs'] = $product['thumbs_text'];
            }
            $data = [
                'coupon' => $result,
                'usemoney' => $usemoney,
                'nomoney' => $nomoney
            ];
            if ($result) {
                $this->success('查询成功', null, $data);
                exit;
            } else {
                $this->error('没有数据');
                exit;
            }
        }
    }

    public function extract()
    {
        if ($this->request->isPOST()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $total = $this->CommissionModel->where(['parentid' => $busid, 'status' => '0'])->sum('amount');
            $oldmoney = $this->BusinessModel->where(['id' => $busid])->value('money');
            $newmoney = bcadd($total, $oldmoney, 2);

            $data = [
                'total' => "+" . $newmoney,
                'content' => '佣金提现' . $newmoney . '元',
                'busid' => $busid
            ];
            // 事务开启
            $this->CommissionModel->startTrans(); //startTrans
            $this->BusinessModel->startTrans();
            $this->RecordModel->startTrans();

            $CommissionStatus = $this->CommissionModel->where(['parentid' => $busid])->update(['status' => '1']);

            if ($CommissionStatus === FALSE) {
                $this->error($this->CommissionModel->getError());
                exit;
            }

            $BusinessStatus = $this->BusinessModel->where(['id' => $busid])->update(['money' => $newmoney]);

            if ($BusinessStatus === FALSE) {
                $this->CommissionModel->rollback();
                $this->error($this->BusinessModle->getError());
                exit;
            }


            $RecordStatus = $this->RecordModel->save($data);
            if ($RecordStatus === FALSE) {
                $this->BusinessModle->rollback();
                $this->CommissionModel->rollback();
                $this->error($this->RecordModel->getError());
                exit;
            }

            if ($CommissionStatus === FALSE || $BusinessStatus === FALSE || $RecordStatus === FALSE) {
                $this->RecordModel->rollback();
                $this->BusinessModle->rollback();
                $this->CommissionModel->rollback();
                $this->error('提现失败');
                exit;
            } else {
                $this->CommissionModel->commit();
                $this->BusinessModel->commit();
                $this->RecordModel->commit();
                $this->success('提现成功');
                exit;
            }
        }
    }
}
