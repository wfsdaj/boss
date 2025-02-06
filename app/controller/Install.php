<?php

namespace app\controller;

use Exception;

class Install
{
    public function __construct()
    {
        self::checkInstall();
    }

    /**
     * 显示安装首页
     */
    public function index()
    {
        return view('install/index');
    }

    public function first()
    {
        $write_dir = [
            '../config',
            '../public/uploads',
            '../runtime/',
            '../runtime/cache',
            '../runtime/logs',
            '../runtime/sessions',
        ];

        $write = [];
        foreach ($write_dir as &$dir) {
            $write[$dir] = is_writable('./' . $dir);
        }

        $data = [
            'php_version' => version_compare(phpversion(), "7.0.0", ">="),
            'write'       => $write,
            'succeed'     => 1,
        ];

        return view('install/first', $data);
    }

    public function last()
    {
        return view('install/last');
    }

    public function submit()
    {
        if (!is_post()) {
            throw new Exception("请求方式错误", 1);
        }

        $lock_file   = ROOT_PATH . 'config/install_lock.php';

        $data = [
            'driver'   => 'mysql',
            'host'     => post('host'),
            'database' => post('database'),
            'username' => post('username'),
            'password' => post('password'),
            'charset'  => 'utf8mb4',
            'port'     => 3306,
            'prefix'   => post('prefix'),
        ];

        try {
            write_config('db', $data);
            self::writeLockFile($lock_file);

            return redirect('/');
        } catch (\Throwable $th) {
            throw new Exception($th, 1);
        }
    }

    /**
     * 写入安装锁文件
     */
    private static function writeLockFile(string $locked_file)
    {
        file_put_contents($locked_file, '');
    }

    /**
     * 检测是否安装
     */
    private static function checkInstall()
    {
        $lock_file = ROOT_PATH . 'config/install_lock.php';

        if (file_exists($lock_file)) {
            view('install/installed');
            exit();
        }
    }
}
