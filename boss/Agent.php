<?php

declare(strict_types=1);

namespace boss;

class Agent
{
    /**
     * 获取用户浏览器或检测特定浏览器标识
     *
     * @param string|null $bs 需要检测的浏览器标识（可选）
     * @return string|bool|null 返回浏览器名称、检测结果（true/false）或 null（无法检测）
     */
    public static function browser(?string $bs = null)
    {
        // 获取用户代理字符串
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($user_agent)) {
            return null;
        }

        $user_agent = strtolower($user_agent);

        // 如果传入了浏览器标识，直接检测
        if ($bs !== null) {
            return strpos($user_agent, strtolower($bs)) !== false;
        }

        // 检测浏览器类型
        if (strpos($user_agent, 'micromessenger') !== false) {
            return 'Weixin';
        } elseif (strpos($user_agent, 'qq') !== false) {
            return 'QQ';
        } elseif (strpos($user_agent, 'weibo') !== false) {
            return 'Weibo';
        } elseif (strpos($user_agent, 'alipayclient') !== false) {
            return 'Alipay';
        } elseif (strpos($user_agent, 'trident/7.0') !== false) {
            return 'IE11';
        } elseif (strpos($user_agent, 'trident/6.0') !== false) {
            return 'IE10';
        } elseif (strpos($user_agent, 'trident/5.0') !== false) {
            return 'IE9';
        } elseif (strpos($user_agent, 'trident/4.0') !== false) {
            return 'IE8';
        } elseif (strpos($user_agent, 'msie 7.0') !== false) {
            return 'IE7';
        } elseif (strpos($user_agent, 'msie 6.0') !== false) {
            return 'IE6';
        } elseif (strpos($user_agent, 'edge') !== false) {
            return 'Edge';
        } elseif (strpos($user_agent, 'firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($user_agent, 'opera') !== false || strpos($user_agent, 'opr/') !== false) {
            return 'Opera';
        } elseif (strpos($user_agent, 'chrome') !== false || strpos($user_agent, 'crios') !== false) {
            return 'Chrome';
        } elseif (strpos($user_agent, 'safari') !== false && strpos($user_agent, 'chrome') === false) {
            return 'Safari';
        } elseif (strpos($user_agent, 'brave') !== false) {
            return 'Brave';
        } elseif (strpos($user_agent, 'mj12bot') !== false) {
            return 'MJ12bot';
        } else {
            return 'Other';
        }
    }


    /**
     * 获取用户操作系统或检测特定操作系统标识
     *
     * @param string|null $osstr 需要检测的操作系统标识（可选）
     * @return string|bool|null 返回操作系统名称、检测结果（true/false）或 null（无法检测）
     */
    public static function os(?string $osstr = null)
    {
        // 获取用户代理字符串
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return null;
        }
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        // 如果传入了操作系统标识，直接检测
        if ($osstr !== null) {
            return strpos($user_agent, strtolower($osstr)) !== false;
        }

        // 检测操作系统类型
        switch (true) {
            case strpos($user_agent, 'windows nt 5.0') !== false:
                return 'Windows 2000';
            case strpos($user_agent, 'windows nt 5.1') !== false:
                return 'Windows XP';
            case strpos($user_agent, 'windows nt 5.2') !== false:
                return 'Windows 2003';
            case strpos($user_agent, 'windows nt 6.0') !== false:
                return 'Windows Vista';
            case strpos($user_agent, 'windows nt 6.1') !== false:
                return 'Windows 7';
            case strpos($user_agent, 'windows nt 6.2') !== false:
                return 'Windows 8';
            case strpos($user_agent, 'windows nt 6.3') !== false:
                return 'Windows 8.1';
            case strpos($user_agent, 'windows nt 10') !== false:
                return 'Windows 10';
            case strpos($user_agent, 'windows phone') !== false:
                return 'Windows Phone';
            case strpos($user_agent, 'android') !== false:
                return 'Android';
            case strpos($user_agent, 'iphone') !== false:
                return 'iPhone';
            case strpos($user_agent, 'ipad') !== false:
                return 'iPad';
            case strpos($user_agent, 'mac') !== false:
                return 'Mac';
            case strpos($user_agent, 'sunos') !== false:
                return 'Sun OS';
            case strpos($user_agent, 'bsd') !== false:
                return 'BSD';
            case strpos($user_agent, 'ubuntu') !== false:
                return 'Ubuntu';
            case strpos($user_agent, 'linux') !== false:
                return 'Linux';
            case strpos($user_agent, 'unix') !== false:
                return 'Unix';
            default:
                return 'Other';
        }
    }
}
