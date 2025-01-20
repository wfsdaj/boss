<?php

declare(strict_types=1);

namespace boss;

use Exception;

/**
 * 视图渲染类
 * 用于渲染模板，处理布局和缓存
 */
class View
{
    private string $layout = '';           // 布局文件
    private array  $data = [];             // 页面数据（数组形式）
    private string $content = '';          // 内容
    private array  $blocks = [];           // 块内容
    private string $view_path = '';        // 模板目录
    private string $cache_path = 'cache';  // 缓存目录
    private string $view_suffix = '.html'; // 模板文件默认后缀名

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
        $this->view_path = $config['view_path'] ?? '';

        // 设置缓存目录
        $this->cache_path = $config['cache_path'] ?? 'cache';

        // 设置模板文件后缀名
        $this->view_suffix = $config['view_suffix'] ?? '.html';

        // 创建缓存目录如果不存在
        if (!is_dir($this->cache_path) && !mkdir($this->cache_path, 0755, true)) {
            throw new Exception('无法创建缓存目录: ' . htmlspecialchars($this->cache_path, ENT_QUOTES, 'UTF-8'));
        }
    }

    /**
     * 渲染内容文件
     *
     * @param string $contentFile 内容文件路径（不带后缀）
     * @param array $data 页面数据数组
     * @throws Exception 如果内容文件不存在
     *
     * @return void
     */
    public function render(string $contentFile, array $data = []): void
    {
        // 将输入数据安全转换为HTML，防止XSS攻击
        $this->data = array_map(fn($item) => is_string($item) ? htmlspecialchars($item, ENT_QUOTES, 'UTF-8') : $item, $data);

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
     *
     * @return void
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
            $this->layout = $matches[1];
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
     * @return void
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
     * @return void
     */
    private function renderLayout(): void
    {
        if (empty($this->layout)) {
            throw new Exception('布局文件未设置');
        }

        $layoutFile = $this->resolvePath($this->layout);

        if (!file_exists($layoutFile)) {
            throw new Exception('布局文件不存在');
        }

        // 检查缓存文件是否存在且未过期
        $cacheFile = $this->cache_path . '/' . md5($layoutFile) . '.php';
        if (file_exists($cacheFile) && (filemtime($cacheFile) >= filemtime($layoutFile))) {
            include $cacheFile; // 直接包含缓存文件
        } else {
            // 读取布局文件内容
            $layoutContent = file_get_contents($layoutFile);

            // 替换布局中的块内容
            $layoutContent = $this->replaceBlocks($layoutContent);

            // 将替换后的布局内容保存到临时文件中
            $tempLayoutFile = $this->cache_path . '/temp_layout_' . md5($layoutFile) . '.php';
            file_put_contents($tempLayoutFile, $layoutContent);

            // 生成缓存文件
            ob_start();
            extract($this->data, EXTR_OVERWRITE); // 提取数据到当前作用域
            include $tempLayoutFile; // 包含临时布局文件
            $output = ob_get_clean();

            // 检查缓存目录是否可写
            if (!is_writable($this->cache_path)) {
                throw new Exception('缓存目录不可写: ' . $this->cache_path);
            }

            // 写入缓存文件
            file_put_contents($cacheFile, $output);

            // 删除临时布局文件
            unlink($tempLayoutFile);

            // 输出内容
            echo $output;
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
            fn($matches) => $this->blocks[$matches[1]] ?? '',
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
        $resolvedPath = $this->view_path . $path;

        if (!preg_match('/\.[a-zA-Z0-9]+$/', $resolvedPath)) {
            $resolvedPath .= $this->view_suffix;
        }

        $resolvedPath = realpath($resolvedPath) ?: $resolvedPath;
        if (!$resolvedPath) {
            throw new Exception("无法解析路径: " . htmlspecialchars($path, ENT_QUOTES, 'UTF-8'));
        }

        return $resolvedPath;
    }

    /**
     * 清理过期的缓存文件
     *
     * @param int $maxLifetime 缓存文件的最大生命周期（秒）
     * @return void
     */
    public function clearExpiredCache(int $maxLifetime = 86400): void
    {
        $files = glob($this->cache_path . '/*.php');
        if ($files === false) {
            throw new Exception('无法读取缓存目录: ' . $this->cache_path);
        }
        foreach ($files as $file) {
            if (file_exists($file) && is_writable($file) && filemtime($file) < time() - $maxLifetime) {
                unlink($file);
            }
        }
    }
}
