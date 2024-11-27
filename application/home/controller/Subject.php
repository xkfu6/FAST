<?php

namespace app\home\controller;

use app\common\controller\Home;

class Subject extends Home
{


    public function __construct()
    {
        parent::__construct();
        $this->SubjectModel = model('common/Subject/Subject');
        $this->ChapterModel = model('common/Subject/Chapter');
        $this->CommentModel = model('common/Subject/Comment');
        $this->BusinessModel = model('common/Business/Business');
        $this->TeacherModel = model('common/Subject/Teacher/Teacher');
        $this->OrderModel = model('common/Subject/Order');
        $this->ReceiveModel = model('common/Coupon/Receive');
        $this->CommissionModel = model('Business.Commission');
        $this->RecordModel = model('Business.Record');
        $this->CollevtionModel = model('common/Business/Collevtion');
        $this->CollectionModel = model('common/Business/Collection');
        $this->PayModel = model('common/Pay/Pay');
    }

    // 课程
    public function index()
    {
        if ($this->request->isAjax()) {
            $page = $this->request->param('page', 1, 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $limit = 10;
            $start = ($page - 1) * $limit; //分页的起始位置 

            $where = [];

            if (!empty($keywords)) {
                $where['title'] = ['LIKE', "%$keywords%"];
            }
            $count = $this->SubjectModel
                ->count();
            $list = $this->SubjectModel
                ->with(['category', 'teacher'])
                ->where($where)
                ->limit($start, $limit)
                ->select();
            if ($list) {
                $this->success('返回列表', null, ['list' => $list, 'count' => $count]);
                exit;
            } else {
                $this->error('暂无更多数据');
            }
        }
        return $this->fetch();
    }
    // 课程介绍
    public function info()
    {
        $action = $this->request->param('action', '', 'trim');

        $subid = $this->request->param('subid');
        // 课程
        $subject = $this->SubjectModel
            ->with(['category', 'teacher'])
            ->find($subid);
        if ($action == "success") {
            $this->success('支付成功，可播放视频', url("home/subject/info", ['subid' => $subid]));
            exit;
        }
        if (!$subject) {
            $this->error('课程不存在');
            exit;
        }

        // echo $subject['sc'];
        // exit;
        //判断是否有点赞
        $login = $this->IsLogin(false);
        $buy = false; //是否购买
        //如果有登录
        if ($login) {
            $likes = isset($subject['likes']) ? trim($subject['likes']) : '';
            $likes = explode(',', $likes); //将字符串转换为数组
            $likes = array_filter($likes); //去空

            $sc = $this->CollectionModel->where(['collectid' => $subid])->find();

            $subject['sc'] = !empty($sc) ? true : false;

            //判断当前用户在不在点赞列表 
            //在里面就点过赞，没在里面就说明没点赞过
            $subject['likes_active'] = in_array($login['id'], $likes);

            //查询当前登录用户是否有购买过该课程
            $check = $this->OrderModel->where(['busid' => $login['id'], 'subid' => $subid])->find();

            $buy = $check ? true : false;
        } else {
            $subject['likes_active'] = false; //没登录就当没点过赞
        }

        // 章节
        $chapter = $this->ChapterModel
            ->where(['subid' => $subid])
            ->select();

        if ($this->request->isAjax()) {
            $page = $this->request->param('page', 1, 'trim');
            $keywords = $this->request->param('keywords', '', 'trim');
            $limit = 10;
            $where = ['subid' => $subid];
            if (!empty($keywords)) {
                $where['keywords'] = $keywords;
            }
            $count = $this->CommentModel->where($where)->count();
            // 评论
            $list = $this->CommentModel
                ->with(['business', 'subject'])
                ->where($where)
                ->limit($page, $limit)
                ->select();
            $data = [
                'count' => $count,
                'list' => $list
            ];
            // var_dump($data);
            // exit;
            if ($list) {
                $this->success('查询成功', null, $data);
            } else {
                $this->error('查询失败',);
            }
        }

        $this->assign([
            'subject' => $subject,
            'chapter' => $chapter,
            'buy' => $buy,
        ]);

        return $this->view->fetch();
    }
    // 点赞
    public function like()
    {
        if ($this->request->isAjax()) {
            $subid = $this->request->param('subid', 0, 'trim');
            $subject = $this->SubjectModel
                ->find($subid);

            if (!$subject) {
                $this->error('课程不存在');
                exit;
            }
            $login = $this->Islogin(false);

            if (!$login) {
                $this->error('请登录');
                exit;
            }
            $likes = empty($subject['likes']) ? '' : trim($subject['likes']);
            $likes = explode(',', $likes);
            $likes = array_filter($likes);
            if (in_array($login['id'], $likes)) {
                // 取消点赞
                //找出点赞的位置
                $key = array_search($login['id'], $likes);
                unset($likes[$key]);
                //去空和去重
                $likes = array_filter($likes);
                $likes = array_unique($likes); //去重
                $likes = implode(',', $likes); //将数组变成字符串
                //更新到数据库里面
                $result = $this->SubjectModel->where(['id' => $subid])->update(['likes' => $likes]);

                $action = 'cancel'; //取消点赞
                $msg = $result === FALSE ? "取消点赞失败" : "取消点赞成功";
            } else {
                $likes[] = $login['id'];
                //去空和去重
                $likes = array_filter($likes);
                $likes = array_unique($likes); //去重
                $likes = implode(',', $likes); //将数组变成字符串
                //更新到数据库里面
                $result = $this->SubjectModel->where(['id' => $subid])->update(['likes' => $likes]);

                $action = 'active'; //点赞
                $msg = $result === FALSE ? "点赞失败" : "点赞成功";
            }

            if ($result === FALSE) {
                $this->error($msg, null);
                exit;
            } else {
                $this->success($msg, null, $action);
                exit;
            }
        }
    }
    //收藏
    public function collevtion()
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
            // echo $this->CollevtionModel->getLastSql();
            // exit;
            if ($collevtion) {
                $result = $this->CollevtionModel->where(['collectid' => $subid])->delete();

                $scaction = 'cancel'; //取消收藏
                $msg = $result === FALSE ? "取消收藏失败" : "取消收藏成功";
            } else {
                $result = $this->CollevtionModel->save($date);
                $scaction = 'active'; //收藏
                $msg = $result === FALSE ? "收藏失败" : "收藏成功";
            }
            if ($result === FALSE) {
                $this->error($msg, null);
                exit;
            } else {
                $this->success($msg, null, $scaction);
                exit;
            }
        }
    }

    //购买
    public function confirm()
    {
        $subid = $this->request->param('subid');

        $subject = $this->SubjectModel
            ->with(['category', 'teacher'])
            ->find($subid);
        $busid = cookie('busid');
        // 判断是否购买了
        $chear = $this->OrderModel->where(['busid' => $busid, 'subid' => $subid])->find();
        if ($chear) {
            $this->error('课程已经购买了');
            exit;
        }

        $coupon = $this->ReceiveModel->with(['coupon'])->where(['busid' => $busid, 'receive.status' => '1'])->select();
        if ($this->request->isPOST()) {
            $pay = $this->request->param('pay', 'money', 'trim');
            $receiveid = $this->request->param('coupon', 0, 'trim');
            // 折扣率
            $receive = $this->ReceiveModel->with(['coupon'])->find($receiveid);
            //先计算出订单的价格
            $total = $subject['price'];
            if ($receive) {
                //判断是否他本人的
                if ($receive['busid'] != $this->view->AutoLogin['id']) {
                    $this->error('优惠券不属于你的');
                }
                if ($receive['status'] == '0') {
                    $this->error('优惠卷过期');
                }
                //折扣
                $rate = isset($receive['coupon']['rate']) ? $receive['coupon']['rate'] : 1;
                // 最终的价格
                $total = bcmul($total, $rate, 2);
            }
            // 余额支付
            if ($pay == 'money') {
                // $UpdateMoney = bcsud($this->view->AutoLogin['money'], $total, 2);
                $UpdateMoney = bcsub($this->view->AutoLogin['money'], $total, 2);
                if ($UpdateMoney < 0) {
                    $this->error('余额不足');
                    exit;
                }

                // subject_order 插入订单表
                // business_record 插入消费记录表 
                // business 更新余额
                // coupon_receive 更新status 状态
                // business_commission 插入佣金记录 
                $this->OrderModel->startTrans();
                $this->RecordModel->startTrans();
                $this->BusinessModel->startTrans();
                $this->ReceiveModel->startTrans();
                $this->CommissionModel->startTrans();
                $orderDate = [
                    'subid' => $subid,
                    'busid' => $busid,
                    'total' => $total,
                    'code' => build_code("ST"),
                    'pay' => 'money'
                ];
                $OrderStar = $this->OrderModel->validate('common/Subject/Order')->save($orderDate);
                if ($OrderStar === FALSE) {
                    $this->error($this->OrderModel->getError());
                    exit;
                }
                $title = $subject['title'];
                // 消费表
                $recordDate = [
                    'total' => "-$total",
                    'busid' => $busid,
                    'content' => "购买了【{$title}】 课程,花费了￥ $total 元"
                    // 购买了[php]课程,花费了￥12.00元
                ];
                $recordStar = $this->RecordModel->validate('common/business/Record')->save($recordDate);
                if ($recordStar === FALSE) {
                    $this->OrderModel->rollback();
                    $this->error($this->RecordModel->getError());
                    exit;
                }
                // 余额
                $businessDate = [
                    'id' => $busid,
                    'money' => $UpdateMoney
                ];
                //定义的验证器
                $validate = [
                    [
                        'money' => ['number', 'egt:0'],
                    ],

                    [
                        'money.number'  => '余额必须是数字类型',
                        'money.egt'      => '余额不足请先充值',
                    ]
                ];
                $businessStar = $this->BusinessModel->validate(...$validate)->isUpdate(true)->save($businessDate);
                if ($businessStar === FALSE) {
                    $this->OrderModel->rollback();
                    $this->RecordModel->rollback();
                    $this->error($this->RecordModel->getError());
                    exit;
                }
                //优惠卷
                if ($receive) {
                    $receiveDate = [
                        'id' => $receive['id'],
                        'status' => '0'
                    ];
                    // 优惠卷状态
                    $receiveStar = $this->ReceiveModel->isUpdate(true)->save($receiveDate);
                    if ($receiveStar === FALSE) {
                        $this->OrderModel->rollback();
                        $this->RecordModel->rollback();
                        $this->BusinessModel->rollback();
                        $this->error('优惠券状态更新失败');
                        exit;
                    }
                }
                // 判断是否有推荐人
                $parentid = isset($this->view->AutoLogin['parentid']) ? trim($this->view->AutoLogin['parentid']) : 0;
                $parent = $this->BusinessModel->find($parentid);
                if ($parent) {
                    $AmountRate = config('site.AmountRate');
                    $amount = bcmul($total, $AmountRate, 2);
                    if ($amount > 0) {
                        $CommissionData = [
                            'orderid' => $this->OrderModel->id,
                            'busid' => $busid,
                            'parentid' => $parentid,
                            'amount' => $amount,
                            'type' => 'subject',
                            'status' => "0"
                        ];
                        $commissionStar = $this->CommissionModel->save($CommissionData);
                        if ($commissionStar === FALSE) {
                            $this->ReceiveModel->rollback();
                            $this->BusinessModel->rollback();
                            $this->RecordModel->rollback();
                            $this->OrderModel->rollback();
                            $this->error('推荐信息存储失败');
                            exit;
                        }
                    }
                }
                if ($OrderStar === FALSE || $recordStar === FALSE || $businessStar === FALSE) {
                    $this->CommissionModel->rollback();
                    $this->ReceiveModel->rollback();
                    $this->BusinessModel->rollback();
                    $this->RecordModel->rollback();
                    $this->OrderModel->rollback();
                    $this->error('余额购买失败');
                    exit;
                } else {
                    $this->OrderModel->commit();
                    $this->RecordModel->commit();
                    $this->BusinessModel->commit();
                    $this->ReceiveModel->commit();
                    $this->CommissionModel->commit();
                    $this->success('购买课程成功', url('home/subject/info', ['subid' => $subid]));
                    exit;
                }
            } else {
                // 微信 / 支付宝

                //携带一个自定义的参数过去
                $third = [
                    'busid' => $this->view->AutoLogin['id'],
                    'subid' => $subid,
                    'couid' => $receiveid,
                ];

                //组装参数
                $data = [
                    'name' => '课程购买', //标题
                    'third' => $third, //传递的第三方的参数
                    'total' => $total, //订单原价充值的价格
                    'type' => $pay, //支付方式
                    'cashier' => 1, //是否需要收银台界面
                    'jump' => "/home/subject/info?subid=$subid&action=success", //订单支付完成后跳转的界面
                    'notice' => '/home/pay/subject',  //异步回调地址
                ];

                //调用模型中的支付方法
                $result = $this->PayModel->payment($data);


                exit;
            }
        }
        $this->assign([
            'subject' => $subject,
            'coupon' => json_encode($coupon)
        ]);
        return $this->view->fetch();
    }

    // 视频播放
    public function play()
    {
        if ($this->request->isAjax()) {
            $subid = $this->request->param('subid', 0, 'trim');
            $cid = $this->request->param('cid', 0, 'trim');

            $subject = $this->SubjectModel->find($subid);
            if (!$subject) {
                $this->error('课程不存在');
                exit;
            }

            //查询章节
            $chapter = $this->ChapterModel->find($cid);
            if (!$chapter) {
                $this->error('章节不存在');
                exit;
            }
            //判断是否有登录
            $login = $this->IsLogin(false);
            if (!$login) {
                $this->error('请先登录');
                exit;
            }
            $buy = $this->OrderModel->where(['subid' => $subid, 'busid' => $login['id']])->find();
            $buy = $this->OrderModel->where(['subid' => $subid, 'busid' => $login['id']])->find();
            if (!$buy) {
                $this->error("暂未购买该课程", null, ['action' => 'buy']);
                exit;
            }

            $this->success('返回课程内容', null, ['url' => $chapter['url_text']]);
            exit;
        }
    }
}
