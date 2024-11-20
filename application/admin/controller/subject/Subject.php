<?php

namespace app\admin\controller\subject;

use app\common\controller\Backend;

/**
 * 课程管理
 *
 * @icon fa fa-circle-o
 */
class Subject extends Backend
{

    protected $relationSearch = true;
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('common/Subject/Subject');
        $this->Categorymodel = model('common/Subject/Category');
        $this->Teachermodel = model('common/Subject/Teacher/Teacher');
    }
    public function index()
    {
        list($where, $sort, $order, $offect, $limit) = $this->buildparams();
        if ($this->request->isAjax()) {
            $count = $this->model
                ->with(['teacher', 'category'])
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->with(['teacher', 'category'])
                ->where($where)
                ->order($sort, $order)
                ->limit($offect, $limit)
                ->select();
            $result = ['total' => $count, 'rows' => $list];
            return json($result);
        }
        return $this->view->fetch();
    }
    public function add()
    {
        $parmas = $this->request->param('row/a');
        if ($this->request->isAjax()) {
            $result = $this->model
                ->validate('common/Subject/Subject')
                ->save($parmas);
            if ($result === FALSE) {
                $this->error($this->model->getError());
                exit;
            } else {
                $this->success("添加成功");
                exit;
            }
        }
        $this->assign('cateid', build_select('row[cateid]', $this->Categorymodel->column('id,name'), [], ['class' => 'form-control selectpicker']));
        $this->assign('teacherid', build_select('row[teacherid]', $this->Teachermodel->column('id,name'), [], ['class' => 'form-control selectpicker']));
        return $this->view->fetch();
    }
    public function edit($ids = null)
    {

        $subject = $this->model->with(['teacher', 'category'])->find($ids);
        if ($this->request->isPOST()) {
            $parmas = $this->request->param('row/a');
            $parmas['id'] = $ids;
            $result = $this->model->validate('common/Subject/Subject')->isUpdate(true)->save($parmas);
            if ($result) {
                $this->success();
                exit;
            } else {
                $this->error($this->SourmodelceModle->getError());
                exit;
            }
        }

        $this->assign('subject', $subject);
        $this->assign('cateid', build_select('row[cateid]', $this->Categorymodel->column('id,name'), $subject['cateid'], ['class' => 'form-control selectpicker']));
        $this->assign('teacherid', build_select('row[teacherid]', $this->Teachermodel->column('id,name'), $subject['teacherid'], ['class' => 'form-control selectpicker']));
        return $this->view->fetch();
    }
}
