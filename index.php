<?php
include 'config.php';

// 如果用户未登录，重定向到登录页面
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 消息显示
$upload_message = '';
if (isset($_SESSION['upload_message'])) {
    $upload_message = $_SESSION['upload_message'];
    unset($_SESSION['upload_message']);
}

// 处理文件上传
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // 检查上传错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_message = '<div class="message error">上传失败: ' . $file['error'] . '</div>';
    } else {
        // 验证文件大小（10MB限制）
        $max_size = 10 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            $upload_message = '<div class="message error">文件大小不能超过10MB</div>';
        } else {
            // 验证文件类型（可选）
            $allowed_types = [
                'image/jpeg', 'image/png', 'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'application/zip',
                'application/x-rar-compressed',
                'application/octet-stream'
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            // 创建用户上传目录
            $upload_dir = 'uploads/' . $user_id . '/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // 生成唯一文件名（避免冲突）
            $original_filename = basename($file['name']);
            $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
            $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $new_filename;

            // 移动上传的文件
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // 获取文件大小
                $file_size = filesize($file_path);

                // 保存到数据库
                $stmt = $pdo->prepare("INSERT INTO files (user_id, filename, file_path, file_size) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$user_id, $original_filename, $file_path, $file_size])) {
                    $upload_message = '<div class="message success">文件上传成功!</div>';
                } else {
                    // 如果数据库保存失败，删除已上传的文件
                    unlink($file_path);
                    $upload_message = '<div class="message error">文件保存失败</div>';
                }
            } else {
                $upload_message = '<div class="message error">文件移动失败，请检查目录权限</div>';
            }
        }
    }
}

// 获取用户文件列表
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY upload_time DESC");
$stmt->execute([$user_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 计算总文件大小
$total_size = 0;
foreach ($files as $file) {
    $total_size += $file['file_size'];
}

// 格式化文件大小
function formatFileSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024*1024) return round($bytes/1024, 2) . ' KB';
    if ($bytes < 1024*1024*1024) return round($bytes/(1024*1024), 2) . ' MB';
    return round($bytes/(1024*1024*1024), 2) . ' GB';
}

