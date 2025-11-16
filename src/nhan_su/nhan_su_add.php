<?php
require_once '../config.php';
checkLogin();

// Xử lý thêm nhân sự
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Upload ảnh đại diện
        $anh_dai_dien = '';
        if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] == 0) {
            $upload_result = uploadFile($_FILES['anh_dai_dien'], 'uploads/avatars/');
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
                    $upload_result = uploadFile($_FILES[$file_key], 'uploads/documents/');
                    
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
                <span>Xin chào, <strong><?php echo $_SESSION['username']; ?></strong></span>
                <a href="logout.php" class="btn-logout">Đăng xuất</a>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">✗ <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-container">
            <form method="POST" enctype="multipart/form-data">
                <!-- Profile Header -->
                <div class="profile-header-inline">
                    <div class="profile-avatar-inline" id="avatarPreview">
                        <div class="avatar-placeholder">👤</div>
                    </div>
                    <div class="profile-info-inline">
                        <h2>Nhân viên mới</h2>
                        <div class="profile-meta">
                            <label for="anh_dai_dien" class="btn-upload-inline">📷 Chọn ảnh đại diện</label>
                            <input type="file" id="anh_dai_dien" name="anh_dai_dien" accept="image/*"
                                style="display: none;" onchange="previewAvatar(this)">
                        </div>
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
                        <h3 style="margin-bottom: 20px;">📁 Tài liệu đính kèm (Không bắt buộc)</h3>
                        <div class="upload-area">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Loại tài liệu *</label>
                                    <select id="loai_tai_lieu" class="form-control">
                                        <option value="">-- Chọn loại tài liệu --</option>
                                        <?php foreach ($loai_tai_lieu as $lt): ?>
                                        <option value="<?php echo $lt['id']; ?>"><?php echo $lt['ten_loai']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Tên tài liệu *</label>
                                    <input type="text" id="ten_tai_lieu" class="form-control"
                                        placeholder="VD: Hợp đồng lao động">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Ghi chú</label>
                                <textarea id="ghi_chu" class="form-control" rows="2"
                                    placeholder="Ghi chú thêm..."></textarea>
                            </div>

                            <div class="form-group">
                                <label>Chọn file *</label>
                                <input type="file" id="file_tai_lieu" class="form-control"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4">
                                <small style="color: #666; display: block; margin-top: 5px;">
                                    ✓ Cho phép: PDF, Word, Excel, Hình ảnh, Video (Tối đa 5MB)
                                </small>
                            </div>

                            <button type="button" onclick="addFile()" class="btn-primary">➕ Thêm tài liệu</button>
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

        // Reset form
        loaiTaiLieu.value = '';
        document.getElementById('ten_tai_lieu').value = '';
        document.getElementById('ghi_chu').value = '';
        document.getElementById('file_tai_lieu').value = '';
    }

    function removeFile(index) {
        filesArray = filesArray.filter(f => f.index !== index);
        delete fileObjects[index];
        renderFiles();
    }

    function renderFiles() {
        const container = document.getElementById('filesList');
        if (filesArray.length === 0) {
            container.innerHTML =
                '<div style="text-align: center; color: #999; padding: 20px;">Chưa có tài liệu nào. Click "Thêm tài liệu" để upload.</div>';
            return;
        }

        let html = '<div class="files-grid">';
        filesArray.forEach(file => {
            const icon = getFileIcon(file.file_name);
            html += `
                    <div class="file-item">
                        <div class="file-icon">${icon}</div>
                        <div class="file-info">
                            <div class="file-name">${file.ten_tai_lieu}</div>
                            <div class="file-meta">${file.file_name} • ${file.file_size}</div>
                            <div class="file-type">${file.loai_tai_lieu_text}</div>
                            ${file.ghi_chu ? `<div class="file-note">📝 ${file.ghi_chu}</div>` : ''}
                        </div>
                        <button type="button" onclick="removeFile(${file.index})" class="btn-icon btn-delete">🗑️</button>
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
    </script>

    <style>
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #dc3545;
    }

    .profile-header-inline {
        display: flex;
        gap: 25px;
        padding: 30px;
        background: #f8f9fa;
        border-bottom: 2px solid #e0e0e0;
    }

    .profile-avatar-inline {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 3px dashed #667eea;
        overflow: hidden;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.3s;
    }

    .profile-avatar-inline:hover {
        border-style: solid;
        transform: scale(1.05);
    }

    .profile-avatar-inline img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-placeholder {
        font-size: 48px;
        color: #667eea;
    }

    .profile-info-inline h2 {
        font-size: 24px;
        margin-bottom: 10px;
        color: #333;
    }

    .profile-meta {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .btn-upload-inline {
        padding: 8px 16px;
        background: #667eea;
        color: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
        display: inline-block;
    }

    .btn-upload-inline:hover {
        background: #5568d3;
        transform: translateY(-2px);
    }

    .upload-area {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 12px;
        border: 2px dashed #e0e0e0;
    }

    .files-grid {
        display: grid;
        gap: 15px;
        margin-bottom: 20px;
    }

    .file-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }

    .file-icon {
        font-size: 32px;
        width: 50px;
        height: 50px;
        background: #f0f4ff;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .file-info {
        flex: 1;
    }

    .file-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }

    .file-meta {
        font-size: 12px;
        color: #999;
        margin-bottom: 4px;
    }

    .file-type {
        font-size: 11px;
        color: #667eea;
        background: #f0f4ff;
        padding: 2px 8px;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 4px;
    }

    .file-note {
        font-size: 12px;
        color: #666;
        background: #fff;
        padding: 5px 10px;
        border-radius: 4px;
        margin-top: 5px;
    }
    </style>
</body>

</html>