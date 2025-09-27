<?php
/**
 * 工具类自动加载器
 * 自动加载所有工具类
 */

// 自动加载工具类
spl_autoload_register(function ($className) {
    $toolDir = __DIR__;
    $classFile = $toolDir . '/' . $className . '.php';

    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }

    return false;
});

// 手动加载核心工具类
require_once __DIR__ . '/ConfigManager.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/QRCodeGenerator.php';
require_once __DIR__ . '/WeChatPay.php';
require_once __DIR__ . '/ConfigValidator.php';
require_once __DIR__ . '/CertManager.php';
