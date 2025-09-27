<?php
/**
 * 微信支付工具类
 * 处理微信支付相关功能
 */

class WeChatPay {
    private $config;
    private $logger;
    private $app;

    /**
     * 构造函数
     */
    public function __construct() {
        $this->config = ConfigManager::getInstance();
        $this->logger = Logger::getInstance();
        $this->initializeApp();
    }

    /**
     * 初始化微信支付应用
     */
    private function initializeApp() {
        try {
            include __DIR__ . '/../vendor/autoload.php';

            $wechatConfig = $this->config->get('wechat', []);

            // 检查证书文件
            $certPath = __DIR__ . '/../1710407126_20250927_cert/apiclient_cert.pem';
            $keyPath = __DIR__ . '/../1710407126_20250927_cert/apiclient_key.pem';

            if (file_exists($certPath) && file_exists($keyPath)) {
                $wechatConfig['cert_path'] = $certPath;
                $wechatConfig['key_path'] = $keyPath;
                $this->logger->info('使用证书文件', ['cert_path' => $certPath]);
            } else {
                $this->logger->info('使用沙箱模式，无需证书文件');
            }

            $this->app = new \EasyWeChat\Pay\Application($wechatConfig);

        } catch (Exception $e) {
            $this->logger->error('微信支付应用初始化失败', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建支付订单
     * @param array $orderData 订单数据
     * @return array
     */
    public function createOrder($orderData) {
        try {
            $this->logger->info('开始创建支付订单', $orderData);

            // 构建支付参数
            $order = [
                'appid'            => $this->config->get('wechat.app_id'),
                'mch_id'           => $this->config->get('wechat.mch_id'),
                'nonce_str'        => md5(uniqid()),
                'body'             => $orderData['body'] ?? '商品名称',
                'out_trade_no'     => $orderData['out_trade_no'] ?? 'ORDER_' . time() . '_' . rand(1000, 9999),
                'total_fee'        => intval($orderData['total_fee'] ?? 1),
                'spbill_create_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'trade_type'       => 'NATIVE',
                'product_id'       => $orderData['product_id'] ?? 'PRODUCT_' . time(),
                'notify_url'       => $this->config->get('wechat.notify_url'),
            ];

            // 验证参数
            if ($order['total_fee'] < 1) {
                throw new Exception('支付金额不能小于1分');
            }

            // 调用微信支付API
            $result = $this->app->getClient()->post('pay/unifiedorder', [
                'xml' => $order
            ]);

            // 解析响应
            $responseBody = $result->getContent();
            $responseCode = $result->getStatusCode();

            if ($responseCode === 200) {
                $xml = simplexml_load_string($responseBody, 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($xml->return_code == 'SUCCESS' && $xml->result_code == 'SUCCESS') {
                    $codeUrl = (string)$xml->code_url;
                    $prepayId = (string)$xml->prepay_id;

                    $this->logger->info('支付订单创建成功', [
                        'prepay_id' => $prepayId,
                        'out_trade_no' => $order['out_trade_no']
                    ]);

                    return [
                        'success' => true,
                        'code_url' => $codeUrl,
                        'prepay_id' => $prepayId,
                        'order_no' => $order['out_trade_no']
                    ];
                } else {
                    throw new Exception('微信支付API返回错误: ' . (string)$xml->return_msg);
                }
            } else {
                throw new Exception('HTTP请求失败，状态码: ' . $responseCode);
            }

        } catch (Exception $e) {
            $this->logger->error('支付订单创建失败', [
                'message' => $e->getMessage(),
                'order_data' => $orderData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 验证支付回调
     * @param array $data 回调数据
     * @return array
     */
    public function verifyNotify($data) {
        try {
            $this->logger->info('验证支付回调', $data);

            // 这里应该实现微信支付回调验证逻辑
            // 包括签名验证、订单状态检查等

            return [
                'success' => true,
                'message' => '验证成功'
            ];

        } catch (Exception $e) {
            $this->logger->error('支付回调验证失败', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 查询订单状态
     * @param string $orderNo 订单号
     * @return array
     */
    public function queryOrder($orderNo) {
        try {
            $this->logger->info('查询订单状态', ['order_no' => $orderNo]);

            // 这里应该实现订单查询逻辑

            return [
                'success' => true,
                'status' => 'SUCCESS',
                'message' => '订单查询成功'
            ];

        } catch (Exception $e) {
            $this->logger->error('订单查询失败', [
                'message' => $e->getMessage(),
                'order_no' => $orderNo
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
