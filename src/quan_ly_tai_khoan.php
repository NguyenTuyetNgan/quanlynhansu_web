<?php
require_once 'config.php';
checkLogin();

// Kiểm tra quyền admin
if ($_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Xử lý thêm/sửa/xóa tài khoản
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">✗ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-header">
                <h2>Danh sách tài khoản (<?php echo count($users); ?>)</h2>
                <button class="btn-primary" onclick="showAddModal()">+ Thêm tài khoản</button>
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
                        <td><strong><?php echo $user['username']; ?></strong></td>
                        <td><?php echo $user['email'] ?: '-'; ?></td>
                        <td>
                            <span
                                class="badge <?php echo $user['role'] == 'admin' ? 'badge-success' : 'badge-warning'; ?>">
                                <?php echo $user['role'] == 'admin' ? '👑 Admin' : '👤 Nhân viên'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button onclick='editUser(<?php echo json_encode($user); ?>)' class="btn-icon btn-edit"
                                    title="Sửa">✏️</button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn-icon btn-delete"
                                    title="Xóa">🗑️</button>
                                <?php endif; ?>
                            </div>
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
    </style>
</body>

</html>