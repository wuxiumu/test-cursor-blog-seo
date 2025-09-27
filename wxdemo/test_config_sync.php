<?php
/**
 * 配置同步测试页面
 * 用于验证setup.php的数据是否同步到app.php
 */

// 加载工具类
require_once __DIR__ . '/tool/autoload.php';

$config = ConfigManager::getInstance();

echo "<h1>配置同步测试</h1>";

echo "<h2>当前内存中的配置：</h2>";
echo "<pre>";
print_r($config->all());
echo "</pre>";

echo "<h2>app.php文件内容：</h2>";
$configFile = __DIR__ . '/config/app.php';
if (file_exists($configFile)) {
    echo "<pre>";
    echo htmlspecialchars(file_get_contents($configFile));
    echo "</pre>";
} else {
    echo "<p style='color: red;'>app.php文件不存在</p>";
}

echo "<h2>配置对比：</h2>";
$fileConfig = file_exists($configFile) ? include $configFile : [];
$memoryConfig = $config->all();

echo "<h3>微信配置对比：</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>配置项</th><th>内存中</th><th>文件中</th><th>是否同步</th></tr>";

$wechatKeys = ['app_id', 'mch_id', 'key', 'notify_url', 'sandbox'];
foreach ($wechatKeys as $key) {
    $memoryValue = $config->get("wechat.{$key}", '');
    $fileValue = $fileConfig['wechat'][$key] ?? '';
    $synced = $memoryValue === $fileValue ? '✅' : '❌';

    echo "<tr>";
    echo "<td>{$key}</td>";
    echo "<td>" . htmlspecialchars($memoryValue) . "</td>";
    echo "<td>" . htmlspecialchars($fileValue) . "</td>";
    echo "<td>{$synced}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>安全配置对比：</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>配置项</th><th>内存中</th><th>文件中</th><th>是否同步</th></tr>";

$securityKeys = ['admin_password', 'force_https'];
foreach ($securityKeys as $key) {
    $memoryValue = $config->get("security.{$key}", '');
    $fileValue = $fileConfig['security'][$key] ?? '';
    $synced = $memoryValue === $fileValue ? '✅' : '❌';

    echo "<tr>";
    echo "<td>{$key}</td>";
    echo "<td>" . htmlspecialchars($memoryValue) . "</td>";
    echo "<td>" . htmlspecialchars($fileValue) . "</td>";
    echo "<td>{$synced}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='setup.php'>返回设置页面</a></p>";
?>
