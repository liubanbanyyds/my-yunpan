<?php
include 'config.php';

// 如果用户已经登录，直接跳转到主页面
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// 处理注册表单
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 验证输入
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "请填写所有字段";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = "用户名长度应在3-50个字符之间";
    } elseif (strlen($password) < 6) {
        $error = "密码长度至少需要6位";
    } elseif ($password !== $confirm_password) {
        $error = "两次输入的密码不一致";
    } else {
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $error = "用户名已存在";
        } else {
            // 创建用户
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");

            if ($stmt->execute([$username, $hashed_password])) {
                $_SESSION['register_success'] = "注册成功！请登录";
                header('Location: login.php');
                exit;
            } else {
                $error = "注册失败，请稍后再试";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - 刘班班云盘</title>
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
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-card {
            background: var(--bg-light);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logo i {
            font-size: 28px;
            color: var(--primary-color);
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #ddd;
            background: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(0, 122, 255, 0.2);
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #0062cc;
            transform: translateY(-1px);
        }

        .error {
            background: rgba(255, 59, 48, 0.15);
            color: var(--danger-color);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid rgba(255, 59, 48, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #555;
            font-size: 14px;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* 响应式设计 */
        @media (max-width: 480px) {
            .register-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">
            <i>☁️</i> 刘班班云盘
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="input-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required placeholder="请输入用户名" maxlength="50">
            </div>
            <div class="input-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required placeholder="至少6位字符" minlength="6">
            </div>
            <div class="input-group">
                <label for="confirm_password">确认密码</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="再次输入密码">
            </div>
            <button type="submit" class="btn">注册</button>
        </form>

        <div class="login-link">
            已有账号？<a href="login.php">立即登录</a>
        </div>
    </div>
</body>
</html>
