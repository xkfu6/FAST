<?php

namespace app\common\model\Live;

// 引入模块
use think\Model;

/**
 * 直播模型
 */
class Live extends Model
{
    // 表名
    protected $name = 'live';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;

    protected $append = [
        'status_text',
        'createtime_text',
        'starttime_text',
        'endtime_text',
        'url_text'
    ];

    protected function setStarttimeAttr($value)
    {
        return $value == '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEndtimeAttr($value)
    {
        return $value == '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    public function StatusList()
    {
        return ['0' => __('未开播'), '1' => __('正在直播'),  '2' => __('直播结束')];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->StatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? trim($data['createtime']) : "";

        if(empty($createtime)) return '暂无时间';

        return date("Y-m-d", $createtime);
    }

    public function getStarttimeTextAttr($value, $data)
    {
        $starttime = isset($data['starttime']) ? trim($data['starttime']) : "";

        if(empty($starttime)) return '暂无时间';

        return date("Y-m-d", $starttime);
    }

    public function getEndtimeTextAttr($value, $data)
    {
        $endtime = isset($data['endtime']) ? trim($data['endtime']) : "";

        if(empty($endtime)) return '暂无时间';

        return date("Y-m-d", $endtime);
    }

    public function getUrlTextAttr($value, $data)
    {
        //获取域名部分
        $domain = request()->domain();
        $domain = trim($domain, '/');

        $url = isset($data['url']) ? $data['url'] : '';

        //如果为空就给一个默认图片地址
        if(empty($url)) return '';
        
        return $domain.$url;
    }
}