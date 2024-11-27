<?php

namespace app\common\model\Hotel;

use think\Model;

class Guest extends Model
{
    // 表名
    protected $name = 'hotel_guest';

    // 忽略数据表不存在的字段
    protected $field = true;

    protected $append = [
        'gender_text',
    ];

    public function getGenderTextAttr($value, $data)
    {
        $gender = $data['gender'] ? $data['gender'] : 0;

        $list = ['0' => '保密', '1' => '男', '2' => '女'];

        return $list[$gender];
    }
}
