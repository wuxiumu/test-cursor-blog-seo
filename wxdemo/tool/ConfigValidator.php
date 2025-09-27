<?php
/**
 * 配置验证器
 * 验证配置文件的完整性和正确性
 */

class ConfigValidator {
    private $config;
    private $errors = [];
    private $warnings = [];

    /**
     * 构造函数
     */
    public function __construct() {
        $this->config = ConfigManager::getInstance();
    }

    /**
     * 验证所有配置
     * @return array 验证结果
     */
    public function validateAll() {
        $this->errors = [];
        $this->warnings = [];

        // 验证微信支付配置
        $this->validateWechatConfig();

        // 验证二维码配置
        $this->validateQRCodeConfig();

        // 验证日志配置
        $this->validateLoggingConfig();

        // 验证安全配置
        $this->validateSecurityConfig();

        // 验证文件权限
        $this->validateFilePermissions();

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }

    /**
     * 验证微信支付配置
     */
    private function validateWechatConfig() {
        $wechatConfig = $this->config->get('wechat', []);

        // 检查必填字段
        $requiredFields = ['app_id', 'mch_id', 'key', 'notify_url'];
        foreach ($requiredFields as $field) {
            if (empty($wechatConfig[$field])) {
                $this->errors[] = "微信支付配置缺少必填字段: {$field}";
            }
        }

        // 检查AppID格式
        if (!empty($wechatConfig['app_id'])) {
            if (!preg_match('/^wx[a-f0-9]{16}$/', $wechatConfig['app_id'])) {
                $this->errors[] = "微信AppID格式不正确，应为wx开头的18位字符串";
            }
        }

        // 检查商户号格式
        if (!empty($wechatConfig['mch_id'])) {
            if (!preg_match('/^\d{10}$/', $wechatConfig['mch_id'])) {
                $this->errors[] = "商户号格式不正确，应为10位数字";
            }
        }

        // 检查API密钥长度
        if (!empty($wechatConfig['key']) && strlen($wechatConfig['key']) < 32) {
            $this->warnings[] = "API密钥长度建议不少于32位";
        }

        // 检查通知URL
        if (!empty($wechatConfig['notify_url'])) {
            if (!filter_var($wechatConfig['notify_url'], FILTER_VALIDATE_URL)) {
                $this->errors[] = "通知URL格式不正确";
            }
            if (!preg_match('/^https?:\/\//', $wechatConfig['notify_url'])) {
                $this->warnings[] = "通知URL建议使用HTTPS协议";
            }
        }

        // 检查沙箱模式
        if (isset($wechatConfig['sandbox']) && $wechatConfig['sandbox']) {
            $this->warnings[] = "当前使用沙箱模式，仅用于测试，生产环境请设置为false";
        }
    }

    /**
     * 验证二维码配置
     */
    private function validateQRCodeConfig() {
        $qrConfig = $this->config->get('qr_code', []);

        // 检查默认尺寸
        $defaultSize = $qrConfig['default_size'] ?? 200;
        if ($defaultSize < 50 || $defaultSize > 1000) {
            $this->warnings[] = "二维码默认尺寸建议在50-1000像素之间";
        }

        // 检查Logo文件
        if (!empty($qrConfig['default_logo'])) {
            $logoPath = $qrConfig['default_logo'];
            if (!file_exists($logoPath)) {
                $this->warnings[] = "Logo文件不存在: {$logoPath}";
            }
        }

        // 检查API配置
        $apis = $qrConfig['apis'] ?? [];
        $enabledApis = array_filter($apis, function($api) {
            return $api['enabled'] ?? false;
        });

        if (empty($enabledApis)) {
            $this->warnings[] = "没有启用的二维码生成API";
        }
    }

    /**
     * 验证日志配置
     */
    private function validateLoggingConfig() {
        $loggingConfig = $this->config->get('logging', []);

        if ($loggingConfig['enabled'] ?? false) {
            $logFile = $loggingConfig['file'] ?? '';
            if (!empty($logFile)) {
                $logDir = dirname($logFile);
                if (!is_dir($logDir)) {
                    $this->errors[] = "日志目录不存在: {$logDir}";
                } elseif (!is_writable($logDir)) {
                    $this->errors[] = "日志目录不可写: {$logDir}";
                }
            }
        }
    }

    /**
     * 验证安全配置
     */
    private function validateSecurityConfig() {
        $securityConfig = $this->config->get('security', []);

        // 检查管理密码
        $adminPassword = $securityConfig['admin_password'] ?? '';
        if (strlen($adminPassword) < 6) {
            $this->warnings[] = "管理密码长度建议不少于6位";
        }

        // 检查HTTPS配置
        if (!($securityConfig['force_https'] ?? false)) {
            $this->warnings[] = "生产环境建议启用HTTPS";
        }
    }

    /**
     * 验证文件权限
     */
    private function validateFilePermissions() {
        // 检查证书文件
        $certDir = __DIR__ . '/../1710407126_20250927_cert/';
        if (is_dir($certDir)) {
            $certFiles = ['apiclient_cert.pem', 'apiclient_key.pem'];
            foreach ($certFiles as $file) {
                $filePath = $certDir . $file;
                if (file_exists($filePath)) {
                    if (!is_readable($filePath)) {
                        $this->errors[] = "证书文件不可读: {$filePath}";
                    }
                }
            }
        } else {
            $this->warnings[] = "证书目录不存在，将使用沙箱模式";
        }

        // 检查配置文件权限
        $configFile = __DIR__ . '/../config/app.php';
        if (file_exists($configFile)) {
            if (!is_readable($configFile)) {
                $this->errors[] = "配置文件不可读: {$configFile}";
            }
        }
    }

    /**
     * 获取配置状态摘要
     * @return array
     */
    public function getConfigSummary() {
        $wechatConfig = $this->config->get('wechat', []);
        $securityConfig = $this->config->get('security', []);

        return [
            'wechat_configured' => !empty($wechatConfig['app_id']) &&
                                 !empty($wechatConfig['mch_id']) &&
                                 !empty($wechatConfig['key']),
            'sandbox_mode' => $wechatConfig['sandbox'] ?? true,
            'https_enabled' => $securityConfig['force_https'] ?? false,
            'cert_files_exist' => $this->checkCertFiles(),
            'config_file_writable' => is_writable(__DIR__ . '/../config/'),
        ];
    }

    /**
     * 检查证书文件
     * @return bool
     */
    private function checkCertFiles() {
        $certDir = __DIR__ . '/../1710407126_20250927_cert/';
        $certFiles = ['apiclient_cert.pem', 'apiclient_key.pem'];

        foreach ($certFiles as $file) {
            if (!file_exists($certDir . $file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 生成配置建议
     * @return array
     */
    public function getConfigSuggestions() {
        $suggestions = [];

        $wechatConfig = $this->config->get('wechat', []);

        if (empty($wechatConfig['app_id'])) {
            $suggestions[] = "请设置微信AppID";
        }

        if (empty($wechatConfig['mch_id'])) {
            $suggestions[] = "请设置微信支付商户号";
        }

        if (empty($wechatConfig['key'])) {
            $suggestions[] = "请设置微信支付API密钥";
        }

        if (empty($wechatConfig['notify_url'])) {
            $suggestions[] = "请设置支付结果通知URL";
        }

        if (!file_exists(__DIR__ . '/../1710407126_20250927_cert/apiclient_cert.pem')) {
            $suggestions[] = "请上传微信支付证书文件";
        }

        return $suggestions;
    }
}
