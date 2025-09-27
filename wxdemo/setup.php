<?php
/**
 * 系统设置向导
 * 引导用户完成系统配置
 */

// 加载工具类
require_once __DIR__ . '/tool/autoload.php';

$config = ConfigManager::getInstance();
$validator = new ConfigValidator();
$logger = Logger::getInstance();

// 处理配置保存和证书上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_config') {
        handleConfigSave();
    } elseif ($_POST['action'] === 'upload_cert') {
        handleCertUpload();
    }
    exit;
}

// 获取当前配置状态
$validationResult = $validator->validateAll();
$configSummary = $validator->getConfigSummary();
$suggestions = $validator->getConfigSuggestions();

// 获取证书文件状态
$certManager = new CertManager();
$certStatus = $certManager->checkCertFiles();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置向导</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #07c160, #06ad56);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .status-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e1e5e9;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section h3 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #07c160;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #07c160;
        }
        .form-group .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .btn {
            background: #07c160;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn:hover {
            background: #06ad56;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e1e5e9;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        .step.active {
            background: #07c160;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 系统设置向导</h1>
            <p>请按照以下步骤完成系统配置</p>
        </div>

        <div class="content">
            <!-- 步骤指示器 -->
            <div class="step-indicator">
                <div class="step <?php echo $configSummary['wechat_configured'] ? 'completed' : 'active'; ?>">1</div>
                <div class="step <?php echo $configSummary['cert_files_exist'] ? 'completed' : ($configSummary['wechat_configured'] ? 'active' : ''); ?>">2</div>
                <div class="step <?php echo $validationResult['valid'] ? 'completed' : (($configSummary['wechat_configured'] && $configSummary['cert_files_exist']) ? 'active' : ''); ?>">3</div>
            </div>

            <!-- 配置状态 -->
            <div class="status-card">
                <h3>📊 配置状态</h3>
                <div class="status-item">
                    <span>微信支付配置</span>
                    <span class="status-badge <?php echo $configSummary['wechat_configured'] ? 'status-success' : 'status-error'; ?>">
                        <?php echo $configSummary['wechat_configured'] ? '已配置' : '未配置'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span>证书文件</span>
                    <span class="status-badge <?php echo $configSummary['cert_files_exist'] ? 'status-success' : 'status-warning'; ?>">
                        <?php echo $configSummary['cert_files_exist'] ? '已上传' : '未上传'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span>运行模式</span>
                    <span class="status-badge <?php echo $configSummary['sandbox_mode'] ? 'status-warning' : 'status-success'; ?>">
                        <?php echo $configSummary['sandbox_mode'] ? '沙箱模式' : '正式模式'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span>HTTPS</span>
                    <span class="status-badge <?php echo $configSummary['https_enabled'] ? 'status-success' : 'status-warning'; ?>">
                        <?php echo $configSummary['https_enabled'] ? '已启用' : '未启用'; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span>配置同步</span>
                    <span class="status-badge <?php echo checkConfigSync() ? 'status-success' : 'status-warning'; ?>">
                        <?php echo checkConfigSync() ? '已同步' : '未同步'; ?>
                    </span>
                </div>
            </div>

            <!-- 错误和警告 -->
            <?php if (!empty($validationResult['errors'])): ?>
                <div class="alert alert-error">
                    <strong>❌ 配置错误：</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($validationResult['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($validationResult['warnings'])): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ 配置警告：</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($validationResult['warnings'] as $warning): ?>
                            <li><?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- 配置建议 -->
            <?php if (!empty($suggestions)): ?>
                <div class="alert alert-warning">
                    <strong>💡 配置建议：</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($suggestions as $suggestion): ?>
                            <li><?php echo htmlspecialchars($suggestion); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- 配置表单 -->
            <form method="POST" id="configForm">
                <input type="hidden" name="action" value="save_config">

                <!-- 微信支付配置 -->
                <div class="form-section">
                    <h3>🔐 微信支付配置</h3>

                    <div class="form-group">
                        <label for="app_id">微信AppID *</label>
                        <input type="text" id="app_id" name="wechat[app_id]"
                               value="<?php echo htmlspecialchars($config->get('wechat.app_id', '')); ?>"
                               placeholder="wx开头的18位字符串">
                        <div class="help-text">获取方式：微信公众平台 → 开发 → 基本配置 → AppID(应用ID)</div>
                    </div>

                    <div class="form-group">
                        <label for="mch_id">商户号 *</label>
                        <input type="text" id="mch_id" name="wechat[mch_id]"
                               value="<?php echo htmlspecialchars($config->get('wechat.mch_id', '')); ?>"
                               placeholder="10位数字">
                        <div class="help-text">获取方式：微信支付商户平台 → 账户中心 → 商户信息 → 商户号</div>
                    </div>

                    <div class="form-group">
                        <label for="key">API密钥 *</label>
                        <input type="text" id="key" name="wechat[key]"
                               value="<?php echo htmlspecialchars($config->get('wechat.key', '')); ?>"
                               placeholder="32位字符串">
                        <div class="help-text">获取方式：微信支付商户平台 → 账户中心 → API安全 → 设置API密钥</div>
                        <!-- 隐藏字段：v2_secret_key 与 key 保持一致 -->
                        <input type="hidden" name="wechat[v2_secret_key]" id="v2_secret_key"
                               value="<?php echo htmlspecialchars($config->get('wechat.v2_secret_key', $config->get('wechat.key', ''))); ?>">
                    </div>

                    <div class="form-group">
                        <label for="notify_url">通知URL *</label>
                        <input type="url" id="notify_url" name="wechat[notify_url]"
                               value="<?php echo htmlspecialchars($config->get('wechat.notify_url', '')); ?>"
                               placeholder="https://yourdomain.com/notify.php">
                        <div class="help-text">用户支付成功后，微信会向此URL发送支付结果通知</div>
                    </div>

                    <div class="form-group">
                        <label for="sandbox">运行模式</label>
                        <select id="sandbox" name="wechat[sandbox]">
                            <option value="1" <?php echo $config->get('wechat.sandbox', true) ? 'selected' : ''; ?>>沙箱模式（测试）</option>
                            <option value="0" <?php echo !$config->get('wechat.sandbox', true) ? 'selected' : ''; ?>>正式模式（生产）</option>
                        </select>
                        <div class="help-text">沙箱模式用于测试，正式模式用于生产环境</div>
                    </div>
                </div>

                <!-- 安全配置 -->
                <div class="form-section">
                    <h3>🔒 安全配置</h3>

                    <div class="form-group">
                        <label for="admin_password">管理密码</label>
                        <input type="password" id="admin_password" name="security[admin_password]"
                               value="<?php echo htmlspecialchars($config->get('security.admin_password', 'admin123')); ?>"
                               placeholder="用于访问管理功能">
                        <div class="help-text">建议使用强密码，至少6位</div>
                    </div>

                    <div class="form-group">
                        <label for="force_https">强制HTTPS</label>
                        <select id="force_https" name="security[force_https]">
                            <option value="0" <?php echo !$config->get('security.force_https', false) ? 'selected' : ''; ?>>不强制</option>
                            <option value="1" <?php echo $config->get('security.force_https', false) ? 'selected' : ''; ?>>强制使用HTTPS</option>
                        </select>
                        <div class="help-text">生产环境建议启用HTTPS</div>
                    </div>
                </div>

                <!-- 操作按钮 -->
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">💾 保存配置</button>
                    <button type="button" class="btn btn-secondary" onclick="testConfig()">🧪 测试配置</button>
                    <button type="button" class="btn btn-secondary" onclick="checkSync()">🔄 检查同步</button>
                    <button type="button" class="btn btn-secondary" onclick="testV2Key()">🔑 测试V2密钥</button>
                    <button type="button" class="btn btn-secondary" onclick="resetConfig()">🔄 重置配置</button>
                </div>
            </form>

            <!-- 证书文件上传 -->
            <div class="form-section">
                <h3>📁 证书文件上传</h3>

                <!-- 文件上传方式 -->
                <div style="margin-bottom: 20px;">
                    <h4>方式一：文件上传</h4>
                    <form id="certUploadForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_cert">
                        <div class="form-group">
                            <label for="cert_file">证书文件 (apiclient_cert.pem)</label>
                            <input type="file" id="cert_file" name="cert_file" accept=".pem,.crt,.cer">
                        </div>
                        <div class="form-group">
                            <label for="key_file">私钥文件 (apiclient_key.pem)</label>
                            <input type="file" id="key_file" name="key_file" accept=".pem,.key">
                        </div>
                        <button type="submit" class="btn">📤 上传证书文件</button>
                    </form>
                </div>

                <!-- 文本提交方式 -->
                <div style="margin-bottom: 20px;">
                    <h4>方式二：文本提交</h4>
                    <form id="certTextForm">
                        <input type="hidden" name="action" value="upload_cert">
                        <div class="form-group">
                            <label for="cert_content">证书内容</label>
                            <textarea id="cert_content" name="cert_content" rows="8"
                                      placeholder="-----BEGIN CERTIFICATE-----&#10;...&#10;-----END CERTIFICATE-----"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="key_content">私钥内容</label>
                            <textarea id="key_content" name="key_content" rows="8"
                                      placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"></textarea>
                        </div>
                        <button type="submit" class="btn">💾 保存证书内容</button>
                    </form>
                </div>

                <!-- 证书状态 -->
                <div class="status-card">
                    <h4>📋 证书文件状态</h4>
                    <div class="status-item">
                        <span>证书文件 (apiclient_cert.pem)</span>
                        <span class="status-badge <?php echo $certStatus['cert_exists'] ? 'status-success' : 'status-error'; ?>">
                            <?php echo $certStatus['cert_exists'] ? '已上传' : '未上传'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span>私钥文件 (apiclient_key.pem)</span>
                        <span class="status-badge <?php echo $certStatus['key_exists'] ? 'status-success' : 'status-error'; ?>">
                            <?php echo $certStatus['key_exists'] ? '已上传' : '未上传'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span>证书目录</span>
                        <span class="status-badge <?php echo is_dir($certStatus['cert_dir']) ? 'status-success' : 'status-warning'; ?>">
                            <?php echo is_dir($certStatus['cert_dir']) ? '已创建' : '未创建'; ?>
                        </span>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <strong>重要提示：</strong>
                    <p>证书文件将保存到：<code><?php echo $certStatus['cert_dir']; ?></code></p>
                    <p>获取方式：微信支付商户平台 → 账户中心 → API安全 → 下载证书</p>
                </div>
            </div>

            <!-- 快速链接 -->
            <div class="form-section">
                <h3>🔗 快速链接</h3>
                <div style="text-align: center;">
                    <a href="index.php" class="btn btn-secondary">🏠 返回首页</a>
                    <a href="index.php?action=admin" class="btn btn-secondary">⚙️ 管理面板</a>
                    <a href="index.php?action=test" class="btn btn-secondary">🧪 系统测试</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 测试配置
        function testConfig() {
            alert('配置测试功能开发中...');
        }

        // 重置配置
        function resetConfig() {
            if (confirm('确定要重置所有配置吗？此操作不可恢复！')) {
                // 这里可以添加重置逻辑
                alert('重置功能开发中...');
            }
        }

        // 检查配置同步
        function checkSync() {
            fetch('test_config_sync.php')
                .then(response => response.text())
                .then(html => {
                    // 在新窗口中显示同步检查结果
                    const newWindow = window.open('', '_blank', 'width=800,height=600');
                    newWindow.document.write(html);
                })
                .catch(error => {
                    alert('检查同步失败：' + error.message);
                });
        }

        // 测试V2密钥同步
        function testV2Key() {
            fetch('test_v2_key.php')
                .then(response => response.text())
                .then(html => {
                    // 在新窗口中显示V2密钥测试结果
                    const newWindow = window.open('', '_blank', 'width=800,height=600');
                    newWindow.document.write(html);
                })
                .catch(error => {
                    alert('测试V2密钥失败：' + error.message);
                });
        }

        // 确保v2_secret_key与key保持一致
        document.getElementById('key').addEventListener('input', function() {
            document.getElementById('v2_secret_key').value = this.value;
        });

        // 配置表单提交处理
        document.getElementById('configForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // 确保v2_secret_key与key保持一致
            const keyValue = document.getElementById('key').value;
            document.getElementById('v2_secret_key').value = keyValue;

            const formData = new FormData(this);

            fetch('setup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('配置保存成功！');
                    location.reload();
                } else {
                    alert('配置保存失败：' + (data.error || '未知错误'));
                }
            })
            .catch(error => {
                alert('网络错误：' + error.message);
            });
        });

        // 证书文件上传处理
        document.getElementById('certUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('setup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('证书文件上传成功！');
                    location.reload();
                } else {
                    alert('证书文件上传失败：' + (data.error || '未知错误'));
                }
            })
            .catch(error => {
                alert('网络错误：' + error.message);
            });
        });

        // 证书文本提交处理
        document.getElementById('certTextForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('setup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('证书内容保存成功！');
                    location.reload();
                } else {
                    alert('证书内容保存失败：' + (data.error || '未知错误'));
                }
            })
            .catch(error => {
                alert('网络错误：' + error.message);
            });
        });
    </script>
