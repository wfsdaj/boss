<?php

namespace app\controller;

use boss\Captcha;
use app\model\User;

class Auth
{
    /**
     * 显示验证码图片.
     */
    public function captcha()
    {
        /**
         * Captcha constructor.
         * @param int $width         图片宽度
         * @param int $height        图片高度
         * @param int $totalChars    总字符数
         * @param int $numbers       数字字符数
         * @param string $fontFamily 字体文件路径
         */
        return (new Captcha())->make();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $user_id       = (int)session('user_id');
        $url_token     = segment(3);
        $session_token = session('csrf_token');

        if (!$user_id || empty($url_token) || !hash_equals($url_token, $session_token)) {
            abort('403');
        }

        // 清空 $_SESSION 数组
        $_SESSION = [];

        // 销毁 session 数据
        session_destroy();

        // 重定向到登录页面或其他安全页面
        return redirect(url('/'));

    }

    /**
     * 检查用户是否存在
     *
     * @return \app\model\User|void
     */
    public static function checkUser()
    {
        $user_id = (int)segment(3);

        if (!$user_id || session('user_id')) {
            return abort(404);  // 用户 ID 无效，终止程序
        }

        $user = (new User())->find(session('user_id'));

        if (!$user) {
            return abort(404); // 用户不存在，终止程序
        }

        return $user; // 返回用户对象
    }
}
