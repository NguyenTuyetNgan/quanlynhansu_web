<?php
require_once 'config.php';
checkLogin();

// Lấy thống kê
try {
    // Lấy filter từ GET
    $filter_trang_thai = $_GET['trang_thai'] ?? '';
    $filter_date_from = $_GET['date_from'] ?? '';
    $filter_date_to = $_GET['date_to'] ?? '';
    
    // Build WHERE clause
    $where_clauses = [];
    $params = [];
    
    if ($filter_trang_thai !== '') {
        $where_clauses[] = "trang_thai_id = ?";
        $params[] = $filter_trang_thai;
    }
    
    if ($filter_date_from) {
        $where_clauses[] = "ngay_vao_lam >= ?";
        $params[] = $filter_date_from;
    }
    
    if ($filter_date_to) {
        $where_clauses[] = "ngay_vao_lam <= ?";
        $params[] = $filter_date_to;
    }
    
    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }
    
    // Tổng số nhân sự (có filter)
    $sql = "SELECT COUNT(*) FROM nhan_su $where_sql";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tong_nhan_su = $stmt->fetchColumn();
    
    // Tổng số phòng ban
    $stmt = $pdo->query("SELECT COUNT(*) FROM phong_ban");
    $tong_phong_ban = $stmt->fetchColumn();
    
    // Nhân sự mới (tháng này) - có filter
    $where_clauses_month = $where_clauses;
    $where_clauses_month[] = "MONTH(created_at) = MONTH(CURRENT_DATE())";
    $where_clauses_month[] = "YEAR(created_at) = YEAR(CURRENT_DATE())";
    $where_sql_month = "WHERE " . implode(" AND ", $where_clauses_month);
    
    $sql = "SELECT COUNT(*) FROM nhan_su $where_sql_month";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $nhan_su_moi = $stmt->fetchColumn();
    
    // Sinh nhật trong tháng - có filter
    $where_clauses_bday = $where_clauses;
    $where_clauses_bday[] = "MONTH(ngay_sinh) = MONTH(CURRENT_DATE())";
    $where_sql_bday = "WHERE " . implode(" AND ", $where_clauses_bday);
    
    $sql = "SELECT COUNT(*) FROM nhan_su $where_sql_bday";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sinh_nhat_thang = $stmt->fetchColumn();
    
    // Thống kê theo phòng ban - có filter
    $sql = "SELECT pb.ten_phong_ban, COUNT(ns.id) as so_luong
            FROM phong_ban pb
            LEFT JOIN nhan_su ns ON pb.id = ns.phong_ban_id";
    
    if (!empty($where_clauses)) {
        $sql .= " AND " . implode(" AND ", array_map(function($clause) {
            return "ns." . $clause;
        }, $where_clauses));
    }
    
    $sql .= " GROUP BY pb.id, pb.ten_phong_ban
              ORDER BY so_luong DESC
              LIMIT 3";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $phong_ban_stats = $stmt->fetchAll();
    
    // Thống kê giới tính
    $sql = "
        SELECT gioi_tinh, COUNT(*) as so_luong
        FROM nhan_su
        $where_sql
        GROUP BY gioi_tinh
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $gioi_tinh_stats = $stmt->fetchAll();

    
    // Thống kê chức vụ
    $sql = "
        SELECT cv.ten_chuc_vu, COUNT(ns.id) as so_luong
        FROM chuc_vu cv
        LEFT JOIN nhan_su ns ON cv.id = ns.chuc_vu_id
    ";
    if (!empty($where_clauses)) {
        $sql .= " AND " . implode(" AND ", array_map(fn($c) => "ns.$c", $where_clauses));
    }
    $sql .= " GROUP BY cv.id, cv.ten_chuc_vu LIMIT 4";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $chuc_vu_stats = $stmt->fetchAll();

    
    // Thống kê loại hợp đồng
    $sql = "
        SELECT lhd.ten_loai, COUNT(ns.id) as so_luong
        FROM loai_hop_dong lhd
        LEFT JOIN nhan_su ns ON lhd.id = ns.loai_hop_dong_id
    ";
    if (!empty($where_clauses)) {
        $sql .= " AND " . implode(" AND ", array_map(fn($c) => "ns.$c", $where_clauses));
    }
    $sql .= " GROUP BY lhd.id, lhd.ten_loai";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $hop_dong_stats = $stmt->fetchAll();


    // Lọc theo năm cho biểu đồ biến động nhân sự
    $year = $_GET['year'] ?? date('Y');

    // 12 tháng của năm
    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $months[] = $year . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
    }

    // Lấy dữ liệu theo năm
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(ngay_vao_lam, '%Y-%m') AS thang,
            COUNT(*) AS so_luong
        FROM nhan_su
        WHERE YEAR(ngay_vao_lam) = ?
        GROUP BY thang
    ");
    $stmt->execute([$year]);
    $data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Merge dữ liệu 12 tháng
    $nhan_su_theo_thang = [];
    foreach ($months as $m) {
        $nhan_su_theo_thang[] = [
            'thang' => $m,
            'so_luong' => isset($data[$m]) ? (int)$data[$m] : 0
        ];
    }
    
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tổng quan - Quản lý nhân sự</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>📊 Tổng quan hệ thống</h1>
        </div>

        <!-- Bộ lọc -->
        <div class="filter-section">
            <form method="GET" action="" id="filterForm"
                style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; overflow-x: auto;">
                <div class="filter-item" style="flex-shrink: 0;">
                    <input type="radio" name="trang_thai" id="all" value=""
                        <?php echo !isset($_GET['trang_thai']) || $_GET['trang_thai'] == '' ? 'checked' : ''; ?>
                        onchange="this.form.submit()">
                    <label for="all" class="filter-btn">📊 Tất cả</label>
                </div>
                <div class="filter-item" style="flex-shrink: 0;">
                    <input type="radio" name="trang_thai" id="working" value="1"
                        <?php echo isset($_GET['trang_thai']) && $_GET['trang_thai'] == '1' ? 'checked' : ''; ?>
                        onchange="this.form.submit()">
                    <label for="working" class="filter-btn">✅ Đang làm việc</label>
                </div>
                <div class="filter-item" style="flex-shrink: 0;">
                    <input type="radio" name="trang_thai" id="maternity" value="2"
                        <?php echo isset($_GET['trang_thai']) && $_GET['trang_thai'] == '2' ? 'checked' : ''; ?>
                        onchange="this.form.submit()">
                    <label for="maternity" class="filter-btn">🤰 Đang nghỉ sinh</label>
                </div>
                <div class="filter-item" style="flex-shrink: 0;">
                    <input type="radio" name="trang_thai" id="resigned" value="3"
                        <?php echo isset($_GET['trang_thai']) && $_GET['trang_thai'] == '3' ? 'checked' : ''; ?>
                        onchange="this.form.submit()">
                    <label for="resigned" class="filter-btn">❌ Đã nghỉ việc</label>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                    <input type="date" name="date_from" class="date-input"
                        value="<?php echo $_GET['date_from'] ?? ''; ?>" style="width: 140px;"
                        onchange="autoSubmitDate()">

                    <span style="font-size: 13px;">đến</span>

                    <input type="date" name="date_to" id="date_to" class="date-input"
                        value="<?php echo $_GET['date_to'] ?? ''; ?>" style="width: 140px;" onchange="autoSubmitDate()">


                </div>
                <a href="index.php" class="btn-reset" style="flex-shrink: 0;">↻ Đặt lại</a>
            </form>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="stats-grid">
            <a href="nhan_su/nhan_su.php?<?php
                $params = $_GET;
                $params['from_dashboard'] = '1';
                echo http_build_query($params); 
            ?>" class="stat-card stat-purple" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo $tong_nhan_su; ?></h3>
                    <p>Tổng nhân sự</p>
                </div>
            </a>

            <div class="stat-card stat-cyan">
                <div class="stat-icon">🏢</div>
                <div class="stat-content">
                    <h3><?php echo $tong_phong_ban; ?></h3>
                    <p>Phòng ban</p>
                </div>
            </div>

            <a href="nhan_su/nhan_su.php?<?php 
                $params = $_GET;
                $params['month_added'] = date('Y-m');
                $params['from_dashboard'] = '1';
                echo http_build_query($params); 
            ?>" class="stat-card stat-pink" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">👤</div>
                <div class="stat-content">
                    <h3><?php echo $nhan_su_moi; ?></h3>
                    <p>Nhân sự mới</p>
                </div>
            </a>

            <a href="nhan_su/nhan_su.php?<?php 
                $params = $_GET;
                $params['birthday_month'] = date('m');
                echo http_build_query($params); 
            ?>" class="stat-card stat-blue" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">🎂</div>
                <div class="stat-content">
                    <h3><?php echo $sinh_nhat_thang; ?></h3>
                    <p>Sinh nhật tháng <?php echo date('m'); ?></p>
                </div>
            </a>
        </div>

        <!-- Phân bố nhân sự theo phòng ban -->
        <div class="charts-row">
            <div class="chart-card">
                <h3>Phân bố nhân sự theo phòng ban</h3>
                <canvas id="phongBanChart"></canvas>
                <div class="chart-legend">
                    <?php foreach ($phong_ban_stats as $index => $pb): ?>
                    <div class="legend-item">
                        <span class="legend-number"><?php echo $index + 1; ?></span>
                        <span><?php echo $pb['ten_phong_ban']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <div class="chart-card">
                <div class="chart-header">
                    <h3>Biến động nhân sự</h3>
                    <select id="yearSelect" class="year-select"
                        onchange="window.location='index.php?year='+this.value;">
                        <?php
                        $selectedYear = $_GET['year'] ?? date('Y');  // năm được chọn
                        $maxYear = date('Y'); // năm hiện tại của hệ thống (2025)
                        for ($y = $maxYear; $y >= 2000; $y--) {
                            $selected = ($y == $selectedYear) ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>
                <canvas id="bienDongChart"></canvas>
            </div>

        </div>

        <!-- Thống kê chi tiết -->
        <div class="stats-detail-row">
            <div class="stats-detail-card">
                <h3>Thống kê giới tính</h3>
                <div class="pie-chart-container">
                    <canvas id="gioiTinhChart"></canvas>
                </div>
                <div class="stats-list">
                    <?php 
                    $total_gioi_tinh = array_sum(array_column($gioi_tinh_stats, 'so_luong'));
                    foreach ($gioi_tinh_stats as $gt): 
                    ?>
                    <div class="stats-item">
                        <span><?php echo $gt['gioi_tinh']; ?></span>
                        <span class="stats-value"><?php echo $gt['so_luong']; ?>
                            (<?php echo $total_gioi_tinh > 0 ? round($gt['so_luong']/$total_gioi_tinh*100, 1) : 0; ?>%)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="stats-detail-card">
                <h3>Thống kê chức vụ</h3>
                <div class="bar-chart-container">
                    <?php foreach ($chuc_vu_stats as $cv): ?>
                    <div class="bar-item">
                        <div class="bar-label"><?php echo $cv['ten_chuc_vu']; ?></div>
                        <div class="bar-progress">
                            <div class="bar-fill"
                                style="width: <?php echo $tong_nhan_su > 0 ? ($cv['so_luong']/$tong_nhan_su*100) : 0; ?>%">
                            </div>
                        </div>
                        <div class="bar-value"><?php echo $cv['so_luong']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="stats-detail-card">
                <h3>Thống kê loại hợp đồng</h3>
                <div class="bar-chart-container">
                    <?php foreach ($hop_dong_stats as $hd): ?>
                    <div class="bar-item">
                        <div class="bar-label"><?php echo $hd['ten_loai']; ?></div>
                        <div class="bar-progress">
                            <div class="bar-fill bar-fill-purple"
                                style="width: <?php echo $tong_nhan_su > 0 ? ($hd['so_luong']/$tong_nhan_su*100) : 0; ?>%">
                            </div>
                        </div>
                        <div class="bar-value"><?php echo $hd['so_luong']; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Biểu đồ phòng ban
    const pbCtx = document.getElementById('phongBanChart').getContext('2d');
    new Chart(pbCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($phong_ban_stats, 'ten_phong_ban')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($phong_ban_stats, 'so_luong')); ?>,
                backgroundColor: ['#667eea', '#764ba2', '#f093fb']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Biểu đồ giới tính
    const gtCtx = document.getElementById('gioiTinhChart').getContext('2d');
    new Chart(gtCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($gioi_tinh_stats, 'gioi_tinh')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($gioi_tinh_stats, 'so_luong')); ?>,
                backgroundColor: ['#667eea', '#f093fb', '#4facfe']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Biểu đồ biến động nhân sự
    const bdCtx = document.getElementById('bienDongChart').getContext('2d');
    new Chart(bdCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($nhan_su_theo_thang, 'thang')); ?>,
            datasets: [{
                label: 'Nhân viên vào',
                data: <?php echo json_encode(array_column($nhan_su_theo_thang, 'so_luong')); ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    function autoSubmitDate() {
        const form = document.getElementById('filterForm');
        form.submit();
    }
    </script>

    <style>
    /* Thêm CSS để nút reset đẹp hơn */
    .btn-reset {
        padding: 10px 20px;
        background: #f1f3f5;
        color: #666;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-reset:hover {
        background: #e0e0e0;
    }

    /* Hiệu ứng loading khi submit */
    .filter-section form.loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .filter-section form.loading::after {
        content: '⏳ Đang lọc...';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 10px 20px;
        border-radius: 6px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        font-weight: 600;
        color: #667eea;
    }
    </style>
</body>

</html>