<?php

namespace app\admin\controller\subject;

use app\common\controller\Backend;

/**
 * 课程章节管理
 *
 * @icon fa fa-circle-o
 */
class Chapter extends Backend
{

    /**
     * Chapter模型对象
     * @var \app\admin\model\subject\Chapter
     */
    protected $model = null;

    public function __construct()
    {
        parent::__construct();
        $this->model = model('common/Subject/Chapter');
    }

    public function index($ids = null)
    {
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $iswhere = ['subid' => $ids];
            $count = $this->model
                ->where($where)
                ->where($iswhere)
                ->order($sort, $order)
                ->count();
            $list = $this->model
                ->where($where)
                ->where($iswhere)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    public function add($ids = null)
    {
        if ($this->request->isPOST()) {
            $row = $this->request->param('row/a');
            $row['subid'] = $ids;
            $result = $this->model->validate('common/Subject/Chapter')->save($row);
            if ($result) {
                $this->success('添加成功');
            } else {
                $this->error($this->model->getError());
            }
        }
        return $this->view->fetch();
    }
    public function edit($ids = null)
    {
        $chapter = $this->model->where(['id' => $ids])->find();
        if ($this->request->isPOST()) {
            $row = $this->request->param('row/a');
            $row['id'] = $ids;
            $row['subid'] = $chapter['subid'];
            $result = $this->model->validate('common/Subject/Chapter')->isUpdate(true)->save($row);
            if ($result) {
                $this->success('添加成功');
            } else {
                $this->error($this->model->getError());
            }
        }
        $this->assign('chapter', $chapter);
        return $this->view->fetch();
    }
}
