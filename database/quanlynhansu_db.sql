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

INSERT INTO phong_ban (ma_phong_ban, ten_phong_ban, truong_phong, mo_ta) VALUES
('PB001', 'Phòng Kinh doanh', 1, 'Phòng phụ trách kinh doanh và chăm sóc khách hàng'),
('PB002', 'Phòng Nhân sự', 2, 'Quản lý nhân sự, tuyển dụng và đào tạo'),
('PB003', 'Phòng IT', 3, 'Phát triển hệ thống, bảo trì máy tính và server'),
('PB004', 'Phòng Marketing', NULL, 'Quảng bá sản phẩm và chiến dịch marketing'),
('PB005', 'Phòng Hành chính', NULL, 'Quản lý văn phòng và hành chính chung');

INSERT INTO nhan_su (ma_nhan_vien, ho_ten, ngay_sinh, gioi_tinh, so_dien_thoai, email, dia_chi, anh_dai_dien,
chuc_vu_id, phong_ban_id, ngay_vao_lam, ngay_nghi_viec, loai_hop_dong_id, muc_luong, trinh_do_hoc_van_id, trang_thai_id) VALUES
('NV001', 'Nguyễn Văn A', '1985-03-12', 'Nam', '0912345678', 'a.nguyen@gmail.com', 'Hà Nội', NULL, 1, 1, '2010-05-01', NULL, 5, 20000000, 2, 1),
('NV002', 'Trần Thị B', '1990-07-22', 'Nữ', '0987654321', 'b.tran@gmail.com', 'Hà Nội', NULL, 2, 2, '2015-08-15', NULL, 1, 15000000, 2, 1),
('NV003', 'Lê Văn C', '1992-11-05', 'Nam', '0911223344', 'c.le@gmail.com', 'Hồ Chí Minh', NULL, 3, 3, '2018-01-10', NULL, 2, 18000000, 1, 1),
('NV004', 'Phạm Thị D', '1995-09-12', 'Nữ', '0909876543', 'd.pham@gmail.com', 'Đà Nẵng', NULL, 4, 4, '2020-03-01', NULL, 4, 12000000, 3, 1),
('NV005', 'Hoàng Văn E', '1988-12-30', 'Nam', '0976543210', 'e.hoang@gmail.com', 'Hải Phòng', NULL, 3, 3, '2012-11-20', NULL, 5, 22000000, 2, 1);

INSERT INTO tai_lieu_nhan_su (nhan_su_id, loai_tai_lieu_id, ten_tai_lieu, duong_dan_file, ghi_chu) VALUES
(1, 1, 'Ảnh đại diện Nguyễn Văn A', '/uploads/nhan_su/anh_a.jpg', NULL),
(2, 2, 'Hợp đồng Trần Thị B', '/uploads/nhan_su/hd_b.pdf', NULL),
(3, 3, 'CV Lê Văn C', '/uploads/nhan_su/cv_c.docx', NULL),
(4, 4, 'Video đào tạo Phạm Thị D', '/uploads/nhan_su/video_d.mp4', NULL),
(5, 1, 'Ảnh đại diện Hoàng Văn E', '/uploads/nhan_su/anh_e.jpg', 'Ảnh chất lượng HD');

INSERT INTO users (username, password, email, role, nhan_su_id) VALUES
('a_admin', '123', 'admin@gmail.com', 'admin', 1),
('b_user', '123', 'b_user@gmail.com', 'user', 2),
('c_user', '123', 'c_user@gmail.com', 'user', 3);



-- Tạo user admin mặc định (password: admin123)
INSERT INTO users (username, password, email, role) VALUES 
('admin', '123', 'admin@gmail.com', 'admin');


/*-- Cập nhật password admin thành hash (nếu đang dùng plaintext)
-- Password: admin123
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';]*/

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