<?php

namespace app\pay\exception;

use Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\Log;

class PayException extends Handle
{
    public function render(Exception $e)
    {
        // TODO::开发者对ajax请求异常的处理
        if ($e instanceof HttpException && request()->isAjax()) {
            // 写入错误日志
            Log::error($e->getMessage());

            // 返回错误信息和错误编码
            return response($e->getMessage(), $e->getStatusCode());
        }
        
        // TODO::开发者对异常的操作
        // 写入错误日志
        Log::error($e->getMessage());
        
        // 其他异常交由系统处理
        return parent::render($e);
    }
}