</body>
</html>

<?php
/**
 * 处理配置保存
 */
function handleConfigSave() {
    $config = ConfigManager::getInstance();
    $logger = Logger::getInstance();

    try {
        // 保存微信支付配置
        if (isset($_POST['wechat'])) {
            foreach ($_POST['wechat'] as $key => $value) {
                if ($key === 'sandbox') {
                    $value = (bool)$value;
                }
                $config->set("wechat.{$key}", $value);
            }

            // 确保v2_secret_key与key保持一致
            if (isset($_POST['wechat']['key']) && !empty($_POST['wechat']['key'])) {
                $config->set("wechat.v2_secret_key", $_POST['wechat']['key']);
            }
        }

        // 保存安全配置
        if (isset($_POST['security'])) {
            foreach ($_POST['security'] as $key => $value) {
                if ($key === 'force_https') {
                    $value = (bool)$value;
                }
                $config->set("security.{$key}", $value);
            }
        }

        // 将配置同步到app.php文件
        saveConfigToFile($config->all());

        // 记录日志
        $logger->info('配置已更新并同步到文件', [
            'wechat_configured' => !empty($_POST['wechat']['app_id']),
            'sandbox_mode' => $_POST['wechat']['sandbox'] ?? false
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '配置保存成功并已同步到app.php']);

    } catch (Exception $e) {
        $logger->error('配置保存失败', ['error' => $e->getMessage()]);

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * 将配置保存到app.php文件
 */
function saveConfigToFile($config) {
    $configFile = __DIR__ . '/config/app.php';

    // 生成配置文件内容
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * 应用配置文件\n";
    $content .= " * 微信支付二维码生成系统\n";
    $content .= " * \n";
    $content .= " * 注意：此文件由setup.php自动生成，请勿手动修改\n";
    $content .= " * 如需修改配置，请使用 setup.php 页面\n";
    $content .= " */\n\n";
    $content .= "return " . var_export($config, true) . ";\n";

    // 写入文件
    if (file_put_contents($configFile, $content) === false) {
        throw new Exception('无法写入配置文件: ' . $configFile);
    }

    return true;
}

/**
 * 处理证书上传
 */
function handleCertUpload() {
    $certManager = new CertManager();
    $logger = Logger::getInstance();

    try {
        // 检查是否有文件上传
        if (isset($_FILES['cert_file']) || isset($_FILES['key_file'])) {
            // 文件上传方式
            $result = $certManager->uploadCertFiles($_FILES);
        } elseif (isset($_POST['cert_content']) || isset($_POST['key_content'])) {
            // 文本提交方式
            $result = $certManager->saveCertFromText(
                $_POST['cert_content'] ?? '',
                $_POST['key_content'] ?? ''
            );
        } else {
            $result = ['success' => false, 'error' => '没有提供证书数据'];
        }

        header('Content-Type: application/json');
        echo json_encode($result);

    } catch (Exception $e) {
        $logger->error('证书上传处理失败', ['error' => $e->getMessage()]);

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * 检查配置同步状态
 */
function checkConfigSync() {
    $config = ConfigManager::getInstance();
    $configFile = __DIR__ . '/config/app.php';

    if (!file_exists($configFile)) {
        return false;
    }

    try {
        $fileConfig = include $configFile;
        $memoryConfig = $config->all();

        // 检查关键配置项是否同步
        $keyChecks = [
            'wechat.app_id',
            'wechat.mch_id',
            'wechat.key',
            'wechat.v2_secret_key',
            'wechat.notify_url',
            'security.admin_password'
        ];

        foreach ($keyChecks as $key) {
            $keys = explode('.', $key);
            $memoryValue = $config->get($key, '');
            $fileValue = $fileConfig;

            foreach ($keys as $k) {
                $fileValue = $fileValue[$k] ?? '';
            }

            if ($memoryValue !== $fileValue) {
                return false;
            }
        }

        return true;

    } catch (Exception $e) {
        return false;
    }
}
?>
