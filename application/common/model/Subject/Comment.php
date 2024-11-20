<?php

namespace app\common\model\Subject;

use think\Model;

class Comment extends Model
{
    // 模型对应的是哪张表
    protected $name = "subject_comment";

    // 开启自动写入
    protected $autoWriteTimestamp = true;

    // 设置字段的名字
    protected $createTime = "createtime";

    // 禁止写入更新时间字段
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;
    // 追加属性
    protected $append = [
        'createtime_text'
    ];

    // 关联查询用户
    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function subject()
    {
        return $this->belongsTo('app\common\model\Subject\Subject', 'subid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function getcreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? $data['createtime'] : 0;
        return date('Y-m-d H:i', $createtime);
    }
}
