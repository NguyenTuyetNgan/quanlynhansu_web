<?php
require_once 'config.php';
checkLogin();

// Kiểm tra quyền admin
if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Lấy mật khẩu truy cập từ database
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'account_page_password'");
$stmt->execute();
$result = $stmt->fetch();

// Nếu chưa có trong database thì tạo mới
if (!$result) {
    $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('account_page_password', '123456')")->execute();
    $ACCOUNT_PAGE_PASSWORD = '123456';
} else {
    $ACCOUNT_PAGE_PASSWORD = $result['setting_value'];
}

// Xử lý đổi mật khẩu truy cập
if (isset($_POST['change_page_password'])) {
    $old_pass = $_POST['old_page_password'] ?? '';
    $new_pass = $_POST['new_page_password'] ?? '';
    $confirm_pass = $_POST['confirm_page_password'] ?? '';

    if ($old_pass !== $ACCOUNT_PAGE_PASSWORD) {
        $pass_error = "Mật khẩu cũ không đúng!";
    } elseif (strlen($new_pass) < 4) {
        $pass_error = "Mật khẩu mới phải có ít nhất 4 ký tự!";
    } elseif ($new_pass !== $confirm_pass) {
        $pass_error = "Xác nhận mật khẩu không khớp!";
    } else {
        // Cập nhật mật khẩu vào database
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'account_page_password'");
        $stmt->execute([$new_pass]);
        $ACCOUNT_PAGE_PASSWORD = $new_pass;
        $pass_success = "Đổi mật khẩu truy cập thành công!";
    }
}

// Kiểm tra xác thực - chỉ cho phép nếu vừa POST đúng mật khẩu
$verified = false;
$current_access_pass = ''; // <--- THÊM DÒNG NÀY: Biến để giữ mật khẩu tạm thời

if (isset($_POST['verify_password'])) {
    if ($_POST['page_password'] === $ACCOUNT_PAGE_PASSWORD) {
        $verified = true;
        $current_access_pass = $_POST['page_password']; // <--- THÊM DÒNG NÀY: Lưu lại mật khẩu đúng
    } else {
        $verify_error = "Mật khẩu không đúng!";
    }
}

// Nếu chưa xác thực, hiển thị form nhập mật khẩu
if (!$verified) {
?>
    <!DOCTYPE html>
    <html lang="vi">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Xác thực - Quản lý tài khoản</title>
        <link rel="stylesheet" href="../assets/style.css">
    </head>

    <body>
        <?php include 'sidebar.php'; ?>
        <div class="main-content">
            <div
                style="max-width: 400px; margin: 100px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.1);">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="font-size: 64px; margin-bottom: 15px;">🔐</div>
                    <h2 style="color: #333;">Xác thực bảo mật</h2>
                    <p style="color: #666; font-size: 14px;">Nhập mật khẩu để truy cập trang quản lý tài khoản</p>
                </div>

                <?php if (isset($verify_error)): ?>
                    <div
                        style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center;">
                        ✗ <?php echo $verify_error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="verify_password" value="1">
                    <div class="form-group">
                        <label>Mật khẩu truy cập</label>
                        <input type="password" name="page_password" class="form-control" placeholder="Nhập mật khẩu..."
                            required autofocus>
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%; justify-content: center;">🔓 Xác
                        nhận</button>
                </form>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="index.php" style="color: #667eea; text-decoration: none;">← Quay lại trang chủ</a>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}

// Xử lý thêm/sửa/xóa tài khoản
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'add') {
            // Kiểm tra username đã tồn tại
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([clean($_POST['username'])]);
            if ($stmt->fetch()) {
                $error = "Tên đăng nhập đã tồn tại!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    clean($_POST['username']),
                    $_POST['password'], // Lưu password dạng plain text như login.php
                    clean($_POST['email']),
                    $_POST['role']
                ]);
                $success = "Thêm tài khoản thành công!";
            }
        } elseif ($_POST['action'] == 'edit') {
            $sql = "UPDATE users SET username = ?, email = ?, role = ?";
            $params = [clean($_POST['username']), clean($_POST['email']), $_POST['role']];

            // Nếu có đổi mật khẩu
            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = $_POST['password'];
            }

            $sql .= " WHERE id = ?";
            $params[] = $_POST['id'];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $success = "Cập nhật tài khoản thành công!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Xử lý xóa
