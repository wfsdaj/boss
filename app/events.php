<?php

use boss\Event;
use app\event\UserRegistered;

/**
 * 网站全部事件
 */

// 注册事件监听器
Event::listen('user.registered', UserRegistered::class);