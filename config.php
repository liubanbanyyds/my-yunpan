<?php
// 会话安全配置（必须在session_start之前）
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // 如果使用HTTPS，设置为1
ini_set('session.cookie_samesite', 'Strict');

// 启动会话
session_start();

// 数据库配置 - 请根据宝塔面板显示的信息修改
$host = 'localhost';       // 通常为localhost
$dbname = 'cloud_disk';    // 你创建的数据库名
$username = 'cloud_user';  // 数据库用户名
$password = '你的数据库密码'; // 数据库密码

// 设置时区
date_default_timezone_set('Asia/Shanghai'); // 根据需要修改时区

// 创建数据库连接
try {
    // 创建PDO连接
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 额外的会话配置（如果需要）
// 注意：这些配置也可以在php.ini中设置，或者在session_start之前使用ini_set()

// 设置会话过期时间（可选）
// ini_set('session.gc_maxlifetime', 86400); // 24小时
// session_set_cookie_params(86400);

// 安全的会话ID（可选）
// ini_set('session.use_strict_mode', 1);

// 禁用会话ID通过URL传递（可选）
// ini_set('session.use_trans_sid', 0);
?>
