<?php
// driver/my_trips.php - Driver Trip History
require_once '../db.php';

// Security: Only drivers
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'driver') {
    header('Location: ../login.php');
    exit;
}

$driverId = $_SESSION['user_id'];
$driverName = $_SESSION['user_name'] ?? 'Driver';

// Get driver's vehicle
try {
    $vehicleStmt = $pdo->prepare("SELECT id FROM vehicles WHERE driver_id = ?");
    $vehicleStmt->execute([$driverId]);
    $vehicleId = $vehicleStmt->fetchColumn();
    
    // All trips
    $tripsStmt = $pdo->prepare("
        SELECT b.*, p.park_name, p.province, u.full_name as visitor_name, u.phone as visitor_phone,
               g.full_name as guide_name
        FROM bookings b
        JOIN parks p ON b.park_id = p.id
        JOIN users u ON b.visitor_id = u.id
        LEFT JOIN users g ON b.guide_id = g.id
        WHERE b.vehicle_id = ?
        ORDER BY b.entry_date DESC
    ");
    $tripsStmt->execute([$vehicleId]);
    $allTrips = $tripsStmt->fetchAll();
    
} catch (PDOException $e) {
    $allTrips = [];
    $vehicleId = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trips - Driver Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-bg: #1a1a2e; --sidebar-hover: #16213e; --primary-green: #1a5f2a; }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: var(--sidebar-bg); min-height: 100vh; position: fixed; left: 0; top: 0; width: 260px; padding-top: 20px; z-index: 1000; }
        .sidebar-brand { color: white; font-size: 22px; font-weight: bold; text-align: center; padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .nav-link { color: rgba(255,255,255,0.7) !important; padding: 12px 25px; margin: 2px 10px; border-radius: 8px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--sidebar-hover); color: white !important; }
        .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .main-content { margin-left: 260px; padding: 20px; }
        .topbar { background: white; border-radius: 12px; padding: 15px 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 24px; font-weight: bold; color: #333; margin: 0; }
        .user-badge { background: var(--primary-green); color: white; padding: 8px 20px; border-radius: 25px; font-weight: 500; }
        .trip-card { background: white; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 20px; }
        .trip-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .trip-body { padding: 20px; }
        .trip-detail { display: flex; align-items: center; margin-bottom: 10px; color: #555; }
        .trip-detail i { width: 25px; color: var(--primary-green); margin-right: 10px; }
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .empty-state { text-align: center; padding: 50px; color: #888; }
        .empty-state i { font-size: 64px; margin-bottom: 20px; color: #ddd; }
        .footer { text-align: center; color: #888; padding: 30px; font-size: 13px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand"><i class="bi bi-truck"></i> Driver Panel</div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link active" href="my_trips.php"><i class="bi bi-calendar-check"></i> My Trips</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="../index.php"><i class="bi bi-globe"></i> View Website</a></li>
            <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h1 class="page-title"><i class="bi bi-calendar-check"></i> My Trips</h1>
            <div class="user-badge"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($driverName); ?></div>
        </div>

        <h4 class="mb-3">All Trip History</h4>
        
        <?php if (empty($allTrips)): ?>
        <div class="trip-card">
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>No trips found</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($allTrips as $trip): ?>
        <div class="trip-card">
            <div class="trip-header">
                <span class="fw-bold">Booking #<?php echo str_pad($trip['id'], 4, '0', STR_PAD_LEFT); ?></span>
                <span class="status-badge status-<?php echo $trip['status']; ?>"><?php echo ucfirst($trip['status']); ?></span>
            </div>
            <div class="trip-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="trip-detail"><i class="bi bi-geo-alt"></i> <strong><?php echo htmlspecialchars($trip['park_name']); ?></strong> (<?php echo htmlspecialchars($trip['province']); ?>)</div>
                        <div class="trip-detail"><i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($trip['entry_date'])); ?> - <?php echo date('M d, Y', strtotime($trip['exit_date'])); ?></div>
                        <div class="trip-detail"><i class="bi bi-people"></i> <?php echo $trip['visitors_count']; ?> visitors</div>
                    </div>
                    <div class="col-md-6">
                        <div class="trip-detail"><i class="bi bi-person"></i> Visitor: <strong><?php echo htmlspecialchars($trip['visitor_name']); ?></strong></div>
                        <div class="trip-detail"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($trip['visitor_phone'] ?? 'N/A'); ?></div>
                        <?php if ($trip['guide_name']): ?>
                        <div class="trip-detail"><i class="bi bi-compass"></i> Guide: <?php echo htmlspecialchars($trip['guide_name']); ?></div>
                        <?php endif; ?>
                        <div class="trip-detail"><i class="bi bi-cash"></i> Amount: <strong>Rs. <?php echo number_format($trip['total_amount'], 2); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="footer">
            <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Driver Panel &copy; 2026
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>