<?php

declare(strict_types=1);

use app\model\User;

/**
 * 检查当前用户是否为游客
 *
 * 该函数通过检查会话中是否存在用户ID来判断当前用户是否为游客。
 * 如果用户ID存在，则返回 `false`（表示不是游客），如果不存在，则返回 `true`（表示是游客）。
 *
 * @return bool 如果是游客返回 `true`，否则返回 `false`
 */
function is_guest(): bool
{
    $userId = session('user_id');

    return empty($userId);
}

/**

 * 检查用户是否已登录。
 * @return bool 如果用户已登录，则返回 true；否则返回 false。
 */
function is_logined(): bool
{
    return session('user_id') !== null;
}

/**
 * 检查是否为作者本人
 *
 * 该函数检查当前用户ID是否与资源的作者ID相同。
 * 如果用户是作者，返回 `true`，否则返回 `false`。
 *
 * @param int $author_id 资源（例如文章、帖子等）的作者ID
 * @return bool 如果用户是作者返回 `true`，否则返回 `false`
 */
function is_author(int $author_id): bool
{
    // 比较用户ID和资源作者ID是否相同
    return session('user_id') === $author_id;
}

/**
 * 跳转到之前页面
 *
 * 该函数根据会话中的 `back_to_link` 变量决定跳转的目标页面。如果该变量不存在，
 * 则会返回首页。如果 `back_to_link` 不存在，且 HTTP_REFERER 可用，则使用 `REFERER` 重定向。
 *
 * @return void
 */
function go_back(): void
{
    // 尝试获取会话中的 `back_to_link` 变量，若不存在则尝试使用 HTTP_REFERER 或跳转到首页
    $backLink = session('back_to_link') ?? $_SERVER['HTTP_REFERER'] ?? url('/');

    // 执行重定向
    redirect($backLink);
}

/**
 * 检查用户登录状态
 *
 * 该函数检查当前用户是否已登录。如果已登录，返回到上一页面。如果未登录，跳转到登录页面。
 * 如果当前页面已经是登录页面，则不会执行任何跳转。
 *
 * @return void 如果用户未登录，重定向到登录页面；如果已登录，返回到上一页面
 */
function check_login(): void
{
    // 获取当前用户的用户ID
    $user_id = session('user_id');

    // 如果用户已登录，返回到上一页面
    if ($user_id) {
        go_back(); // 用户已登录，返回上一页
        return;
    }

    // 如果用户未登录，并且当前页面不是登录页面，重定向到登录页面
    if (strpos(url_current(), '/login') === false) {
        redirect(url('/login'));
    }
}



/**
 * 获取用户头像
 */
function get_avatar(string $user_id)
{
    $avatars = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    $hash        = abs(crc32($user_id));     // crc32是一个常见的哈希函数，这里取其绝对值以防得到负数
    $avatarIndex = $hash % 12;  // 取模确保索引在数组范围内
    $userAvatar  = $avatars[$avatarIndex] . '.png';

    return '/img/avatar/' . $userAvatar;
}



/**
 * 返回缩略图路径，若无缩略图则返回原图路径
 *
 * @param  string $target_image 目标图片路径
 * @return string 返回缩略图或原图的路径
 */
function thumb(string $target_image): string
{
    $imgInfo = pathinfo($target_image);
    $thumbnailPath = "{$imgInfo['dirname']}/{$imgInfo['filename']}_thumb.{$imgInfo['extension']}";

    return is_file($thumbnailPath) ? $thumbnailPath : $target_image;
}
