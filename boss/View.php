<?php

namespace boss;

use Exception;

class View
{
    private string $layout = '';           // 布局文件
    private array  $data = [];             // 页面数据（数组形式）
    private string $content = '';          // 内容
    private array  $blocks = [];           // 块内容
    private string $view_path = '';        // 模板目录
    private string $cache_path;            // 缓存目录
    private string $view_suffix = '.php';  // 模板文件默认后缀名

    /**
     * 构造函数，初始化缓存目录和模板目录
     *
     * @param array $config 配置数组，支持以下键：
     *                      - 'view_path' (string): 模板目录
     *                      - 'cache_path' (string): 缓存目录
     *                      - 'view_suffix' (string): 模板文件默认后缀名
     */
    public function __construct(array $config = [])
    {
        // 设置模板目录
        $this->view_path = rtrim($config['view_path'] ?? '', '/');

        // 设置缓存目录
        $this->cache_path = rtrim($config['cache_path'] ?? 'cache', '/');

        // 设置模板文件后缀名
        $this->view_suffix = $config['view_suffix'] ?? '.php';

        // 创建缓存目录如果不存在
        if (!is_dir($this->cache_path)) {
            mkdir($this->cache_path, 0755, true);
        }
    }

    /**
     * 设置模板目录
     *
     * @param string $view_path 模板目录
     */
    public function setViewPath(string $view_path): void
    {
        $this->view_path = rtrim($view_path, '/');
    }

    /**
     * 设置模板文件后缀名
     *
     * @param string $view_suffix 模板文件后缀名
     */
    public function setViewSuffix(string $view_suffix): void
    {
        $this->view_suffix = $view_suffix;
    }

    /**
     * 渲染内容文件
     *
     * @param string $contentFile 内容文件路径
     * @param array $data 页面数据数组
     * @throws Exception 如果内容文件不存在
     */
    public function render(string $contentFile, array $data = []): void
    {
        // 将输入数据安全转换为HTML
        $this->data = array_map(function ($item) {
            return htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
        }, $data);

        $this->loadContent($contentFile);

        // 如果没有定义布局模板，直接输出内容
        if (empty($this->layout)) {
            echo $this->content;
        } else {
            // 否则，渲染布局模板
            $this->renderLayout();
        }
    }

    /**
     * 加载并解析内容文件
     *
     * @param string $contentFile 内容文件路径
     * @throws Exception 如果内容文件不存在
     */
    private function loadContent(string $contentFile): void
    {
        $contentFile = $this->resolvePath($contentFile);

        if (!file_exists($contentFile)) {
            throw new Exception('内容文件不存在：' . htmlspecialchars($contentFile, ENT_QUOTES, 'UTF-8'));
        }

        // 获取内容文件内容
        $content = file_get_contents($contentFile);

        // 匹配并设置布局文件
        if (preg_match('/\{layout\s+name="([^"]+)"\s*\/?\}/', $content, $matches)) {
            $this->layout = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . $this->view_suffix;
            // 移除布局声明
            $content = str_replace($matches[0], '', $content);
        }

        // 提取块内容
        $this->extractBlocks($content);

        // 开始输出缓冲
        ob_start();
        extract($this->data, EXTR_OVERWRITE); // 提取数据到当前作用域
        include $contentFile; // 包含模板文件，模板中可以访问 $data 中的数据
        $this->content = ob_get_clean(); // 获取缓冲区内容
    }

    /**
     * 提取块内容
     *
     * @param string $content 内容文件内容
     */
    private function extractBlocks(string $content): void
    {
        // 匹配所有块内容
        if (preg_match_all('/\{block\s+name="([^"]+)"\}(.*?)\{\/block\}/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $this->blocks[$match[1]] = $match[2]; // 保存块内容
            }
        }
    }

    /**
     * 渲染布局文件
     *
     * @throws Exception 如果布局文件未设置或不存在
     */
    private function renderLayout(): void
    {
        if (empty($this->layout)) {
            throw new Exception('布局文件未设置');
        }

        $layoutFile = $this->resolvePath($this->layout);

        if (!file_exists($layoutFile)) {
            throw new Exception('布局文件不存在', 500);
        }

        // 提取数据供布局使用
        extract($this->data, EXTR_OVERWRITE);
        $content = $this->content;

        // 检查缓存文件是否存在且未过期
        $cacheFile = $this->cache_path . '/' . md5($layoutFile) . '.php';
        if (file_exists($cacheFile) && (filemtime($cacheFile) >= filemtime($layoutFile))) {
            include $cacheFile; // 直接包含缓存文件
        } else {
            // 生成缓存文件
            ob_start();
            extract($this->data, EXTR_OVERWRITE); // 提取数据到当前作用域
            include $layoutFile; // 包含布局文件，布局中可以访问 $data 中的数据
            $output = ob_get_clean();

            // 替换布局中的块内容
            $output = $this->replaceBlocks($output);

            // 检查缓存目录是否可写
            if (!is_writable($this->cache_path)) {
                throw new Exception('缓存目录不可写', 500);
            }

            file_put_contents($cacheFile, $output); // 写入缓存文件
            echo $output; // 输出内容
        }
    }

    /**
     * 替换布局中的块内容
     *
     * @param string $layoutContent 布局文件内容
     * @return string 替换后的布局内容
     */
    private function replaceBlocks(string $layoutContent): string
    {
        // 替换布局中的块内容
        return preg_replace_callback(
            '/\{block\s+name="([^"]+)"\}\s*\{\/block\}/s',
            function ($matches) {
                $blockName = $matches[1];
                return $this->blocks[$blockName] ?? ''; // 返回块内容，如果不存在则返回空字符串
            },
            $layoutContent
        );
    }

    /**
     * 解析文件路径
     *
     * @param string $path 文件路径
     * @return string 解析后的绝对路径
     */
    private function resolvePath(string $path): string
    {
        if (strpos($path, '/') === 0 || strpos($path, DIRECTORY_SEPARATOR) === 0 || strpos($path, ':') === 1) {
            return $path;
        }

        $resolvedPath = $this->view_path . DIRECTORY_SEPARATOR . ltrim($path, '/');

        if (!preg_match('/\.[a-zA-Z0-9]+$/', $resolvedPath)) {
            $resolvedPath .= $this->view_suffix;
        }

        return realpath($resolvedPath) ?: $resolvedPath;
    }

    /**
     * 清理过期的缓存文件
     *
     * @param int $maxLifetime 缓存文件的最大生命周期（秒）
     */
    public function clearExpiredCache(int $maxLifetime = 86400): void
    {
        $files = glob($this->cache_path . '/*.php');
        foreach ($files as $file) {
            if (file_exists($file) && is_writable($file) && filemtime($file) < time() - $maxLifetime) {
                unlink($file);
            }
        }
    }
}
