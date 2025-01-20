<?php

declare(strict_types=1);

namespace boss;

class Paginate
{
    /** @var int 总记录数 */
    public int $totalRows;

    /** @var int 每页显示的记录数 */
    public int $eachPage;

    /** @var int 最大页码数 */
    public int $maxPage;

    /** @var string SQL LIMIT 子句 */
    public string $limit;

    /** @var int 当前页码 */
    public int $currentPage = 1;

    /** @var string 第一页的URL */
    public string $firstPage;

    /** @var string 上一页的URL */
    public string $prePage;

    /** @var array<int, string> 分页列表的URL */
    public array $listPage = [];

    /** @var string 下一页的URL */
    public string $nextPage;

    /** @var string 最后一页的URL */
    public string $lastPage;

    /** @var string 跳转分页的HTML代码 */
    public string $skipPage;

    /** @var string 当前页面的基础URL */
    private string $currentUrl;

    /**
     * 构造函数，初始化分页信息
     *
     * @param int $totalRows 总记录数
     * @param int $eachPage 每页显示的记录数
     * @throws \InvalidArgumentException 如果总记录数或每页记录数不合法
     */
    public function __construct(int $totalRows, int $eachPage = 10)
    {
        if ($totalRows < 0) {
            throw new \InvalidArgumentException("总记录数不能为负数");
        }
        if ($eachPage < 1) {
            throw new \InvalidArgumentException("每页记录数必须大于0");
        }

        $this->totalRows = $totalRows;
        $this->eachPage = $eachPage;
        $this->maxPage = (int)max(1, ceil($totalRows / $eachPage)); // 确保最大页码至少为1

        // 修正当前页码
        $this->currentPage = $this->normalizeCurrentPage(PAGE_NUMBER);

        // 获取当前页面的基础URL
        $this->currentUrl = $this->buildCurrentUrl();

        // 初始化分页URL
        $this->initializePageUrls();

        // 初始化跳转分页的HTML代码
        $this->skipPage = $this->buildSkipPage();
    }

    /**
     * 修正当前页码
     *
     * @param int $page 当前页码
     * @return int 修正后的当前页码
     */
    private function normalizeCurrentPage(int $page): int
    {
        return max(1, min($page, $this->maxPage));
    }

    /**
     * 构建当前页面的基础URL
     *
     * @return string 当前页面的基础URL
     */
    private function buildCurrentUrl(): string
    {
        $baseUrl = CONTROLLER_NAME . '/' . METHOD_NAME;
        return PG_URL ? $baseUrl . '/' . PG_URL : $baseUrl;
    }

    /**
     * 初始化分页URL
     */
    private function initializePageUrls(): void
    {
        $suffix = PAGE_SUFFIX ?: '/';
        $getsRec = $this->addGet();

        $this->limit     = ' LIMIT ' . (($this->currentPage - 1) * $this->eachPage) . ',' . $this->eachPage;
        $this->firstPage = $this->currentUrl . '/page_1' . $suffix . $getsRec;
        $this->prePage   = $this->currentUrl . '/page_' . max(1, $this->currentPage - 1) . $suffix . $getsRec;
        $this->nextPage  = $this->currentUrl . '/page_' . min($this->maxPage, $this->currentPage + 1) . $suffix . $getsRec;
        $this->lastPage  = $this->currentUrl . '/page_' . $this->maxPage . $suffix . $getsRec;

        // 初始化分页列表
        $this->listPage = $this->buildPageList($suffix, $getsRec);
    }

    /**
     * 构建分页列表
     *
     * @param string $suffix URL后缀
     * @param string $getsRec GET参数
     * @return array<int, string> 分页列表的URL
     */
    private function buildPageList(string $suffix, string $getsRec): array
    {
        $start = max(1, $this->currentPage - 2);
        $end   = min($this->maxPage, $this->currentPage + 3);

        if ($end - $start < 5) {
            $start = max(1, $end - 5);
        }

        $pageList = [];
        for ($i = $start; $i <= $end; $i++) {
            $pageList[$i] = $this->currentUrl . '/page_' . $i . $suffix . $getsRec;
        }

        return $pageList;
    }

    /**
     * 构建跳转分页的HTML代码
     *
     * @return string 跳转分页的HTML代码
     */
    private function buildSkipPage(): string
    {
        $skipPage = '<select onchange="location.href=\'' . $this->currentUrl . '/page_\'+this.value+\'' . (PAGE_SUFFIX ?: '/') . $this->addGet() . '\';">';
        for ($i = 1; $i <= $this->maxPage; $i++) {
            $selected = $i === $this->currentPage ? ' selected="selected"' : '';
            $skipPage .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
        }
        $skipPage .= '</select>';

        return $skipPage;
    }

    /**
     * 获取分页信息
     *
     * @return array<string, mixed> 分页信息
     */
    public function pager(): array
    {
        return [
            'firstPage' => $this->firstPage,
            'prePage'   => $this->prePage,
            'listPage'  => $this->listPage,
            'nextPage'  => $this->nextPage,
            'lastPage'  => $this->lastPage,
        ];
    }

    /**
     * 获取跳转分页的HTML代码
     *
     * @return string 跳转分页的HTML代码
     */
    public function skipPager(): string
    {
        return $this->skipPage;
    }

    /**
     * 添加GET参数
     *
     * @return string GET参数字符串
     */
    private function addGet(): string
    {
        if (empty($_GET)) {
            return '';
        }

        $params = [];
        foreach ($_GET as $key => $value) {
            $params[] = urlencode($key) . '=' . urlencode($value);
        }

        return '?' . implode('&', $params);
    }
}