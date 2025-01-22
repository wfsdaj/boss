<?php

namespace boss;

class Image
{
    protected $filename;
    protected $image;
    protected $image_type;
    protected $image_format;

    /**
     * 加载图像文件
     *
     * @param string $filename 图像文件路径
     * @return bool 加载成功返回true，否则返回false
     */
    public function load(string $filename): bool
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException("文件不存在或不可读: {$filename}");
        }

        $image_info = getimagesize($filename);
        if ($image_info === false) {
            throw new \RuntimeException("无法获取图像信息: {$filename}");
        }

        $this->filename = $filename;
        $this->image_type = $image_info[2];
        $this->image_format = trim(image_type_to_extension($this->image_type), ".");

        switch ($this->image_type) {
            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($filename);
                break;
            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($filename);
                break;
            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($filename);
                break;
            case IMAGETYPE_WEBP:
                $this->image = imagecreatefromwebp($filename);
                break;
            case IMAGETYPE_BMP:
                $this->image = imagecreatefrombmp($filename);
                break;
            case IMAGETYPE_WBMP:
                $this->image = imagecreatefromwbmp($filename);
                break;
            default:
                throw new \RuntimeException("不支持的图像类型: {$filename}");
        }

        return $this->image !== false;
    }

    /**
     * 保存图像到文件
     *
     * @param string $result_filename 保存的文件路径
     * @param int|string $image_type 图像类型（IMAGETYPE_* 常量或字符串）
     * @param int $compression 压缩质量（仅适用于JPEG）
     * @return bool 保存成功返回true，否则返回false
     */
    public function save(string $result_filename, $image_type, int $compression = 100): bool
    {
        if ($this->image === null) {
            throw new \RuntimeException("没有加载的图像");
        }

        $image_type = is_int($image_type) ? $image_type : $this->getImageTypeFromString($image_type);

        switch ($image_type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($this->image, $result_filename, $compression);
            case IMAGETYPE_GIF:
                return imagegif($this->image, $result_filename);
            case IMAGETYPE_PNG:
                return imagepng($this->image, $result_filename);
            case IMAGETYPE_WEBP:
                return imagewebp($this->image, $result_filename);
            case IMAGETYPE_BMP:
                return imagebmp($this->image, $result_filename);
            case IMAGETYPE_WBMP:
                return imagewbmp($this->image, $result_filename);
            default:
                throw new \RuntimeException("不支持的图像类型: {$image_type}");
        }
    }

    /**
     * 输出图像到浏览器
     *
     * @param int|string $image_type 图像类型（IMAGETYPE_* 常量或字符串）
     * @return bool 输出成功返回true，否则返回false
     */
    public function output($image_type = IMAGETYPE_JPEG): bool
    {
        if ($this->image === null) {
            throw new \RuntimeException("没有加载的图像");
        }

        $image_type = is_int($image_type) ? $image_type : $this->getImageTypeFromString($image_type);

        switch ($image_type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($this->image);
            case IMAGETYPE_GIF:
                return imagegif($this->image);
            case IMAGETYPE_PNG:
                return imagepng($this->image);
            case IMAGETYPE_WEBP:
                return imagewebp($this->image);
            case IMAGETYPE_BMP:
                return imagebmp($this->image);
            case IMAGETYPE_WBMP:
                return imagewbmp($this->image);
            default:
                throw new \RuntimeException("不支持的图像类型: {$image_type}");
        }
    }

    /**
     * 获取图像资源
     *
     * @return resource|null 返回图像资源，如果未加载则返回null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * 获取图像宽度
     *
     * @return int 图像宽度
     */
    public function getWidth(): int
    {
        return $this->image ? imagesx($this->image) : 0;
    }

    /**
     * 获取图像高度
     *
     * @return int 图像高度
     */
    public function getHeight(): int
    {
        return $this->image ? imagesy($this->image) : 0;
    }

    /**
     * 获取图像类型
     *
     * @return int 图像类型（IMAGETYPE_* 常量）
     */
    public function getImageType(): int
    {
        return $this->image_type;
    }

    /**
     * 获取图像格式
     *
     * @return string 图像格式（如 "jpg", "png" 等）
     */
    public function getImageFormat(): string
    {
        return $this->image_format;
    }

    /**
     * 获取文件路径
     *
     * @return string 文件路径
     */
    public function getFilePath(): string
    {
        return $this->filename;
    }

    /**
     * 按高度等比缩放图像
     *
     * @param int $height 目标高度
     */
    public function resizeToHeight(int $height): void
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * 按宽度等比缩放图像
     *
     * @param int $width 目标宽度
     */
    public function resizeToWidth(int $width): void
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getHeight() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * 按比例缩放图像
     *
     * @param float $scale 缩放比例（百分比）
     */
    public function scale(float $scale): void
    {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getHeight() * $scale / 100;
        $this->resize($width, $height);
    }

    /**
     * 调整图像大小
     *
     * @param float $width 目标宽度
     * @param float $height 目标高度
     */
    public function resize(float $width, float $height): void
    {
        $width = max(1, ceil($width));
        $height = max(1, ceil($height));
        $new_image = imagecreatetruecolor($width, $height);
        $this->imageCopyResampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $new_image;
    }

    /**
     * 居中裁剪并调整图像大小
     *
     * @param int $width 目标宽度
     * @param int $height 目标高度
     */
    public function resizeInCenter(int $width, int $height): void
    {
        $width = max(1, $width);
        $height = max(1, $height);
        $new_image = imagecreatetruecolor($width, $height);
        $img_width = $this->getWidth();
        $img_height = $this->getHeight();
        if ($img_width == 0 || $img_height == 0) return;

        $ratio = min($width / $img_width, $height / $img_height);
        $new_width = $img_width * $ratio;
        $new_height = $img_height * $ratio;
        $x = ($width - $new_width) / 2;
        $y = ($height - $new_height) / 2;

        $this->imageCopyResampled($new_image, $this->image, $x, $y, 0, 0, $new_width, $new_height, $img_width, $img_height);
        $this->image = $new_image;
    }

    /**
     * 裁剪图像
     *
     * @param int $width 裁剪宽度
     * @param int $height 裁剪高度
     * @param int $x 裁剪起始X坐标
     * @param int $y 裁剪起始Y坐标
     */
    public function cropBySelectedRegion(int $width, int $height, int $x, int $y): void
    {
        $width = max(1, $width);
        $height = max(1, $height);
        $new_image = imagecreatetruecolor($width, $height);
        $this->imageCopyResampled($new_image, $this->image, 0, 0, $x, $y, $width, $height, $width, $height);
        $this->image = $new_image;
    }

    /**
     * 添加RGB背景色
     *
     * @param int $red 红色值 (0-255)
     * @param int $green 绿色值 (0-255)
     * @param int $blue 蓝色值 (0-255)
     * @return array RGB颜色数组
     */
    public function addRgbColor(int $red, int $green, int $blue): array
    {
        return [$red, $green, $blue];
    }

    /**
     * 居中调整图像大小并添加背景
     *
     * @param int $width 目标宽度
     * @param int $height 目标高度
     * @param array|string|null $background 背景颜色（RGB数组或十六进制字符串）
     */
    public function resizeAllInCenter(int $width, int $height, $background = null): void
    {
        $width = max(1, $width);
        $height = max(1, $height);
        list($r, $g, $b) = $background ? (is_array($background) ? $background : sscanf($background, "#%02x%02x%02x")) : [0, 0, 0];
        $new_image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($new_image, $r, $g, $b);
        if ($background) {
            imagefilledrectangle($new_image, 0, 0, $width, $height, $color);
        } else {
            imagecolortransparent($new_image, $color);
        }

        $img_width = $this->getWidth();
        $img_height = $this->getHeight();
        if ($img_width == 0 || $img_height == 0) return;

        $ratio = min($width / $img_width, $height / $img_height);
        $new_width = $img_width * $ratio;
        $new_height = $img_height * $ratio;
        $x = ($width - $new_width) / 2;
        $y = ($height - $new_height) / 2;

        $this->imageCopyResampled($new_image, $this->image, $x, $y, 0, 0, $new_width, $new_height, $img_width, $img_height);
        $this->image = $new_image;
    }

    /**
     * 图像复制并重新采样
     *
     * @param resource $dst_image 目标图像资源
     * @param resource $src_image 源图像资源
     * @param float $dst_x 目标X坐标
     * @param float $dst_y 目标Y坐标
     * @param float $src_x 源X坐标
     * @param float $src_y 源Y坐标
     * @param float $dst_w 目标宽度
     * @param float $dst_h 目标高度
     * @param int $src_w 源宽度
     * @param int $src_h 源高度
     */
    protected function imageCopyResampled($dst_image, $src_image, float $dst_x, float $dst_y, float $src_x, float $src_y, float $dst_w, float $dst_h, int $src_w, int $src_h): void
    {
        imagecopyresampled(
            $dst_image,
            $src_image,
            intval(ceil($dst_x)),
            intval(ceil($dst_y)),
            intval(ceil($src_x)),
            intval(ceil($src_y)),
            intval(ceil($dst_w)),
            intval(ceil($dst_h)),
            $src_w,
            $src_h
        );
    }

    /**
     * 从字符串获取图像类型
     *
     * @param string $image_type 图像类型字符串（如 "jpg", "png" 等）
     * @return int 图像类型（IMAGETYPE_* 常量）
     */
    private function getImageTypeFromString(string $image_type): int
    {
        switch (strtolower($image_type)) {
            case 'jpeg':
            case 'jpg':
                return IMAGETYPE_JPEG;
            case 'gif':
                return IMAGETYPE_GIF;
            case 'png':
                return IMAGETYPE_PNG;
            case 'webp':
                return IMAGETYPE_WEBP;
            case 'bmp':
                return IMAGETYPE_BMP;
            case 'wbmp':
                return IMAGETYPE_WBMP;
            default:
                throw new \RuntimeException("不支持的图像类型: {$image_type}");
        }
    }
}