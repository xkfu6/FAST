<?php

namespace app\common\model\Hotel;

use think\Model;

class Room extends Model
{
    // 表名
    protected $name = 'hotel_room';

    // 追加属性
    protected $append = [
        'thumb_text',
        'flag_text'
    ];

    public function getFlagTextAttr($value, $data)
    {
        $flag = isset($data['flag']) ? trim($data['flag']) : '';

        $list = explode(',', $flag);

        return $list;
    }

    public function getThumbTextAttr($value, $data)
    {
        //先获取到图片信息
        $cover = isset($data['thumb']) ? trim($data['thumb']) : '';

        //如果为空 或者 图片不存在 给一个默认图
        if(empty($cover) || !is_file(".".$cover)) $cover = config("site.cover");

        // 组装域名信息
        $domain = request()->domain();
        $domain = trim($domain, '/');
        return $domain.$cover;
    }
}
