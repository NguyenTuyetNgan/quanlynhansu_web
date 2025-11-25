<?php
require_once '../config.php';
checkLogin();

// Xử lý thêm nhân sự
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Upload ảnh đại diện
        $anh_dai_dien = '';
        if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] == 0) {
            $upload_result = uploadFile($_FILES['anh_dai_dien'], '../assets/uploads/avatars');
            if ($upload_result['success']) {
                $anh_dai_dien = $upload_result['path'];
            }
        }
        
        // Insert nhân sự
        $sql = "INSERT INTO nhan_su (ma_nhan_vien, ho_ten, ngay_sinh, gioi_tinh, so_dien_thoai, email, dia_chi, anh_dai_dien, chuc_vu_id, phong_ban_id, ngay_vao_lam, ngay_nghi_viec, loai_hop_dong_id, muc_luong, trinh_do_hoc_van_id, trang_thai_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['ma_nhan_vien'],
            $_POST['ho_ten'],
            $_POST['ngay_sinh'] ?: null,
            $_POST['gioi_tinh'],
            $_POST['so_dien_thoai'],
            $_POST['email'],
            $_POST['dia_chi'],
            $anh_dai_dien,
            $_POST['chuc_vu_id'] ?: null,
            $_POST['phong_ban_id'] ?: null,
            $_POST['ngay_vao_lam'] ?: null,
            $_POST['ngay_nghi_viec'] ?: null,
            $_POST['loai_hop_dong_id'] ?: null,
            $_POST['muc_luong'] ?: null,
            $_POST['trinh_do_hoc_van_id'] ?: null,
            $_POST['trang_thai_id']
        ]);
        
        $new_id = $pdo->lastInsertId();
        
        // Upload tài liệu nếu có
        if (isset($_POST['tai_lieu_data']) && !empty($_POST['tai_lieu_data'])) {
            $tai_lieu_data = json_decode($_POST['tai_lieu_data'], true);
            
            foreach ($tai_lieu_data as $index => $doc) {
                $file_key = 'tai_lieu_file_' . $index;
                
                if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
                    $upload_result = uploadFile($_FILES[$file_key], '../../assets/uploads/documents/');
                    
                    if ($upload_result['success']) {
                        $stmt = $pdo->prepare("
                            INSERT INTO tai_lieu_nhan_su (nhan_su_id, loai_tai_lieu_id, ten_tai_lieu, duong_dan_file, ghi_chu)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $new_id,
                            $doc['loai_tai_lieu_id'],
                            $doc['ten_tai_lieu'],
                            $upload_result['path'],
                            $doc['ghi_chu']
                        ]);
                    }
                }
            }
        }
        
        // Chuyển đến trang chi tiết
        header('Location: nhan_su_detail.php?id=' . $new_id . '&msg=added');
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy dữ liệu cho dropdown
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
    <title>Thêm nhân sự mới</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>

