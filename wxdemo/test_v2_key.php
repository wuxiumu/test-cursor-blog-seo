<?php
/**
 * 测试v2_secret_key同步
 */

require_once __DIR__ . '/tool/autoload.php';

$config = ConfigManager::getInstance();

echo "<h1>v2_secret_key 同步测试</h1>";

echo "<h2>当前配置：</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>配置项</th><th>内存中的值</th><th>文件中的值</th><th>是否一致</th></tr>";

$configFile = __DIR__ . '/config/app.php';
$fileConfig = file_exists($configFile) ? include $configFile : [];

$keys = ['app_id', 'mch_id', 'key', 'v2_secret_key', 'notify_url'];
foreach ($keys as $key) {
    $memoryValue = $config->get("wechat.{$key}", '');
    $fileValue = $fileConfig['wechat'][$key] ?? '';
    $isSame = $memoryValue === $fileValue ? '✅' : '❌';

    echo "<tr>";
    echo "<td>wechat.{$key}</td>";
    echo "<td>" . htmlspecialchars($memoryValue) . "</td>";
    echo "<td>" . htmlspecialchars($fileValue) . "</td>";
    echo "<td>{$isSame}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>特殊检查：key 和 v2_secret_key 是否一致</h2>";
$keyValue = $config->get('wechat.key', '');
$v2KeyValue = $config->get('wechat.v2_secret_key', '');
$isKeySame = $keyValue === $v2KeyValue ? '✅' : '❌';

echo "<p>key: " . htmlspecialchars($keyValue) . "</p>";
echo "<p>v2_secret_key: " . htmlspecialchars($v2KeyValue) . "</p>";
echo "<p>是否一致: {$isKeySame}</p>";

echo "<p><a href='setup.php'>返回设置页面</a></p>";
?>
