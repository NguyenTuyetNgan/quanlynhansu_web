<?php
require_once 'config.php';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean($_POST['username']);
    $password = $_POST['password'];

    try {
        // Chuẩn PDO — KHÔNG dùng get_result
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        // Lấy user
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra
        if ($user && $password == $user['password']) {


            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header('Location: ../src/dashboard/index.php');
            exit();
        } else {
            $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
        }

    } catch (PDOException $e) {
        $error = "Lỗi hệ thống: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Quản lý nhân sự</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-container {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 400px;
    }

    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .login-header h1 {
        color: #667eea;
        font-size: 28px;
        margin-bottom: 10px;
    }

    .login-header p {
        color: #666;
        font-size: 14px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
    }

    .btn-login {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .btn-login:hover {
        transform: translateY(-2px);
    }

    .error-message {
        background: #fee;
        color: #c00;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        font-size: 14px;
    }

    .info-box {
        background: #f0f4ff;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        font-size: 13px;
        color: #666;
    }

    .info-box strong {
        color: #667eea;
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🏢 QUẢN LÝ NHÂN SỰ</h1>
            <p>Đăng nhập để tiếp tục</p>
        </div>

        <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" required placeholder="Nhập tên đăng nhập">
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" required placeholder="Nhập mật khẩu">
            </div>

            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>
    </div>
</body>

</html>