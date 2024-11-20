<?php

namespace app\common\model\Subject\Teacher;

use think\Model;

//引入模型
use app\common\model\Subject\Subject;
use app\common\model\Subject\Teacher\Follow;

class Teacher extends Model
{
    // 指定模型可操作的表名
    protected $name = "subject_teacher";

    //指定自动插入时间戳
    protected $autoWriteTimestamp = true;

    // 插入时间自动写入的字段名
    protected $createTime = "createtime";

    //更新时候的写入字段名
    protected $updateTime = false;

    // 忽略数据表不存在的字段
    protected $field = true;

    //追加虚拟字段
    protected $append = [
        'follow_count',
        'avatar_text',
        'subject_count'
    ];

    //课程总数
    public function getSubjectCountAttr($value, $data)
    {
        $id = isset($data['id']) ? trim($data['id']) : 0;

        $SubjectModel = new Subject();

        $count = $SubjectModel->where(['teacherid' => $id])->count();

        return $count;
    }

    // 头像判断的虚拟字段
    public function getAvatarTextAttr($value, $data)
    {
        //先获取到图片信息
        $cover = isset($data['avatar']) ? trim($data['avatar']) : '';

        //如果为空 或者 图片不存在 给一个默认图
        if(empty($cover) || !is_file(".".$cover)) $cover = config("site.cover");

        // 组装域名信息
        $domain = request()->domain();
        $domain = trim($domain, '/');
        return $domain.$cover;
    }

    // 关注粉丝数
    public function getFollowCountAttr($value, $data)
    {
        $teacherid = isset($data['id']) ? trim($data['id']) : 0;

        return Follow::where(['teacherid' => $teacherid])->count();
    }
}
