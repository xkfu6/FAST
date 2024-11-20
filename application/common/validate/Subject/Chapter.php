<?php

namespace app\common\validate\Subject;

use think\Validate;

class Chapter extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'subid'   => ['require',],
        'title'   => ['require',],
        'url'   => ['require',],
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'name.require' => '课程必填',
        'weight.require' => '课程章节必填',
        'url.require' => '课程视频链接必填',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
    ];


}
