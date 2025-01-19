<?php

namespace core;

class Paginate
{
    public int $totalRows;
    public int $eachPage;
    public int $maxPage;
    public int $currentPage = 1;
    public string $limit;
    public $firstPage;
    public $prevPage;
    public array $listPage = [];
    public $nextPage;
    public $lastPage;
    public $skipPage;
    public string $currentUrl;

    public function __construct(int $totalRows, int $eachPage = 10)
    {
        $totalRows < 1 ? $this->maxPage = 1 : $this->maxPage = ceil($totalRows / $eachPage);

        $this->totalRows = $totalRows;
        $this->eachPage  = $eachPage;

        //修正当前页码
        $this->currentPage = max(1, min(PAGE_NUMBER, $this->maxPage));

        // 获取URL
        $this->currentUrl = '/' . lcfirst(CONTROLLER_NAME) . '/' . METHOD_NAME . (URL_SEGMENT != '' ? '/' . URL_SEGMENT : '');

        $suffix = PAGE_SUFFIX ? PAGE_SUFFIX : '/';

        $this->limit     = ' limit ' . (($this->currentPage - 1) * $eachPage) . ',' . $eachPage;
        // 抽象URL生成
        $getsRec         = $this->addGet();

        $this->firstPage = $this->currentUrl.'/page1';
        $this->prevPage   = $this->currentUrl.'/page'.($this->currentPage - 1);
        $this->nextPage  = $this->currentUrl.'/page'.($this->currentPage + 1);
        $this->lastPage  = $this->currentUrl.'/page'.$this->maxPage;

        // 确定起始和结束页码
        $start = max(1, $this->currentPage - 2);
        $end = min($this->maxPage, $this->currentPage + 3);

        // 确保至少显示5个页码
        if ($end - $start < 5) {
            $start = max(1, $end - 5);
        }

        // 构建页码列表
        for ($i = $start; $i <= $end; $i++) {
            $this->listPage[$i] = $this->currentUrl . '/page' . $i;
        }

        // dd($this->currentPage);

        // 跳转分页
        $this->skipPage = '<select onchange="location.href=\'' . $this->currentUrl . '/page\'+this.value+\'' . $suffix . $getsRec . '\';">';
        for ($i = 1; $i <= $this->maxPage; $i++) {
            if ($i == $this->currentPage) {
                $this->skipPage .= '<option value="' . $i . '" selected="selected">' . $i . '</option>';
            } else {
                $this->skipPage .= '<option value="' . $i . '">' . $i . '</option>';
            }
        }
        $this->skipPage .= '</select>';
    }

    public function pager()
    {
        return [
            $this->firstPage,
            $this->prevPage,
            $this->listPage,
            $this->nextPage,
            $this->lastPage,
        ];
    }

    public function skipPager()
    {
        return $this->skipPage;
    }

    public function addGet() {
        // 检查$_GET是否为空
        if (empty($_GET)) {
            return '';
        }

        // 初始化查询字符串
        $str = '?';

        // 遍历$_GET数组
        foreach ($_GET as $k => $v) {
            // 对键和值进行过滤和转义
            $k = escape($k);
            $v = escape($v);

            // 如果键或值为空，则跳过该参数
            if (empty($k) || empty($v)) {
                continue;
            }

            // 拼接键和值到查询字符串
            $str .= $k . '=' . $v . '&';
        }

        // 移除末尾的'&'字符
        $str = rtrim($str, '&');

        // 返回最终的查询字符串
        return $str;
    }
}
