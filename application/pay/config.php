<?php
//配置文件
return [
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'   => '\\app\\pay\\exception\\PayException',
    // 配置日志驱动
    'log'   => [
        // 日志记录方式，支持 file socket
        'type' => 'File',
        //日志保存目录
        'path' => LOG_PATH . 'pay' . DS,
        //单个日志文件的大小限制，超过后会自动记录到第二个文件
        'file_size' => 2097152,
        //日志的时间格式，默认是` c `
        'time_format'   => 'Y-m-d H:i:s',
        // 单独日志记录
        'apart_level'   =>  ['error', 'sql', 'notice', 'debug']
    ],
];
