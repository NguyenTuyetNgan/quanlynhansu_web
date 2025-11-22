<?php
require_once '../config.php';
checkLogin();

// Xử lý xóa nhân sự
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM nhan_su WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header('Location: nhan_su.php?msg=deleted');
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách nhân sự với filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$phong_ban = isset($_GET['phong_ban']) ? $_GET['phong_ban'] : '';
$chuc_vu = isset($_GET['chuc_vu']) ? $_GET['chuc_vu'] : '';
$trang_thai = isset($_GET['trang_thai']) ? $_GET['trang_thai'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$month_added = isset($_GET['month_added']) ? $_GET['month_added'] : '';
$birthday_month = isset($_GET['birthday_month']) ? $_GET['birthday_month'] : '';

$sql = "SELECT ns.*, pb.ten_phong_ban, cv.ten_chuc_vu, tt.ten_trang_thai
        FROM nhan_su ns
        LEFT JOIN phong_ban pb ON ns.phong_ban_id = pb.id
        LEFT JOIN chuc_vu cv ON ns.chuc_vu_id = cv.id
        LEFT JOIN trang_thai_nhan_vien tt ON ns.trang_thai_id = tt.id
        WHERE 1=1";

$params = [];

if ($search) {
    $sql .= " AND (ns.ma_nhan_vien LIKE ? OR ns.ho_ten LIKE ? OR ns.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($phong_ban) {
    $sql .= " AND ns.phong_ban_id = ?";
    $params[] = $phong_ban;
}

if ($chuc_vu) {
    $sql .= " AND ns.chuc_vu_id = ?";
    $params[] = $chuc_vu;
}

if ($trang_thai) {
    $sql .= " AND ns.trang_thai_id = ?";
    $params[] = $trang_thai;
}

if ($date_from) {
    $sql .= " AND ns.ngay_vao_lam >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $sql .= " AND ns.ngay_vao_lam <= ?";
    $params[] = $date_to;
}

// Filter: Nhân sự mới (thêm trong tháng)
if ($month_added) {
    $sql .= " AND DATE_FORMAT(ns.created_at, '%Y-%m') = ?";
    $params[] = $month_added;
}

// Filter: Sinh nhật trong tháng
if ($birthday_month) {
    $sql .= " AND MONTH(ns.ngay_sinh) = ?";
    $params[] = $birthday_month;
}


$sql .= " ORDER BY ns.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $nhan_su_list = $stmt->fetchAll();
    
    // Lấy danh sách phòng ban, chức vụ
    $phong_ban_list = $pdo->query("SELECT * FROM phong_ban ORDER BY ten_phong_ban")->fetchAll();
    $chuc_vu_list = $pdo->query("SELECT * FROM chuc_vu ORDER BY ten_chuc_vu")->fetchAll();
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý nhân sự</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>

<body>
    <?php include '../sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>👥 Quản lý nhân sự</h1>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php 
                if ($_GET['msg'] == 'added') echo '✓ Thêm nhân sự thành công!';
                if ($_GET['msg'] == 'updated') echo '✓ Cập nhật thành công!';
                if ($_GET['msg'] == 'deleted') echo '✓ Xóa thành công!';
            ?>
        </div>
        <?php endif; ?>

        <!-- Filter & Search -->
        <div class="table-container">
            <div class="table-header">
                <h2>Danh sách nhân sự (<?php echo count($nhan_su_list); ?>)</h2>
                <div class="table-actions">
                    <button class="btn-import" onclick="showImportModal()">📥 Import Excel</button>
                    <button class="btn-export" onclick="showExportModal()">📤 Export Excel</button>
                    <a href="nhan_su_add.php" class="btn-primary">+ Thêm nhân sự</a>
                </div>
            </div>

            <!-- Hiển thị filter đang áp dụng -->
            <?php if ($month_added || $birthday_month || ($trang_thai && isset($_GET['from_dashboard'])) || ($date_from && $date_to && isset($_GET['from_dashboard']))): ?>
            <div style="padding: 15px 20px; background: #f0f4ff; border-bottom: 2px solid #e0e0e0;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <strong style="color: #667eea;">🔍 Đang lọc từ Dashboard:</strong>
                    <?php if ($month_added): ?>
                    <span class="filter-badge">📅 Nhân sự mới tháng
                        <?php echo date('m/Y', strtotime($month_added . '-01')); ?></span>
                    <?php endif; ?>
                    <?php if ($birthday_month): ?>
                    <span class="filter-badge">🎂 Sinh nhật tháng <?php echo $birthday_month; ?></span>
                    <?php endif; ?>
                    <?php if ($trang_thai && isset($_GET['from_dashboard'])): ?>
                    <span class="filter-badge">
                        <?php 
                                    if ($trang_thai == 1) echo '✅ Đang làm việc';
                                    if ($trang_thai == 2) echo '🤰 Đang nghỉ sinh';
                                    if ($trang_thai == 3) echo '❌ Đã nghỉ việc';
                                ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($date_from && $date_to && isset($_GET['from_dashboard'])): ?>
                    <span class="filter-badge">📆 Từ <?php echo formatDate($date_from); ?> đến
                        <?php echo formatDate($date_to); ?></span>
                    <?php endif; ?>
                    <a href="nhan_su.php"
                        style="color: #667eea; text-decoration: none; font-size: 14px; margin-left: 10px;">✖ Xóa bộ
                        lọc</a>
                </div>
            </div>
            <?php endif; ?>

            <div style="padding: 20px; border-bottom: 2px solid #f0f0f0;">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <input type="text" name="search" class="search-box" placeholder="🔍 Tìm kiếm..."
                        value="<?php echo $search; ?>">

                    <select name="phong_ban" class="form-control" style="width: auto;">
                        <option value="">Tất cả phòng ban</option>
                        <?php foreach ($phong_ban_list as $pb): ?>
                        <option value="<?php echo $pb['id']; ?>"
                            <?php echo $phong_ban == $pb['id'] ? 'selected' : ''; ?>>
                            <?php echo $pb['ten_phong_ban']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="chuc_vu" class="form-control" style="width: auto;">
                        <option value="">Tất cả chức vụ</option>
                        <?php foreach ($chuc_vu_list as $cv): ?>
                        <option value="<?php echo $cv['id']; ?>" <?php echo $chuc_vu == $cv['id'] ? 'selected' : ''; ?>>
                            <?php echo $cv['ten_chuc_vu']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="trang_thai" class="form-control" style="width: auto;">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1" <?php echo $trang_thai == '1' ? 'selected' : ''; ?>>Đang làm việc</option>
                        <option value="2" <?php echo $trang_thai == '2' ? 'selected' : ''; ?>>Đang nghỉ sinh</option>
                        <option value="3" <?php echo $trang_thai == '3' ? 'selected' : ''; ?>>Đã nghỉ việc</option>
                    </select>
                    <a href="nhan_su.php" class="btn-secondary">↻ Đặt lại</a>
                </form>
            </div>

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Hình ảnh</th>
                        <th>Họ và tên</th>
                        <th>Chức vụ</th>
                        <th>Phòng ban</th>
                        <th>Thông tin liên hệ</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($nhan_su_list)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                            <div style="color: #666;">Chưa có nhân sự nào</div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($nhan_su_list as $ns): ?>
                    <tr>
                        <td><strong><?php echo $ns['ma_nhan_vien']; ?></strong></td>
                        <td>
                            <?php if ($ns['anh_dai_dien']): ?>
                            <img src="<?php echo $ns['anh_dai_dien']; ?>"
                                style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                            <div
                                style="width: 40px; height: 40px; border-radius: 50%; background: #e0e0e0; display: flex; align-items: center; justify-content: center;">
                                👤</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><strong><?php echo $ns['ho_ten']; ?></strong></div>
                            <div style="font-size: 12px; color: #666;"><?php echo $ns['gioi_tinh']; ?></div>
                        </td>
                        <td><?php echo $ns['ten_chuc_vu'] ?? '-'; ?></td>
                        <td><?php echo $ns['ten_phong_ban'] ?? '-'; ?></td>
                        <td>
                            <div style="font-size: 13px;">📧 <?php echo $ns['email'] ?? '-'; ?></div>
                            <div style="font-size: 13px; margin-top: 4px;">📱 <?php echo $ns['so_dien_thoai'] ?? '-'; ?>
                            </div>
                        </td>
                        <td>
                            <?php
                                $badge = 'badge-success';
                                if ($ns['trang_thai_id'] == 2) $badge = 'badge-warning';
                                if ($ns['trang_thai_id'] == 3) $badge = 'badge-danger';
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo $ns['ten_trang_thai']; ?></span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="nhan_su_detail.php?id=<?php echo $ns['id']; ?>"
                                    class="btn-icon btn-view">👁️</a>
                                <a href="nhan_su_edit.php?id=<?php echo $ns['id']; ?>" class="btn-icon btn-edit">✏️</a>
                                <button onclick="deleteNhanSu(<?php echo $ns['id']; ?>)"
                                    class="btn-icon btn-delete">🗑️</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Import -->
    <div id="importModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>📥 Import nhân sự từ Excel</h2>
                <button class="btn-close" onclick="closeImportModal()">×</button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="import-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Tải file mẫu</h4>
                                <p>Tải file Excel mẫu để điền thông tin nhân sự</p>
                                <a href="../export_all.php?type=template" class="btn-download">📄 Tải file mẫu
                                    (.xlsx)</a>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Điền thông tin</h4>
                                <p>Mở file mẫu và điền thông tin nhân sự theo hướng dẫn trong sheet "Hướng dẫn"</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Upload file</h4>
                                <p>Chọn file Excel đã điền thông tin để import</p>
                                <input type="file" name="import_file" id="import_file" class="form-control"
                                    accept=".xlsx,.xls,.csv" required>
                                <small style="color: #666; margin-top: 8px; display: block;">
                                    ✓ Hỗ trợ: Excel (.xlsx, .xls) và CSV<br>
                                    ✓ Cột bắt buộc: ma_nhan_vien, ho_ten
                                </small>
                            </div>
                        </div>
                    </div>

                    <div id="importResult" style="display: none; padding: 15px; border-radius: 6px; margin-top: 15px;">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeImportModal()">Đóng</button>
                    <button type="submit" class="btn-primary" id="importBtn">📤 Import ngay</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Export -->
    <div id="exportModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>📤 Export danh sách nhân sự</h2>
                <button class="btn-close" onclick="closeExportModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="export-options">
                    <a href="../export_all.php?type=nhansu" class="export-option">
                        <div class="export-icon">👥</div>
                        <div class="export-info">
                            <h4>Danh sách nhân sự</h4>
                            <p>Xuất toàn bộ thông tin nhân viên</p>
                        </div>
                        <span class="export-format">Excel</span>
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeExportModal()">Đóng</button>
            </div>
        </div>
    </div>

    <script>
    function deleteNhanSu(id) {
        if (confirm('Bạn có chắc muốn xóa nhân sự này?')) {
            window.location.href = 'nhan_su.php?delete=' + id;
        }
    }

    function showImportModal() {
        document.getElementById('importModal').classList.add('active');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.remove('active');
        document.getElementById('importForm').reset();
        document.getElementById('importResult').style.display = 'none';
    }

    function showExportModal() {
        document.getElementById('exportModal').classList.add('active');
    }

    function closeExportModal() {
        document.getElementById('exportModal').classList.remove('active');
    }

    // Xử lý import
    document.getElementById('importForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const importBtn = document.getElementById('importBtn');
        const resultDiv = document.getElementById('importResult');

        importBtn.disabled = true;
        importBtn.textContent = '⏳ Đang import...';
        resultDiv.style.display = 'none';

        try {
            const response = await fetch('import_excel.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            resultDiv.style.display = 'block';
            if (result.success) {
                resultDiv.style.background = '#d4edda';
                resultDiv.style.color = '#155724';
                let html = '✓ ' + result.message;
                if (result.details.errors && result.details.errors.length > 0) {
                    html +=
                        '<br><br><strong>Chi tiết lỗi:</strong><ul style="margin: 10px 0; padding-left: 20px;">';
                    result.details.errors.forEach(err => html += '<li>' + err + '</li>');
                    html += '</ul>';
                }
                resultDiv.innerHTML = html;
                setTimeout(() => location.reload(), 2000);
            } else {
                resultDiv.style.background = '#f8d7da';
                resultDiv.style.color = '#721c24';
                resultDiv.innerHTML = '✗ ' + result.message;
            }
        } catch (error) {
            resultDiv.style.display = 'block';
            resultDiv.style.background = '#f8d7da';
            resultDiv.style.color = '#721c24';
            resultDiv.innerHTML = '✗ Lỗi: ' + error.message;
        } finally {
            importBtn.disabled = false;
            importBtn.textContent = '📤 Import ngay';
        }
    });

    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => el.style.display = 'none');
    }, 3000);

    // Tự động submit khi chọn select
    document.querySelectorAll("select").forEach(function(sel) {
        sel.addEventListener("change", function() {
            this.form.submit();
        });
    });

    // Tự động submit khi đổi ngày
    document.querySelectorAll("input[type='date']").forEach(function(input) {
        input.addEventListener("change", function() {
            this.form.submit();
        });
    });

    // Bấm ENTER trong ô tìm kiếm -> submit form
    const searchInput = document.querySelector("input[name='search']");
    if (searchInput) {
        searchInput.addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
    </script>

    <style>
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-size: 14px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .filter-badge {
        display: inline-block;
        padding: 6px 12px;
        background: white;
        border: 1px solid #667eea;
        border-radius: 20px;
        font-size: 13px;
        color: #667eea;
        font-weight: 500;
    }

    .table-header {
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #f0f0f0;
        flex-wrap: nowrap;
        gap: 20px;
    }

    .table-header h2 {
        font-size: 20px;
        color: #333;
        margin: 0;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .table-actions {
        display: flex;
        gap: 10px;
        flex-shrink: 0;
        flex-wrap: nowrap;
    }

    .btn-import,
    .btn-export {
        padding: 10px 16px;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
        white-space: nowrap;
    }

    .btn-import {
        background: #28a745;
    }

    .btn-import:hover {
        background: #218838;
        transform: translateY(-2px);
    }

    .btn-export {
        background: #17a2b8;
    }

    .btn-export:hover {
        background: #138496;
        transform: translateY(-2px);
    }

    .import-steps {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .step {
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }

    .step-number {
        width: 32px;
        height: 32px;
        background: #667eea;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
    }

    .step-content {
        flex: 1;
    }

    .step-content h4 {
        margin: 0 0 5px 0;
        color: #333;
    }

    .step-content p {
        margin: 0 0 10px 0;
        color: #666;
        font-size: 14px;
    }

    .btn-download {
        display: inline-block;
        padding: 8px 16px;
        background: #f0f4ff;
        color: #667eea;
        border-radius: 6px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-download:hover {
        background: #667eea;
        color: white;
    }

    .export-options {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .export-option {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        text-decoration: none;
        color: inherit;
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .export-option:hover {
        background: #e9ecef;
        border-color: #667eea;
        transform: translateX(5px);
    }

    .export-icon {
        width: 50px;
        height: 50px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .export-info {
        flex: 1;
    }

    .export-info h4 {
        font-size: 16px;
        color: #333;
        margin: 0 0 4px 0;
    }

    .export-info p {
        font-size: 13px;
        color: #666;
        margin: 0;
    }

    .export-format {
        padding: 5px 12px;
        background: #667eea;
        color: white;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Responsive cho màn hình nhỏ */
    @media (max-width: 768px) {
        .table-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .table-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .btn-import,
        .btn-export,
        .table-actions .btn-primary {
            padding: 8px 12px;
            font-size: 13px;
        }
    }
    </style>
</body>

</html>