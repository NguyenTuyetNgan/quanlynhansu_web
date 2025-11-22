<?php
require_once 'config.php';
checkLogin();

// Xử lý thêm/sửa/xóa phòng ban
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                $stmt = $pdo->prepare("INSERT INTO phong_ban (ma_phong_ban, ten_phong_ban, truong_phong, mo_ta) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    clean($_POST['ma_phong_ban']), 
                    clean($_POST['ten_phong_ban']), 
                    $_POST['truong_phong'] ?: null,  // Fix: cho phép null
                    clean($_POST['mo_ta'])
                ]);
                $success = "Thêm phòng ban thành công!";
            } elseif ($_POST['action'] == 'edit') {
                $stmt = $pdo->prepare("UPDATE phong_ban SET ma_phong_ban = ?, ten_phong_ban = ?, truong_phong = ?, mo_ta = ? WHERE id = ?");
                $stmt->execute([
                    clean($_POST['ma_phong_ban']), 
                    clean($_POST['ten_phong_ban']), 
                    $_POST['truong_phong'] ?: null,  // Fix: cho phép null
                    clean($_POST['mo_ta']), 
                    $_POST['id']
                ]);
                $success = "Cập nhật thành công!";
            }
        }
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

if (isset($_GET['delete'])) {
    try {
        // Kiểm tra xem phòng ban có nhân viên không
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM nhan_su WHERE phong_ban_id = ?");
        $stmt->execute([$_GET['delete']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $error = "Không thể xóa phòng ban này vì còn $count nhân viên!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM phong_ban WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            header('Location: phong_ban.php?msg=deleted');
            exit();
        }
    } catch (PDOException $e) {
        $error = "Không thể xóa phòng ban này!";
    }
}

// Thông báo từ redirect
if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $success = "Xóa phòng ban thành công!";
}

