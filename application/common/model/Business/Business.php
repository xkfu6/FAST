<?php

namespace app\common\model\Business;

use think\Model;
use think\Request;

// 软删除的模型
use traits\model\SoftDelete;


/**
 * 用户模型
 */
class Business extends Model
{
    //继承软删除
    use SoftDelete;

    //模型对应的是哪张表
    protected $name = "business";

    //指定一个自动设置的时间字段
    //开启自动写入
    protected $autoWriteTimestamp = true;

    //设置字段的名字
    protected $createTime = "createtime"; //插入的时候设置的字段名

    //禁止 写入的时间字段
    protected $updateTime = false;

    // 软删除的字段
    protected $deleteTime = 'deletetime';

    // 忽略数据表不存在的字段
    protected $field = true;

    protected $append = [
        'gender_text', //性别
        'mobile_text', // 手机字符串
        'avatar_text', // 头像资源
        'poster_text', //海报资源
        'deal_text', //成交状态
        'auth_text', //认证状态
        'region_text', //地区字符串,
        'province_text', //省
        'city_text', //市
        'district_text', //区
        'createtime_text', // 创建时间
        'deletetime_text', // 创建时间
    ];

    public function getGenderList()
    {
        return ['0' => __('Gender 0'), '1' => __('Gender 1'), '2' => __('Gender 2')];
    }

    public function getStatusList()
    {
        return ['normal' => __('Normal'), 'disabled' => __('Disabled'), 'apply' => __('Apply')];
    }

    //成交状态
    public function getDealList()
    {
        return ['0' => __('BusinessDeal 0'), '1' => __('BusinessDeal 1')];
    }

    //邮箱认证状态
    public function getAuthList()
    {
        return ['0' => __('BusinessAuth 0'), '1' => __('BusinessAuth 1')];
    }

    public function getMobileTextAttr($value, $data)
    {
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';

        return substr_replace($mobile, '****', 3, 4);
    }

    public function getGenderTextAttr($value, $data)
    {
        $list = ['0' => '保密', '1' => '男', '2' => '女'];

        $gender = isset($data['gender']) ? $data['gender'] : '0';

        return $list[$gender];
    }

    public function getDealTextAttr($value, $data)
    {
        $deallist = [0 => '未成交', 1 => '已成交'];

        $deal = isset($data['deal']) ? $data['deal'] : '';

        if ($deal >= '0') {
            return $deallist[$deal];
        }
        return;
    }

    public function getAuthTextAttr($value, $data)
    {
        $list = [0 => '未认证', 1 => '已认证'];

        $auth = isset($data['auth']) ? $data['auth'] : '0';

        return $list[$auth];
    }

    // 头像判断的虚拟字段
    public function getAvatarTextAttr($value, $data)
    {
        //先获取到图片信息
        $avatar = isset($data['avatar']) ? trim($data['avatar']) : '';

        //如果为空 或者 图片不存在 给一个默认图
        if (empty($avatar) || !is_file("." . $avatar)) {
            $avatar = config("site.cover");
        }

        //组装完整地址
        // http://www.fast.com/assets/img/cover.png
        $request = Request::instance();
        $domain = $request->domain();
        $avatar = $domain . $avatar;
        return $avatar;
    }

    public function getPosterTextAttr($value, $data)
    {
        $default = trim(config('site.poster'), '/'); //系统上传的默认图
        // var_dump($value); //NULL
        // var_dump($data); // []
        //先获取到头像字段
        $poster = isset($data['poster']) ? trim($data['poster']) : '';
        $poster = trim($poster, '/');

        //为空 或者 不存在
        if (empty($poster) || !is_file($poster)) {
            $poster = $default; //给一张默认图
        }

        $poster = request()->domain() . '/' . $poster;

        return $poster;
    }

    public function getRegionTextAttr($value, $data)
    {
        $region = '';

        // 省
        $province = empty($data['province']) ? '' : $data['province'];
        if ($province) {
            $province_text = model('Region')::where('code', $province)->value('name');
            $region = $province_text;
        }

        // 市
        $city = empty($data['city']) ? '' : $data['city'];
        if ($city) {
            $city_text = model('Region')::where('code', $city)->value('name');
            $region .=  "-" . $city_text;
        }

        // 区
        $district = empty($data['district']) ? '' : $data['district'];
        if ($district) {
            $district_text = model('Region')::where('code', $district)->value('name');
            $region .= "-" . $district_text;
        }

        //广东省-广州市-海珠区
        return $region;
    }

    // 省
    public function getProvinceTextAttr($value, $data)
    {
        $province = empty($data['province']) ? '' : $data['province'];

        if ($province) {
            return model('Region')::where(['code' => $province])->value('name');
        } else {
            return '';
        }
    }

    // 市
    public function getCityTextAttr($value, $data)
    {
        $city = empty($data['city']) ? '' : $data['city'];

        if ($city) {
            return model('Region')::where(['code' => $city])->value('name');
        } else {
            return '';
        }
    }

    // 区
    public function getDistrictTextAttr($value, $data)
    {
        $district = empty($data['district']) ? '' : $data['district'];

        if ($district) {
            return model('Region')::where(['code' => $district])->value('name');
        } else {
            return '';
        }
    }

    //给模型定义一个关联查询
    public function provinces()
    {
        // belongsTo('关联模型名','外键名','关联表主键名',['模型别名定义'],'join类型');
        //参数1：关联的模型
        //参数2：用户表的外键的字段
        //参数3：关联表的主键
        //参数4：模型别名
        //参数5：链接方式 left
        // setEagerlyType(1) IN查询
        // setEagerlyType(0) JOIN查询
        return $this->belongsTo('app\common\model\Region', 'province', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    //查询城市
    public function citys()
    {
        return $this->belongsTo('app\common\model\Region', 'city', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    //查询地区
    public function districts()
    {
        return $this->belongsTo('app\common\model\Region', 'district', 'code', [], 'LEFT')->setEagerlyType(0);
    }

    // 客户来源
    public function source()
    {
        return $this->belongsTo('app\common\model\Business\Source', 'sourceid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    //管理员
    public function admin()
    {
        return $this->belongsTo('app\admin\model\Admin', 'adminid', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    // 定义日期格式
    public function getCreatetimeTextAttr($value, $data)
    {
        $createtime = isset($data['createtime']) ? $data['createtime'] : 0;
        return date('Y-m-d H:i', $createtime);
    }
    public function getDeletetimeTextAttr($value, $data)
    {
        $createtime = isset($data['deletetime']) ? $data['deletetime'] : 0;
        return date('Y-m-d H:i', $createtime);
    }
}
