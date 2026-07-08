<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: admin/dashboard.php');
            exit;
        } else {
            $error = '❌ اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    } else {
        $error = '⚠️ يرجى إدخال اسم المستخدم وكلمة المرور';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>تسجيل الدخول - متجر الكيلاني</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background: #0a0f1d;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .login-box {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(0, 240, 255, 0.2);
            border-radius: 16px;
            padding: 40px 30px;
            box-shadow: 0 0 50px rgba(0, 240, 255, 0.1);
        }
        .login-header { text-align: center; margin-bottom: 40px; }
        .login-logo { font-size: 3rem; margin-bottom: 16px; }
        .login-title { font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .login-subtitle { font-size: 0.9rem; color: #94a3b8; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-family: 'Cairo', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s;
        }
        .form-group input:focus {
            border-color: #00f0ff;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.1);
        }
        .login-button {
            width: 100%;
            padding: 14px;
            margin-top: 10px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #00f0ff, #0099cc);
            color: #0a0f1d;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 240, 255, 0.3);
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .back-link {
            display: inline-block;
            margin-top: 12px;
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.3s;
        }
        .back-link:hover { color: #00f0ff; }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo">⚡</div>
                <h1 class="login-title">متجر الكيلاني</h1>
                <p class="login-subtitle">لوحة التحكم - تسجيل الدخول</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">اسم المستخدم:</label>
                    <input type="text" id="username" name="username" placeholder="أدخل اسم المستخدم" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">كلمة المرور:</label>
                    <input type="password" id="password" name="password" placeholder="أدخل كلمة المرور" required>
                </div>

                <button type="submit" class="login-button">🔓 دخول</button>
            </form>

            <a href="/index.php" class="back-link">⬅ العودة إلى المتجر</a>
        </div>
    </div>

</body>
</html>