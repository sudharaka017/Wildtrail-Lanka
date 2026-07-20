<?php
// admin/dashboard.php - Admin Control Panel
// Go up one folder to find db.php
require_once '../db.php';

// SECURITY: Only allow admin users
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Get admin name
$adminName = $_SESSION['user_name'] ?? 'Admin';

// Get statistics from database
try {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalVisitors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'visitor'")->fetchColumn();
    $totalDrivers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'driver'")->fetchColumn();
    $totalGuides = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guide'")->fetchColumn();
    $totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $totalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
    $totalParks = $pdo->query("SELECT COUNT(*) FROM parks")->fetchColumn();
    
    // Recent bookings
    $recentBookings = $pdo->query("
        SELECT b.*, u.full_name as visitor_name, p.park_name 
        FROM bookings b 
        LEFT JOIN users u ON b.visitor_id = u.id 
        LEFT JOIN parks p ON b.park_id = p.id 
        ORDER BY b.created_at DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Monthly booking data for chart (last 6 months)
    $monthlyData = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as count 
        FROM bookings 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
        ORDER BY created_at DESC 
        LIMIT 6
    ")->fetchAll();
    $monthlyData = array_reverse($monthlyData);
    
} catch (PDOException $e) {
    $totalUsers = $totalVisitors = $totalDrivers = $totalGuides = 0;
    $totalBookings = $totalVehicles = $totalParks = 0;
    $recentBookings = [];
    $monthlyData = [];
}

// Prepare chart data
$chartLabels = [];
$chartValues = [];
foreach ($monthlyData as $row) {
    $chartLabels[] = $row['month'];
    $chartValues[] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - WildTrail Lanka</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Chart.js -->
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
        
        /* Sidebar */
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
        
        .sidebar-brand i {
            color: var(--accent-gold);
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
        
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
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
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
            border-left: 4px solid var(--primary-green);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-icon.blue { background: #e3f2fd; color: #1976d2; }
        .stat-icon.green { background: #e8f5e9; color: #388e3c; }
        .stat-icon.orange { background: #fff3e0; color: #f57c00; }
        .stat-icon.red { background: #ffebee; color: #d32f2f; }
        .stat-icon.purple { background: #f3e5f5; color: #7b1fa2; }
        .stat-icon.teal { background: #e0f2f1; color: #00796b; }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #888;
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        
        /* Table Card */
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
        
        .table-responsive {
            padding: 0;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background: #f8f9fa;
            border: none;
            color: #666;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .btn-action {
            padding: 5px 15px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .footer {
            text-align: center;
            color: #888;
            padding: 30px;
            font-size: 13px;
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
                <a class="nav-link active" href="dashboard.php">
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
                <a class="nav-link" href="reports.php">
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
            <h1 class="page-title"><i class="bi bi-speedometer2"></i> Dashboard Overview</h1>
            <div class="user-badge">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($adminName); ?>
            </div>
        </div>

        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-people"></i></div>
                    <div class="stat-number"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-person"></i></div>
                    <div class="stat-number"><?php echo $totalVisitors; ?></div>
                    <div class="stat-label">Visitors</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="bi bi-truck"></i></div>
                    <div class="stat-number"><?php echo $totalDrivers; ?></div>
                    <div class="stat-label">Drivers</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="bi bi-compass"></i></div>
                    <div class="stat-number"><?php echo $totalGuides; ?></div>
                    <div class="stat-label">Guides</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="bi bi-ticket-perforated"></i></div>
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Bookings</div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-truck-front"></i></div>
                    <div class="stat-number"><?php echo $totalVehicles; ?></div>
                    <div class="stat-label">Vehicles</div>
                </div>
            </div>
        </div>

        <!-- Chart and Recent Bookings Row -->
        <div class="row">
            <!-- Chart -->
            <div class="col-lg-7 mb-4">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-graph-up"></i> Monthly Bookings</div>
                    <canvas id="bookingsChart" height="250"></canvas>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="col-lg-5 mb-4">
                <div class="chart-card">
                    <div class="chart-title"><i class="bi bi-lightning"></i> Quick Info</div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="bi bi-tree text-success"></i> National Parks</span>
                            <span class="badge bg-success rounded-pill"><?php echo $totalParks; ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="bi bi-clock text-warning"></i> Pending Bookings</span>
                            <span class="badge bg-warning rounded-pill">
                                <?php 
                                $pending = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
                                echo $pending;
                                ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="bi bi-check-circle text-info"></i> Confirmed Bookings</span>
                            <span class="badge bg-info rounded-pill">
                                <?php 
                                $confirmed = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
                                echo $confirmed;
                                ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="bi bi-cash-stack text-primary"></i> Total Revenue</span>
                            <span class="badge bg-primary rounded-pill">
                                Rs. <?php 
                                $revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE payment_status = 'paid'")->fetchColumn();
                                echo number_format($revenue, 0);
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="table-card mb-4">
            <div class="table-header">
                <i class="bi bi-clock-history"></i> Recent Bookings
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Visitor</th>
                            <th>Park</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentBookings)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 32px;"></i>
                                    <p class="mt-2">No bookings yet</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($booking['visitor_name'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($booking['park_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['entry_date'])); ?></td>
                                <td>Rs. <?php echo number_format($booking['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js Script -->
    <script>
        const ctx = document.getElementById('bookingsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels ?: ['No Data']); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode($chartValues ?: [0]); ?>,
                    backgroundColor: 'rgba(26, 95, 42, 0.8)',
                    borderColor: 'rgba(26, 95, 42, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>
</html>