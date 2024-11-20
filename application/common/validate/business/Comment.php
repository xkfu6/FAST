<?php

namespace app\common\validate\Business;

use think\Validate;

class Comment extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'content' => 'require',
    ];


    /**
     * 提示消息
     */
    protected $message = [
        'content.require' => '请填写评论',
    ];
    


}
