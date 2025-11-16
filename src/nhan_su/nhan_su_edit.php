<?php
require_once '../config.php';
checkLogin();

$id = $_GET['id'] ?? 0;

// Lấy thông tin nhân sự
try {
    $stmt = $pdo->prepare("SELECT * FROM nhan_su WHERE id = ?");
    $stmt->execute([$id]);
    $nhansu = $stmt->fetch();
    
    if (!$nhansu) {
        die("Không tìm thấy nhân sự!");
    }
    
    // Lấy tài liệu
    $stmt = $pdo->prepare("
        SELECT tl.*, lt.ten_loai
        FROM tai_lieu_nhan_su tl
        LEFT JOIN loai_tai_lieu lt ON tl.loai_tai_lieu_id = lt.id
        WHERE tl.nhan_su_id = ?
        ORDER BY tl.created_at DESC
    ");
    $stmt->execute([$id]);
    $tai_lieu_list = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['upload_doc'])) {
    try {
        // Upload ảnh mới nếu có
        $anh_dai_dien = $nhansu['anh_dai_dien'];
        if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] == 0) {
            $upload_result = uploadFile($_FILES['anh_dai_dien'], '../../assets/uploads/avatars/');
            if ($upload_result['success']) {
                $anh_dai_dien = $upload_result['path'];
                // Xóa ảnh cũ
                if ($nhansu['anh_dai_dien'] && file_exists($nhansu['anh_dai_dien'])) {
                    unlink($nhansu['anh_dai_dien']);
                }
            }
        }
        
        $sql = "UPDATE nhan_su SET 
                ma_nhan_vien = ?, ho_ten = ?, ngay_sinh = ?, gioi_tinh = ?, 
                so_dien_thoai = ?, email = ?, dia_chi = ?, anh_dai_dien = ?,
                chuc_vu_id = ?, phong_ban_id = ?, ngay_vao_lam = ?, ngay_nghi_viec = ?,
                loai_hop_dong_id = ?, muc_luong = ?, trinh_do_hoc_van_id = ?, trang_thai_id = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['ma_nhan_vien'], $_POST['ho_ten'], $_POST['ngay_sinh'], $_POST['gioi_tinh'],
            $_POST['so_dien_thoai'], $_POST['email'], $_POST['dia_chi'], $anh_dai_dien,
            $_POST['chuc_vu_id'], $_POST['phong_ban_id'], $_POST['ngay_vao_lam'], $_POST['ngay_nghi_viec'] ?: null,
            $_POST['loai_hop_dong_id'], $_POST['muc_luong'], $_POST['trinh_do_hoc_van_id'], $_POST['trang_thai_id'],
            $id        ]);
        
        $success = "Cập nhật thông tin thành công!";
        
        // Reload data
    /*    $stmt = $pdo->prepare("SELECT * FROM nhan_su WHERE id = ?");
        $stmt->execute([$id]);
        $nhansu = $stmt->fetch();*/
        header('Location: nhan_su.php?msg=updated');
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy dữ liệu dropdown
try {
    $phong_ban_list = $pdo->query("SELECT * FROM phong_ban ORDER BY ten_phong_ban")->fetchAll();
    $chuc_vu_list = $pdo->query("SELECT * FROM chuc_vu ORDER BY ten_chuc_vu")->fetchAll();
    $loai_hop_dong_list = $pdo->query("SELECT * FROM loai_hop_dong ORDER BY ten_loai")->fetchAll();
    $trinh_do_list = $pdo->query("SELECT * FROM trinh_do_hoc_van ORDER BY ten_trinh_do")->fetchAll();
    $trang_thai_list = $pdo->query("SELECT * FROM trang_thai_nhan_vien ORDER BY id")->fetchAll();
    $loai_tai_lieu = $pdo->query("SELECT * FROM loai_tai_lieu ORDER BY ten_loai")->fetchAll();
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa nhân sự - <?php echo $nhansu['ho_ten']; ?></title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>

<body>
    <?php include '../sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>✏️ Chỉnh sửa thông tin nhân sự</h1>
            <div class="user-info">
                <a href="nhan_su_detail.php?id=<?php echo $id; ?>" class="btn-secondary">← Quay lại</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">✗ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-container">
            <form method="POST" enctype="multipart/form-data">
                <!-- Tab 1: Thông tin cơ bản -->
                <div id="tab1" class="tab-content active">
                    <div style="padding: 30px;">
                        <div class="form-avatar">
                            <div class="avatar-preview" id="avatarPreview">
                                <?php if ($nhansu['anh_dai_dien']): ?>
                                <img src="<?php echo $nhansu['anh_dai_dien']; ?>"
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                <?php else: ?>
                                <div class="avatar-placeholder">👤</div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3><?php echo $nhansu['ho_ten']; ?></h3>
                                <label for="anh_dai_dien" class="btn-upload">📷 Đổi ảnh</label>
                                <input type="file" id="anh_dai_dien" name="anh_dai_dien" accept="image/*"
                                    style="display: none;" onchange="previewAvatar(this)">
                            </div>
                        </div>

                        <div class="form-grid" style="margin-top: 30px;">
                            <div class="form-group">
                                <label>Mã nhân viên *</label>
                                <input type="text" name="ma_nhan_vien" class="form-control"
                                    value="<?php echo $nhansu['ma_nhan_vien']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Họ và tên *</label>
                                <input type="text" name="ho_ten" class="form-control"
                                    value="<?php echo $nhansu['ho_ten']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Ngày sinh</label>
                                <input type="date" name="ngay_sinh" class="form-control"
                                    value="<?php echo $nhansu['ngay_sinh']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Giới tính</label>
                                <select name="gioi_tinh" class="form-control">
                                    <option value="Nam" <?php echo $nhansu['gioi_tinh'] == 'Nam' ? 'selected' : ''; ?>>
                                        Nam</option>
                                    <option value="Nữ" <?php echo $nhansu['gioi_tinh'] == 'Nữ' ? 'selected' : ''; ?>>Nữ
                                    </option>
                                    <option value="Khác"
                                        <?php echo $nhansu['gioi_tinh'] == 'Khác' ? 'selected' : ''; ?>>Khác</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="tel" name="so_dien_thoai" class="form-control"
                                    value="<?php echo $nhansu['so_dien_thoai']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?php echo $nhansu['email']; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <textarea name="dia_chi" class="form-control"
                                rows="3"><?php echo $nhansu['dia_chi']; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Thông tin công việc -->
                <div id="tab2" class="tab-content">
                    <div style="padding: 30px;">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Chức vụ *</label>
                                <select name="chuc_vu_id" class="form-control" required>
                                    <option value="">-- Chọn chức vụ --</option>
                                    <?php foreach ($chuc_vu_list as $cv): ?>
                                    <option value="<?php echo $cv['id']; ?>"
                                        <?php echo $nhansu['chuc_vu_id'] == $cv['id'] ? 'selected' : ''; ?>>
                                        <?php echo $cv['ten_chuc_vu']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Phòng ban *</label>
                                <select name="phong_ban_id" class="form-control" required>
                                    <option value="">-- Chọn phòng ban --</option>
                                    <?php foreach ($phong_ban_list as $pb): ?>
                                    <option value="<?php echo $pb['id']; ?>"
                                        <?php echo $nhansu['phong_ban_id'] == $pb['id'] ? 'selected' : ''; ?>>
                                        <?php echo $pb['ten_phong_ban']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Ngày vào làm</label>
                                <input type="date" name="ngay_vao_lam" class="form-control"
                                    value="<?php echo $nhansu['ngay_vao_lam']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Ngày nghỉ việc</label>
                                <input type="date" name="ngay_nghi_viec" class="form-control"
                                    value="<?php echo $nhansu['ngay_nghi_viec']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Loại hợp đồng</label>
                                <select name="loai_hop_dong_id" class="form-control">
                                    <option value="">-- Chọn loại hợp đồng --</option>
                                    <?php foreach ($loai_hop_dong_list as $hd): ?>
                                    <option value="<?php echo $hd['id']; ?>"
                                        <?php echo $nhansu['loai_hop_dong_id'] == $hd['id'] ? 'selected' : ''; ?>>
                                        <?php echo $hd['ten_loai']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Mức lương (VNĐ)</label>
                                <input type="number" name="muc_luong" class="form-control" min="0" step="1000"
                                    value="<?php echo $nhansu['muc_luong']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Trình độ học vấn</label>
                                <select name="trinh_do_hoc_van_id" class="form-control">
                                    <option value="">-- Chọn trình độ --</option>
                                    <?php foreach ($trinh_do_list as $td): ?>
                                    <option value="<?php echo $td['id']; ?>"
                                        <?php echo $nhansu['trinh_do_hoc_van_id'] == $td['id'] ? 'selected' : ''; ?>>
                                        <?php echo $td['ten_trinh_do']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Trạng thái</label>
                                <select name="trang_thai_id" class="form-control">
                                    <?php foreach ($trang_thai_list as $tt): ?>
                                    <option value="<?php echo $tt['id']; ?>"
                                        <?php echo $nhansu['trang_thai_id'] == $tt['id'] ? 'selected' : ''; ?>>
                                        <?php echo $tt['ten_trang_thai']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Hồ sơ & Tài liệu -->
                <div id="tab3" class="tab-content">
                    <div style="padding: 30px;">
                        <div style="margin-bottom: 20px;">
                            <button type="button" onclick="showUploadModal()" class="btn-primary">➕ Thêm tài liệu
                                mới</button>
                        </div>

                        <?php if (empty($tai_lieu_list)): ?>
                        <div style="text-align: center; padding: 60px; color: #999;">
                            <div style="font-size: 64px; margin-bottom: 15px;">📁</div>
                            <p>Chưa có tài liệu nào. Click "Thêm tài liệu mới" để upload.</p>
                        </div>
                        <?php else: ?>
                        <div class="documents-grid">
                            <?php foreach ($tai_lieu_list as $tl): ?>
                            <div class="document-card">
                                <div class="doc-icon">
                                    <?php
                                            $ext = strtolower(pathinfo($tl['duong_dan_file'], PATHINFO_EXTENSION));
                                            $icons = ['pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'xls' => '📊', 'xlsx' => '📊', 'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'mp4' => '🎥'];
                                            echo $icons[$ext] ?? '📎';
                                            ?>
                                </div>
                                <div class="doc-info">
                                    <div class="doc-name"><?php echo $tl['ten_tai_lieu']; ?></div>
                                    <div class="doc-type"><?php echo $tl['ten_loai']; ?></div>
                                    <div class="doc-meta">
                                        📅 <?php echo date('d/m/Y H:i', strtotime($tl['created_at'])); ?>
                                    </div>
                                    <?php if ($tl['ghi_chu']): ?>
                                    <div class="doc-note">📝 <?php echo $tl['ghi_chu']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="doc-actions">
                                    <a href="<?php echo $tl['duong_dan_file']; ?>" target="_blank"
                                        class="btn-icon btn-view" title="Xem">👁️</a>
                                    <a href="<?php echo $tl['duong_dan_file']; ?>" download class="btn-icon btn-edit"
                                        title="Tải về">⬇️</a>
                                    <button type="button" onclick="deleteTaiLieu(<?php echo $tl['id']; ?>)"
                                        class="btn-icon btn-delete" title="Xóa">🗑️</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Footer buttons -->
                <div
                    style="padding: 20px 30px; border-top: 2px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
                    <a href="nhan_su_detail.php?id=<?php echo $id; ?>" class="btn-secondary">Hủy</a>
                    <button type="submit" class="btn-primary">💾 Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Upload Tài liệu -->
    <div id="uploadModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>➕ Thêm tài liệu mới</h2>
                <button class="btn-close" onclick="closeUploadModal()">×</button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="nhan_su_id" value="<?php echo $id; ?>">

                    <div class="form-group">
                        <label>Loại tài liệu *</label>
                        <select name="loai_tai_lieu_id" class="form-control" required>
                            <option value="">-- Chọn loại tài liệu --</option>
                            <?php foreach ($loai_tai_lieu as $lt): ?>
                            <option value="<?php echo $lt['id']; ?>"><?php echo $lt['ten_loai']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tên tài liệu *</label>
                        <input type="text" name="ten_tai_lieu" class="form-control"
                            placeholder="VD: Hợp đồng lao động năm 2025" required>
                    </div>

                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="ghi_chu" class="form-control" rows="3"
                            placeholder="Ghi chú thêm về tài liệu..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Chọn file *</label>
                        <input type="file" name="file" class="form-control"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4" required>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            ✓ Cho phép: PDF, Word, Excel, Hình ảnh, Video<br>
                            ✓ Kích thước tối đa: 5MB
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeUploadModal()">Đóng</button>
                    <button type="submit" class="btn-primary">📤 Upload tài liệu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Tab switching
    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById(tabId).classList.add('active');
        event.target.classList.add('active');
    }

    // Preview avatar
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').innerHTML =
                    '<img src="' + e.target.result +
                    '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Upload modal
    function showUploadModal() {
        document.getElementById('uploadModal').classList.add('active');
    }

    function closeUploadModal() {
        document.getElementById('uploadModal').classList.remove('active');
        document.getElementById('uploadForm').reset();
    }

    // Xử lý upload
    document.getElementById('uploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Đang upload...';

        try {
            const response = await fetch('upload_tai_lieu.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert('✓ ' + result.message);
                location.reload();
            } else {
                alert('✗ ' + result.message);
                submitBtn.disabled = false;
                submitBtn.textContent = '📤 Upload tài liệu';
            }
        } catch (error) {
            alert('❌ Lỗi: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.textContent = '📤 Upload tài liệu';
        }
    });

    // Xóa tài liệu
    async function deleteTaiLieu(id) {
        if (!confirm('⚠️ Bạn có chắc muốn xóa tài liệu này?\n\nHành động này không thể hoàn tác!')) return;

        try {
            const response = await fetch('upload_tai_lieu.php?delete=' + id);
            const result = await response.json();

            if (result.success) {
                alert('✓ ' + result.message);
                location.reload();
            } else {
                alert('✗ ' + result.message);
            }
        } catch (error) {
            alert('❌ Lỗi: ' + error.message);
        }
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

    .form-avatar {
        display: flex;
        gap: 25px;
        align-items: center;
        padding: 25px;
        background: #f8f9fa;
        border-radius: 12px;
        margin-bottom: 30px;
    }

    .avatar-preview {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: white;
        border: 3px solid #667eea;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .avatar-placeholder {
        font-size: 48px;
    }

    .btn-upload {
        padding: 8px 16px;
        background: #667eea;
        color: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        display: inline-block;
        margin-top: 10px;
        transition: all 0.3s;
    }

    .btn-upload:hover {
        background: #5568d3;
    }

    .documents-grid {
        display: grid;
        gap: 15px;
    }

    .document-card {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .document-card:hover {
        background: #e9ecef;
        border-color: #667eea;
        transform: translateX(5px);
    }

    .doc-icon {
        font-size: 40px;
        width: 60px;
        height: 60px;
        background: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .doc-info {
        flex: 1;
    }

    .doc-name {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .doc-type {
        font-size: 13px;
        color: #667eea;
        background: white;
        padding: 3px 10px;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 5px;
    }

    .doc-meta {
        font-size: 12px;
        color: #999;
    }

    .doc-note {
        font-size: 13px;
        color: #666;
        margin-top: 5px;
        background: white;
        padding: 5px 10px;
        border-radius: 4px;
    }

    .doc-actions {
        display: flex;
        gap: 10px;
    }
    </style>
</body>

</html>