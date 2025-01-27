<?php

namespace boss;

class Paginate
{
    public $totalRows;
    public $eachPage;
    public $maxPage;
    public $limit;
    public $currentPage = 1;
    public $firstPage;
    public $prevPage;
    public $listPage = [];
    public $nextPage;
    public $lastPage;
    public $skipPage;
    public $currentUrl;

    public function __construct($totalRows, $eachPage = 10)
    {
        $totalRows < 1 ? $this->maxPage = 1 : $this->maxPage = ceil($totalRows / $eachPage);
        $this->totalRows = $totalRows;
        $this->eachPage  = $eachPage;
        //修正当前页码
        if (PAGE_NUMBER < 1) {
            $this->currentPage = 1;
        } else if (PAGE_NUMBER > $this->maxPage) {
            $this->currentPage = $this->maxPage;
        } else {
            $this->currentPage = PAGE_NUMBER;
        }
        //获取URL
        if (URL_SEGMENT != '') {
            $this->currentUrl = '/' . strtolower(CONTROLLER_NAME) . '/' . METHOD_NAME . '/' . URL_SEGMENT;
        } else {
            $this->currentUrl = '/' . strtolower(CONTROLLER_NAME) . '/' . METHOD_NAME;
        }
        $suffix = 'PAGE_SUFFIX' ? PAGE_SUFFIX : '/';
        $this->limit     = ' LIMIT ' . (($this->currentPage - 1) * $eachPage) . ',' . $eachPage;
        $getsRec         = $this->addGet();
        $this->firstPage = $this->currentUrl . '/page_1' . $suffix . $getsRec;
        $this->prevPage   = $this->currentUrl . '/page_' . ($this->currentPage - 1) . $suffix . $getsRec;
        $this->nextPage  = $this->currentUrl . '/page_' . ($this->currentPage + 1) . $suffix . $getsRec;
        $this->lastPage  = $this->currentUrl . '/page_' . $this->maxPage . $suffix . $getsRec;
        //分页列表
        if ($this->currentPage <= 3) {
            $start = 1;
            $end = 6;
        } else {
            $start = $this->currentPage - 2;
            $end = $this->currentPage + 3;
        }
        if ($end > $this->maxPage) {
            $end = $this->maxPage;
        }
        if ($end - $start < 5) {
            $start = $end - 5;
        }
        if ($start < 1) {
            $start = 1;
        }
        for ($i = $start; $i <= $end; $i++) {
            $this->listPage[$i] = $this->currentUrl . '/page_' . $i . $suffix . $getsRec;
        }
        //跳转分页
        $this->skipPage = '<select onchange="location.href=\'' . $this->currentUrl . '/page_\'+this.value+\'' . $suffix . $getsRec . '\';">';
        for ($i = 1; $i <= $this->maxPage; $i++) {
            if ($i == $this->currentPage) {
                $this->skipPage .= '<option value="' . $i . '" selected="selected">' . $i . '</option>';
            } else {
                $this->skipPage .= '<option value="' . $i . '">' . $i . '</option>';
            }
        }
        $this->skipPage .= '</select>';
    }

    /**
     * 获取分页链接
     *
     * @return array<string, mixed> 返回分页链接的关联数组
     */
    public function pager(): array
    {
        return [
            'firstPage' => $this->firstPage,
            'prevPage'  => $this->prevPage,
            'listPage'  => $this->listPage,
            'nextPage'  => $this->nextPage,
            'lastPage'  => $this->lastPage,
        ];
    }

    /**
     * 获取跳转分页的HTML代码
     *
     * @return string 返回跳转分页的HTML代码
     */
    public function skipPager(): string
    {
        return htmlspecialchars($this->skipPage, ENT_QUOTES, 'UTF-8');
    }


    public function addGet(array $queryParams = []): string
    {
        // 如果没有传递参数，则默认使用 $_GET
        $queryParams = empty($queryParams) ? $_GET : $queryParams;

        // 如果参数为空，则返回空字符串
        if (empty($queryParams)) {
            return '';
        }

        // 使用 http_build_query 构建查询字符串并返回
        return '?' . http_build_query($queryParams);
    }
}
