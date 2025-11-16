<?php
/**
 * File cấu hình kết nối database
 * Sử dụng PDO để bảo mật và hiện đại hơn mysqli
 */

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_NAME', 'quanlynhansu_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Cấu hình session
session_start();

// Múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * Tạo kết nối PDO
 * Try-catch để bắt lỗi kết nối
 */
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Bật chế độ báo lỗi
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Trả về mảng associative
        PDO::ATTR_EMULATE_PREPARES   => false,                   // Tắt emulation, bảo mật hơn
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

/**
 * Hàm kiểm tra đăng nhập
 * Kiểm tra xem user đã đăng nhập chưa
 */
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Hàm làm sạch dữ liệu đầu vào
 * Ngăn chặn XSS attack
 */
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Hàm upload file
 * Kiểm tra và upload file an toàn
 */
function uploadFile($file, $target_dir = "uploads/") {
    // Tạo thư mục nếu chưa tồn tại
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Lấy phần mở rộng file
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    // Tạo tên file unique
    $newFileName = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $newFileName;
    
    // Kiểm tra kích thước file (5MB)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'File quá lớn. Tối đa 5MB'];
    }
    
    // Cho phép một số định dạng file
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp4'];
    if (!in_array($imageFileType, $allowed)) {
        return ['success' => false, 'message' => 'Chỉ cho phép file: ' . implode(', ', $allowed)];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'path' => $target_file];
    } else {
        return ['success' => false, 'message' => 'Lỗi khi upload file'];
    }
}

/**
 * Hàm format tiền VND
 */
function formatVND($number) {
    return number_format($number, 0, ',', '.') . ' VNĐ';
}

/**
 * Hàm format ngày tháng
 */
function formatDate($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}
?>