// 格式化上传时间
function formatTime($timestamp) {
    $date = new DateTime($timestamp);
    return $date->format('Y-m-d H:i');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>刘班班云盘</title>
    <style>
        :root {
            --primary-color: #007AFF;
            --secondary-color: #5856D6;
            --success-color: #34C759;
            --danger-color: #FF3B30;
            --warning-color: #FF9500;
            --bg-light: rgba(255, 255, 255, 0.85);
            --bg-dark: rgba(242, 242, 247, 0.95);
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            color: #333;
            overflow-x: hidden;
        }

        /* 顶部导航栏 */
        .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 20px;
            font-weight: 600;
            color: #333;
            gap: 10px;
        }

        .logo i {
            font-size: 24px;
            color: var(--primary-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .username {
            font-weight: 500;
            color: #555;
            background: rgba(0, 0, 0, 0.05);
            padding: 5px 12px;
            border-radius: 20px;
        }

        .logout-btn {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background: #e0362c;
            transform: translateY(-2px);
        }

        /* 主内容区 */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        /* 上传区域 */
        .upload-section {
            background: var(--bg-light);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            font-size: 20px;
            color: var(--primary-color);
        }

        .upload-area {
            border: 2px dashed #ccc;
            border-radius: var(--border-radius);
            padding: 40px 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.6);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .upload-area:hover, .upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(0, 122, 255, 0.05);
        }

        .upload-area i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }

        .upload-area p {
            color: #666;
            margin-bottom: 15px;
        }

        .file-types {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }

        .file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .btn-upload {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
        }

        .btn-upload:hover {
            background: #0062cc;
            transform: translateY(-2px);
        }

        /* 文件列表 */
        .files-section {
            background: var(--bg-light);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .file-list {
            margin-top: 20px;
            max-height: 500px;
            overflow-y: auto;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }

        .file-item:hover {
            background: rgba(0, 0, 0, 0.03);
            border-radius: 8px;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            min-width: 0;
        }

        .file-icon {
            width: 40px;
            height: 40px;
            background: #e0e0e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #666;
            flex-shrink: 0;
        }

        .file-details {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .file-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-meta {
            font-size: 12px;
            color: #777;
        }

        .file-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn-download, .btn-delete {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
            display: inline-block;
        }

        .btn-download {
            background: var(--success-color);
            color: white;
        }

        .btn-download:hover {
            background: #2db14e;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background: #e0362c;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: fadeIn 0.3s ease;
        }

        .success {
            background: rgba(52, 199, 89, 0.15);
            color: #2db14e;
            border: 1px solid rgba(52, 199, 89, 0.3);
        }

        .error {
            background: rgba(255, 59, 48, 0.15);
            color: #e0362c;
            border: 1px solid rgba(255, 59, 48, 0.3);
        }

        .warning {
            background: rgba(255, 149, 0, 0.15);
            color: #ff9500;
            border: 1px solid rgba(255, 149, 0, 0.3);
        }

        /* 侧边栏信息 */
        .storage-info {
            margin-top: 20px;
            padding: 15px 0;
        }

        .storage-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .storage-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }

        .storage-fill {
            height: 100%;
            background: var(--primary-color);
            transition: width 0.5s ease;
        }

        /* 动画 */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 响应式设计 */
        @media (max-width: 900px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .navbar {
                padding: 12px 20px;
            }

            .file-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .file-actions {
                align-self: flex-end;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .user-info {
                width: 100%;
                justify-content: space-between;
            }

            .upload-area {
                padding: 30px 15px;
            }

            .upload-area i {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <!-- 顶部导航栏 -->
    <nav class="navbar">
        <div class="logo">
            <i>☁️</i> 刘班班云盘
        </div>
        <div class="user-info">
            <span class="username">欢迎, <?php echo htmlspecialchars($username); ?></span>
            <a href="logout.php" class="logout-btn">退出</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($upload_message): ?>
            <?php echo $upload_message; ?>
        <?php endif; ?>

        <div class="dashboard">
            <div>
                <!-- 上传区域 -->
                <div class="upload-section">
                    <h2 class="section-title"><i>⬆️</i> 上传文件</h2>
                    <div class="upload-area" id="drop-area">
                        <i>📁</i>
                        <p>拖拽文件到此处或点击选择</p>
                        <p class="file-types">支持所有类型文件，最大10MB</p>
                        <form id="upload-form" method="POST" enctype="multipart/form-data">
                            <input type="file" name="file" id="file-input" class="file-input" required>
                        </form>
                        <button class="btn-upload" id="upload-btn">选择文件</button>
                    </div>
                </div>

                <!-- 文件列表 -->
                <div class="files-section">
                    <h2 class="section-title"><i>📄</i> 我的文件</h2>
                    <div class="file-list">
                        <?php if (count($files) > 0): ?>
                            <?php foreach ($files as $file): ?>
                                <div class="file-item">
                                    <div class="file-info">
                                        <div class="file-icon">📄</div>
                                        <div class="file-details">
                                            <div class="file-name" title="<?php echo htmlspecialchars($file['filename']); ?>">
                                                <?php echo htmlspecialchars($file['filename']); ?>
                                            </div>
                                            <div class="file-meta">
                                                <?php echo formatFileSize($file['file_size']); ?> •
                                                <?php echo formatTime($file['upload_time']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="file-actions">
                                        <a href="download.php?id=<?php echo $file['id']; ?>" class="btn-download">下载</a>
                                        <button class="btn-delete" onclick="deleteFile(<?php echo $file['id']; ?>)">删除</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i>📂</i>
                                <p>还没有文件，上传一个试试吧！</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 侧边栏信息 -->
            <div class="files-section">
                <h2 class="section-title"><i>ℹ️</i> 云盘信息</h2>
                <div class="storage-info">
                    <div class="storage-item">
                        <span>用户名:</span>
                        <span><?php echo htmlspecialchars($username); ?></span>
                    </div>
                    <div class="storage-item">
                        <span>文件数量:</span>
                        <span><?php echo count($files); ?> 个</span>
                    </div>
                    <div class="storage-item">
                        <span>总空间:</span>
                        <span>10 GB</span>
                    </div>
                    <div class="storage-item">
                        <span>已使用:</span>
                        <span><?php echo formatFileSize($total_size); ?></span>
                    </div>
                    <div class="storage-bar">
                        <div class="storage-fill" style="width: <?php echo min(100, ($total_size / (10*1024*1024*1024)) * 100); ?>%;"></div>
                    </div>
                    <div class="storage-item" style="margin-top: 5px;">
                        <span>使用率:</span>
                        <span><?php echo round(min(100, ($total_size / (10*1024*1024*1024)) * 100), 1); ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 文件上传交互
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('file-input');
        const uploadBtn = document.getElementById('upload-btn');
        const uploadForm = document.getElementById('upload-form');

        // 点击按钮触发文件选择
        uploadBtn.addEventListener('click', () => {
            fileInput.click();
        });

        // 文件选择后自动提交
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                uploadForm.submit();
            }
        });

        // 拖拽上传功能
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropArea.classList.add('dragover');
        }

        function unhighlight() {
            dropArea.classList.remove('dragover');
        }

        // 处理拖放文件
        dropArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                // 创建一个新的FileList对象并赋值给input
                const dataTransfer = new DataTransfer();
                for (let i = 0; i < files.length; i++) {
                    dataTransfer.items.add(files[i]);
                }
                fileInput.files = dataTransfer.files;

                // 提交表单
                uploadForm.submit();
            }
        }

        // 删除文件
        function deleteFile(fileId) {
            if (confirm('确定要删除这个文件吗？此操作不可恢复。')) {
                // 创建隐藏表单提交删除请求
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'file_id';
                input.value = fileId;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // 添加页面加载动画
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>
