<?php
require_once '../config.php';
checkLogin();

$response = ['success' => false, 'message' => '', 'details' => []];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['import_file'])) {
    try {
        $file = $_FILES['import_file'];
        
        if ($file['error'] !== 0) {
            throw new Exception('Lỗi khi upload file!');
        }
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, ['csv', 'xlsx', 'xls'])) {
            throw new Exception('Chỉ hỗ trợ file CSV hoặc Excel (.xlsx, .xls)!');
        }
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        $data_rows = [];
        
        if ($file_ext == 'csv') {
            // Đọc CSV
            $handle = fopen($file['tmp_name'], 'r');
            fgetcsv($handle); // Skip header
            while (($row = fgetcsv($handle)) !== false) {
                $data_rows[] = $row;
            }
            fclose($handle);
            
        } else {
            // Đọc Excel XML (SpreadsheetML)
            $content = file_get_contents($file['tmp_name']);
            
            // Kiểm tra xem có phải Excel XML không
            if (strpos($content, 'urn:schemas-microsoft-com:office:spreadsheet') !== false) {
                // Parse Excel XML
                $xml = simplexml_load_string($content);
                $namespaces = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
                
                $worksheets = $xml->xpath('//ss:Worksheet');
                if (!empty($worksheets)) {
                    $rows = $worksheets[0]->xpath('.//ss:Row');
                    $first_row = true;
                    foreach ($rows as $row) {
                        if ($first_row) {
                            $first_row = false;
                            continue; // Skip header
                        }
                        $cells = $row->xpath('.//ss:Cell/ss:Data');
                        $row_data = [];
                        foreach ($cells as $cell) {
                            $row_data[] = (string)$cell;
                        }
                        if (!empty(array_filter($row_data))) {
                            $data_rows[] = $row_data;
                        }
                    }
                }
            } else {
                // Thử đọc như ZIP (xlsx thật)
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) === TRUE) {
                    // Đọc sheet1.xml
                    $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
                    $shared_strings_xml = $zip->getFromName('xl/sharedStrings.xml');
                    $zip->close();
                    
                    if ($sheet_xml) {
                        // Parse shared strings
                        $shared_strings = [];
                        if ($shared_strings_xml) {
                            $ss_xml = simplexml_load_string($shared_strings_xml);
                            foreach ($ss_xml->si as $si) {
                                $shared_strings[] = (string)$si->t;
                            }
                        }
                        
                        // Parse sheet
                        $sheet = simplexml_load_string($sheet_xml);
                        $first_row = true;
                        foreach ($sheet->sheetData->row as $row) {
                            if ($first_row) {
                                $first_row = false;
                                continue;
                            }
                            $row_data = [];
                            foreach ($row->c as $cell) {
                                $value = '';
                                if (isset($cell->v)) {
                                    $value = (string)$cell->v;
                                    // Nếu type là 's' thì lấy từ shared strings
                                    if (isset($cell['t']) && (string)$cell['t'] == 's') {
                                        $value = $shared_strings[(int)$value] ?? '';
                                    }
                                }
                                $row_data[] = $value;
                            }
                            if (!empty(array_filter($row_data))) {
                                $data_rows[] = $row_data;
                            }
                        }
                    }
                } else {
                    throw new Exception('Không thể đọc file Excel. Vui lòng sử dụng file mẫu từ hệ thống.');
                }
            }
        }
        
        if (empty($data_rows)) {
            throw new Exception('File không có dữ liệu hoặc định dạng không đúng!');
        }
        
        $pdo->beginTransaction();
        
        $row_number = 1;
        foreach ($data_rows as $data) {
            $row_number++;
            
            try {
                // Kiểm tra và bỏ qua cột STT nếu có
                // Nếu cột đầu tiên là số (STT) và cột thứ 2 giống format mã NV thì bỏ qua cột đầu
                $data = detectAndSkipSTT($data);
                
                // Đảm bảo có đủ cột
                while (count($data) < 14) {
                    $data[] = '';
                }
                
                if (empty(trim($data[0])) || empty(trim($data[1]))) {
                    $errors[] = "Dòng $row_number: Thiếu mã NV hoặc họ tên";
                    $error_count++;
                    continue;
                }
                
                // Kiểm tra mã NV đã tồn tại
                $stmt = $pdo->prepare("SELECT id FROM nhan_su WHERE ma_nhan_vien = ?");
                $stmt->execute([trim($data[0])]);
                if ($stmt->fetch()) {
                    $errors[] = "Dòng $row_number: Mã NV '" . trim($data[0]) . "' đã tồn tại";
                    $error_count++;
                    continue;
                }
                
                // Lấy ID từ tên
                $chuc_vu_id = null;
                if (!empty(trim($data[7]))) {
                    $stmt = $pdo->prepare("SELECT id FROM chuc_vu WHERE ten_chuc_vu = ?");
                    $stmt->execute([trim($data[7])]);
                    $chuc_vu_id = $stmt->fetchColumn() ?: null;
                }
                
                $phong_ban_id = null;
                if (!empty(trim($data[8]))) {
                    $stmt = $pdo->prepare("SELECT id FROM phong_ban WHERE ten_phong_ban = ?");
                    $stmt->execute([trim($data[8])]);
                    $phong_ban_id = $stmt->fetchColumn() ?: null;
                }
                
                $loai_hop_dong_id = null;
                if (!empty(trim($data[11]))) {
                    $stmt = $pdo->prepare("SELECT id FROM loai_hop_dong WHERE ten_loai = ?");
                    $stmt->execute([trim($data[11])]);
                    $loai_hop_dong_id = $stmt->fetchColumn() ?: null;
                }
                
                $trinh_do_id = null;
                if (!empty(trim($data[13]))) {
                    $stmt = $pdo->prepare("SELECT id FROM trinh_do_hoc_van WHERE ten_trinh_do = ?");
                    $stmt->execute([trim($data[13])]);
                    $trinh_do_id = $stmt->fetchColumn() ?: null;
                }
                
                // Xử lý ngày tháng (hỗ trợ nhiều định dạng)
                $ngay_sinh = parseDate($data[2] ?? '');
                $ngay_vao_lam = parseDate($data[9] ?? '');
                $ngay_nghi_viec = parseDate($data[10] ?? '');
                
                // Insert
                $sql = "INSERT INTO nhan_su (ma_nhan_vien, ho_ten, ngay_sinh, gioi_tinh, so_dien_thoai, 
                        email, dia_chi, anh_dai_dien, chuc_vu_id, phong_ban_id, ngay_vao_lam, 
                        ngay_nghi_viec, loai_hop_dong_id, muc_luong, trinh_do_hoc_van_id, trang_thai_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, 1)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    trim($data[0]),                           // ma_nhan_vien
                    trim($data[1]),                           // ho_ten
                    $ngay_sinh,                               // ngay_sinh
                    !empty(trim($data[3])) ? trim($data[3]) : 'Nam',  // gioi_tinh
                    trim($data[4] ?? ''),                     // so_dien_thoai
                    trim($data[5] ?? ''),                     // email
                    trim($data[6] ?? ''),                     // dia_chi
                    $chuc_vu_id,                              // chuc_vu_id
                    $phong_ban_id,                            // phong_ban_id
                    $ngay_vao_lam,                            // ngay_vao_lam
                    $ngay_nghi_viec,                          // ngay_nghi_viec
                    $loai_hop_dong_id,                        // loai_hop_dong_id
                    !empty($data[12]) ? (int)preg_replace('/[^0-9]/', '', $data[12]) : null,  // muc_luong
                    $trinh_do_id                              // trinh_do_hoc_van_id
                ]);
                
                $success_count++;
                
            } catch (PDOException $e) {
                $errors[] = "Dòng $row_number: " . $e->getMessage();
                $error_count++;
            }
        }
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = "Import thành công $success_count nhân sự!" . ($error_count > 0 ? " Có $error_count lỗi." : "");
        $response['details'] = [
            'success' => $success_count,
            'error' => $error_count,
            'errors' => array_slice($errors, 0, 10)
        ];
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
}

