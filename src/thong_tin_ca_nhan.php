<?php
require_once 'config.php';
checkLogin();

$user_id = $_SESSION['user_id'];

// Lấy thông tin user hiện tại
try {
    $stmt = $pdo->prepare("SELECT u.*, ns.ho_ten, ns.email as ns_email, ns.so_dien_thoai
                           FROM users u 
                           LEFT JOIN nhan_su ns ON u.nhan_su_id = ns.id 
                           WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

// Xử lý đổi mật khẩu
if (isset($_POST['change_password'])) {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    
    if ($old_pass !== $user['password']) {
        $pass_error = "Mật khẩu cũ không đúng!";
    } elseif (strlen($new_pass) < 6) {
        $pass_error = "Mật khẩu mới phải có ít nhất 6 ký tự!";
    } elseif ($new_pass !== $confirm_pass) {
        $pass_error = "Xác nhận mật khẩu không khớp!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_pass, $user_id]);
            $pass_success = "Đổi mật khẩu thành công!";
            
            // Reload user data
            $stmt = $pdo->prepare("SELECT u.*, ns.ho_ten, ns.email as ns_email, ns.so_dien_thoai
                                   FROM users u 
                                   LEFT JOIN nhan_su ns ON u.nhan_su_id = ns.id 
                                   WHERE u.id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $pass_error = "Lỗi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>👤 Thông tin cá nhân</h1>
        </div>

        <div class="profile-container">
            <!-- Card Thông tin -->
            <div class="profile-card">
                <div class="card-header">
                    <h3>📋 Thông tin tài khoản</h3>
                </div>
                <div class="card-body">
                    <div class="info-list">
                        <div class="info-item-row">
                            <span class="info-label">👤 Tên đăng nhập:</span>
                            <span class="info-value"><?php echo $user['username']; ?></span>
                        </div>
                        <div class="info-item-row">
                            <span class="info-label">📧 Email:</span>
                            <span class="info-value"><?php echo $user['email'] ?: $user['ns_email'] ?: '-'; ?></span>
                        </div>
                        <div class="info-item-row">
                            <span class="info-label">🏷️ Vai trò:</span>
                            <span class="info-value">
                                <span
                                    class="badge <?php echo $user['role'] == 'admin' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $user['role'] == 'admin' ? '👑 Quản trị viên' : '👤 Nhân viên'; ?>
                                </span>
                            </span>
                        </div>
                        <?php if ($user['ho_ten']): ?>
                        <div class="info-item-row">
                            <span class="info-label">📝 Họ tên:</span>
                            <span class="info-value"><?php echo $user['ho_ten']; ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($user['so_dien_thoai']): ?>
                        <div class="info-item-row">
                            <span class="info-label">📱 Số điện thoại:</span>
                            <span class="info-value"><?php echo $user['so_dien_thoai']; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item-row">
                            <span class="info-label">📅 Ngày tạo:</span>
                            <span
                                class="info-value"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Đổi mật khẩu -->
            <div class="profile-card">
                <div class="card-header">
                    <h3>🔐 Đổi mật khẩu</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($pass_success)): ?>
                    <div class="alert alert-success">✓ <?php echo $pass_success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($pass_error)): ?>
                    <div class="alert alert-danger">✗ <?php echo $pass_error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">

                        <div class="form-group">
                            <label>Mật khẩu hiện tại *</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Mật khẩu mới *</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                            <small style="color: #666;">Tối thiểu 6 ký tự</small>
                        </div>

                        <div class="form-group">
                            <label>Xác nhận mật khẩu mới *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn-primary">💾 Đổi mật khẩu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Auto hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.style.display = 'none', 300);
        });
    }, 3000);
    </script>

    <style>
    .profile-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 25px;
    }

    .profile-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .card-header {
        padding: 20px 25px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .card-header h3 {
        margin: 0;
        font-size: 18px;
    }

    .card-body {
        padding: 25px;
    }

    .info-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .info-item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .info-item-row .info-label {
        color: #666;
        font-size: 14px;
    }

    .info-item-row .info-value {
        font-weight: 500;
        color: #333;
    }

    .alert {
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
        transition: opacity 0.3s;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
    }

    @media (max-width: 768px) {
        .profile-container {
            grid-template-columns: 1fr;
        }
    }
    </style>
</body>

</html>