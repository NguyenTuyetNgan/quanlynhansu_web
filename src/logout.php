<?php
/**
 * File logout.php - Xử lý đăng xuất
 * 
 * Giải thích:
 * 1. session_start() - Khởi động session
 * 2. session_destroy() - Hủy toàn bộ session
 * 3. header() - Chuyển hướng về trang login
 */

session_start();
session_destroy();
header('Location: login.php');
exit();
?>