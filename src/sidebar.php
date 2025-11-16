<?php 
    $base = "/quanlynhansu_web/src/";
    $current = $_SERVER['PHP_SELF'];
?>

<div class="sidebar">
    <div class="logo">
        <div class="logo-icon">👥</div>
        <h2>QUẢN LÝ HỒ SƠ NHÂN SỰ</h2>
    </div>

    <nav class="nav-menu">

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

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">👤</div>
            <div class="user-details">
                <div class="user-name"><?php echo $_SESSION['username']; ?></div>
                <div class="user-role"><?php echo $_SESSION['role']; ?></div>
            </div>
        </div>
    </div>
</div>