if (isset($_GET['delete'])) {
    try {
        $id = $_GET['delete'];
        // Không cho xóa chính mình
        if ($id == $_SESSION['user_id']) {
            $error = "Không thể xóa tài khoản đang đăng nhập!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Xóa tài khoản thành công!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách tài khoản
try {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>🔐 Quản lý tài khoản</h1>
            <div class="header-actions">
                <button class="btn-secondary" onclick="showChangePassModal()">🔑 Đổi mật khẩu truy cập</button>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">✗ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-header">
            <h2>Danh sách tài khoản (<?php echo count($users); ?>)</h2>
            <div style="display: flex; gap: 10px;">
                <button class="btn-primary" onclick="showAddModal()">+ Thêm tài khoản</button>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <strong><?php echo $user['username']; ?></strong>
                            <?php if ($user['id'] == 1): ?>
                                <span class="badge badge-warning" style="margin-left: 8px; font-size: 11px;">👑 Admin chính</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $user['email'] ?: '-'; ?></td>
                        <td>
                            <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-success' : 'badge-warning'; ?>">
                                <?php echo $user['role'] == 'admin' ? '👑 Admin' : '👤 Nhân viên'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php
                                // TRƯỜNG HỢP 1: Admin chính (ID 1) -> Luôn khóa
                                if ($user['id'] == 1):
                                ?>
                                    <span class="btn-icon" style="cursor: not-allowed; opacity: 0.5;" title="Admin hệ thống">🔒</span>

                                <?php
                                // TRƯỜNG HỢP 2: Chính là tài khoản đang đăng nhập -> Không hiện nút sửa/xóa
                                // (Có thể thay bằng icon người dùng để nhận biết, hoặc để trống)
                                elseif ($user['id'] == $_SESSION['user_id']):
                                ?>
                                    <span class="btn-icon" style="cursor: default; color: #666;" title="Tài khoản của bạn">👤</span>

                                <?php
                                // CÁC TRƯỜNG HỢP CÒN LẠI: Admin con khác -> Hiện đủ nút Sửa và Xóa
                                else:
                                ?>
                                    <button onclick='editUser(<?php echo json_encode($user); ?>)' class="btn-icon btn-edit" title="Sửa">
                                        ✏️
                                    </button>

                                    <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn-icon btn-delete" title="Xóa">
                                        🗑️
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div>

    <!-- Modal Thêm/Sửa -->
    <div id="userModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm tài khoản mới</h2>
                <button class="btn-close" onclick="closeModal()">×</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="user_id">

                    <input type="hidden" name="verify_password" value="1">
                    <input type="hidden" name="page_password" value="<?php echo $current_access_pass; ?>">

                    <div class="form-group">
                        <label>Tên đăng nhập *</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label id="passwordLabel">Mật khẩu *</label>
                        <input type="password" name="password" id="password" class="form-control">
                        <small id="passwordHint" style="color: #666; display: none;">Để trống nếu không đổi mật
                            khẩu</small>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Vai trò *</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="user">👤 Nhân viên</option>
                            <option value="admin">👑 Admin</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Đóng</button>
                    <button type="submit" class="btn-primary">💾 Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Thêm tài khoản mới';
            document.getElementById('action').value = 'add';
            document.getElementById('user_id').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passwordLabel').textContent = 'Mật khẩu *';
            document.getElementById('passwordHint').style.display = 'none';
            document.getElementById('email').value = '';
            document.getElementById('role').value = 'user';
            document.getElementById('userModal').classList.add('active');
        }

        function editUser(user) {
            document.getElementById('modalTitle').textContent = 'Chỉnh sửa tài khoản';
            document.getElementById('action').value = 'edit';
            document.getElementById('user_id').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passwordLabel').textContent = 'Mật khẩu mới';
            document.getElementById('passwordHint').style.display = 'block';
            document.getElementById('email').value = user.email || '';
            document.getElementById('role').value = user.role;
            document.getElementById('userModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        function deleteUser(id) {
            if (confirm('⚠️ Bạn có chắc muốn xóa tài khoản này?\n\nHành động này không thể hoàn tác!')) {
                window.location.href = 'quan_ly_tai_khoan.php?delete=' + id;
            }
        }

        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(el => el.style.display = 'none');
        }, 3000);
    </script>

    <style>
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .btn-secondary {
            padding: 10px 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }
    </style>

    <!-- Modal Đổi mật khẩu truy cập -->
    <div id="changePassModal" class="modal">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h2>🔑 Đổi mật khẩu truy cập</h2>
                <button class="btn-close" onclick="closeChangePassModal()">×</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="change_page_password" value="1">

                    <?php if (isset($pass_error)): ?>
                        <div
                            style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                            ✗ <?php echo $pass_error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($pass_success)): ?>
                        <div
                            style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                            ✓ <?php echo $pass_success; ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Mật khẩu cũ *</label>
                        <input type="password" name="old_page_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu mới *</label>
                        <input type="password" name="new_page_password" class="form-control" required minlength="4">
                    </div>

                    <div class="form-group">
                        <label>Xác nhận mật khẩu mới *</label>
                        <input type="password" name="confirm_page_password" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeChangePassModal()">Đóng</button>
                    <button type="submit" class="btn-primary">💾 Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showChangePassModal() {
            document.getElementById('changePassModal').classList.add('active');
        }

        function closeChangePassModal() {
            document.getElementById('changePassModal').classList.remove('active');
        }

        <?php if (isset($pass_error) || isset($pass_success)): ?>
            showChangePassModal();
        <?php endif; ?>
    </script>
</body>

</html>