<body>
    <?php include '../sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>➕ Thêm nhân sự mới</h1>
            <div class="user-info">
                <a href="nhan_su.php" class="btn-secondary">← Quay lại</a>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">✗ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-container">
            <form method="POST" enctype="multipart/form-data">
                <!-- Profile Header -->
                <div class="form-avatar">
                    <div class="avatar-preview" id="avatarPreview">
                        <div class="avatar-placeholder">👤</div>
                    </div>
                    <div>
                        <h3>Nhân viên mới</h3>
                        <label for="anh_dai_dien" class="btn-upload">📷 Chọn ảnh đại diện</label>
                        <input type="file" id="anh_dai_dien" name="anh_dai_dien" accept="image/*" style="display: none;"
                            onchange="previewAvatar(this)">
                    </div>
                </div>

                <!-- Tab 1: Thông tin cơ bản -->
                <div id="tab1" class="tab-content active">
                    <div style="padding: 30px;">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Mã nhân viên *</label>
                                <input type="text" name="ma_nhan_vien" class="form-control" placeholder="VD: NV001"
                                    required>
                            </div>

                            <div class="form-group">
                                <label>Họ và tên *</label>
                                <input type="text" name="ho_ten" class="form-control" placeholder="VD: Nguyễn Văn A"
                                    required>
                            </div>

                            <div class="form-group">
                                <label>Ngày sinh</label>
                                <input type="date" name="ngay_sinh" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Giới tính</label>
                                <select name="gioi_tinh" class="form-control">
                                    <option value="Nam">Nam</option>
                                    <option value="Nữ">Nữ</option>
                                    <option value="Khác">Khác</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="tel" name="so_dien_thoai" class="form-control"
                                    placeholder="VD: 0123456789">
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control"
                                    placeholder="VD: example@gmail.com">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <textarea name="dia_chi" class="form-control" rows="3"
                                placeholder="Nhập địa chỉ đầy đủ..."></textarea>
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
                                    <option value="<?php echo $cv['id']; ?>"><?php echo $cv['ten_chuc_vu']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Phòng ban *</label>
                                <select name="phong_ban_id" class="form-control" required>
                                    <option value="">-- Chọn phòng ban --</option>
                                    <?php foreach ($phong_ban_list as $pb): ?>
                                    <option value="<?php echo $pb['id']; ?>"><?php echo $pb['ten_phong_ban']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Ngày vào làm</label>
                                <input type="date" name="ngay_vao_lam" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Ngày nghỉ việc</label>
                                <input type="date" name="ngay_nghi_viec" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Loại hợp đồng</label>
                                <select name="loai_hop_dong_id" class="form-control">
                                    <option value="">-- Chọn loại hợp đồng --</option>
                                    <?php foreach ($loai_hop_dong_list as $hd): ?>
                                    <option value="<?php echo $hd['id']; ?>"><?php echo $hd['ten_loai']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Mức lương (VNĐ)</label>
                                <input type="number" name="muc_luong" class="form-control" min="0" step="1000"
                                    placeholder="VD: 10000000">
                            </div>

                            <div class="form-group">
                                <label>Trình độ học vấn</label>
                                <select name="trinh_do_hoc_van_id" class="form-control">
                                    <option value="">-- Chọn trình độ --</option>
                                    <?php foreach ($trinh_do_list as $td): ?>
                                    <option value="<?php echo $td['id']; ?>"><?php echo $td['ten_trinh_do']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Trạng thái</label>
                                <select name="trang_thai_id" class="form-control">
                                    <?php foreach ($trang_thai_list as $tt): ?>
                                    <option value="<?php echo $tt['id']; ?>"
                                        <?php echo $tt['id'] == 1 ? 'selected' : ''; ?>>
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

                        <div id="filesList">
                            <div style="text-align: center; padding: 60px; color: #999;">
                                <div style="font-size: 64px; margin-bottom: 15px;">📁</div>
                                <p>Chưa có tài liệu nào. Click "Thêm tài liệu mới" để upload.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer buttons -->
                <div
                    style="padding: 20px 30px; border-top: 2px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
                    <a href="nhan_su.php" class="btn-secondary">Hủy</a>
                    <button type="submit" class="btn-primary">💾 Lưu thông tin</button>
                </div>

                <!-- Hidden input để lưu data tài liệu -->
                <input type="hidden" name="tai_lieu_data" id="tai_lieu_data">
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
            <div class="modal-body">
                <div class="form-group">
                    <label>Loại tài liệu *</label>
                    <select id="loai_tai_lieu" class="form-control" required>
                        <option value="">-- Chọn loại tài liệu --</option>
                        <?php foreach ($loai_tai_lieu as $lt): ?>
                        <option value="<?php echo $lt['id']; ?>"><?php echo $lt['ten_loai']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tên tài liệu *</label>
                    <input type="text" id="ten_tai_lieu" class="form-control"
                        placeholder="VD: Hợp đồng lao động năm 2025" required>
                </div>

                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea id="ghi_chu" class="form-control" rows="3"
                        placeholder="Ghi chú thêm về tài liệu..."></textarea>
                </div>

                <div class="form-group">
                    <label>Chọn file *</label>
                    <input type="file" id="file_tai_lieu" class="form-control"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4" required>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        ✓ Cho phép: PDF, Word, Excel, Hình ảnh, Video<br>
                        ✓ Kích thước tối đa: 5MB
                    </small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeUploadModal()">Đóng</button>
                <button type="button" onclick="addFile()" class="btn-primary">➕ Thêm tài liệu</button>
            </div>
        </div>
    </div>

    <script>
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
        document.getElementById('loai_tai_lieu').value = '';
        document.getElementById('ten_tai_lieu').value = '';
        document.getElementById('ghi_chu').value = '';
        document.getElementById('file_tai_lieu').value = '';
    }

    // File management
    let filesArray = [];
    let fileObjects = {};

    function addFile() {
        const loaiTaiLieu = document.getElementById('loai_tai_lieu');
        const tenTaiLieu = document.getElementById('ten_tai_lieu').value;
        const ghiChu = document.getElementById('ghi_chu').value;
        const fileTaiLieu = document.getElementById('file_tai_lieu');

        if (!loaiTaiLieu.value || !tenTaiLieu || !fileTaiLieu.files[0]) {
            alert('⚠️ Vui lòng điền đầy đủ thông tin và chọn file!');
            return;
        }

        const fileIndex = filesArray.length;
        const file = {
            index: fileIndex,
            loai_tai_lieu_id: loaiTaiLieu.value,
            loai_tai_lieu_text: loaiTaiLieu.options[loaiTaiLieu.selectedIndex].text,
            ten_tai_lieu: tenTaiLieu,
            ghi_chu: ghiChu,
            file_name: fileTaiLieu.files[0].name,
            file_size: (fileTaiLieu.files[0].size / 1024 / 1024).toFixed(2) + ' MB'
        };

        filesArray.push(file);
        fileObjects[fileIndex] = fileTaiLieu.files[0];

        renderFiles();
        closeUploadModal();
    }

    function removeFile(index) {
        if (confirm('⚠️ Bạn có chắc muốn xóa tài liệu này?')) {
            filesArray = filesArray.filter(f => f.index !== index);
            delete fileObjects[index];
            renderFiles();
        }
    }

    function renderFiles() {
        const container = document.getElementById('filesList');
        if (filesArray.length === 0) {
            container.innerHTML =
                '<div style="text-align: center; padding: 60px; color: #999;"><div style="font-size: 64px; margin-bottom: 15px;">📁</div><p>Chưa có tài liệu nào. Click "Thêm tài liệu mới" để upload.</p></div>';
            return;
        }

        let html = '<div class="documents-grid">';
        filesArray.forEach(file => {
            const icon = getFileIcon(file.file_name);
            html += `
                <div class="document-card">
                    <div class="doc-icon">${icon}</div>
                    <div class="doc-info">
                        <div class="doc-name">${file.ten_tai_lieu}</div>
                        <div class="doc-type">${file.loai_tai_lieu_text}</div>
                        <div class="doc-meta">📄 ${file.file_name} • ${file.file_size}</div>
                        ${file.ghi_chu ? `<div class="doc-note">📝 ${file.ghi_chu}</div>` : ''}
                    </div>
                    <div class="doc-actions">
                        <button type="button" onclick="removeFile(${file.index})" class="btn-icon btn-delete" title="Xóa">🗑️</button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    function getFileIcon(fileName) {
        const ext = fileName.split('.').pop().toLowerCase();
        const icons = {
            'pdf': '📄',
            'doc': '📝',
            'docx': '📝',
            'xls': '📊',
            'xlsx': '📊',
            'jpg': '🖼️',
            'jpeg': '🖼️',
            'png': '🖼️',
            'mp4': '🎥'
        };
        return icons[ext] || '📎';
    }

    // Submit form
    document.querySelector('form').addEventListener('submit', function(e) {
        // Lưu data tài liệu vào hidden input
        if (filesArray.length > 0) {
            document.getElementById('tai_lieu_data').value = JSON.stringify(filesArray);

            // Tạo input file cho mỗi tài liệu
            const form = this;
            filesArray.forEach(file => {
                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.name = 'tai_lieu_file_' + file.index;
                fileInput.style.display = 'none';

                // Transfer file object
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(fileObjects[file.index]);
                fileInput.files = dataTransfer.files;

                form.appendChild(fileInput);
            });
        }
    });

    // Initialize
    renderFiles();

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
        margin: 30px;
        margin-bottom: 0;
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