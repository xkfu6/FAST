<?php

namespace app\common\model\Subject;

use think\Model;

// 软删除的模型
use traits\model\SoftDelete;

class Order extends Model
{
    //继承软删除
    use SoftDelete;

    //模型对应的是哪张表
    protected $name = "subject_order";

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = true;

    //设置字段的名字
    protected $createTime = "createtime"; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = false;

    // 软删除的字段
    protected $deleteTime = 'deletetime';

    //自动过滤掉不存在的字段
    protected $field = true;

    // 追加字段
    protected $append = [
        'comment_status',
        'createtime_text'
    ];
    public function getPayList()
    {
        return ['money' => __('Money'), 'zfb' => __('Zfb'), 'wx' => __('Wx')];
    }

    public function subject()
    {
        return $this->belongsTo('app\common\model\Subject\Subject', 'subid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function comment()
    {
        return $this->belongsTo('app\common\model\Subject\Comment', 'id', 'orderid', [], 'LEFT')->setEagerlyType(0);
    }

    public function business()
    {
        return $this->belongsTo('app\common\model\Business\Business', 'busid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? trim($data['createtime']) : "";

        if (empty($createtime)) {
            return '暂无上架时间';
        }

        return date("Y-m-d", $createtime);
    }

    public function getCommentStatusAttr($value, $data)
    {
        $busid = $data['busid'] ?? '';
        $subid = $data['subid'] ?? '';

        $comment = model('Subject.Comment')->where(['busid' => $busid, 'subid' => $subid])->find();

        if ($comment) {
            return true;
        } else {
            return false;
        }
    }
}
