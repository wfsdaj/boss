{extend name="layout/app" /}

{block name="title"}标签{/block}

{block name="main"}
<div class="breadcrumb d-flex justify-content-between align-items-center mb-1 px-3">
    <h2 class="w-100 fs-18 hand js-top">标签</h2>
</div>

<div class="m-3">
    <h2 class="fs-18">置顶标签</h2>
    <div class="p-5 text-center text-muted border-bottom">
        置顶你喜欢的标签，然后就能快速访问他们。
    </div>
</div>

<ul class="card-list list-unstyled">
{if !$tags[0]}
    <div class="placeholder fs-28">
        <p>暂时还没人建立标签</p>
        <p class="mt-4">
            <a href="" class="btn btn-lg btn-secondary rounded-pill px-4"><b>建立一个标签</b></a>
        </p>
    </div>
{else/}
    {foreach $tags[0] as $tag}
    <li class="d-flex cursor-pointer js-tap" data-href="/tag/list/{$tag->id}">
        <img class="avatar avatar-lg me-3" src="/img/avatar.jpg" alt="">
        <div>
            <strong>{$tag->name}</strong>
            <p class="text-muted fs-15">{$tag->intro}</p>
        </div>
        <div class="ms-auto">
            <a href="###" class="btn btn-icon" data-bs-toggle="tooltip" data-bs-title="置顶">
                <i class="iconfont icon-thumbtack fs-20"></i>
            </a>
        </div>
    </li>
    {/foreach}
{/if}
</ul>

<?= pageLinks($tags); ?>

{/block}

{block name="aside"}
<div class="search mt-2 mb-3">
    <i class="iconfont icon-search"></i>
    <input class="form-control search-input" type="text" placeholder="搜索功能开发中...">
</div>

<div class="tile mt-2">
    <h2 class="px-3 pb-2 fs-18 fw-bold">有什么新鲜事？</h2>
    <div href="###" class="tile-item d-flex">
        <img src="/img/avatar.jpg" width="79" height="79" alt="">
        <div class="d-flex flex-column">
            <div class="fs-15 fw-bold">摔跤大赛是真打吗？</div>
            <div class="fs-13 text-muted">摔角 . 昨天</div>
        </div>
    </div>
    <div class="tile-item d-flex flex-column">
        <div class="fs-13 text-muted">California 的趋势</div>
        <div class="fs-15 fw-bold">Buying</div>
        <div class="fs-13 text-muted">16</div>
        <!-- <button class="btn btn-icon btn-sm tile-corner" role="button">
            <i class="iconfont icon-more fs-18"></i>
        </button> -->
    </div>
</div>
{/block}

{block name="js"}
<script>
    // 确保DOM加载完毕
    document.addEventListener('DOMContentLoaded', () => {
        highlightActiveLink('tag');
    });
</script>
{/block}