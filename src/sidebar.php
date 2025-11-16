<?php 
    $base = "/quanlynhansu_web/src/";
    $current = $_SERVER['PHP_SELF'];
?>

<div class="top-navbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <div class="logo-icon">👥</div>
            <h2>QUẢN LÝ NHÂN SỰ</h2>
        </div>

        <nav class="navbar-menu">
            <!-- Tổng quan -->
            <a href="<?php echo $base; ?>dashboard/index.php"
                class="nav-item <?php echo (basename($current) == 'index.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span>
                <span>Tổng quan</span>
            </a>

            <!-- Nhân sự -->
            <a href="<?php echo $base; ?>nhan_su/nhan_su.php"
                class="nav-item <?php echo (strpos($current, 'nhan_su') !== false) ? 'active' : ''; ?>">
                <span class="nav-icon">👥</span>
                <span>Nhân sự</span>
            </a>

            <!-- Phòng ban -->
            <a href="<?php echo $base; ?>phong_ban/phong_ban.php"
                class="nav-item <?php echo (strpos($current, 'phong_ban') !== false) ? 'active' : ''; ?>">
                <span class="nav-icon">🏢</span>
                <span>Phòng ban</span>
            </a>

            <!-- Báo cáo -->
            <a href="<?php echo $base; ?>bao_cao.php"
                class="nav-item <?php echo (basename($current) == 'bao_cao.php') ? 'active' : ''; ?>">
                <span class="nav-icon">📋</span>
                <span>Báo cáo</span>
            </a>

            <!-- Cài đặt -->
            <a href="<?php echo $base; ?>cai_dat.php"
                class="nav-item <?php echo (basename($current) == 'cai_dat.php') ? 'active' : ''; ?>">
                <span class="nav-icon">⚙️</span>
                <span>Cài đặt</span>
            </a>
        </nav>

        <div class="navbar-user">
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['username']; ?></div>
                <div class="user-role"><?php echo $_SESSION['role']; ?></div>
            </div>
            <div class="user-avatar">👤</div>
            <a href="<?php echo $base; ?>logout.php" class="btn-logout-nav">Đăng xuất</a>
        </div>
    </div>
</div>

<style>
/* Top Navbar */
.top-navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 70px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.navbar-container {
    display: flex;
    align-items: center;
    gap: 30px;
    height: 100%;
    padding: 0 30px;
    max-width: 100%;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
    flex-shrink: 0;
}

.navbar-brand .logo-icon {
    font-size: 32px;
}

.navbar-brand h2 {
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.navbar-menu {
    display: flex;
    gap: 5px;
    flex: 1;
}

.navbar-menu .nav-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
}

.navbar-menu .nav-item:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
}

.navbar-menu .nav-item.active {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.navbar-menu .nav-icon {
    font-size: 18px;
}

.navbar-user {
    display: flex;
    align-items: center;
    gap: 15px;
    color: white;
    flex-shrink: 0;
}

.navbar-user .user-info {
    display: flex;
    flex-direction: column;
    text-align: right;
}

.navbar-user .user-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.navbar-user .user-name {
    font-weight: 600;
    font-size: 14px;
}

.navbar-user .user-role {
    font-size: 12px;
    opacity: 0.85;
}

.btn-logout-nav {
    padding: 8px 16px;
    background: rgba(255, 71, 87, 0.9);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s;
    white-space: nowrap;
}

.btn-logout-nav:hover {
    background: #ff4757;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
}

/* Adjust main content */
.main-content {
    margin-left: 0 !important;
    margin-top: 70px;
    padding: 30px;
    min-height: calc(100vh - 70px);
}

/* Hide old sidebar (nếu vẫn còn CSS cũ) */
.sidebar {
    display: none !important;
}

/* Responsive */
@media (max-width: 1024px) {
    .navbar-brand h2 {
        display: none;
    }

    .navbar-menu .nav-item span:not(.nav-icon) {
        display: none;
    }

    .navbar-menu .nav-item {
        padding: 10px 15px;
    }

    .navbar-user .user-info {
        display: none;
    }

    .btn-logout-nav {
        padding: 8px 12px;
        font-size: 12px;
    }
}

@media (max-width: 768px) {
    .navbar-container {
        padding: 0 15px;
        gap: 15px;
    }

    .navbar-menu {
        gap: 2px;
    }

    .navbar-menu .nav-item {
        padding: 8px 12px;
    }

    .navbar-menu .nav-icon {
        font-size: 20px;
    }

    .navbar-user .user-avatar {
        width: 35px;
        height: 35px;
        font-size: 18px;
    }
}
</style>