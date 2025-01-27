<?php

namespace app\controller;

use boss\Validator;
use app\model\User;

class Login
{
    /**
     * 显示登录页面
     */
    public function index()
    {
        // 如果用户已登录，则重定向到首页。
        if (is_logined()) {
            return redirect(url('/'));
        }

        return view('user/login');
    }

    /**
     * 提交登录
     */
    public function submit()
    {
        // 检查请求方法
        if (!is_post()) {
            return json('请求方法不正确', 'error', 405);
        }

        // 比对验证码
        $captcha = post('captcha');
        if (!$captcha || $captcha !== session('captcha')) {
            return json('验证码错误');
        }

        // 清除验证码，防止重放攻击
        session('captcha', null);

        // 获取表单数据
        $data = [
            'username' => post('username'),
            'password' => post('password'),
        ];

        // 保存提交的数据，防止表单刷新时重新输入
        storeOldInput($data);

        // 验证表单数据
        $errors = self::validateFormData($data);
        if ($errors) {
            return json($errors);
        }

        $userModel = new User();

        // 尝试登录用户
        $user = $userModel->login($data['username'], $data['password']);

        if (!$user) {
            return json('用户名或密码错误');
        }

        // 登录成功，设置 session
        session_regenerate_id(true);
        session_set([
            'user_id'  => $user['id'],
            'username' => $user['username'],
        ]);
        if ($user['id'] === 1 && $user['group_id'] === 1) {
            session_set('is_admin', true);
        }

        return json('登录成功', 'success', 200);
    }

    /**
     * 表单数据验证
     *
     * @param array $data
     * @return string|null 错误消息或 null
     */
    private static function validateFormData(array $data): ?string
    {
        $rules  = [
            'username' => ['string', '3,32', '姓名应为 3-32 个字符'],
            'password' => ['string', '6,30', '密码应为 6 个字符及以上'],
        ];

        $validate = new Validator($data, $rules, true);

        if (!$validate->check()) {
            return $validate->error;
        }

        return null;  // 无错误
    }
}
