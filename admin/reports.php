<?php
// admin/reports.php - Reports & Analytics with Chart.js
require_once '../db.php';

// Security: Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';

// Get data for charts
try {
    // Monthly bookings for last 12 months
    $monthlyBookings = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as bookings, SUM(total_amount) as revenue
        FROM bookings
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY created_at ASC
        LIMIT 12
    ")->fetchAll();
    
    // Park popularity
    $parkPopularity = $pdo->query("
        SELECT p.park_name, COUNT(b.id) as booking_count
        FROM bookings b
        JOIN parks p ON b.park_id = p.id
        GROUP BY b.park_id
        ORDER BY booking_count DESC
        LIMIT 8
    ")->fetchAll();
    
    // Vehicle type distribution
    $vehicleDistribution = $pdo->query("
        SELECT v.vehicle_type, COUNT(b.id) as usage_count
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.vehicle_id IS NOT NULL
        GROUP BY v.vehicle_type
    ")->fetchAll();
    
    // Status distribution
    $statusData = $pdo->query("
        SELECT status, COUNT(*) as count
        FROM bookings
        GROUP BY status
    ")->fetchAll();
    
    // Top visitors
    $topVisitors = $pdo->query("
        SELECT u.full_name, u.email, COUNT(b.id) as booking_count, SUM(b.total_amount) as total_spent
        FROM bookings b
        JOIN users u ON b.visitor_id = u.id
        GROUP BY b.visitor_id
        ORDER BY total_spent DESC
        LIMIT 10
    ")->fetchAll();
    
    // Revenue by month
    $revenueData = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%b %Y') as month, SUM(total_amount) as revenue
        FROM bookings
        WHERE payment_status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY created_at ASC
        LIMIT 6
    ")->fetchAll();
    
    // Summary stats
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE payment_status = 'paid'")->fetchColumn();
    $totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $avgBookingValue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;
    $totalVisitors = $pdo->query("SELECT COUNT(DISTINCT visitor_id) FROM bookings")->fetchColumn();
    
} catch (PDOException $e) {
    $monthlyBookings = $parkPopularity = $vehicleDistribution = $statusData = [];
    $topVisitors = $revenueData = [];
    $totalRevenue = $totalBookings = $avgBookingValue = $totalVisitors = 0;
}

// Prepare chart data
$monthLabels = [];
$monthBookingValues = [];
$monthRevenueValues = [];
foreach ($monthlyBookings as $row) {
    $monthLabels[] = $row['month'];
    $monthBookingValues[] = (int)$row['bookings'];
    $monthRevenueValues[] = (float)$row['revenue'];
}

$parkLabels = [];
$parkValues = [];
foreach ($parkPopularity as $row) {
    $parkLabels[] = $row['park_name'];
    $parkValues[] = (int)$row['booking_count'];
}

$vehicleLabels = [];
$vehicleValues = [];
foreach ($vehicleDistribution as $row) {
    $vehicleLabels[] = $row['vehicle_type'];
    $vehicleValues[] = (int)$row['usage_count'];
}

$statusLabels = [];
$statusValues = [];
$statusColors = [];
$statusColorMap = [
    'pending' => 'rgba(255, 193, 7, 0.8)',
    'confirmed' => 'rgba(26, 95, 42, 0.8)',
    'completed' => 'rgba(13, 110, 253, 0.8)',
    'cancelled' => 'rgba(220, 53, 69, 0.8)'
];
foreach ($statusData as $row) {
    $statusLabels[] = ucfirst($row['status']);
    $statusValues[] = (int)$row['count'];
    $statusColors[] = $statusColorMap[$row['status']] ?? 'rgba(108, 117, 125, 0.8)';
}

$revenueLabels = [];
$revenueValues = [];
foreach ($revenueData as $row) {
    $revenueLabels[] = $row['month'];
    $revenueValues[] = (float)$row['revenue'];
}

// If no data, provide sample data so charts don't break
$hasData = $totalBookings > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sidebar-bg: #1a1a2e;
            --sidebar-hover: #16213e;
            --primary-green: #1a5f2a;
            --accent-gold: #d4a017;
        }
        
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            padding-top: 20px;
            z-index: 1000;
        }
        
        .sidebar-brand {
            color: white;
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.7) !important;
            padding: 12px 25px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: var(--sidebar-hover);
            color: white !important;
        }
        
        .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        
        .topbar {
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .user-badge {
            background: var(--primary-green);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            text-align: center;
            border-left: 4px solid var(--primary-green);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-icon {
            font-size: 36px;
            margin-bottom: 12px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #888;
            font-size: 14px;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .chart-container-small {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--primary-green);
            color: white;
            padding: 18px 25px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .table { margin-bottom: 0; }
        .table thead th {
            background: #f8f9fa;
            border: none;
            color: #666;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }
        .table tbody td { padding: 15px; vertical-align: middle; }
        
        .footer {
            text-align: center;
            color: #888;
            padding: 30px;
            font-size: 13px;
        }
        
        .no-data-msg {
            text-align: center;
            color: #888;
            padding: 40px;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-shield-lock"></i> Admin Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="parks.php">
                    <i class="bi bi-tree"></i> Parks & Animals
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="bookings.php">
                    <i class="bi bi-calendar-check"></i> Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="vehicles.php">
                    <i class="bi bi-truck"></i> Vehicles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="reports.php">
                    <i class="bi bi-graph-up"></i> Reports
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="../index.php">
                    <i class="bi bi-globe"></i> View Website
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Top Bar -->
        <div class="topbar">
            <h1 class="page-title"><i class="bi bi-graph-up"></i> Reports & Analytics</h1>
            <div class="user-badge">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($adminName); ?>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-number">Rs. <?php echo number_format($totalRevenue, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo $totalVisitors; ?></div>
                    <div class="stat-label">Unique Visitors</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number">Rs. <?php echo number_format($avgBookingValue, 0); ?></div>
                    <div class="stat-label">Avg. Booking</div>
                </div>
            </div>
        </div>

        <?php if (!$hasData): ?>
        <!-- No Data Message -->
        <div class="chart-card mb-4">
            <div class="no-data-msg">
                <i class="bi bi-bar-chart" style="font-size: 64px; color: #ddd;"></i>
                <h4 class="mt-3 text-muted">No Data Available Yet</h4>
                <p class="text-muted">Charts will appear once you have more bookings. Create some bookings first!</p>
            </div>
        </div>
        <?php else: ?>

        <!-- Charts Row 1 -->
        <div class="row">
            <!-- Monthly Bookings Line Chart -->
            <div class="col-lg-8 mb-4">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-graph-up-arrow"></i> Monthly Bookings Trend</div>
                    <div class="chart-container">
                        <canvas id="monthlyBookingsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Booking Status Pie Chart -->
            <div class="col-lg-4 mb-4">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-pie-chart"></i> Booking Status</div>
                    <div class="chart-container-small">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row">
            <!-- Park Popularity Bar Chart -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-bar-chart"></i> Popular Parks</div>
                    <div class="chart-container">
                        <canvas id="parkChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Vehicle Distribution Doughnut -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-circle"></i> Vehicle Usage</div>
                    <div class="chart-container">
                        <canvas id="vehicleChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-currency-dollar"></i> Monthly Revenue (Paid Bookings)</div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>

        <!-- Top Visitors Table -->
        <div class="table-card mb-4">
            <div class="table-header">
                <i class="bi bi-trophy"></i> Top Visitors by Spending
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Visitor Name</th>
                            <th>Email</th>
                            <th>Bookings</th>
                            <th>Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topVisitors)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-people" style="font-size: 48px;"></i>
                                <p class="mt-3">No visitor data yet</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php $rank = 1; foreach ($topVisitors as $visitor): ?>
                        <tr>
                            <td>
                                <?php if ($rank <= 3): ?>
                                    <span style="font-size: 24px;">
                                        <?php echo $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉'); ?>
                                    </span>
                                <?php else: ?>
                                    #<?php echo $rank; ?>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($visitor['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($visitor['email']); ?></td>
                            <td><?php echo $visitor['booking_count']; ?></td>
                            <td><strong class="text-success">Rs. <?php echo number_format($visitor['total_spent'], 2); ?></strong></td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Admin Panel &copy; 2026
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($hasData): ?>
    <script>
        // Chart colors
        const greenColor = 'rgba(26, 95, 42, 0.8)';
        const greenBorder = 'rgba(26, 95, 42, 1)';
        const goldColor = 'rgba(212, 160, 23, 0.8)';
        const blueColor = 'rgba(13, 110, 253, 0.8)';
        const redColor = 'rgba(220, 53, 69, 0.8)';
        const yellowColor = 'rgba(255, 193, 7, 0.8)';
        
        const colors = [greenColor, goldColor, blueColor, redColor, yellowColor, 'rgba(111, 66, 193, 0.8)', 'rgba(23, 162, 184, 0.8)', 'rgba(108, 117, 125, 0.8)'];
        
        // Common chart options for clean scaling
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        font: { size: 12 },
                        padding: 15
                    }
                }
            }
        };
        
        // 1. Monthly Bookings Line Chart
        const monthlyCtx = document.getElementById('monthlyBookingsChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthLabels); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode($monthBookingValues); ?>,
                    borderColor: greenBorder,
                    backgroundColor: 'rgba(26, 95, 42, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 6,
                    pointBackgroundColor: greenBorder,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // 2. Status Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($statusLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($statusValues); ?>,
                    backgroundColor: <?php echo json_encode($statusColors); ?>,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
        
        // 3. Park Popularity Bar Chart
        const parkCtx = document.getElementById('parkChart').getContext('2d');
        new Chart(parkCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($parkLabels); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode($parkValues); ?>,
                    backgroundColor: greenColor,
                    borderColor: greenBorder,
                    borderWidth: 1,
                    borderRadius: 8,
                    barPercentage: 0.6
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // 4. Vehicle Doughnut Chart
        const vehicleCtx = document.getElementById('vehicleChart').getContext('2d');
        new Chart(vehicleCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($vehicleLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($vehicleValues); ?>,
                    backgroundColor: [greenColor, goldColor, blueColor],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                ...commonOptions,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
        
        // 5. Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($revenueLabels); ?>,
                datasets: [{
                    label: 'Revenue (Rs.)',
                    data: <?php echo json_encode($revenueValues); ?>,
                    backgroundColor: goldColor,
                    borderColor: 'rgba(212, 160, 23, 1)',
                    borderWidth: 1,
                    borderRadius: 8,
                    barPercentage: 0.5
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs. ' + value.toLocaleString();
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>