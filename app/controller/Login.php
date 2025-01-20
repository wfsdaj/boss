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
        if (!is_post()) {
            return json('请求方法不正确');
        }

        // 比对验证码
        $captcha = post('captcha');
        if (!$captcha || $captcha !== session('captcha')) {
            return json('验证码错误');
        }

        // 表单数据
        $data = [
            'username' => post('username'),
            'password' => post('password'),
        ];
        $errors = $this->validateFormData($data);
        if ($errors) {
            return json($errors);
        }

        $userModel = new User();

        // 用户名存在，比对密码
        $user = $userModel->login($data['username'], $data['password']);

        if (!$user) {
            return json('用户名或密码错误', 'error');
        }

        // 登录成功，设置 session
        session('user_id', $user['id']);
        session('username', $user['username']);
        if ($user['id'] === 1 && $user['group_id'] === 1) {
            session('is_admin', true);
        }

        return json('登录成功', 'success');
    }

    /**
     * 表单数据验证
     *
     * @param array $data
     * @return string|null 错误消息或 null
     */
    private function validateFormData(array $data): ?string
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
