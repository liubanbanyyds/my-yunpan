<?php
include 'config.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 检查文件ID是否提供
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['upload_message'] = '<div class="message error">文件ID无效</div>';
    header('Location: index.php');
    exit;
}

$file_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// 获取文件信息
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if ($file) {
    $file_path = $file['file_path'];

    // 检查文件是否存在
    if (file_exists($file_path)) {
        // 获取文件MIME类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);

        // 设置下载头信息
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . basename($file['filename']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));

        // 清除输出缓冲区
        if (ob_get_level()) ob_end_clean();

        // 读取文件并输出
        readfile($file_path);
        exit;
    } else {
        $_SESSION['upload_message'] = '<div class="message error">文件不存在</div>';
    }
} else {
    $_SESSION['upload_message'] = '<div class="message error">文件未找到或无权访问</div>';
}

header('Location: index.php');
exit;
?>
