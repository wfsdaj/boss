<?php

namespace app\event;

class UserRegistered
{
    public function handle(string $username): void
    {
        echo "用户 {$username} 已注册，发送欢迎邮件。\n";
    }
}