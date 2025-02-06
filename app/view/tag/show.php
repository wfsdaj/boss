<div class="breadcrumb d-flex align-items-center px-3 js-scroll">
    <div class="btn btn-icon me-4 js-back" aria-label="返回" role="button" tabindex="0">
        <i class="iconfont icon-left-arrow fs-20"></i>
    </div>
    <h2 class="w-100 fs-18 hand js-top"><?= $page_title ?? ''; ?></h2>
    <div class="dropdown">
        <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="iconfont icon-more"></i></button>
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
        <div class="avatars">
            <img class="avatar avatar-xs" src="/img/avatar/1.png" alt="">
            <img class="avatar avatar-xs" src="/img/avatar/2.png" alt="">
            <img class="avatar avatar-xs" src="/img/avatar/3.png" alt="">
            <span class="fs-15 ms-2"><b>2321</b> 位成员</span>
        </div>
        <div class="ms-auto">
            <a class="btn btn-sm btn-outline rounded-pill text-dark" href="#">加入</a>
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
    <li class="feed-item d-flex js-tap" data-href="/post/show/503" data-pid="503">
        <div class="feed-item-count">
            <spap class="post-num">
                <i>0</i>
                <span class="list-triangle-border"></span>
                <span class="list-triangle-body"></span>
            </spap>
        </div>
        <div class="d-flex flex-column w-100">
            <p class="typo-text fs-18">“一位前辈告诉我，穷不怪父，孝不比兄，<br>苦不责妻，气不凶子，方能称之为男人。” ​​​</p>
            <img class="img-fluid feed-item-img rounded align-self-baseline mt-2" src="/upload/20240104/bf9c1ca39e91e634f37b.jpg" alt="" data-zoomable="" loading="lazy" decoding="async" style="cursor: zoom-in;">
            <div class="d-flex align-items-center mt-1">
                <a class="link-dark fs-15 post-meta" href="/user/profile/23">bbb</a>
                <a class="link-muted fs-15" href="/post/show/503">1<span class="time-text">小时前</span></a>
            </div>
        </div>
    </li>
</ul>

<script>
// 确保DOM加载完毕
document.addEventListener('DOMContentLoaded', () => {
    highlightActiveLink('group');
    document.querySelector('a[data-active="qun-news"]').classList.add('active');
});
</script>