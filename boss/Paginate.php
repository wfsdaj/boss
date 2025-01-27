<?php

namespace boss;

class Paginate
{
    /** @var int 总记录数 */
    public int $totalRows;

    /** @var int 每页记录数 */
    public int $eachPage;

    /** @var int 最大页数 */
    public int $maxPage;

    /** @var string LIMIT 子句 */
    public string $limit;

    /** @var int 当前页码 */
    public int $currentPage = 1;

    /** @var string 首页 URL */
    public string $firstPage;

    /** @var string 上一页 URL */
    public string $prevPage;

    /** @var array<int, string> 分页列表 [页码 => URL] */
    public array $listPage = [];

    /** @var string 下一页 URL */
    public string $nextPage;

    /** @var string 最后一页 URL */
    public string $lastPage;

    /** @var string 分页跳转的 HTML 下拉菜单 */
    public string $skipPage;

    /** @var string 当前基础 URL */
    public string $currentUrl;

    /**
     * 构造函数
     *
     * @param int $totalRows 总记录数
     * @param int $eachPage 每页记录数（默认值为 10）
     */
    public function __construct(int $totalRows, int $eachPage = 10)
    {
        // 初始化总记录数和每页记录数
        $this->totalRows = max(0, $totalRows);
        $this->eachPage = max(1, $eachPage);

        // 计算最大页码
        $this->maxPage = max(1, (int)ceil($totalRows / $eachPage));

        // 修正当前页码
        $this->currentPage = $this->normalizeCurrentPage(PAGE_NUMBER);

        // 修正当前页码
        $this->currentPage = $this->normalizeCurrentPage(PAGE_NUMBER);

        // 构建当前 URL
        $this->currentUrl = $this->getCurrentUrl();

        // 构建分页链接和分页数据
        $this->buildPagination();
    }

    /**
     * 修正当前页码
     *
     * @param int $page 当前页码
     * @return int 修正后的页码
     */
    private function normalizeCurrentPage(int $page): int
    {
        return max(1, min($page, $this->maxPage));
    }

    /**
     * 构建当前 URL
     *
     * @return string 返回基础 URL
     */
    private function getCurrentUrl(): string
    {
        $baseUrl = '/' . strtolower(CONTROLLER_NAME) . '/' . METHOD_NAME;
        return URL_SEGMENT ? $baseUrl . '/' . URL_SEGMENT : $baseUrl;
    }

    /**
     * 构建分页数据
     */
    private function buildPagination(): void
    {
        // URL 后缀和附加 GET 参数
        $suffix = PAGE_SUFFIX ?: '/';
        $getsRec = $this->addGet();

        // 构建 LIMIT 子句
        $this->limit = sprintf(' LIMIT %d, %d', ($this->currentPage - 1) * $this->eachPage, $this->eachPage);

        // 构建分页链接
        $this->firstPage = $this->currentUrl . '/page_1' . $suffix . $getsRec;
        $this->prevPage = $this->currentUrl . '/page_' . max(1, $this->currentPage - 1) . $suffix . $getsRec;
        $this->nextPage = $this->currentUrl . '/page_' . min($this->maxPage, $this->currentPage + 1) . $suffix . $getsRec;
        $this->lastPage = $this->currentUrl . '/page_' . $this->maxPage . $suffix . $getsRec;

        // 构建分页列表
        $this->listPage = $this->buildPaginationList($suffix, $getsRec);

        // 构建跳转分页 HTML
        $this->skipPage = $this->buildSkipPage($suffix, $getsRec);
    }

    /**
     * 构建分页列表
     *
     * @param string $suffix URL 后缀
     * @param string $getsRec 附加 GET 参数
     * @return array<int, string> 分页列表
     */
    private function buildPaginationList(string $suffix, string $getsRec): array
    {
        $start = max(1, $this->currentPage - 2);
        $end = min($this->maxPage, $this->currentPage + 3);

        if ($end - $start < 5) {
            $start = max(1, $end - 5);
        }

        $listPage = [];
        for ($i = $start; $i <= $end; $i++) {
            $listPage[$i] = $this->currentUrl . '/page_' . $i . $suffix . $getsRec;
        }

        return $listPage;
    }

    /**
     * 构建跳转分页 HTML
     *
     * @param string $suffix 后缀
     * @param string $getsRec 附加的 GET 参数
     * @return string 跳转分页 HTML
     */
    private function buildSkipPage(string $suffix, string $getsRec): string
    {
        $skipPage = '<select onchange="location.href=\'' . htmlspecialchars($this->currentUrl, ENT_QUOTES, 'UTF-8') . '/page_\'+this.value+\'' . htmlspecialchars($suffix . $getsRec, ENT_QUOTES, 'UTF-8') . '\';">';
        for ($i = 1; $i <= $this->maxPage; $i++) {
            $selected = $i === $this->currentPage ? ' selected="selected"' : '';
            $skipPage .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
        }
        $skipPage .= '</select>';
        return $skipPage;
    }

    /**
     * 获取附加的 GET 参数
     *
     * @return string 拼接的 GET 参数字符串
     */
    private function addGet(): string
    {
        if (empty($_GET)) {
            return '';
        }

        $queryParams = [];
        foreach ($_GET as $key => $value) {
            $key = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
            $value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            $queryParams[] = $key . '=' . $value;
        }

        return '?' . implode('&', $queryParams);
    }
}
