<?php
require_once 'config.php';
checkLogin();

$type = $_GET['type'] ?? 'all';

try {
    // Hàm lấy data nhân sự
    function getNhanSuData($pdo) {
        $sql = "SELECT ns.*, pb.ten_phong_ban, cv.ten_chuc_vu, tt.ten_trang_thai, 
                       lhd.ten_loai as loai_hop_dong, td.ten_trinh_do
                FROM nhan_su ns
                LEFT JOIN phong_ban pb ON ns.phong_ban_id = pb.id
                LEFT JOIN chuc_vu cv ON ns.chuc_vu_id = cv.id
                LEFT JOIN trang_thai_nhan_vien tt ON ns.trang_thai_id = tt.id
                LEFT JOIN loai_hop_dong lhd ON ns.loai_hop_dong_id = lhd.id
                LEFT JOIN trinh_do_hoc_van td ON ns.trinh_do_hoc_van_id = td.id
                ORDER BY ns.ma_nhan_vien";
        return $pdo->query($sql)->fetchAll();
    }
    
    // Hàm lấy data phòng ban
    function getPhongBanData($pdo) {
        $sql = "SELECT pb.*, ns.ho_ten as ten_truong_phong, ns.ma_nhan_vien as ma_truong_phong,
                       (SELECT COUNT(*) FROM nhan_su WHERE phong_ban_id = pb.id) as so_nhan_vien,
                       (SELECT COUNT(*) FROM nhan_su WHERE phong_ban_id = pb.id AND trang_thai_id = 1) as dang_lam_viec,
                       (SELECT COUNT(*) FROM nhan_su WHERE phong_ban_id = pb.id AND trang_thai_id = 2) as nghi_sinh,
                       (SELECT COUNT(*) FROM nhan_su WHERE phong_ban_id = pb.id AND trang_thai_id = 3) as da_nghi
                FROM phong_ban pb
                LEFT JOIN nhan_su ns ON pb.truong_phong = ns.id
                ORDER BY pb.ma_phong_ban";
        return $pdo->query($sql)->fetchAll();
    }
    
    // Hàm tạo Excel XML
    function createExcelXML($sheets) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <Styles>
        <Style ss:ID="Header">
        <Alignment ss:Horizontal="Center"ss:Vertical="Center"/><Font ss:Bold="1"ss:Size="11"ss:Color="#FFFFFF"/><Interior ss:Color="#667EEA"ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="Normal">
        <Alignment ss:Vertical="Center"/>
        </Style>
        <Style ss:ID="Number">
        <Alignment ss:Horizontal="Right"/><NumberFormat ss:Format="#,##0"/>
        </Style>
    </Styles>';

    foreach ($sheets as $sheetName => $sheetData) {
    $xml .= '<Worksheet ss:Name="' . htmlspecialchars($sheetName) . '">
        <Table>';

            // Header row
            if (!empty($sheetData['headers'])) {
            $xml .= '<Row ss:Height="25">';
                foreach ($sheetData['headers'] as $header) {
                $xml .= '<Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($header) . '</Data>
                </Cell>';
                }
                $xml .= '</Row>';
            }

            // Data rows
            foreach ($sheetData['data'] as $row) {
            $xml .= '<Row>';
                foreach ($row as $cell) {
                $type = is_numeric($cell) ? 'Number' : 'String';
                $style = is_numeric($cell) ? 'Number' : 'Normal';
                $xml .= '<Cell ss:StyleID="' . $style . '"><Data ss:Type="' . $type . '">' . htmlspecialchars($cell ??
                        '') . '</Data></Cell>';
                }
                $xml .= '</Row>';
            }

            $xml .= '</Table>
    </Worksheet>';
    }

    $xml .= '
</Workbook>';
return $xml;
}

