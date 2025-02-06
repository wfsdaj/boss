{extend name="layout/app" /}

{block name="title"}{$tag->name} - 标签{/block}

{block name="main"}
<div class="breadcrumb d-flex justify-content-between align-items-center mb-1 px-3">
    <div class="btn btn-icon btn-sm me-3 js-back" aria-label="返回" role="button" tabindex="0">
        <i class="iconfont icon-left-arrow fs-20"></i>
    </div>
    <h2 class="w-100 fs-18 cursor-pointer js-top">{$tag->name}</h2>
    <div class="dropdown">
        <button class="btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="iconfont icon-more"></i></button>
        <div class="dropdown-menu dropdown-menu-end shadow-sm">
            <a class="dropdown-item d-flex align-items-center" href="#">
                <i class="iconfont icon-exchange me-3"></i>
                <div>
                    <p class="fw-bold">某些设置</p>
                    <p class="text-muted">设置说明文字</p>
                </div>
            </a>
        </div>
    </div>
</div>
<div class="header-cover">
    <img class="img-fluid" src="/img/bg-header-cover.jpg" alt="">
</div>
<div class="p-3 bg-1">
    <h1 class="mb-2 fs-24"><?= $tag->name ?></h1>
    <p class="mb-3"><?= $tag->intro ?></p>
    <div class="d-flex align-items-center">
        <div class="avatars d-flex align-items-center">
            <img class="avatar avatar-xs" src="/img/avatar/1.png" alt="">
            <img class="avatar avatar-xs" src="/img/avatar/2.png" alt="">
            <img class="avatar avatar-xs" src="/img/avatar/3.png" alt="">
            <span class="fs-15 ms-2"><b>2321</b> 位成员</span>
        </div>
        <div class="ms-auto">
            <a class="btn btn-sm btn-secondary rounded-pill" href="#">加入</a>
        </div>
    </div>
</div>
<nav class="nav nav-justified border-bottom pt-2">
    <a class="nav-link" href="###" data-active="qun-news">最新</a>
    <a class="nav-link" href="###" data-active="qun-hot">热门</a>
    <a class="nav-link" href="###" data-active="qun-media">媒体</a>
    <a class="nav-link" href="###" data-active="qun-about">关于</a>
</nav>

<ul class="feed-list list-unstyled">
<?php if(!$posts[0]): ?>
    <div class="placeholder fs-28">無</div>
<?php else: ?>
    <?php foreach ($posts[0] as $post): ?>
    <li class="feed-item d-flex js-tap" id="feed<?= $post->p_id ?>" data-href="/post/show/<?= $post->p_id ?>" data-pid="<?= $post->p_id ?>">
        <div class="feed-item-count">
            <spap class="post-num">
                <i><?= $post->comments ?: 0 ?></i>
                <span class="list-triangle-border"></span>
                <span class="list-triangle-body"></span>
            </spap>
        </div>
        <div class="d-flex flex-column w-100">
            <p class="typo-text fs-18"><?= $post->content ?> ​​​</p>
            <?php if($post->images > 0): ?>
                <?php $post->images = min(max(1, $post->images), 3); ?>
                <div class="gallery mt-2">
                    <ul class="thumbnail-container feed-item-imgs list-unstyled row row-cols-<?= $post->images ?> mx-0"><?= getImagesList($post->images, $post->id) ?></ul>
                    <div class="fullscreen-container">
                        <div class="loading-indicator"><div class="spinner-border m-4" role="status"><span class="visually-hidden">Loading...</span></div></div>
                        <div class="image-wrapper">
                            <img class="fullscreen img-fluid rounded" src="" alt="">
                            <i class="iconfont icon-return prev-btn text-white" title="上一张"></i>
                            <i class="iconfont icon-enter next-btn text-white" title="下一张"></i>
                        </div>
                    </div>
                </div>
            <?php endif ?>
            <div class="d-flex align-items-center mt-1">
                <a class="link-dark fs-15 post-meta" href="/user/profile/23"><?= $post->username ?></a>
                <a class="link-muted fs-15" href="/post/show/<?= $post->p_id ?>">1<span class="time-text">小时前</span></a>
            </div>
        </div>
    </li>
    <?php endforeach; ?>
<?php endif ?>
</ul>

{/block}

{block name="js"}
<script>
    // 确保DOM加载完毕
    document.addEventListener('DOMContentLoaded', () => {
        highlightActiveLink('tag');
    });
</script>
{/block}