<?php

declare(strict_types=1);

namespace boss;

use Exception;

class Captcha
{
    // 图片基本参数
    private int $width;            // 图片宽度
    private int $height;           // 图片高度
    private $img;                  // 绘图资源
    private string $securityCode;  // 验证码内容

    // 配置项
    public array $bgcolor     = [255, 255, 255];  // 背景颜色
    public array $codeColor   = [51, 51, 51];     // 验证码颜色
    public int $fontSize      = 22;               // 验证码字符大小
    public string $fontFamily;                    // 字体文件路径
    public bool $noise        = true;             // 是否绘制干扰
    public int $noiseNumber   = 6;                // 干扰点数量
    public int $noiseLines    = 5;                // 干扰线数量
    public string $sessionName = 'captcha';       // 验证码在 Session 中储存的名称

    private int $totalChars = 4;  // 总计字符数
    private int $numbers    = 1;  // 数字形式字符数量

    // 常量
    public const CHARACTER_SET = 'abcdefghjkmnpqrstwxyz';
    public const NOISE_SET     = '2345678abcdefghjkmnpqrstuvwxyz';
    public const DEFAULT_FONT  = 'cuteaurora.ttf';

    /**
     * Captcha constructor.
     * @param int $width         图片宽度
     * @param int $height        图片高度
     * @param int $totalChars    总字符数
     * @param int $numbers       数字字符数
     * @param string $fontFamily 字体文件路径
     * @throws Exception 如果字体文件不存在
     */
    public function __construct(int $width = 90, int $height = 40, int $totalChars = 4, int $numbers = 1, string $fontFamily = self::DEFAULT_FONT)
    {
        $this->fontFamily = __DIR__ . '/font/' . $fontFamily;
        $this->width = $width;
        $this->height = $height;
        $this->totalChars = $totalChars;
        $this->numbers = $numbers;

        // 校验字体文件
        if (!is_file($this->fontFamily)) {
            throw new Exception('验证码字体文件不存在: ' . $this->fontFamily);
        }
    }

    /**
     * 设置验证码字符
     */
    private function setCharacters(): void
    {
        $text = [];

        // 生成字母字符
        $charCount = $this->totalChars - $this->numbers;
        for ($i = 0; $i < $charCount; $i++) {
            $text[] = self::CHARACTER_SET[random_int(0, strlen(self::CHARACTER_SET) - 1)];
        }

        // 生成数字字符
        for ($i = 0; $i < $this->numbers; $i++) {
            $text[] = random_int(2, 9);  // 限定范围为 2 到 9，避免容易识别的字符（0,1）
        }

        shuffle($text);
        $this->securityCode = implode('', $text);
    }

    /**
     * 绘制验证码背景、干扰及文字
     */
    private function createImage(): void
    {
        // 创建画布
        $this->img = imagecreatetruecolor($this->width, $this->height);

        // 启用抗锯齿
        imageantialias($this->img, true);

        // 背景颜色
        $bgColor = imagecolorallocate($this->img, $this->bgcolor[0], $this->bgcolor[1], $this->bgcolor[2]);
        imagefill($this->img, 0, 0, $bgColor);

        // 绘制干扰
        if ($this->noise) {
            $this->writeNoise();
            $this->writeNoiseLines();
        }

        // 文字颜色
        $textColor = imagecolorallocate($this->img, $this->codeColor[0], $this->codeColor[1], $this->codeColor[2]);

        // 计算文字位置
        $textBox = imagettfbbox($this->fontSize, 0, $this->fontFamily, $this->securityCode);
        $textWidth = $textBox[2] - $textBox[0];
        $x = ($this->width - $textWidth) / 2;
        $y = ($this->height + $this->fontSize) / 2;

        // 添加文字
        imagefttext($this->img, $this->fontSize, 0, $x, $y, $textColor, $this->fontFamily, $this->securityCode);
    }

    /**
     * 绘制干扰点
     */
    private function writeNoise(): void
    {
        for ($i = 0; $i < $this->noiseNumber; $i++) {
            $noiseColor = imagecolorallocate($this->img, random_int(150, 225), random_int(150, 225), random_int(150, 225));
            $noiseX = random_int(0, $this->width);
            $noiseY = random_int(0, $this->height);
            imagestring($this->img, 5, $noiseX, $noiseY, self::NOISE_SET[random_int(0, strlen(self::NOISE_SET) - 1)], $noiseColor);
        }
    }

    /**
     * 绘制干扰线
     */
    private function writeNoiseLines(): void
    {
        for ($i = 0; $i < $this->noiseLines; $i++) {
            $lineColor = imagecolorallocate($this->img, random_int(150, 225), random_int(150, 225), random_int(150, 225));
            imageline($this->img, random_int(0, $this->width), random_int(0, $this->height), random_int(0, $this->width), random_int(0, $this->height), $lineColor);
        }
    }

    /**
     * 生成并输出验证码图像
     */
    public function make(): void
    {
        // 设置验证码字符
        $this->setCharacters();

        // 保存验证码到 Session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[$this->sessionName] = $this->securityCode;

        // 创建并输出图像
        header('Content-type:image/png');
        $this->createImage();
        imagepng($this->img);
        imagedestroy($this->img);
    }
}
