<?php
require_once '../config.php';
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $nhan_su_id = $_POST['nhan_su_id'] ?? 0;
        $loai_tai_lieu_id = $_POST['loai_tai_lieu_id'] ?? 0;
        $ten_tai_lieu = clean($_POST['ten_tai_lieu'] ?? '');
        $ghi_chu = clean($_POST['ghi_chu'] ?? '');
        
        if (!$nhan_su_id || !$loai_tai_lieu_id || !$ten_tai_lieu) {
            throw new Exception('Thiếu thông tin bắt buộc!');
        }
        
        // Upload file
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
            throw new Exception('Vui lòng chọn file!');
        }
        
        $upload_result = uploadFile($_FILES['file'], '../../assets/uploads/documents/');
        
        if (!$upload_result['success']) {
            throw new Exception($upload_result['message']);
        }
        
        // Lưu vào database
        $stmt = $pdo->prepare("
            INSERT INTO tai_lieu_nhan_su (nhan_su_id, loai_tai_lieu_id, ten_tai_lieu, duong_dan_file, ghi_chu)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $nhan_su_id,
            $loai_tai_lieu_id,
            $ten_tai_lieu,
            $upload_result['path'],
            $ghi_chu
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Upload tài liệu thành công!',
            'file_path' => $upload_result['path']
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete'])) {
    // Xóa tài liệu
    try {
        $id = $_GET['delete'];
        
        // Lấy thông tin file
        $stmt = $pdo->prepare("SELECT duong_dan_file FROM tai_lieu_nhan_su WHERE id = ?");
        $stmt->execute([$id]);
        $tai_lieu = $stmt->fetch();
        
        if ($tai_lieu) {
            // Xóa file vật lý
            if (file_exists($tai_lieu['duong_dan_file'])) {
                unlink($tai_lieu['duong_dan_file']);
            }
            
            // Xóa record
            $stmt = $pdo->prepare("DELETE FROM tai_lieu_nhan_su WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Xóa tài liệu thành công!'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>