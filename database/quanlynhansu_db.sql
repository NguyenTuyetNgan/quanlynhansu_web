-- Tạo database
CREATE DATABASE IF NOT EXISTS quanlynhansu_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quanlynhansu_db;

-- Bảng phòng ban
CREATE TABLE phong_ban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_phong_ban VARCHAR(50) UNIQUE NOT NULL,
    ten_phong_ban VARCHAR(100) NOT NULL,
    truong_phong INT NULL,
    mo_ta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng chức vụ
CREATE TABLE chuc_vu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_chuc_vu VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng loại hợp đồng
CREATE TABLE loai_hop_dong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_loai VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng trình độ học vấn
CREATE TABLE trinh_do_hoc_van (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_trinh_do VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng trạng thái nhân viên
CREATE TABLE trang_thai_nhan_vien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_trang_thai VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng loại tài liệu
CREATE TABLE loai_tai_lieu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_loai VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng nhân sự
CREATE TABLE nhan_su (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_nhan_vien VARCHAR(50) UNIQUE NOT NULL,
    ho_ten VARCHAR(100) NOT NULL,
    ngay_sinh DATE,
    gioi_tinh ENUM('Nam', 'Nữ', 'Khác') DEFAULT 'Nam',
    so_dien_thoai VARCHAR(15),
    email VARCHAR(100),
    dia_chi TEXT,
    anh_dai_dien VARCHAR(255),
    
    -- Thông tin công việc
    chuc_vu_id INT,
    phong_ban_id INT,
    ngay_vao_lam DATE,
    ngay_nghi_viec DATE NULL,
    loai_hop_dong_id INT,
    muc_luong DECIMAL(15,2),
    trinh_do_hoc_van_id INT,
    trang_thai_id INT DEFAULT 1,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (chuc_vu_id) REFERENCES chuc_vu(id),
    FOREIGN KEY (phong_ban_id) REFERENCES phong_ban(id),
    FOREIGN KEY (loai_hop_dong_id) REFERENCES loai_hop_dong(id),
    FOREIGN KEY (trinh_do_hoc_van_id) REFERENCES trinh_do_hoc_van(id),
    FOREIGN KEY (trang_thai_id) REFERENCES trang_thai_nhan_vien(id)
);

-- Bảng tài liệu nhân sự
CREATE TABLE tai_lieu_nhan_su (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nhan_su_id INT NOT NULL,
    loai_tai_lieu_id INT NOT NULL,
    ten_tai_lieu VARCHAR(255) NOT NULL,
    duong_dan_file VARCHAR(255) NOT NULL,
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (nhan_su_id) REFERENCES nhan_su(id) ON DELETE CASCADE,
    FOREIGN KEY (loai_tai_lieu_id) REFERENCES loai_tai_lieu(id)
);

-- Bảng users (admin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Thêm cột nhan_su_id vào bảng users để liên kết với nhân viên
ALTER TABLE users ADD COLUMN nhan_su_id INT NULL AFTER role;

-- Thêm foreign key (tùy chọn)
ALTER TABLE users ADD CONSTRAINT fk_users_nhan_su 
FOREIGN KEY (nhan_su_id) REFERENCES nhan_su(id) ON DELETE SET NULL;

-- Cập nhật password admin thành hash (nếu đang dùng plaintext)
-- Password: admin123
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';]

-- Tạo bảng lưu cài đặt hệ thống
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Thêm mật khẩu truy cập mặc định
INSERT INTO system_settings (setting_key, setting_value) VALUES ('account_page_password', '123456');

-- Insert dữ liệu mẫu
INSERT INTO chuc_vu (ten_chuc_vu) VALUES 
('Giám đốc'), ('Trưởng phòng'), ('Nhân viên'), ('Thực tập sinh');

INSERT INTO loai_hop_dong (ten_loai) VALUES 
('Hợp đồng 1 năm'), ('Hợp đồng 2 năm'), ('Công tác viên'), ('Hợp đồng thử việc'), ('Hợp đồng không thời hạn');

INSERT INTO trinh_do_hoc_van (ten_trinh_do) VALUES 
('Cao học'), ('Đại học'), ('Cao đẳng'), ('Trung cấp');

INSERT INTO trang_thai_nhan_vien (ten_trang_thai) VALUES 
('Đang làm việc'), ('Đang nghỉ sinh'), ('Đã nghỉ việc');

INSERT INTO loai_tai_lieu (ten_loai) VALUES 
('Tài liệu hình ảnh'), ('Tài liệu pdf'), ('Tài liệu văn bản'), ('Tài liệu video');

-- Tạo user admin mặc định (password: admin123)
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@gmail.com', 'admin');