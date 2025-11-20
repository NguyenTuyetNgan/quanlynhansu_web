<?php
require_once 'config.php';
checkLogin();

// Xử lý thêm/xóa dữ liệu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $table = $_POST['table'];
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $name = clean($_POST['name']);
            switch ($table) {
                case 'chuc_vu':
                    $pdo->prepare("INSERT INTO chuc_vu (ten_chuc_vu) VALUES (?)")->execute([$name]);
                    break;
                case 'loai_hop_dong':
                    $pdo->prepare("INSERT INTO loai_hop_dong (ten_loai) VALUES (?)")->execute([$name]);
                    break;
                case 'trinh_do_hoc_van':
                    $pdo->prepare("INSERT INTO trinh_do_hoc_van (ten_trinh_do) VALUES (?)")->execute([$name]);
                    break;
                case 'trang_thai_nhan_vien':
                    $pdo->prepare("INSERT INTO trang_thai_nhan_vien (ten_trang_thai) VALUES (?)")->execute([$name]);
                    break;
                case 'loai_tai_lieu':
                    $pdo->prepare("INSERT INTO loai_tai_lieu (ten_loai) VALUES (?)")->execute([$name]);
                    break;
            }
            $success = "Thêm thành công!";
        } elseif ($action == 'delete') {
            $id = $_POST['id'];
            switch ($table) {
                case 'chuc_vu':
                    $pdo->prepare("DELETE FROM chuc_vu WHERE id = ?")->execute([$id]);
                    break;
                case 'loai_hop_dong':
                    $pdo->prepare("DELETE FROM loai_hop_dong WHERE id = ?")->execute([$id]);
                    break;
                case 'trinh_do_hoc_van':
                    $pdo->prepare("DELETE FROM trinh_do_hoc_van WHERE id = ?")->execute([$id]);
                    break;
                case 'trang_thai_nhan_vien':
                    $pdo->prepare("DELETE FROM trang_thai_nhan_vien WHERE id = ?")->execute([$id]);
                    break;
                case 'loai_tai_lieu':
                    $pdo->prepare("DELETE FROM loai_tai_lieu WHERE id = ?")->execute([$id]);
                    break;
            }
            $success = "Xóa thành công!";
        }
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy dữ liệu
try {
    $chuc_vu_list = $pdo->query("SELECT * FROM chuc_vu ORDER BY ten_chuc_vu")->fetchAll();
    $loai_hop_dong_list = $pdo->query("SELECT * FROM loai_hop_dong ORDER BY ten_loai")->fetchAll();
    $trinh_do_list = $pdo->query("SELECT * FROM trinh_do_hoc_van ORDER BY ten_trinh_do")->fetchAll();
    $trang_thai_list = $pdo->query("SELECT * FROM trang_thai_nhan_vien ORDER BY id")->fetchAll();
    $loai_tai_lieu_list = $pdo->query("SELECT * FROM loai_tai_lieu ORDER BY ten_loai")->fetchAll();
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>⚙️ Cài đặt hệ thống</h1>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">✗ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            <!-- Chức vụ -->
            <div class="setting-card">
                <div class="setting-header">
                    <div class="setting-icon">💼</div>
                    <h3>Chức vụ</h3>
                </div>
                <div class="setting-list">
                    <?php foreach ($chuc_vu_list as $cv): ?>
                    <div class="setting-item">
                        <span><?php echo $cv['ten_chuc_vu']; ?></span>
                        <button onclick="deleteItem('chuc_vu', <?php echo $cv['id']; ?>)"
                            class="btn-delete-small">🗑️</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="showAddModal('chuc_vu', 'Chức vụ')" class="btn-add-item">+ Thêm</button>
            </div>

            <!-- Loại hợp đồng -->
            <div class="setting-card">
                <div class="setting-header">
                    <div class="setting-icon">📋</div>
                    <h3>Loại hợp đồng</h3>
                </div>
                <div class="setting-list">
                    <?php foreach ($loai_hop_dong_list as $hd): ?>
                    <div class="setting-item">
                        <span><?php echo $hd['ten_loai']; ?></span>
                        <button onclick="deleteItem('loai_hop_dong', <?php echo $hd['id']; ?>)"
                            class="btn-delete-small">🗑️</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="showAddModal('loai_hop_dong', 'Loại hợp đồng')" class="btn-add-item">+ Thêm</button>
            </div>

            <!-- Trạng thái nhân viên -->
            <div class="setting-card">
                <div class="setting-header">
                    <div class="setting-icon">👤</div>
                    <h3>Trạng thái nhân viên</h3>
                </div>
                <div class="setting-list">
                    <?php foreach ($trang_thai_list as $tt): ?>
                    <div class="setting-item">
                        <span><?php echo $tt['ten_trang_thai']; ?></span>
                        <button onclick="deleteItem('trang_thai_nhan_vien', <?php echo $tt['id']; ?>)"
                            class="btn-delete-small">🗑️</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="showAddModal('trang_thai_nhan_vien', 'Trạng thái')" class="btn-add-item">+ Thêm trạng
                    thái mới</button>
            </div>

            <!-- Loại tài liệu -->
            <div class="setting-card">
                <div class="setting-header">
                    <div class="setting-icon">📁</div>
                    <h3>Loại tài liệu</h3>
                </div>
                <div class="setting-list">
                    <?php foreach ($loai_tai_lieu_list as $tl): ?>
                    <div class="setting-item">
                        <span><?php echo $tl['ten_loai']; ?></span>
                        <button onclick="deleteItem('loai_tai_lieu', <?php echo $tl['id']; ?>)"
                            class="btn-delete-small">🗑️</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="showAddModal('loai_tai_lieu', 'Loại tài liệu')" class="btn-add-item">+ Thêm</button>
            </div>

            <!-- Trình độ học vấn -->
            <div class="setting-card">
                <div class="setting-header">
                    <div class="setting-icon">🎓</div>
                    <h3>Trình độ học vấn</h3>
                </div>
                <div class="setting-list">
                    <?php foreach ($trinh_do_list as $td): ?>
                    <div class="setting-item">
                        <span><?php echo $td['ten_trinh_do']; ?></span>
                        <button onclick="deleteItem('trinh_do_hoc_van', <?php echo $td['id']; ?>)"
                            class="btn-delete-small">🗑️</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="showAddModal('trinh_do_hoc_van', 'Trình độ')" class="btn-add-item">+ Thêm</button>
            </div>
        </div>
    </div>

    <!-- Modal Add -->
    <div id="addModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2 id="addModalTitle">Thêm mới</h2>
                <button class="btn-close" onclick="closeAddModal()">×</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="table" id="add_table">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label id="addModalLabel">Tên</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeAddModal()">Đóng</button>
                    <button type="submit" class="btn-primary">+ Thêm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete form (hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="table" id="delete_table">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script>
    function showAddModal(table, label) {
        document.getElementById('add_table').value = table;
        document.getElementById('addModalTitle').textContent = 'Thêm ' + label;
        document.getElementById('addModalLabel').textContent = 'Tên ' + label.toLowerCase();
        document.getElementById('addModal').classList.add('active');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.remove('active');
    }

    function deleteItem(table, id) {
        if (confirm('Bạn có chắc muốn xóa?')) {
            document.getElementById('delete_table').value = table;
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => el.style.display = 'none');
    }, 3000);
    </script>

    <style>
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }

    .setting-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .setting-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .setting-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .setting-header h3 {
        font-size: 18px;
        color: #333;
    }

    .setting-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 15px;
        max-height: 300px;
        overflow-y: auto;
    }

    .setting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        background: #f8f9fa;
        border-radius: 6px;
        font-size: 14px;
    }

    .btn-delete-small {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 16px;
        opacity: 0.6;
        transition: all 0.3s;
    }

    .btn-delete-small:hover {
        opacity: 1;
        transform: scale(1.2);
    }

    .btn-add-item {
        width: 100%;
        padding: 12px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-add-item:hover {
        background: #5568d3;
    }
    </style>
</body>

</html>