<?php
require_once '../config.php';
checkLogin();

$id = $_GET['id'] ?? 0;

// Lấy thông tin nhân sự
try {
    $stmt = $pdo->prepare("
        SELECT ns.*, 
               pb.ten_phong_ban,
               cv.ten_chuc_vu,
               lhd.ten_loai as loai_hop_dong,
               td.ten_trinh_do,
               tt.ten_trang_thai
        FROM nhan_su ns
        LEFT JOIN phong_ban pb ON ns.phong_ban_id = pb.id
        LEFT JOIN chuc_vu cv ON ns.chuc_vu_id = cv.id
        LEFT JOIN loai_hop_dong lhd ON ns.loai_hop_dong_id = lhd.id
        LEFT JOIN trinh_do_hoc_van td ON ns.trinh_do_hoc_van_id = td.id
        LEFT JOIN trang_thai_nhan_vien tt ON ns.trang_thai_id = tt.id
        WHERE ns.id = ?
    ");
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
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết nhân sự - <?php echo $nhansu['ho_ten']; ?></title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>

<body>
    <?php include '../sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>👤 Chi tiết nhân sự</h1>
            <div class="header-actions">
                <a href="nhan_su.php" class="btn-secondary">← Quay lại</a>
                <a href="nhan_su_edit.php?id=<?php echo $id; ?>" class="btn-primary">✏️ Chỉnh sửa</a>
            </div>
        </div>

        <div class="table-container">
            <!-- Profile Header -->
            <div class="profile-header-inline">
                <div class="profile-avatar-inline">
                    <?php if ($nhansu['anh_dai_dien']): ?>
                    <img src="<?php echo $nhansu['anh_dai_dien']; ?>" alt="Avatar">
                    <?php else: ?>
                    <div class="avatar-placeholder">👤</div>
                    <?php endif; ?>
                </div>
                <div class="profile-info-inline">
                    <h2><?php echo $nhansu['ho_ten']; ?></h2>
                    <div class="profile-meta">
                        <span
                            class="badge <?php echo $nhansu['trang_thai_id'] == 1 ? 'badge-success' : ($nhansu['trang_thai_id'] == 2 ? 'badge-warning' : 'badge-danger'); ?>">
                            <?php echo $nhansu['ten_trang_thai']; ?>
                        </span>
                        <span>• <?php echo $nhansu['ten_chuc_vu']; ?></span>
                        <span>• <?php echo $nhansu['ten_phong_ban']; ?></span>
                    </div>
                </div>
            </div>


            <!-- Tab 1: Thông tin cơ bản -->
            <div id="tab1" class="tab-content active">
                <div style="padding: 30px;">
                    <div class="info-grid-view">
                        <div class="info-row">
                            <label>Mã nhân viên</label>
                            <div class="info-value"><?php echo $nhansu['ma_nhan_vien']; ?></div>
                        </div>

                        <div class="info-row">
                            <label>Họ và tên</label>
                            <div class="info-value"><?php echo $nhansu['ho_ten']; ?></div>
                        </div>

                        <div class="info-row">
                            <label>Ngày sinh</label>
                            <div class="info-value"><?php echo formatDate($nhansu['ngay_sinh']); ?></div>
                        </div>

                        <div class="info-row">
                            <label>Giới tính</label>
                            <div class="info-value"><?php echo $nhansu['gioi_tinh']; ?></div>
                        </div>

                        <div class="info-row">
                            <label>Số điện thoại</label>
                            <div class="info-value">📱 <?php echo $nhansu['so_dien_thoai'] ?: '-'; ?></div>
                        </div>

                        <div class="info-row">
                            <label>Email</label>
                            <div class="info-value">📧 <?php echo $nhansu['email'] ?: '-'; ?></div>
                        </div>

                        <div class="info-row full-width">
                            <label>Địa chỉ</label>
                            <div class="info-value">📍 <?php echo $nhansu['dia_chi'] ?: '-'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Thông tin công việc -->
            <div id="tab2" class="tab-content">
                <div style="padding: 30px;">
                    <div class="info-grid-view">
                        <div class="info-row">
                            <label>Chức vụ</label>
                            <div class="info-value"><?php echo $nhansu['ten_chuc_vu'] ?: '-'; ?></div>
                        </div>

                        <div class="info-row">
                            <label>Phòng ban</label>
                            <div class="info-value"><?php echo $nhansu['ten_phong_ban'] ?: '-'; ?></div>
                        </div>

                        <div class="info-row">
                            <label>Ngày vào làm</label>
                            <div class="info-value"><?php echo formatDate($nhansu['ngay_vao_lam']); ?></div>
                        </div>

                        <div class="info-row">
                            <label>Ngày nghỉ việc</label>
                            <div class="info-value">
                                <?php echo $nhansu['ngay_nghi_viec'] ? formatDate($nhansu['ngay_nghi_viec']) : '-'; ?>
                            </div>
                        </div>

                        <div class="info-row">
                            <label>Loại hợp đồng</label>
                            <div class="info-value"><?php echo $nhansu['loai_hop_dong'] ?: '-'; ?></div>
                        </div>

                        <div class="info-row">
                            <label>Mức lương</label>
                            <div class="info-value">
                                <?php echo $nhansu['muc_luong'] ? formatVND($nhansu['muc_luong']) : '-'; ?></div>
                        </div>

                        <div class="info-row">
                            <label>Trình độ học vấn</label>
                            <div class="info-value"><?php echo $nhansu['ten_trinh_do'] ?: '-'; ?></div>
                        </div>

                        <div class="info-row">
                            <label>Trạng thái</label>
                            <div class="info-value">
                                <span
                                    class="badge <?php echo $nhansu['trang_thai_id'] == 1 ? 'badge-success' : ($nhansu['trang_thai_id'] == 2 ? 'badge-warning' : 'badge-danger'); ?>">
                                    <?php echo $nhansu['ten_trang_thai']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Hồ sơ & Tài liệu -->
            <div id="tab3" class="tab-content">
                <div style="padding: 30px;">
                    <?php if (empty($tai_lieu_list)): ?>
                    <div style="text-align: center; padding: 60px; color: #999;">
                        <div style="font-size: 64px; margin-bottom: 15px;">📁</div>
                        <p>Chưa có tài liệu nào</p>
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
                                    <?php echo date('d/m/Y H:i', strtotime($tl['created_at'])); ?>
                                </div>
                                <?php if ($tl['ghi_chu']): ?>
                                <div class="doc-note"><?php echo $tl['ghi_chu']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="doc-actions">
                                <a href="<?php echo $tl['duong_dan_file']; ?>" target="_blank" class="btn-icon btn-view"
                                    title="Xem">👁️</a>
                                <a href="<?php echo $tl['duong_dan_file']; ?>" download class="btn-icon btn-edit"
                                    title="Tải về">⬇️</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
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
    </script>

    <style>
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
        border: 3px solid #667eea;
        overflow: hidden;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .profile-avatar-inline img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .avatar-placeholder {
        font-size: 48px;
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
        font-size: 14px;
        color: #666;
    }

    .info-grid-view {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .info-row {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .info-row.full-width {
        grid-column: span 2;
    }

    .info-row label {
        font-size: 13px;
        color: #666;
        font-weight: 600;
    }

    .info-value {
        font-size: 15px;
        color: #333;
        padding: 12px 15px;
        background: #f8f9fa;
        border-radius: 6px;
        border-left: 3px solid #667eea;
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
    }

    .document-card:hover {
        background: #e9ecef;
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
        font-style: italic;
    }

    .doc-actions {
        display: flex;
        gap: 10px;
    }
    </style>
</body>

</html>