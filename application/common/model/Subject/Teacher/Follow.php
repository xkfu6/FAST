<?php

namespace app\common\model\Subject\Teacher;

use think\Model;

//教师粉丝模型
class Follow extends Model
{
    // 指定模型可操作的表名
    protected $name = "subject_teacher_follow";

    //指定自动插入时间戳
    protected $autoWriteTimestamp = true;

    // 插入时间自动写入的字段名
    protected $createTime = "createtime";

    //更新时候的写入字段名
    protected $updateTime = false;

    // 忽略数据表不存在的字段
    protected $field = true;

    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function teacher()
    {
        return $this->belongsTo('app\common\model\Subject\Teacher\Teacher', 'teacherid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