// Lấy danh sách phòng ban
try {
    $stmt = $pdo->query("
        SELECT pb.*, 
               ns.ho_ten as ten_truong_phong,
               ns.ma_nhan_vien as ma_truong_phong,
               (SELECT COUNT(*) FROM nhan_su WHERE phong_ban_id = pb.id) as so_nhan_vien
        FROM phong_ban pb
        LEFT JOIN nhan_su ns ON pb.truong_phong = ns.id
        ORDER BY pb.created_at DESC
    ");
    $phong_ban_list = $stmt->fetchAll();
    
    // Lấy danh sách nhân viên cho dropdown
    $nhan_su_list = $pdo->query("SELECT id, ma_nhan_vien, ho_ten FROM nhan_su WHERE trang_thai_id = 1 ORDER BY ho_ten")->fetchAll();
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phòng ban</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>🏢 Quản lý phòng ban</h1>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">✗ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="departments-grid">
            <?php foreach ($phong_ban_list as $pb): ?>
            <div class="department-card">
                <div class="department-icon">🏢</div>
                <div class="department-content">
                    <div class="department-code"><?php echo $pb['ma_phong_ban']; ?></div>
                    <h3><?php echo $pb['ten_phong_ban']; ?></h3>
                    <div class="department-info">
                        <div class="info-item">
                            <span class="info-label">👤 Trưởng phòng:</span>
                            <span class="info-value"><?php echo $pb['ten_truong_phong'] ?? '<em>Chưa có</em>'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">👥 Số nhân viên:</span>
                            <span class="info-value"><strong><?php echo $pb['so_nhan_vien']; ?></strong> người</span>
                        </div>
                        <?php if ($pb['mo_ta']): ?>
                        <div class="info-item">
                            <span class="info-label">📝 Mô tả:</span>
                            <span class="info-value"><?php echo $pb['mo_ta']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="department-actions">
                        <button onclick='editPhongBan(<?php echo json_encode($pb); ?>)' class="btn-icon btn-edit"
                            title="Sửa">✏️</button>
                        <button
                            onclick="deletePhongBan(<?php echo $pb['id']; ?>, '<?php echo $pb['ten_phong_ban']; ?>', <?php echo $pb['so_nhan_vien']; ?>)"
                            class="btn-icon btn-delete" title="Xóa">🗑️</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Add new department card -->
            <div class="department-card add-card" onclick="showAddModal()">
                <div class="add-icon">➕</div>
                <div class="add-text">Thêm phòng ban mới</div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm/Sửa -->
    <div id="phongBanModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm phòng ban mới</h2>
                <button class="btn-close" onclick="closeModal()">×</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="phong_ban_id">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Mã phòng ban *</label>
                            <input type="text" name="ma_phong_ban" id="ma_phong_ban" class="form-control"
                                placeholder="VD: PB001" required>
                        </div>

                        <div class="form-group">
                            <label>Tên phòng ban *</label>
                            <input type="text" name="ten_phong_ban" id="ten_phong_ban" class="form-control"
                                placeholder="VD: Phòng Kỹ thuật" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Trưởng phòng</label>
                        <select name="truong_phong" id="truong_phong" class="form-control">
                            <option value="">-- Chọn trưởng phòng --</option>
                            <?php foreach ($nhan_su_list as $ns): ?>
                            <option value="<?php echo $ns['id']; ?>">
                                <?php echo $ns['ho_ten']; ?> (<?php echo $ns['ma_nhan_vien']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="mo_ta" id="mo_ta" class="form-control" rows="4"
                            placeholder="Nhập mô tả về phòng ban..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Đóng</button>
                    <button type="submit" class="btn-primary">💾 Lưu thông tin</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showAddModal() {
        document.getElementById('modalTitle').textContent = 'Thêm phòng ban mới';
        document.getElementById('action').value = 'add';
        document.getElementById('phong_ban_id').value = '';
        document.getElementById('ma_phong_ban').value = '';
        document.getElementById('ten_phong_ban').value = '';
        document.getElementById('truong_phong').value = '';
        document.getElementById('mo_ta').value = '';
        document.getElementById('phongBanModal').classList.add('active');
    }

    function editPhongBan(data) {
        document.getElementById('modalTitle').textContent = 'Chỉnh sửa phòng ban';
        document.getElementById('action').value = 'edit';
        document.getElementById('phong_ban_id').value = data.id;
        document.getElementById('ma_phong_ban').value = data.ma_phong_ban;
        document.getElementById('ten_phong_ban').value = data.ten_phong_ban;
        document.getElementById('truong_phong').value = data.truong_phong || '';
        document.getElementById('mo_ta').value = data.mo_ta || '';
        document.getElementById('phongBanModal').classList.add('active');
    }

    function deletePhongBan(id, name, count) {
        if (count > 0) {
            alert('⚠️ Không thể xóa phòng ban "' + name + '"!\n\nPhòng ban này còn ' + count +
                ' nhân viên.\nVui lòng chuyển nhân viên sang phòng ban khác trước.');
            return;
        }

        if (confirm('⚠️ Bạn có chắc muốn xóa phòng ban "' + name + '"?\n\nHành động này không thể hoàn tác!')) {
            window.location.href = 'phong_ban.php?delete=' + id;
        }
    }

    function closeModal() {
        document.getElementById('phongBanModal').classList.remove('active');
    }

    // Auto hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.style.display = 'none', 300);
        });
    }, 3000);
    </script>

    <style>
    .alert {
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-size: 14px;
        transition: opacity 0.3s;
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

    .departments-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }

    .department-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s;
        position: relative;
    }

    .department-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .department-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        margin-bottom: 15px;
    }

    .department-code {
        font-size: 12px;
        color: #667eea;
        background: #f0f4ff;
        padding: 4px 10px;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .department-content h3 {
        font-size: 20px;
        color: #333;
        margin-bottom: 15px;
    }

    .department-info {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 15px;
    }

    .info-item {
        display: flex;
        gap: 10px;
        font-size: 14px;
        flex-wrap: wrap;
    }

    .info-label {
        color: #666;
        min-width: 120px;
    }

    .info-value {
        color: #333;
    }

    .department-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }

    .add-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 280px;
        cursor: pointer;
        border: 2px dashed #e0e0e0;
        background: #fafafa;
    }

    .add-card:hover {
        border-color: #667eea;
        background: #f0f4ff;
    }

    .add-icon {
        font-size: 48px;
        color: #667eea;
        margin-bottom: 10px;
    }

    .add-text {
        color: #667eea;
        font-weight: 600;
        font-size: 16px;
    }
    </style>
</body>

</html>