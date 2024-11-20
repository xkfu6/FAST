<?php
namespace app\common\model\Card;

// 引入模块
use think\Model;

/**
 * 用户数据模型
 */
class Card extends Model
{
    protected $name = 'card';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = false;

    //自动过滤掉不存在的字段
    protected $field = true;

    protected $append = [
        'gender_text', // 性别
        'region_text',  //地区
        'province_text', //省
        'city_text', //市
        'district_text', //区
    ];

    // 省
    public function getProvinceTextAttr($value, $data)
    {
        $province = empty($data['province']) ? '' : $data['province'];

        if ($province) 
        {
           return model('Region')::where(['code' => $province])->value('name');
        }else
        {
            return '';
        }
    }

    // 市
    public function getCityTextAttr($value, $data)
    {
        $city = empty($data['city']) ? '' : $data['city'];

        if ($city) 
        {
           return model('Region')::where(['code' => $city])->value('name');
        }else
        {
            return '';
        }
    }

    // 区
    public function getDistrictTextAttr($value, $data)
    {
        $district = empty($data['district']) ? '' : $data['district'];

        if ($district) 
        {
           return model('Region')::where(['code' => $district])->value('name');
        }else
        {
            return '';
        }
    }

    // 地区
    public function getRegionTextAttr($value, $data)
    {
        $region = '';

        // 省
        $province = empty($data['province']) ? '' : $data['province'];
        if ($province) 
        {
            $province_text = model('Region')::where('code', $province)->value('name');
            $region = $province_text . "-";
        }

        // 市
        $city = empty($data['city']) ? '' : $data['city'];
        if ($city) 
        {
            $city_text = model('Region')::where('code', $city)->value('name');
            $region .= $city_text . "-";
        }

        // 区
        $district = empty($data['district']) ? '' : $data['district'];
        if ($district) 
        {
            $district_text = model('Region')::where('code', $district)->value('name');
            $region .= $district_text;
        }

        return $region;
    }

    // 性别
    public function getGenderTextAttr($value, $data)
    {
        $list = ['0' => '保密', '1' => '男', '2' => '女'];
        $del = $data['gender'] ? $data['gender'] : 0;
        return $list[$del];
    }
}