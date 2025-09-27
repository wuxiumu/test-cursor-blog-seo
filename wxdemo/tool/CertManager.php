<?php
/**
 * 证书文件管理器
 * 支持文件上传和文本提交
 */

class CertManager {
    private $certDir;
    private $logger;

    public function __construct() {
        $this->certDir = __DIR__ . '/../1710407126_20250927_cert/';
        $this->logger = Logger::getInstance();
    }

    /**
     * 创建证书目录
     */
    public function createCertDir() {
        if (!is_dir($this->certDir)) {
            if (!mkdir($this->certDir, 0755, true)) {
                throw new Exception('无法创建证书目录: ' . $this->certDir);
            }
            $this->logger->info('证书目录已创建', ['dir' => $this->certDir]);
        }
        return true;
    }

    /**
     * 上传证书文件
     * @param array $files $_FILES数组
     * @return array
     */
    public function uploadCertFiles($files) {
        try {
            $this->createCertDir();

            $result = ['success' => true, 'files' => []];

            // 处理证书文件
            if (isset($files['cert_file']) && $files['cert_file']['error'] === UPLOAD_ERR_OK) {
                $certPath = $this->certDir . 'apiclient_cert.pem';
                if (move_uploaded_file($files['cert_file']['tmp_name'], $certPath)) {
                    $result['files']['cert'] = 'apiclient_cert.pem';
                    $this->logger->info('证书文件上传成功', ['file' => $certPath]);
                } else {
                    $result['success'] = false;
                    $result['error'] = '证书文件上传失败';
                }
            }

            // 处理私钥文件
            if (isset($files['key_file']) && $files['key_file']['error'] === UPLOAD_ERR_OK) {
                $keyPath = $this->certDir . 'apiclient_key.pem';
                if (move_uploaded_file($files['key_file']['tmp_name'], $keyPath)) {
                    $result['files']['key'] = 'apiclient_key.pem';
                    $this->logger->info('私钥文件上传成功', ['file' => $keyPath]);
                } else {
                    $result['success'] = false;
                    $result['error'] = '私钥文件上传失败';
                }
            }

            return $result;

        } catch (Exception $e) {
            $this->logger->error('证书文件上传失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 通过文本提交保存证书文件
     * @param string $certContent 证书内容
     * @param string $keyContent 私钥内容
     * @return array
     */
    public function saveCertFromText($certContent, $keyContent) {
        try {
            $this->createCertDir();

            $result = ['success' => true, 'files' => []];

            // 保存证书文件
            if (!empty($certContent)) {
                $certPath = $this->certDir . 'apiclient_cert.pem';
                if (file_put_contents($certPath, $certContent)) {
                    $result['files']['cert'] = 'apiclient_cert.pem';
                    $this->logger->info('证书文件保存成功', ['file' => $certPath]);
                } else {
                    $result['success'] = false;
                    $result['error'] = '证书文件保存失败';
                }
            }

            // 保存私钥文件
            if (!empty($keyContent)) {
                $keyPath = $this->certDir . 'apiclient_key.pem';
                if (file_put_contents($keyPath, $keyContent)) {
                    $result['files']['key'] = 'apiclient_key.pem';
                    $this->logger->info('私钥文件保存成功', ['file' => $keyPath]);
                } else {
                    $result['success'] = false;
                    $result['error'] = '私钥文件保存失败';
                }
            }

            return $result;

        } catch (Exception $e) {
            $this->logger->error('证书文件保存失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 检查证书文件是否存在
     * @return array
     */
    public function checkCertFiles() {
        $certPath = $this->certDir . 'apiclient_cert.pem';
        $keyPath = $this->certDir . 'apiclient_key.pem';

        return [
            'cert_exists' => file_exists($certPath),
            'key_exists' => file_exists($keyPath),
            'cert_path' => $certPath,
            'key_path' => $keyPath,
            'cert_dir' => $this->certDir,
        ];
    }

    /**
     * 获取证书文件内容
     * @return array
     */
    public function getCertContent() {
        $certPath = $this->certDir . 'apiclient_cert.pem';
        $keyPath = $this->certDir . 'apiclient_key.pem';

        return [
            'cert_content' => file_exists($certPath) ? file_get_contents($certPath) : '',
            'key_content' => file_exists($keyPath) ? file_get_contents($keyPath) : '',
        ];
    }

    /**
     * 删除证书文件
     * @return array
     */
    public function deleteCertFiles() {
        try {
            $certPath = $this->certDir . 'apiclient_cert.pem';
            $keyPath = $this->certDir . 'apiclient_key.pem';

            $deleted = [];

            if (file_exists($certPath) && unlink($certPath)) {
                $deleted[] = 'apiclient_cert.pem';
            }

            if (file_exists($keyPath) && unlink($keyPath)) {
                $deleted[] = 'apiclient_key.pem';
            }

            $this->logger->info('证书文件已删除', ['files' => $deleted]);

            return ['success' => true, 'deleted' => $deleted];

        } catch (Exception $e) {
            $this->logger->error('证书文件删除失败', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
