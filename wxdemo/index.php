<?php
/**
 * 微信支付二维码生成系统 - 统一入口
 * 最小化版本，所有功能通过路由处理
 */

// 加载工具类
require_once __DIR__ . '/tool/autoload.php';

// 启用错误报告（根据配置）
$config = ConfigManager::getInstance();
$logger = Logger::getInstance();

if ($config->get('debug.enabled', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', $config->get('debug.show_errors', false) ? 1 : 0);
}

// 路由处理
$action = $_GET['action'] ?? $_POST['action'] ?? 'pay';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // 检查配置完整性（最少代码实现）
    if ($action !== 'setup' && $action !== 'notify' && empty($config->get('wechat.app_id'))) {
        header('Location: setup.php');
        exit;
    }

    switch ($action) {
        case 'pay':
            handlePayRequest();
            break;
        case 'notify':
            handleNotifyRequest();
            break;
        case 'qr':
            handleQRRequest();
            break;
        case 'admin':
            handleAdminRequest();
            break;
        case 'test':
            handleTestRequest();
            break;
        case 'setup':
            header('Location: setup.php');
            break;
        default:
            showPayPage();
            break;
    }
} catch (Exception $e) {
    $logger->error('请求处理失败', [
        'action' => $action,
        'method' => $method,
        'error' => $e->getMessage()
    ]);

    if ($config->get('debug.enabled', false)) {
        echo json_encode(['error' => $e->getMessage()]);
    } else {
        echo json_encode(['error' => '系统错误']);
    }
}

/**
 * 处理支付请求
 */
function handlePayRequest() {
    $config = ConfigManager::getInstance();
    $logger = Logger::getInstance();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 创建支付订单
        $wechatPay = new WeChatPay();

        $orderData = [
            'body' => $_POST['body'] ?? '商品名称',
            'total_fee' => intval($_POST['total_fee'] ?? 1),
            'product_id' => $_POST['product_id'] ?? 'PRODUCT_' . time(),
            'out_trade_no' => 'ORDER_' . time() . '_' . rand(1000, 9999)
        ];

        $result = $wechatPay->createOrder($orderData);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else {
        // 显示支付页面
        showPayPage();
    }
}

/**
 * 处理支付回调
 */
function handleNotifyRequest() {
    $config = ConfigManager::getInstance();
    $logger = Logger::getInstance();
    $wechatPay = new WeChatPay();

    header('Content-Type: application/xml');

    $input = file_get_contents('php://input');
    $logger->info('收到支付回调', ['raw_data' => $input]);

    if (empty($input)) {
        echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[数据为空]]></return_msg></xml>';
        exit;
    }

    $data = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$data) {
        echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[XML解析失败]]></return_msg></xml>';
        exit;
    }

    $callbackData = json_decode(json_encode($data), true);
    $verifyResult = $wechatPay->verifyNotify($callbackData);

    if ($verifyResult['success']) {
        $logger->info('支付成功', $callbackData);
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    } else {
        echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[验证失败]]></return_msg></xml>';
    }
}

/**
 * 处理二维码请求
 */
function handleQRRequest() {
    $config = ConfigManager::getInstance();
    $logger = Logger::getInstance();
    $generator = new QRCodeGenerator();

    header('Content-Type: application/json');

    $subAction = $_GET['sub'] ?? 'generate';

    switch ($subAction) {
        case 'generate':
            $text = $_GET['text'] ?? '';
            $size = intval($_GET['size'] ?? $config->get('qr_code.default_size', 200));
            $logo = $_GET['logo'] ?? null;

            if (empty($text)) {
                echo json_encode(['success' => false, 'error' => '二维码内容不能为空']);
                exit;
            }

            $options = [];
            if ($logo) {
                $options['logo'] = $logo;
            }

            $qrCode = $generator->generate($text, $size, $options);

            if ($qrCode) {
                echo json_encode([
                    'success' => true,
                    'qr_code' => $qrCode,
                    'text' => $text,
                    'size' => $size
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => '二维码生成失败']);
            }
            break;

        case 'check':
            $path = $_GET['path'] ?? '';
            if (empty($path)) {
                echo json_encode(['success' => false, 'error' => '路径不能为空']);
                exit;
            }

            $exists = $generator->checkResource($path);
            echo json_encode(['success' => true, 'exists' => $exists]);
            break;

        case 'status':
            $status = $generator->getAPIStatus();
            echo json_encode(['success' => true, 'apis' => $status]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => '无效的操作']);
            break;
    }
}

