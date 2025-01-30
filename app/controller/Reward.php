<?php

namespace app\controller;

use app\model\Reward as RewardModel;

class Reward
{
    public function __construct()
    {
        // 如果用户未登录，则重定向到登录页。
        if (!is_logined()) {
            return redirect(url('/login'));
        }
    }

    /**
     * 奖励首页
     */
    public function index()
    {
        $signed = self::checkSignedIn();

        $data = [
            'is_signed'        => false, // 默认未签到
            'last_signed_time' => null,  // 默认签到时间差为 null
            'golds'            => session('golds'),
        ];

        // 如果用户已签到，计算签到时间差
        if ($signed) {
            $data = [
                'last_signed_time' => $signed['updated_at'],
                'golds' => $signed['golds'],
            ];

            // 距离上次签到小于 24 小时，标记为已签到
            if (!self::isMoreThanOneDay($signed['updated_at'])) {
                $data['is_signed'] = true;
            }
        }

        return view('reward/index', $data);
    }

    /**
     * 执行签到
     */
    public function sign()
    {
        $signed = self::checkSignedIn();
        $reward = new RewardModel();

        $data = [
            'user_id'    => (int)session('user_id'),
            'updated_at' => time(),
        ];

        // 如果用户已经签到
        if ($signed) {
            if (!self::isMoreThanOneDay($signed['updated_at'])) {
                return json('今日已经签到');
            }

            // 更新签到记录
            $result = $reward->update($data);
        } else {
            // 首次签到，保存用户数据
            $result = $reward->store($data);
        }

        // 处理签到结果
        if ($result) {
            return json('签到成功！', 'success', 200);
        } else {
            return json('签到失败', 'error', 500);
        }
    }

    /**
     * 检查是否已签到
     */
    private static function checkSignedIn()
    {
        return (new RewardModel())->checkSignedIn(session('user_id'));
    }

    /**
     * 判断时间戳是否距离当前时间超过 24 小时
     *
     * @param int $timestamp 目标时间戳
     * @return bool 如果超过 24 小时返回 true，否则返回 false
     */
    private static function isMoreThanOneDay(int $timestamp): bool
    {
        $currentTimestamp = time();

        $timeDifference = $currentTimestamp - $timestamp;

        // 判断是否大于 24 小时（24 小时 = 86400 秒）
        return $timeDifference > 86400;
    }
}
