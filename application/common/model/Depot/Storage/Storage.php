<?php

namespace app\common\model\Depot\Storage;

use think\Model;

use traits\model\SoftDelete;

class Storage extends Model
{
    // 软删除
    use SoftDelete;

    // 表名
    protected $name = 'depot_storage';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';


    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    //自动过滤掉不存在的字段
    protected $field = true;

    // 追加属性
    protected $append = [
        'type_text',
        'status_text'
    ];
    
    public function typelist()
    {
        return ['1' => __('直销入库'), '2' => __('退货入库')];
    }

    public function statuslist()
    {
        return ['0' => __('待审批'), '1' => __('审批失败'), '2' => __('待入库'), '3' => __('入库完成')];
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->typelist();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->statuslist();
        return isset($list[$value]) ? $list[$value] : '';
    }

    // 供应商
    public function supplier()
    {
        return $this->belongsTo('app\common\model\Depot\Supplier','supplierid','id',[],'LEFT')->setEagerlyType(0);
    }

    // 入库员
    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin','adminid','id',[],'LEFT')->setEagerlyType(0);
    }

    // 审核员
    public function reviewer()
    {
        return $this->belongsTo('app\admin\model\Admin','reviewerid','id',[],'LEFT')->setEagerlyType(0);
    }
}
