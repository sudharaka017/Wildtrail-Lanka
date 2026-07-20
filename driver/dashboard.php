<?php
// driver/dashboard.php - Driver Dashboard
require_once '../db.php';

// Security: Only drivers
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'driver') {
    header('Location: ../login.php');
    exit;
}

$driverId = $_SESSION['user_id'];
$driverName = $_SESSION['user_name'] ?? 'Driver';
$message = '';
$messageType = '';

// Get driver's vehicle
try {
    $vehicleStmt = $pdo->prepare("SELECT * FROM vehicles WHERE driver_id = ?");
    $vehicleStmt->execute([$driverId]);
    $myVehicle = $vehicleStmt->fetch();
} catch (PDOException $e) {
    $myVehicle = null;
}

// Get today's trips
try {
    $today = date('Y-m-d');
    $tripsStmt = $pdo->prepare("
        SELECT b.*, p.park_name, p.province, u.full_name as visitor_name, u.phone as visitor_phone,
               v.vehicle_name, g.full_name as guide_name
        FROM bookings b
        JOIN parks p ON b.park_id = p.id
        JOIN users u ON b.visitor_id = u.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN users g ON b.guide_id = g.id
        WHERE b.vehicle_id = ? AND b.entry_date <= ? AND b.exit_date >= ? AND b.status IN ('confirmed', 'completed')
        ORDER BY b.entry_date ASC
    ");
    $tripsStmt->execute([$myVehicle['id'] ?? 0, $today, $today]);
    $todayTrips = $tripsStmt->fetchAll();
    
    // Get upcoming trips
    $upcomingStmt = $pdo->prepare("
        SELECT b.*, p.park_name, u.full_name as visitor_name, u.phone as visitor_phone
        FROM bookings b
        JOIN parks p ON b.park_id = p.id
        JOIN users u ON b.visitor_id = u.id
        WHERE b.vehicle_id = ? AND b.entry_date > ? AND b.status = 'confirmed'
        ORDER BY b.entry_date ASC
        LIMIT 5
    ");
    $upcomingStmt->execute([$myVehicle['id'] ?? 0, $today]);
    $upcomingTrips = $upcomingStmt->fetchAll();
    
    // Get trip history
    $historyStmt = $pdo->prepare("
        SELECT b.*, p.park_name, u.full_name as visitor_name
        FROM bookings b
        JOIN parks p ON b.park_id = p.id
        JOIN users u ON b.visitor_id = u.id
        WHERE b.vehicle_id = ? AND b.status = 'completed'
        ORDER BY b.exit_date DESC
        LIMIT 5
    ");
    $historyStmt->execute([$myVehicle['id'] ?? 0]);
    $tripHistory = $historyStmt->fetchAll();
    
} catch (PDOException $e) {
    $todayTrips = $upcomingTrips = $tripHistory = [];
}

// Update trip status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_trip'])) {
    $bookingId = (int)$_POST['booking_id'];
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ? AND vehicle_id = ?");
        $stmt->execute([$bookingId, $myVehicle['id'] ?? 0]);
        $message = 'Trip marked as completed!';
        $messageType = 'success';
        header('Location: dashboard.php');
        exit;
    } catch (PDOException $e) {
        $message = 'Error updating trip!';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - WildTrail Lanka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        
        .vehicle-card {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green, #2d8a3e) 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .trip-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .trip-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .trip-body {
            padding: 20px;
        }
        
        .trip-detail {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #555;
        }
        
        .trip-detail i {
            width: 25px;
            color: var(--primary-green);
            margin-right: 10px;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        
        .btn-complete {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn-complete:hover {
            background: var(--sidebar-hover);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #888;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
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
            <i class="bi bi-truck"></i> Driver Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="my_trips.php">
                    <i class="bi bi-calendar-check"></i> My Trips
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
            <h1 class="page-title"><i class="bi bi-speedometer2"></i> Driver Dashboard</h1>
            <div class="user-badge">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($driverName); ?>
            </div>
        </div>

        <!-- Message -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Vehicle Info -->
        <?php if ($myVehicle): ?>
        <div class="vehicle-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4><i class="bi bi-truck"></i> My Vehicle</h4>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($myVehicle['vehicle_name']); ?></strong> 
                    (<?php echo $myVehicle['vehicle_type']; ?>)</p>
                    <p class="mb-1">Plate: <code><?php echo htmlspecialchars($myVehicle['plate_number']); ?></code></p>
                    <p class="mb-0">Capacity: <?php echo $myVehicle['capacity']; ?> people | 
                    Status: <span class="badge bg-light text-dark"><?php echo ucfirst($myVehicle['status']); ?></span></p>
                </div>
                <div class="col-md-4 text-end">
                    <i class="bi bi-truck-front" style="font-size: 80px; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> No vehicle assigned to you yet. Contact admin.
        </div>
        <?php endif; ?>

        <!-- Today's Trips -->
        <h4 class="mb-3"><i class="bi bi-calendar-day"></i> Today's Trips</h4>
        <?php if (empty($todayTrips)): ?>
        <div class="trip-card">
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>No trips scheduled for today</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($todayTrips as $trip): ?>
        <div class="trip-card">
            <div class="trip-header">
                <span class="fw-bold">Booking #<?php echo str_pad($trip['id'], 4, '0', STR_PAD_LEFT); ?></span>
                <span class="status-badge status-<?php echo $trip['status']; ?>">
                    <?php echo ucfirst($trip['status']); ?>
                </span>
            </div>
            <div class="trip-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="trip-detail"><i class="bi bi-geo-alt"></i> 
                            <strong><?php echo htmlspecialchars($trip['park_name']); ?></strong> 
                            (<?php echo htmlspecialchars($trip['province']); ?>)
                        </div>
                        <div class="trip-detail"><i class="bi bi-calendar"></i> 
                            <?php echo date('M d, Y', strtotime($trip['entry_date'])); ?> - 
                            <?php echo date('M d, Y', strtotime($trip['exit_date'])); ?>
                        </div>
                        <div class="trip-detail"><i class="bi bi-people"></i> 
                            <?php echo $trip['visitors_count']; ?> visitors
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="trip-detail"><i class="bi bi-person"></i> 
                            Visitor: <strong><?php echo htmlspecialchars($trip['visitor_name']); ?></strong>
                        </div>
                        <div class="trip-detail"><i class="bi bi-telephone"></i> 
                            <?php echo htmlspecialchars($trip['visitor_phone'] ?? 'N/A'); ?>
                        </div>
                        <?php if ($trip['guide_name']): ?>
                        <div class="trip-detail"><i class="bi bi-compass"></i> 
                            Guide: <?php echo htmlspecialchars($trip['guide_name']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($trip['status'] == 'confirmed'): ?>
                <div class="mt-3 text-end">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="booking_id" value="<?php echo $trip['id']; ?>">
                        <button type="submit" name="complete_trip" class="btn-complete">
                            <i class="bi bi-check-circle"></i> Mark as Completed
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Upcoming Trips -->
        <h4 class="mb-3 mt-4"><i class="bi bi-calendar-week"></i> Upcoming Trips</h4>
        <?php if (empty($upcomingTrips)): ?>
        <div class="trip-card">
            <div class="empty-state">
                <i class="bi bi-calendar"></i>
                <p>No upcoming trips</p>
            </div>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover bg-white rounded">
                <thead class="table-light">
                    <tr>
                        <th>Park</th>
                        <th>Dates</th>
                        <th>Visitors</th>
                        <th>Visitor Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingTrips as $trip): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($trip['park_name']); ?></strong></td>
                        <td><?php echo date('M d', strtotime($trip['entry_date'])); ?> - <?php echo date('M d', strtotime($trip['exit_date'])); ?></td>
                        <td><?php echo $trip['visitors_count']; ?></td>
                        <td><?php echo htmlspecialchars($trip['visitor_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($trip['visitor_phone'] ?? ''); ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Driver Panel &copy; 2026
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>