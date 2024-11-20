<?php

namespace app\common\model\Subject;

use think\Model;

// 软删除的模型
use traits\model\SoftDelete;

class Subject extends Model
{
    //继承软删除
    use SoftDelete;

    //模型对应的是哪张表
    protected $name = "subject";

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

    // 追加属性
    protected $append = [
        'thumbs_text',
        'likes_text',
        'createtime_text'
    ];

    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? trim($data['createtime']) : "";

        if (empty($createtime)) {
            return '暂无上架时间';
        }

        return date("Y-m-d", $createtime);
    }

    public function getLikesTextAttr($value, $data)
    {
        $likes = isset($data['likes']) ? trim($data['likes']) : '';

        if (empty($likes)) {
            return 0;
        }

        //将字符串变成数组
        $arr = explode(',', $likes);

        //统计数组的长度，就是点赞人的个数
        return count($arr);
    }

    public function getThumbsTextAttr($value, $data)
    {
        //先获取到图片信息
        $cover = isset($data['thumbs']) ? trim($data['thumbs']) : '';

        //如果为空 或者 图片不存在 给一个默认图
        if (empty($cover) || !is_file("." . $cover)) $cover = config("site.cover");

        // 组装域名信息
        $domain = request()->domain();
        $domain = trim($domain, '/');
        return $domain . $cover;
    }

    public function category()
    {
        return $this->belongsTo('app\common\model\Subject\Category', 'cateid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function collection()
    {
        return $this->belongsTo('app\common\model\Business\Collection', 'id', 'collectid', [], 'LEFT')->setEagerlyType(0);
    }
    //关联查询
    public function teacher()
    {
        //课程：老师 1：1
        // 老师：课程 1：n
        // setEagerlyType(0); JOIN查询
        // setEagerlyType(1); IN查询
        return $this->belongsTo('app\common\model\Subject\Teacher\Teacher', 'teacherid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
