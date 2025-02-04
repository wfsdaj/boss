<?php

/**
 * 配置文件
 */

return [
    'timezone'         => 'Asia/Shanghai',   // 时区
    'not_allowed_join' => false,             // 不允许注册
    'maintenance'      => false,             // 维护模式
    'upload_path'      => 'upload/',         // 上传文件目录
    'theme'            => 'default',         // 默认皮肤

    // 数据库
    'db' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'database' => 'mini_test',
        'username' => 'root',
        'password' => 'root',
        'port'     => '3306',
        'charset'  => 'utf8mb4',
        'prefix'   => '',
    ],

    'view' => [
        'view_path'   => VIEW_PATH,
        'cache_path'  => RUNTIME_PATH . 'views/',
        'view_suffix' => '.html',
    ],

    // 文件上传
    'upload' => [
        'mimes'        => ['image/png', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/gif'],   //允许上传的文件MiMe类型
        'maxSize'      => 3,                       // 上传的文件大小限制 MB (0-不做限制)
        'exts'         => ['jpg', 'gif', 'png'],   // 允许上传的文件后缀
        'autoSub'      => true,                    // 自动子目录保存文件
        'subName'      => ['date', 'Y/m/d'],       // 子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath'     => 'uploads/',              // 保存根路径
        'savePath'     => '',                      // 保存路径
        'saveName'     => ['uuid'],            // 上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt'      => '',                      // 文件保存后缀，空则使用原后缀
        'replace'      => true,                   // 存在同名是否覆盖
        'hash'         => false,                   // 是否生成hash编码
        'callback'     => false,                   // 检测文件是否存在回调，如果存在返回文件信息数组
        'driver'       => 'local',                 // 文件上传驱动
        'driverConfig' => [],                      // 上传驱动配置
    ],

    // session
    'session' => [
        // 是否全应用启动 session
        'start'  => true,
        // session 存储类型  [file, memcache, redis]
        'driver' => 'file',
        // 文件型 sessions 文件存放路径
        'path'   => SESSION_PATH,
        //session 类似为 memcache 或 redis 时，对应的主机地址 [memcache 11211 redis 6379]
        'host'   => 'tcp://127.0.0.1:11211',
    ],

    // 缓存类型
    'allowCacheType' => ['file', 'memcache', 'redis',],

    // 设置缓存
    'cache' => [
        'start'    => true,
        'driver'   => 'file',
        'path'     => CACHE_PATH,
        'name2md5' => false,
        // 主机地址 [ 'memcache', 'redis' 需要设置 ]
        'host'     => '127.0.0.1',
        // 对应各类服务的密码, 为空代表不需要密码
        'password' => '',
        // 对应服务的端口
        'port'     => '6379',
        'prefix'   => 'cache_'
    ],

    // 发送邮件
    'smtp' => [
        'server' => '',
        'port' => '',
        'ssl' => '',
        'username' => '',
        'password' => '',
    ]
];
