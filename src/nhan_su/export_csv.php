<?php
require_once '../config.php';
checkLogin();

// Lấy tất cả nhân sự
try {
    $sql = "SELECT ns.*, pb.ten_phong_ban, cv.ten_chuc_vu, tt.ten_trang_thai, lhd.ten_loai as loai_hop_dong, td.ten_trinh_do
            FROM nhan_su ns
            LEFT JOIN phong_ban pb ON ns.phong_ban_id = pb.id
            LEFT JOIN chuc_vu cv ON ns.chuc_vu_id = cv.id
            LEFT JOIN trang_thai_nhan_vien tt ON ns.trang_thai_id = tt.id
            LEFT JOIN loai_hop_dong lhd ON ns.loai_hop_dong_id = lhd.id
            LEFT JOIN trinh_do_hoc_van td ON ns.trinh_do_hoc_van_id = td.id
            ORDER BY ns.ma_nhan_vien";
    
    $stmt = $pdo->query($sql);
    $nhan_su_list = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}

// Tạo file CSV
$filename = 'Bao_cao_nhan_su_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Thêm BOM để Excel hiển thị UTF-8 đúng
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header
fputcsv($output, [
    'STT',
    'Mã nhân viên',
    'Họ và tên',
    'Ngày sinh',
    'Giới tính',
    'Số điện thoại',
    'Email',
    'Địa chỉ',
    'Chức vụ',
    'Phòng ban',
    'Ngày vào làm',
    'Ngày nghỉ việc',
    'Loại hợp đồng',
    'Mức lương',
    'Trình độ học vấn',
    'Trạng thái'
]);

// Dữ liệu
$stt = 1;
foreach ($nhan_su_list as $ns) {
    fputcsv($output, [
        $stt++,
        $ns['ma_nhan_vien'],
        $ns['ho_ten'],
        $ns['ngay_sinh'] ? date('d/m/Y', strtotime($ns['ngay_sinh'])) : '',
        $ns['gioi_tinh'],
        $ns['so_dien_thoai'],
        $ns['email'],
        $ns['dia_chi'],
        $ns['ten_chuc_vu'] ?: '',
        $ns['ten_phong_ban'] ?: '',
        $ns['ngay_vao_lam'] ? date('d/m/Y', strtotime($ns['ngay_vao_lam'])) : '',
        $ns['ngay_nghi_viec'] ? date('d/m/Y', strtotime($ns['ngay_nghi_viec'])) : '',
        $ns['loai_hop_dong'] ?: '',
        $ns['muc_luong'] ? number_format($ns['muc_luong'], 0, ',', '.') : '',
        $ns['ten_trinh_do'] ?: '',
        $ns['ten_trang_thai']
    ]);
}

fclose($output);
exit();
?>