if ($type == 'nhansu') {
// Xuất nhân sự Excel
$nhan_su_list = getNhanSuData($pdo);
$data = [];
$stt = 1;
foreach ($nhan_su_list as $ns) {
$data[] = [
$stt++,
$ns['ma_nhan_vien'],
$ns['ho_ten'],
$ns['ngay_sinh'] ? date('d/m/Y', strtotime($ns['ngay_sinh'])) : '',
$ns['gioi_tinh'],
$ns['so_dien_thoai'] ?? '',
$ns['email'] ?? '',
$ns['dia_chi'] ?? '',
$ns['ten_chuc_vu'] ?? '', $ns['ten_phong_ban'] ?? '',
$ns['ngay_vao_lam'] ? date('d/m/Y', strtotime($ns['ngay_vao_lam'])) : '',
$ns['ngay_nghi_viec'] ? date('d/m/Y', strtotime($ns['ngay_nghi_viec'])) : '',
$ns['loai_hop_dong'] ?? '',
$ns['muc_luong'] ?? 0,
$ns['ten_trinh_do'] ?? '',
$ns['ten_trang_thai'] ?? ''
];
}

$sheets = [
'Danh sách nhân sự' => [
'headers' => ['STT', 'Mã NV', 'Họ tên', 'Ngày sinh', 'Giới tính', 'SĐT', 'Email',
'Địa chỉ', 'Chức vụ', 'Phòng ban', 'Ngày vào làm', 'Ngày nghỉ',
'Loại HĐ', 'Mức lương', 'Trình độ', 'Trạng thái'],
'data' => $data
]
];

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Danh_sach_nhan_su_' . date('Ymd_His') . '.xlsx"');
echo createExcelXML($sheets);

} elseif ($type == 'phongban') {
// Xuất phòng ban Excel
$phong_ban_list = getPhongBanData($pdo);
$data = [];
$stt = 1;
foreach ($phong_ban_list as $pb) {
$data[] = [
$stt++, $pb['ma_phong_ban'], $pb['ten_phong_ban'],
$pb['ten_truong_phong'] ?? 'Chưa có', $pb['ma_truong_phong'] ?? '',
$pb['so_nhan_vien'], $pb['dang_lam_viec'], $pb['nghi_sinh'], $pb['da_nghi'],
$pb['mo_ta'] ?? '', date('d/m/Y H:i', strtotime($pb['created_at']))
];
}

$sheets = [
'Danh sách phòng ban' => [
'headers' => ['STT', 'Mã PB', 'Tên phòng ban', 'Trưởng phòng', 'Mã TP',
'Tổng NV', 'Đang làm', 'Nghỉ sinh', 'Đã nghỉ', 'Mô tả', 'Ngày tạo'],
'data' => $data
]
];

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Danh_sach_phong_ban_' . date('Ymd_His') . '.xlsx"');
echo createExcelXML($sheets);

} elseif ($type == 'all') {
// Xuất tất cả vào 1 file Excel
$nhan_su_list = getNhanSuData($pdo);
$phong_ban_list = getPhongBanData($pdo);

// Data nhân sự
$ns_data = [];
$stt = 1;
foreach ($nhan_su_list as $ns) {
$ns_data[] = [
$stt++, $ns['ma_nhan_vien'], $ns['ho_ten'],
$ns['ngay_sinh'] ? date('d/m/Y', strtotime($ns['ngay_sinh'])) : '',
$ns['gioi_tinh'], $ns['so_dien_thoai'] ?? '', $ns['email'] ?? '', $ns['dia_chi'] ?? '',
$ns['ten_chuc_vu'] ?? '', $ns['ten_phong_ban'] ?? '',
$ns['ngay_vao_lam'] ? date('d/m/Y', strtotime($ns['ngay_vao_lam'])) : '',
$ns['ngay_nghi_viec'] ? date('d/m/Y', strtotime($ns['ngay_nghi_viec'])) : '',
$ns['loai_hop_dong'] ?? '', $ns['muc_luong'] ?? 0,
$ns['ten_trinh_do'] ?? '', $ns['ten_trang_thai'] ?? ''
];
}

// Data phòng ban
$pb_data = [];
$stt = 1;
foreach ($phong_ban_list as $pb) {
$pb_data[] = [
$stt++, $pb['ma_phong_ban'], $pb['ten_phong_ban'],
$pb['ten_truong_phong'] ?? 'Chưa có', $pb['ma_truong_phong'] ?? '',
$pb['so_nhan_vien'], $pb['dang_lam_viec'], $pb['nghi_sinh'], $pb['da_nghi'],
$pb['mo_ta'] ?? '', date('d/m/Y H:i', strtotime($pb['created_at']))
];
}

// Data thống kê
$tong_ns = $pdo->query("SELECT COUNT(*) FROM nhan_su")->fetchColumn();
$tong_pb = $pdo->query("SELECT COUNT(*) FROM phong_ban")->fetchColumn();
$dang_lam = $pdo->query("SELECT COUNT(*) FROM nhan_su WHERE trang_thai_id = 1")->fetchColumn();
$nghi_sinh = $pdo->query("SELECT COUNT(*) FROM nhan_su WHERE trang_thai_id = 2")->fetchColumn();
$da_nghi = $pdo->query("SELECT COUNT(*) FROM nhan_su WHERE trang_thai_id = 3")->fetchColumn();

$tk_data = [
['Ngày xuất báo cáo', date('d/m/Y H:i:s')],
['', ''],
['THỐNG KÊ CHUNG', ''],
['Tổng số nhân sự', $tong_ns],
['Tổng số phòng ban', $tong_pb],
['Đang làm việc', $dang_lam],
['Đang nghỉ sinh', $nghi_sinh],
['Đã nghỉ việc', $da_nghi],
['', ''],
['THỐNG KÊ THEO GIỚI TÍNH', '']
];

$gioi_tinh = $pdo->query("SELECT gioi_tinh, COUNT(*) as sl FROM nhan_su GROUP BY gioi_tinh")->fetchAll();
foreach ($gioi_tinh as $gt) {
$tk_data[] = [$gt['gioi_tinh'], $gt['sl']];
}

$tk_data[] = ['', ''];
$tk_data[] = ['THỐNG KÊ THEO CHỨC VỤ', ''];

$cv_stats = $pdo->query("SELECT cv.ten_chuc_vu, COUNT(ns.id) as sl FROM chuc_vu cv
LEFT JOIN nhan_su ns ON cv.id = ns.chuc_vu_id
GROUP BY cv.id, cv.ten_chuc_vu")->fetchAll();
foreach ($cv_stats as $cv) {
$tk_data[] = [$cv['ten_chuc_vu'], $cv['sl']];
}

$sheets = [
'Danh sách nhân sự' => [
'headers' => ['STT', 'Mã NV', 'Họ tên', 'Ngày sinh', 'Giới tính', 'SĐT', 'Email',
'Địa chỉ', 'Chức vụ', 'Phòng ban', 'Ngày vào làm', 'Ngày nghỉ',
'Loại HĐ', 'Mức lương', 'Trình độ', 'Trạng thái'],
'data' => $ns_data
],
'Danh sách phòng ban' => [
'headers' => ['STT', 'Mã PB', 'Tên phòng ban', 'Trưởng phòng', 'Mã TP',
'Tổng NV', 'Đang làm', 'Nghỉ sinh', 'Đã nghỉ', 'Mô tả', 'Ngày tạo'],
'data' => $pb_data
],
'Thống kê tổng quan' => [
'headers' => ['Chỉ tiêu', 'Giá trị'],
'data' => $tk_data
]
];

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Bao_cao_tong_hop_' . date('Ymd_His') . '.xlsx"');
echo createExcelXML($sheets);

} elseif ($type == 'template') {
// Xuất file mẫu import
$sheets = [
'Mẫu nhập nhân sự' => [
'headers' => ['ma_nhan_vien', 'ho_ten', 'ngay_sinh', 'gioi_tinh', 'so_dien_thoai',
'email', 'dia_chi', 'chuc_vu', 'phong_ban', 'ngay_vao_lam',
'ngay_nghi_viec', 'loai_hop_dong', 'muc_luong', 'trinh_do_hoc_van', 'trang_thai_nhan_vien'],
'data' => [
['NV001', 'Nguyễn Văn A', '1990-01-15', 'Nam', '0901234567',
'nguyenvana@email.com', '123 Đường ABC, Quận 1, TP.HCM', 'Nhân viên', 'Phòng Kỹ thuật', '2024-01-01',
'', 'Hợp đồng 1 năm', '15000000', 'Đại học', 'Đang làm việc'],
['NV002', 'Trần Thị B', '1992-05-20', 'Nữ', '0912345678',
'tranthib@email.com', '456 Đường XYZ, Quận 2, TP.HCM', 'Trưởng phòng', 'Phòng Nhân sự', '2023-06-15',
'', 'Hợp đồng không thời hạn', '25000000', 'Cao học', 'Đã nghỉ việc']
]
],
'Hướng dẫn' => [
'headers' => ['Cột', 'Mô tả', 'Bắt buộc', 'Định dạng'],
'data' => [
['ma_nhan_vien', 'Mã nhân viên (duy nhất)', 'Có', 'VD: NV001'],
['ho_ten', 'Họ và tên đầy đủ', 'Có', 'VD: Nguyễn Văn A'],
['ngay_sinh', 'Ngày sinh', 'Không', 'YYYY-MM-DD (VD: 1990-01-15)'],
['gioi_tinh', 'Giới tính', 'Không', 'Nam / Nữ / Khác'],
['so_dien_thoai', 'Số điện thoại', 'Không', 'VD: 0901234567'],
['email', 'Địa chỉ email', 'Không', 'VD: email@domain.com'],
['dia_chi', 'Địa chỉ thường trú', 'Không', 'Địa chỉ đầy đủ'],
['chuc_vu', 'Tên chức vụ', 'Không', 'Phải tồn tại trong hệ thống'],
['phong_ban', 'Tên phòng ban', 'Không', 'Phải tồn tại trong hệ thống'],
['ngay_vao_lam', 'Ngày vào làm', 'Không', 'YYYY-MM-DD'],
['ngay_nghi_viec', 'Ngày nghỉ việc', 'Không', 'YYYY-MM-DD (để trống nếu còn làm)'],
['loai_hop_dong', 'Loại hợp đồng', 'Không', 'Phải tồn tại trong hệ thống'],
['muc_luong', 'Mức lương (VNĐ)', 'Không', 'Số nguyên (VD: 15000000)'],
['trinh_do_hoc_van', 'Trình độ học vấn', 'Không', 'Phải tồn tại trong hệ thống'],
['trang_thai_nhan_vien', 'Trạng thái nhân viên', 'Không', 'Phải tồn tại trong hệ thống']
]
]
];

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Mau_import_nhan_su.xlsx"');
echo createExcelXML($sheets);
}

} catch (Exception $e) {
die("Lỗi: " . $e->getMessage());
}
?>