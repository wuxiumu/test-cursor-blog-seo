<?php
/**
 * 二维码生成器工具类
 * 支持多API备用策略和图片处理
 */

class QRCodeGenerator {
    private $config;
    private $logger;
    private $apis;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->config = ConfigManager::getInstance();
        $this->logger = Logger::getInstance();
        $this->initializeAPIs();
    }

    /**
     * 初始化API列表
     */
    private function initializeAPIs() {
        $this->apis = $this->config->get('qr_code.apis', []);

        // 按优先级排序
        usort($this->apis, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }

    /**
     * 生成二维码
     * @param string $text 二维码内容
     * @param int $size 图片尺寸
     * @param array $options 选项
     * @return string|false 返回base64编码的图片数据或false
     */
    public function generate($text, $size = null, $options = []) {
        $size = $size ?: $this->config->get('qr_code.default_size', 200);

        // 合并默认选项
        $defaultOptions = $this->config->get('qr_code.style', []);
        $options = array_merge($defaultOptions, $options);

        try {
            $this->logger->info('开始生成二维码', [
                'text' => $text,
                'size' => $size,
                'options' => $options
            ]);

            // 1. 从API获取二维码数据
            $qrImageData = $this->generateFromAPI($text, $size);

            if (!$qrImageData) {
                throw new Exception('无法从任何API获取二维码数据');
            }

            // 2. 处理图片（添加logo等）
            $processedImage = $this->processImage($qrImageData, $size, $options);

            // 3. 转换为base64
            $result = 'data:image/png;base64,' . base64_encode($processedImage);

            $this->logger->info('二维码生成成功', [
                'size' => $size,
                'has_logo' => !empty($options['logo'])
            ]);

            return $result;

        } catch (Exception $e) {
            $this->logger->error('二维码生成失败', [
                'message' => $e->getMessage(),
                'text' => $text,
                'size' => $size
            ]);
            return false;
        }
    }

    /**
     * 从API生成二维码
     * @param string $text 内容
     * @param int $size 尺寸
     * @return string|false
     */
    private function generateFromAPI($text, $size) {
        foreach ($this->apis as $api) {
            if (!$api['enabled']) {
                continue;
            }

            try {
                $url = $this->buildAPIUrl($api, $text, $size);
                $imageData = $this->fetchImage($url, $api['timeout'] ?? 10);

                if ($imageData && $this->validateImageData($imageData)) {
                    $this->logger->info('API成功', ['api' => $api['name']]);
                    return $imageData;
                }
            } catch (Exception $e) {
                $this->logger->warning('API失败', [
                    'api' => $api['name'],
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return false;
    }

    /**
     * 构建API URL
     */
    private function buildAPIUrl($api, $text, $size) {
        $params = $api['params'];
        $params['data'] = $text;
        $params['size'] = $size . 'x' . $size;

        $queryString = http_build_query($params);
        return $api['url'] . '?' . $queryString;
    }

    /**
     * 获取网络图片
     */
    private function fetchImage($url, $timeout = 10) {
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'user_agent' => 'Mozilla/5.0 (compatible; QRCodeGenerator/1.0)'
            ]
        ]);

        return @file_get_contents($url, false, $context);
    }

    /**
     * 验证图片数据
     */
    private function validateImageData($data) {
        $imageTypes = [
            "\xFF\xD8\xFF", // JPEG
            "\x89PNG\r\n\x1a\n", // PNG
            "GIF87a", // GIF87a
            "GIF89a"  // GIF89a
        ];

        foreach ($imageTypes as $header) {
            if (strpos($data, $header) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 处理图片
     */
    private function processImage($imageData, $size, $options) {
        // 创建图片资源
        $qrResource = imagecreatefromstring($imageData);
        if (!$qrResource) {
            throw new Exception('无法创建二维码图片资源');
        }

        // 调整尺寸
        $qrResource = $this->resizeImage($qrResource, $size, $size);

        // 添加logo（如果提供）
        if (!empty($options['logo']) && $this->checkResourceExists($options['logo'])) {
            $qrResource = $this->addLogo($qrResource, $options['logo'], $size, $options);
        }

        // 输出为PNG
        ob_start();
        imagepng($qrResource);
        $result = ob_get_contents();
        ob_end_clean();

        imagedestroy($qrResource);

        return $result;
    }

    /**
     * 调整图片尺寸
     */
    private function resizeImage($resource, $width, $height) {
        $newResource = imagecreatetruecolor($width, $height);
        imagecopyresampled(
            $newResource, $resource,
            0, 0, 0, 0,
            $width, $height,
            imagesx($resource), imagesy($resource)
        );
        imagedestroy($resource);
        return $newResource;
    }

    /**
     * 检查资源是否存在
     */
    private function checkResourceExists($path) {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $headers = @get_headers($path, 1);
            return $headers && strpos($headers[0], '200') !== false;
        } else {
            return file_exists($path);
        }
    }

    /**
     * 添加logo到二维码
     */
    private function addLogo($qrResource, $logoPath, $qrSize, $options) {
        try {
            // 获取logo
            $logoData = $this->getImageData($logoPath);
            $logoResource = imagecreatefromstring($logoData);

            if (!$logoResource) {
                return $qrResource; // 如果logo加载失败，返回原图
            }

            // 计算logo尺寸
            $logoSizeRatio = $this->config->get('qr_code.logo_size_ratio', 0.2);
            $logoSize = intval($qrSize * $logoSizeRatio);

            // 调整logo尺寸
            $resizedLogo = imagecreatetruecolor($logoSize, $logoSize);
            imagecopyresampled(
                $resizedLogo, $logoResource,
                0, 0, 0, 0,
                $logoSize, $logoSize,
                imagesx($logoResource), imagesy($logoResource)
            );

            // 计算位置（居中）
            $logoX = ($qrSize - $logoSize) / 2;
            $logoY = ($qrSize - $logoSize) / 2;

            // 添加白色背景
            $white = imagecolorallocate($qrResource, 255, 255, 255);
            imagefilledrectangle(
                $qrResource,
                $logoX-2, $logoY-2,
                $logoX+$logoSize+2, $logoY+$logoSize+2,
                $white
            );

            // 合并logo
            imagecopy($qrResource, $resizedLogo, $logoX, $logoY, 0, 0, $logoSize, $logoSize);

            // 清理资源
            imagedestroy($logoResource);
            imagedestroy($resizedLogo);

            return $qrResource;

        } catch (Exception $e) {
            $this->logger->warning('Logo添加失败', ['error' => $e->getMessage()]);
            return $qrResource;
        }
    }

    /**
     * 获取图片数据
     */
    private function getImageData($path) {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (compatible; QRCodeGenerator/1.0)'
                ]
            ]);
            return file_get_contents($path, false, $context);
        } else {
            return file_get_contents($path);
        }
    }

    /**
     * 检查资源是否存在
     * @param string $path 资源路径
     * @return bool
     */
    public function checkResource($path) {
        return $this->checkResourceExists($path);
    }

    /**
     * 获取API状态
     * @return array
     */
    public function getAPIStatus() {
        $status = [];
        foreach ($this->apis as $api) {
            $status[] = [
                'name' => $api['name'],
                'enabled' => $api['enabled'],
                'priority' => $api['priority'],
                'url' => $api['url']
            ];
        }
        return $status;
    }
}