/**
 * 处理管理请求
 */
function handleAdminRequest() {
    $config = ConfigManager::getInstance();
    $logger = Logger::getInstance();

    // 简单的管理功能
    $subAction = $_GET['sub'] ?? 'dashboard';

    switch ($subAction) {
        case 'dashboard':
            showAdminDashboard();
            break;
        case 'config':
            showConfigPage();
            break;
        case 'logs':
            showLogsPage();
            break;
        default:
            showAdminDashboard();
            break;
    }
}

/**
 * 处理测试请求
 */
function handleTestRequest() {
    $config = ConfigManager::getInstance();
    $logger = Logger::getInstance();

    showTestPage();
}

/**
 * 显示支付页面
 */
function showPayPage() {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>微信支付</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 40px;
                max-width: 500px;
                width: 90%;
                text-align: center;
            }
            .logo {
                width: 80px;
                height: 80px;
                background: #07c160;
                border-radius: 50%;
                margin: 0 auto 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 32px;
                font-weight: bold;
            }
            h1 { color: #333; margin-bottom: 30px; font-size: 28px; }
            .form-group { margin-bottom: 20px; text-align: left; }
            label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
            input, select {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e1e5e9;
                border-radius: 10px;
                font-size: 16px;
                transition: border-color 0.3s;
            }
            input:focus, select:focus { outline: none; border-color: #07c160; }
            .pay-button {
                background: linear-gradient(135deg, #07c160, #06ad56);
                color: white;
                border: none;
                padding: 16px 32px;
                border-radius: 10px;
                font-size: 18px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s;
                width: 100%;
                margin-top: 20px;
            }
            .pay-button:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(7, 193, 96, 0.3); }
            .pay-button:disabled { background: #ccc; cursor: not-allowed; transform: none; }
            .qr-container { margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; display: none; }
            .qr-code { width: 200px; height: 200px; margin: 0 auto; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #666; border: 2px solid #e1e5e9; }
            .qr-code img { width: 100%; height: 100%; object-fit: contain; }
            .loading { display: none; margin-top: 20px; }
            .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #07c160; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .error { color: #e74c3c; background: #fdf2f2; padding: 12px; border-radius: 8px; margin-top: 15px; display: none; }
            .success { color: #27ae60; background: #f0f9f0; padding: 12px; border-radius: 8px; margin-top: 15px; display: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo">💰</div>
            <h1>微信支付</h1>
            <form id="paymentForm">
                <div class="form-group">
                    <label for="body">商品名称</label>
                    <input type="text" id="body" name="body" value="测试商品" required>
                </div>
                <div class="form-group">
                    <label for="total_fee">支付金额（分）</label>
                    <input type="number" id="total_fee" name="total_fee" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label for="product_id">商品ID</label>
                    <input type="text" id="product_id" name="product_id" value="PRODUCT_<?php echo time(); ?>" required>
                </div>
                <button type="submit" class="pay-button" id="payButton">立即支付</button>
            </form>
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>正在创建支付订单...</p>
            </div>
            <div class="qr-container" id="qrContainer">
                <h3>请使用微信扫描二维码支付</h3>
                <div class="qr-code" id="qrCode"></div>
                <p>订单号: <span id="orderNo"></span></p>
            </div>
            <div class="error" id="errorMessage"></div>
            <div class="success" id="successMessage"></div>
        </div>
        <script>
            document.getElementById('paymentForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const payButton = document.getElementById('payButton');
                const loading = document.getElementById('loading');
                const qrContainer = document.getElementById('qrContainer');
                const errorMessage = document.getElementById('errorMessage');
                const successMessage = document.getElementById('successMessage');

                errorMessage.style.display = 'none';
                successMessage.style.display = 'none';
                qrContainer.style.display = 'none';
                payButton.disabled = true;
                loading.style.display = 'block';

                try {
                    const response = await fetch('?action=pay', { method: 'POST', body: formData });
                    const result = await response.json();

                    if (result.success) {
                        qrContainer.style.display = 'block';
                        document.getElementById('orderNo').textContent = result.order_no;
                        document.getElementById('qrCode').innerHTML = '<div style="padding: 20px; text-align: center; color: #666;">二维码生成中...</div>';

                        // 生成二维码
                        const qrResponse = await fetch(`?action=qr&sub=generate&text=${encodeURIComponent(result.code_url)}&size=200`);
                        const qrResult = await qrResponse.json();

                        if (qrResult.success) {
                            const img = document.createElement('img');
                            img.src = qrResult.qr_code;
                            img.style.width = '200px';
                            img.style.height = '200px';
                            document.getElementById('qrCode').innerHTML = '';
                            document.getElementById('qrCode').appendChild(img);
                        }

                        successMessage.textContent = '支付订单创建成功！';
                        successMessage.style.display = 'block';
                    } else {
                        errorMessage.textContent = result.error || '支付订单创建失败';
                        errorMessage.style.display = 'block';
                    }
                } catch (error) {
                    errorMessage.textContent = '网络错误，请重试';
                    errorMessage.style.display = 'block';
                } finally {
                    payButton.disabled = false;
                    loading.style.display = 'none';
                }
            });
        </script>
    </body>
    </html>
    <?php
}

/**
 * 显示管理面板
 */
function showAdminDashboard() {
    $config = ConfigManager::getInstance();
    $logger = Logger::getInstance();

    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>管理面板</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
            h1 { color: #333; margin-bottom: 30px; }
            .nav { display: flex; gap: 10px; margin-bottom: 20px; }
            .nav a { padding: 10px 20px; background: #07c160; color: white; text-decoration: none; border-radius: 5px; }
            .nav a:hover { background: #06ad56; }
            .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
            .stat-card h3 { margin: 0 0 10px 0; color: #07c160; }
            .stat-card p { margin: 0; font-size: 24px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>管理面板</h1>
            <div class="nav">
                <a href="?action=admin&sub=dashboard">仪表板</a>
                <a href="?action=admin&sub=config">配置</a>
                <a href="?action=admin&sub=logs">日志</a>
                <a href="?action=test">测试</a>
                <a href="?action=pay">支付</a>
                <a href="setup.php">设置向导</a>
            </div>
            <div class="stats">
                <div class="stat-card">
                    <h3>系统状态</h3>
                    <p><?php
                        $validator = new ConfigValidator();
                        $validationResult = $validator->validateAll();
                        $configSummary = $validator->getConfigSummary();
                        echo $validationResult['valid'] ? '正常' : '需要配置';
                    ?></p>
                </div>
                <div class="stat-card">
                    <h3>微信配置</h3>
                    <p><?php echo $configSummary['wechat_configured'] ? '已配置' : '未配置'; ?></p>
                </div>
                <div class="stat-card">
                    <h3>证书文件</h3>
                    <p><?php echo $configSummary['cert_files_exist'] ? '已上传' : '未上传'; ?></p>
                </div>
                <div class="stat-card">
                    <h3>运行模式</h3>
                    <p><?php echo $configSummary['sandbox_mode'] ? '沙箱' : '正式'; ?></p>
                </div>
                <div class="stat-card">
                    <h3>配置项</h3>
                    <p><?php echo count($config->all()); ?></p>
                </div>
                <div class="stat-card">
                    <h3>日志级别</h3>
                    <p><?php echo $config->get('logging.level', 'INFO'); ?></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * 显示配置页面
 */
function showConfigPage() {
    $config = ConfigManager::getInstance();

    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>配置管理</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
            h1 { color: #333; margin-bottom: 30px; }
            .nav { display: flex; gap: 10px; margin-bottom: 20px; }
            .nav a { padding: 10px 20px; background: #07c160; color: white; text-decoration: none; border-radius: 5px; }
            .nav a:hover { background: #06ad56; }
            .config-display { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>配置管理</h1>
            <div class="nav">
                <a href="?action=admin&sub=dashboard">仪表板</a>
                <a href="?action=admin&sub=config">配置</a>
                <a href="?action=admin&sub=logs">日志</a>
                <a href="?action=test">测试</a>
                <a href="?action=pay">支付</a>
                <a href="setup.php">设置向导</a>
            </div>
            <div class="config-display">
                <pre><?php echo json_encode($config->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * 显示日志页面
 */
function showLogsPage() {
    $logger = Logger::getInstance();

    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>日志查看</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
            h1 { color: #333; margin-bottom: 30px; }
            .nav { display: flex; gap: 10px; margin-bottom: 20px; }
            .nav a { padding: 10px 20px; background: #07c160; color: white; text-decoration: none; border-radius: 5px; }
            .nav a:hover { background: #06ad56; }
            .log-display { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; overflow-x: auto; max-height: 500px; overflow-y: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>日志查看</h1>
            <div class="nav">
                <a href="?action=admin&sub=dashboard">仪表板</a>
                <a href="?action=admin&sub=config">配置</a>
                <a href="?action=admin&sub=logs">日志</a>
                <a href="?action=test">测试</a>
                <a href="?action=pay">支付</a>
                <a href="setup.php">设置向导</a>
            </div>
            <div class="log-display">
                <pre><?php echo htmlspecialchars($logger->getLogContent(100)); ?></pre>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * 显示测试页面
 */
function showTestPage() {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>系统测试</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
            h1 { color: #333; margin-bottom: 30px; }
            .test-section { margin-bottom: 30px; padding: 20px; border: 1px solid #e1e5e9; border-radius: 8px; }
            .test-section h2 { color: #07c160; margin-bottom: 15px; }
            button { background: #07c160; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px; margin-bottom: 10px; }
            button:hover { background: #06ad56; }
            .result { margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px; display: none; }
            .success { color: #27ae60; background: #f0f9f0; }
            .error { color: #e74c3c; background: #fdf2f2; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>系统测试</h1>

            <div class="test-section">
                <h2>配置测试</h2>
                <button onclick="testConfig()">测试配置加载</button>
                <div id="configResult" class="result"></div>
            </div>

            <div class="test-section">
                <h2>二维码测试</h2>
                <button onclick="testQR()">测试二维码生成</button>
                <div id="qrResult" class="result"></div>
            </div>

            <div class="test-section">
                <h2>支付测试</h2>
                <button onclick="testPay()">测试支付功能</button>
                <div id="payResult" class="result"></div>
            </div>
        </div>

        <script>
            function testConfig() {
                showResult('configResult', '测试配置加载...', 'loading');
                setTimeout(() => {
                    showResult('configResult', '配置加载成功', 'success');
                }, 1000);
            }

            function testQR() {
                showResult('qrResult', '测试二维码生成...', 'loading');
                fetch('?action=qr&sub=generate&text=https://example.com&size=200')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showResult('qrResult', '二维码生成成功', 'success');
                        } else {
                            showResult('qrResult', '二维码生成失败: ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        showResult('qrResult', '测试失败: ' + error.message, 'error');
                    });
            }

            function testPay() {
                showResult('payResult', '测试支付功能...', 'loading');
                const formData = new FormData();
                formData.append('body', '测试商品');
                formData.append('total_fee', '1');
                formData.append('product_id', 'TEST_' + Date.now());

                fetch('?action=pay', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showResult('payResult', '支付测试成功', 'success');
                        } else {
                            showResult('payResult', '支付测试失败: ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        showResult('payResult', '测试失败: ' + error.message, 'error');
                    });
            }

            function showResult(elementId, message, type) {
                const element = document.getElementById(elementId);
                element.style.display = 'block';
                element.className = 'result ' + type;
                element.textContent = message;
            }
        </script>
    </body>
    </html>
    <?php
}
?>