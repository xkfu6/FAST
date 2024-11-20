<?php

namespace app\home\controller;

use app\common\controller\Home;

class Coupon extends Home
{
    public $NoLogin = ['index', 'info'];
    public function __construct()
    {
        // 调用父类的构造
        parent::__construct();
        $this->CouponModel = model('common/Coupon/Coupon');
        $this->ReceiveModel = model('common/Coupon/Receive');
        $this->RecordModel = model('common/Business/Record');
    }
    // 优惠卷列表
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = $this->request->param('page', 1, 'trim');

            $limit = 10;

            $start = ($page - 1) * $limit;
            $count = $this->CouponModel->where(['status' => '1'])->count();
            $list = $this->CouponModel
                ->where(['status' => '1'])
                ->limit($start, $limit)
                ->order('id', 'desc')
                ->select();
            if (!$list) {
                $this->error('暂无优惠卷');
                exit;
            }
            $login = $this->IsLogin(false);
            $busid = isset($login['id']) ? trim($login['id']) : 0;

            // 查询领取过那些
            $receive = $this->ReceiveModel
                ->where(['busid' => $busid])
                ->column('cid');

            foreach ($list as $item) {
                $item['receive'] = in_array($item['id'], $receive);
            }
            $this->success('优惠卷', null, ['count' => $count, 'list' => $list]);
            exit;
        }


        return $this->view->fetch();
    }
    // 优惠卷介绍
    public function info()
    {
        $cid = $this->request->param('cid');
        $coupon = $this->CouponModel->find($cid);
        if (!$coupon) {
            $this->error('优惠卷不存在');
        }
        $busid = cookie('busid');
        $busid = empty($busid) ? 0 : $busid;
        $but = $this->ReceiveModel->where(['cid' => $cid, 'busid' => $busid])->find();
        if ($but) {
            $isbut = 1;
        } else {
            $isbut = 0;
        }
        $this->assign([
            'coupon' => $coupon,
            'isbut' => $isbut
        ]);
        return $this->view->fetch();
    }
    // 我的优惠卷页面
    public function coupon_list()
    {

        if ($this->request->isAjax()) {
            $cid = $this->request->param('cid', 1, 'trim');
            $pare = $this->request->param('page', 1, 'trim');
            $time = time();
            $limit = 10; //每页显示的个数
            $keywords = $this->request->param('keywords', '', 'trim');
            $start = ($pare - 1) * $limit; //分页起始位置
            $where = ['busid' => $this->view->AutoLogin['id']];
            if ($cid == 1) {
                $where['receive.status'] = "1";
            } else if ($cid == 2) {
                $where['receive.status'] = "0";
            } else if ($cid == 3) {
                $where['coupon.endtime'] = ['<', $time];
                $where['receive.status'] = "0";
            }
            $count = $this->ReceiveModel->with('coupon')->where($where)->count();
            $list = $this->ReceiveModel
                ->with('coupon')
                ->where($where)
                ->limit($start, $limit)
                ->select();
            $url = url('/home/subject/index');
            foreach ($list as $item) {
                $item['url'] = $url;
                $item['urlcid'] = $item['cid'];
            }
            if ($list) {
                $this->success('返回榜单数据成功', null, ['list' => $list, 'count' => $count, 'url' => $url]);
                exit;
            } else {
                $this->error('暂无数据');
            }
        }
        return $this->view->fetch();
    }

    //优惠卷领取操作控制器
    public function receive()
    {
        $cid = $this->request->param('cid');

        $coupon = $this->CouponModel->find($cid);
        if (!$coupon) {
            $this->error('优惠卷不存在');
            exit;
        }

        // 判断是否登录
        $login = $this->IsLogin(true, url('home/coupon/index'));

        // 判断优惠卷是否能获取
        if ($coupon['total'] <= 0) {
            $this->error('优惠卷已经领空');
            exit;
        }
        // if ($coupon['status'] <= 0) {
        //     $this->error('优惠卷过期');
        //     exit;
        // }

        // 判断是否领取了
        $check = $this->ReceiveModel
            ->where(['cid' => $cid, 'busid' => $login['id']])
            ->find();
        if ($check) {
            $this->error('已经领取过了');
            exit;
        }

        // 开启事务
        $this->ReceiveModel->startTrans();
        $this->CouponModel->startTrans();

        $CouponData = [
            'cid' => $cid,
            'busid' => $login['id'],
            'status' => '1'
        ];
        // 插入数据
        $ReceiveStart = $this->ReceiveModel->validate('common/Coupon/Receive')->save($CouponData);
        if ($ReceiveStart === FALSE) {
            $this->error($this->ReceiveModel->getError());
            exit;
        }
        // int转换数字型
        $total = $coupon['total'] ? (int)$coupon['total'] : 0;
        $total--;
        $total = $total <= 0 ? 0 : $total;

        // 更新卷数量
        $Couponstart = $this->CouponModel->where(['id' => $cid])->update(['total' => $total]);
        if ($Couponstart === FALSE) {
            $this->ReceiveModel->rollback();
            $this->error($this->error('更新优惠卷数量失败'));
            exit;
        }
        if ($ReceiveStart === FALSE || $Couponstart === FALSE) {
            // 回滚
            $this->CouponModel->rollback();
            $this->ReceiveModel->rollback();
            $this->error('领取优惠券成功');
        } else {
            $this->ReceiveModel->commit();
            $this->CouponModel->commit();
            $this->success('领取优惠券成功');
            exit;
        }
    }
}
