<?php

declare(strict_types=1);

/**
 * 检查用户是否已登录。
 *
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
    return (int)session('user_id') === $author_id;
}

/**
 * 检查当前用户是否为管理员
 *
 * @return bool 当前用户是否为管理员
 */
function is_admin(): bool
{
    return session('user_id') && session('is_admin') === true;
}

/**
 * 根据用户ID生成头像路径
 *
 * @param int|null $user_id 用户ID，可以为null
 * @return string 返回头像的路径
 */
function get_avatar(?int $user_id): string
{
    // 使用静态变量缓存结果
    static $avatarCache = [];

    // 检查$user_id是否为null或小于等于0，如果是则直接返回默认头像
    if (is_null($user_id) || $user_id === '' || $user_id <= 0) {
        return '/img/avatar/default.jpg';
    }

    // 如果结果已经缓存，直接返回缓存结果
    if (isset($avatarCache[$user_id])) {
        return $avatarCache[$user_id];
    }

    // 使用crc32哈希函数并取绝对值，然后取模得到头像索引
    $avatarIndex = abs(crc32((string)$user_id)) % 38;

    // 拼接头像路径
    $avatarPath = '/img/avatar/' . $avatarIndex . '.png';

    // 缓存结果
    $avatarCache[$user_id] = $avatarPath;

    // 返回头像路径
    return $avatarPath;
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
 * 返回缩略图路径，若无缩略图则返回原图路径
 *
 * @param  string $imagePath 目标图片路径
 * @return string 返回缩略图或原图的路径
 */
function thumb(string $imagePath): string
{
    static $cache = []; // 缓存 pathinfo 的结果

    // 如果结果已经缓存，直接返回缓存结果
    if (isset($cache[$imagePath])) {
        return $cache[$imagePath];
    }

    // 拼接完整路径
    $fullPath = 'upload/' . $imagePath;

    // 获取路径信息
    $pathInfo = $cache[$imagePath] ?? $cache[$imagePath] = pathinfo($fullPath);

    // 构造缩略图路径
    $thumbnailPath = sprintf('%s/%s_thumb.%s', $pathInfo['dirname'], $pathInfo['filename'], $pathInfo['extension']);

    return $thumbnailPath;
}

/**
 * 生成图片 HTML 代码
 *
 * @param array $post 包含图片信息的数组
 * @return string 生成的 HTML 代码
 */
function generate_image_html(array $post): string
{
    // 如果没有图片，返回空字符串
    if (empty($post['images']) || $post['filename'] === null) {
        return '';
    }

    // 检查图片类型是否为 GIF
    $isGif = pathinfo($post['filename'], PATHINFO_EXTENSION) === 'gif';

    // 根据图片宽度选择图片路径
    $imageSrc = ($post['width'] >= 500) ? thumb($post['filename']) : 'upload/' . $post['filename'];

    // 宽度>500会生成缩略图，所以添加 img-small 图片缩放图标
    $imgClass = ($post['width'] >= 500) ? 'cursor-zoomin' : '';

    // 如果是 GIF 图片，只设置 data-gifffer 属性
    if ($isGif) {
        $html = <<<HTML
<div class="feed-gallery mt-2">
    <img class="feed-item-img img-small {$imgClass}" data-gifffer="{$imageSrc}" alt="">
</div>
HTML;
    } else {
        // 如果不是 GIF 图片，设置 src 和 data-large 属性
        $html = <<<HTML
<div class="feed-gallery mt-2">
    <img class="feed-item-img img-small {$imgClass}" src="{$imageSrc}" data-large="upload/{$post['filename']}" alt="">
</div>
HTML;
    }

    return $html;
}

/**
 * 生成分页导航的HTML代码
 *
 * @param object $paginationData 分页数据对象，包含以下属性：
 *   - totalRows: int, 总记录数
 *   - currentPage: int, 当前页码
 *   - prevPage: string, 上一页的URL
 *   - nextPage: string, 下一页的URL
 *   - listPage: array, 页码和对应URL的映射数组，例如 [1 => '/page/1', 2 => '/page/2']
 * @param string $prevIcon 上一页按钮的图标（HTML 字符串），默认是左箭头图标
 * @param string $nextIcon 下一页按钮的图标（HTML 字符串），默认是右箭头图标
 *
 * @return string 返回生成的分页导航HTML代码；如果没有数据，则返回空字符串。
 */
function render_pagination(object $paginationData, string $prevIcon = '&lsaquo;', string $nextIcon = '&rsaquo;'): string
{
    // 检查是否有数据
    if ($paginationData->totalRows === 0) {
        return '';
    }

    // 提取分页数据
    $currentPage = $paginationData->currentPage;
    $prevPage = $paginationData->prevPage;
    $nextPage = $paginationData->nextPage;
    $pageList = $paginationData->listPage;

    // 开始生成分页HTML
    $html = '<nav aria-label="page navigation">';
    $html .= '<ul class="pagination justify-content-center p-3">';

    // 上一页按钮
    $prevDisabled = ($currentPage === 1) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $prevDisabled . '"><a class="page-link" href="' . ($prevDisabled ? '#' : $prevPage) . '">';
    $html .= '<span aria-hidden="true">' . $prevIcon . '</span></a></li>';

    // 遍历页码
    foreach ($pageList as $pageNumber => $pageUrl) {
        $pageUrl = !empty($pageUrl) ? htmlspecialchars($pageUrl) : '#';
        // 如果是当前页，添加 active 类
        $activeClass = ($pageNumber === $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . $pageUrl . '">';
        $html .= $pageNumber . '</a></li>';
    }

    // 下一页按钮
    $nextDisabled = ($currentPage === $paginationData->maxPage) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $nextDisabled . '"><a class="page-link" href="' . ($nextDisabled ? '#' : $nextPage) . '">';
    $html .= '<span aria-hidden="true">' . $nextIcon . '</span></a></li>';

    $html .= '</ul>';
    $html .= '</nav>';

    return $html;
}