// Hàm parse ngày tháng linh hoạt
function parseDate($date_str) {
    if (empty(trim($date_str))) return null;
    
    $date_str = trim($date_str);
    
    // Thử các định dạng
    $formats = [
        'Y-m-d',      // 2024-01-15
        'd/m/Y',      // 15/01/2024
        'd-m-Y',      // 15-01-2024
        'm/d/Y',      // 01/15/2024
        'Y/m/d',      // 2024/01/15
    ];
    
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $date_str);
        if ($dt !== false) {
            return $dt->format('Y-m-d');
        }
    }
    
    // Thử với strtotime
    $timestamp = strtotime($date_str);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return null;
}

// Hàm phát hiện và bỏ qua cột STT
function detectAndSkipSTT($data) {
    if (count($data) < 2) return $data;
    
    $first_col = trim($data[0] ?? '');
    $second_col = trim($data[1] ?? '');
    
    // Kiểm tra cột đầu tiên có phải là STT không
    // STT thường là: số nguyên (1, 2, 3...) hoặc rỗng
    $is_stt = false;
    
    // Nếu cột đầu là số nguyên dương nhỏ (1-9999) và cột 2 giống mã NV
    if (is_numeric($first_col) && (int)$first_col > 0 && (int)$first_col < 10000) {
        // Kiểm tra cột thứ 2 có giống format mã NV không (có chữ cái)
        if (preg_match('/[a-zA-Z]/', $second_col)) {
            $is_stt = true;
        }
        // Hoặc cột thứ 3 là tên người (có dấu cách, chữ cái)
        if (isset($data[2]) && preg_match('/^[\p{L}\s]+$/u', trim($data[2]))) {
            $is_stt = true;
        }
    }
    
    // Nếu phát hiện có cột STT, bỏ cột đầu tiên
    if ($is_stt) {
        array_shift($data);
    }
    
    return $data;
}

header('Content-Type: application/json');
echo json_encode($response);
?>