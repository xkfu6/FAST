<?php

namespace app\common\validate\Live;

use think\Validate;

class Live extends Validate
{
    	protected $rule = [
            'title' => ['require'],
            'url' => ['require'],
        ];

        protected $message = [
            'title.require'      => '请输入直播名称',
            'url.require'      => '请输入直播地址',
        ];

        protected $scene = [

        ];
}
