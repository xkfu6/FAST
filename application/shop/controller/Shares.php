<?php

namespace app\shop\controller;

use think\Controller;

class Shares extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->OrderProductModel = model('Order.Product');
        $this->ShreModel = model('Shre.Shre');
        $this->ConcernModel = model('Shre.Concern');
        $this->BusinessModel = model('Business.Business');
        $this->LikeModel = model('Shre.Like');
        $this->CommentModel = model('Shre.Comment');
    }
    // 种草页面
    public function index()
    {
        if ($this->request->isPOST()) {
            $busid = $this->request->param('busid', 2, 'trim');
            // 判断是公共的还是私人
            $active = $this->request->param('active', '', 'trim');
            if ($active == 'my') {
                $result = $this->ShreModel->with(['business', 'product'])->where(['shre.concernid' => $busid])->select();
            } else {
                // 查找关注信息
                $result = $this->ShreModel->with(['business', 'product'])->select();
            }


            foreach ($result as $item) {
                $concernid = $item['concernid'];
                $shreid = $item['id'];
                // 查看是否关注
                $check = $this->ConcernModel->where(['concernid' => $concernid, 'busid' => $busid])->find();
                // 查看是否点赞
                $like = $this->LikeModel->where(['shreid' => $shreid, 'busid' => $busid])->find();
                $likenum = $this->LikeModel->where(['shreid' => $shreid])->count();
                $commentnum = $this->CommentModel->where(['shreid' => $shreid])->count();
                $item['likenum'] = $likenum;
                $item['commentnum'] = $commentnum;
                if ($check) {
                    $item['concern'] = 1;
                } else {
                    $item['concern'] = 0;
                }
                if ($like) {
                    $item['like'] = true;
                } else {
                    $item['like'] = false;
                }
            }
            if ($result) {
                $this->success('查询成功', null, $result);
                exit;
            } else {
                $this->error('没有数据');
                exit;
            }
        }
    }

    // 分享页面
    public function add()
    {
        if ($this->request->isPOST()) {
            $title = $this->request->param('title', '', 'trim');
            $proid = $this->request->param('proid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            if (empty($title)) {
                $this->error('请输入你要分享的内容');
                exit;
            }
            $params = [
                'title' => $title,
                'proid' => $proid,
                'concernid' => $busid,
            ];

            if (isset($_FILES['thumbs'])) {
                $success = build_uploads('thumbs');
                if ($success['code'] == 0) {
                    $this->error($success['msg']);
                    exit;
                }
                $params['thumbs'] = implode(',', $success['data']);
            }
            $sharesadd = $this->ShreModel->save($params);
            if ($sharesadd) {
                $this->success('分享成功', '/share');
                exit;
            } else {
                $this->error('分享失败');
                exit;
            }
        }
    }

    // 关注
    public function concern()
    {
        if ($this->request->isPOST()) {
            $busid = $this->request->param('busid', 0, 'trim');
            $concernid = $this->request->param('coucerid', 0, 'trim');
            // 判断用户是否存在
            $coucercheck = $this->BusinessModel->find($concernid);
            if (!$coucercheck) {
                $this->error('关注的用户不存在');
                exit;
            }
            $params = [
                'busid' => $busid,
                'concernid' => $concernid,
            ];
            // 
            $check = $this->ConcernModel->where($params)->find();
            $share = $this->ShreModel->where(['concernid' => $concernid])->select();
            if ($check) {
                $result = $this->ConcernModel->where($params)->delete();
                if ($result) {
                    $this->success('取消关注成功', null, ['concer' => 0, 'share' => $share]);
                    exit;
                } else {
                    $this->error('取消关注失败');
                    exit;
                }
            } else {
                $result = $this->ConcernModel->save($params);
                if ($result) {
                    $this->success('关注成功', null, ['concer' => 1, 'share' => $share]);
                    exit;
                } else {
                    $this->error('关注失败');
                    exit;
                }
            }
        }
    }
    // 点赞
    public function like()
    {
        if ($this->request->isPOST()) {
            $shreid = $this->request->param('shreid', 0, 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $shre = $this->ShreModel->find($shreid);
            if (!$shre) {
                $this->error('当前分享内容不存在');
                exit;
            }
            $params = [
                'shreid' => $shreid,
                'busid' => $busid
            ];
            // 判断是否点赞过
            $likecherk = $this->LikeModel->where($params)->find();
            if ($likecherk) {
                // 删除点赞数据
                $result = $this->LikeModel->where($params)->delete();
                $this->success('取消成功');
                exit;
            } else {
                // 插入点赞表
                $result = $this->LikeModel->save($params);
                $this->success('谢谢点赞');
                exit;
            }
            $this->error('点赞失败');
            exit;
        }
    }
    // 评论页面
    public function comment()
    {
        $page = $this->request->param('page', 1, 'trim');
        $shreid = $this->request->param('shreid', '', 'trim');
        $shre = $this->ShreModel->find($shreid);
        $limit = 3;

        //偏移量
        $offset = ($page - 1) * $limit;
        if (!$shre) {
            $this->error('评论分享不存在');
            exit;
        }
        $result = $this->CommentModel->with(['business', 'shre'])->limit($offset, $limit)->order('id desc')->where(['shreid' => $shreid])->select();
        if ($result) {
            $this->success('查询成功', null, $result);
            exit;
        } else {
            $this->error('查询失败');
            exit;
        }
    }
    // 发送评论
    public function commentadd()
    {
        if ($this->request->isPOST()) {
            $title = $this->request->param('title', '', 'trim');
            $busid = $this->request->param('busid', 0, 'trim');
            $shreid = $this->request->param('commenShreid', 0, 'trim');
            if (!$title) {
                $this->error('请输入评论内容');
                exit;
            }
            $shre = $this->ShreModel->find($shreid);
            if (!$shre) {
                $this->error('评论分享不存在');
                exit;
            }
            $params = [
                'title' => $title,
                'busid' => $busid,
                'shreid' => $shreid
            ];
            $result = $this->CommentModel->save($params);
            if ($result) {
                $this->success('评论成功');
                exit;
            } else {
                $this->error('评论失败');
                exit;
            }
        }
    }

    // 删除分享
    public function del()
    {
        if ($this->request->isPOST()) {
            $id = $this->request->param('id', 0, 'trim');
            $shre = $this->ShreModel->where(['id' => $id])->find();
            if (!$shre) {
                $this->error('该分享不存在');
                exit;
            }
            $this->ShreModel->startTrans();
            $this->CommentModel->startTrans();

            $ShreStart = $this->ShreModel->where(['id' => $id])->delete();
            if ($ShreStart === FALSE) {
                $this->ShreModel->getError();
                exit;
            }

            $CommentStart = $this->CommentModel->where(['shreid' => $id])->delete();
            if ($CommentStart === FALSE) {
                $this->ShreModel->rollback();
                $this->CommentModel->getError();
                exit;
            }

            if ($ShreStart || $CommentStart) {
                $this->ShreModel->commit();
                $this->CommentModel->commit();
                $this->success('删除成功');
                exit;
            } else {
                $this->CommentModel->rollback();
                $this->ShreModel->rollback();
                $this->error('删除失败');
                exit;
            }
        }
    }
}
