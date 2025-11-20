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
        
        if ($file_ext == 'csv') {
            // Xử lý CSV
            $handle = fopen($file['tmp_name'], 'r');
            
            // Skip header row
            fgetcsv($handle);
            
            $pdo->beginTransaction();
            
            $row_number = 1;
            while (($data = fgetcsv($handle)) !== false) {
                $row_number++;
                
                try {
                    if (empty($data[0]) || empty($data[1])) {
                        $errors[] = "Dòng $row_number: Thiếu mã NV hoặc họ tên";
                        $error_count++;
                        continue;
                    }
                    
                    // Lấy ID từ tên
                    $chuc_vu_id = null;
                    if (!empty($data[8])) {
                        $stmt = $pdo->prepare("SELECT id FROM chuc_vu WHERE ten_chuc_vu = ?");
                        $stmt->execute([trim($data[8])]);
                        $chuc_vu_id = $stmt->fetchColumn();
                    }
                    
                    $phong_ban_id = null;
                    if (!empty($data[9])) {
                        $stmt = $pdo->prepare("SELECT id FROM phong_ban WHERE ten_phong_ban = ?");
                        $stmt->execute([trim($data[9])]);
                        $phong_ban_id = $stmt->fetchColumn();
                    }
                    
                    $loai_hop_dong_id = null;
                    if (!empty($data[12])) {
                        $stmt = $pdo->prepare("SELECT id FROM loai_hop_dong WHERE ten_loai = ?");
                        $stmt->execute([trim($data[12])]);
                        $loai_hop_dong_id = $stmt->fetchColumn();
                    }
                    
                    $trinh_do_id = null;
                    if (!empty($data[14])) {
                        $stmt = $pdo->prepare("SELECT id FROM trinh_do_hoc_van WHERE ten_trinh_do = ?");
                        $stmt->execute([trim($data[14])]);
                        $trinh_do_id = $stmt->fetchColumn();
                    }
                    
                    // Insert
                    $sql = "INSERT INTO nhan_su (ma_nhan_vien, ho_ten, ngay_sinh, gioi_tinh, so_dien_thoai, email, dia_chi, anh_dai_dien, chuc_vu_id, phong_ban_id, ngay_vao_lam, ngay_nghi_viec, loai_hop_dong_id, muc_luong, trinh_do_hoc_van_id, trang_thai_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, 1)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        trim($data[0]),
                        trim($data[1]),
                        !empty($data[2]) ? $data[2] : null,
                        !empty($data[3]) ? trim($data[3]) : 'Nam',
                        trim($data[4] ?? ''),
                        trim($data[5] ?? ''),
                        trim($data[6] ?? ''),
                        $chuc_vu_id,
                        $phong_ban_id,
                        !empty($data[10]) ? $data[10] : null,
                        !empty($data[11]) ? $data[11] : null,
                        $loai_hop_dong_id,
                        !empty($data[13]) ? $data[13] : null,
                        $trinh_do_id
                    ]);
                    
                    $success_count++;
                    
                } catch (PDOException $e) {
                    $errors[] = "Dòng $row_number: " . $e->getMessage();
                    $error_count++;
                }
            }
            
            fclose($handle);
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = "Import thành công $success_count nhân sự!" . ($error_count > 0 ? " Có $error_count lỗi." : "");
            $response['details'] = [
                'success' => $success_count,
                'error' => $error_count,
                'errors' => array_slice($errors, 0, 5)
            ];
            
        } else {
            throw new Exception('Hiện tại chỉ hỗ trợ file CSV. Vui lòng chuyển Excel sang CSV trước.');
        }
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>