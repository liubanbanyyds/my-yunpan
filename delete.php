<?php
include 'config.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 检查是否通过POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['upload_message'] = '<div class="message error">请求方式错误</div>';
    header('Location: index.php');
    exit;
}

// 检查文件ID是否提供
if (!isset($_POST['file_id']) || empty($_POST['file_id'])) {
    $_SESSION['upload_message'] = '<div class="message error">文件ID无效</div>';
    header('Location: index.php');
    exit;
}

$file_id = intval($_POST['file_id']);
$user_id = $_SESSION['user_id'];

// 获取文件信息
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if ($file) {
    $file_path = $file['file_path'];

    // 删除文件
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            // 从数据库中删除记录
            $stmt = $pdo->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$file_id, $user_id])) {
                $_SESSION['upload_message'] = '<div class="message success">文件删除成功</div>';
            } else {
                $_SESSION['upload_message'] = '<div class="message error">数据库删除失败</div>';
            }
        } else {
            $_SESSION['upload_message'] = '<div class="message error">文件删除失败</div>';
        }
    } else {
        // 文件不存在，但可能数据库记录还存在，尝试删除数据库记录
        $stmt = $pdo->prepare("DELETE FROM files WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$file_id, $user_id])) {
            $_SESSION['upload_message'] = '<div class="message warning">文件已不存在，已清除数据库记录</div>';
        } else {
            $_SESSION['upload_message'] = '<div class="message error">文件未找到</div>';
        }
    }
} else {
    $_SESSION['upload_message'] = '<div class="message error">文件未找到或无权访问</div>';
}

header('Location: index.php');
exit;
?>
