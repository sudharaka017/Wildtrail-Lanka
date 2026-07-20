<?php
// guide/dashboard.php - Guide Dashboard
require_once '../db.php';

// Security: Only guides
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'guide') {
    header('Location: ../login.php');
    exit;
}

$guideId = $_SESSION['user_id'];
$guideName = $_SESSION['user_name'] ?? 'Guide';
$message = '';
$messageType = '';

// Report animal sighting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_sighting'])) {
    $bookingId = !empty($_POST['booking_id']) ? (int)$_POST['booking_id'] : null;
    $animalName = trim($_POST['animal_name'] ?? '');
    $count = (int)($_POST['count'] ?? 1);
    $sightingDate = $_POST['sighting_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($animalName)) {
        $message = 'Animal name is required!';
        $messageType = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO sightings (booking_id, animal_name, count, sighting_date, notes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$bookingId, $animalName, $count, $sightingDate, $notes]);
            $message = 'Animal sighting reported successfully!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Error reporting sighting!';
            $messageType = 'danger';
        }
    }
}

// Get today's assigned tours
try {
    $today = date('Y-m-d');
    $toursStmt = $pdo->prepare("
        SELECT b.*, p.park_name, p.province, u.full_name as visitor_name, u.phone as visitor_phone,
               v.vehicle_name, v.vehicle_type, v.plate_number, dr.full_name as driver_name
        FROM bookings b
        JOIN parks p ON b.park_id = p.id
        JOIN users u ON b.visitor_id = u.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN users dr ON v.driver_id = dr.id
        WHERE b.guide_id = ? AND b.entry_date <= ? AND b.exit_date >= ? AND b.status IN ('confirmed', 'completed')
        ORDER BY b.entry_date ASC
    ");
    $toursStmt->execute([$guideId, $today, $today]);
    $todayTours = $toursStmt->fetchAll();
    
    // Upcoming tours
    $upcomingStmt = $pdo->prepare("
        SELECT b.*, p.park_name, u.full_name as visitor_name, v.vehicle_name
        FROM bookings b
        JOIN parks p ON b.park_id = p.id
        JOIN users u ON b.visitor_id = u.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.guide_id = ? AND b.entry_date > ? AND b.status = 'confirmed'
        ORDER BY b.entry_date ASC
        LIMIT 5
    ");
    $upcomingStmt->execute([$guideId, $today]);
    $upcomingTours = $upcomingStmt->fetchAll();
    
    // Recent sightings reported by this guide
    $sightingsStmt = $pdo->prepare("
        SELECT s.*, p.park_name
        FROM sightings s
        LEFT JOIN bookings b ON s.booking_id = b.id
        LEFT JOIN parks p ON b.park_id = p.id
        WHERE b.guide_id = ? OR s.id IN (
            SELECT s2.id FROM sightings s2 
            JOIN bookings b2 ON s2.booking_id = b2.id 
            WHERE b2.guide_id = ?
        )
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    $sightingsStmt->execute([$guideId, $guideId]);
    $mySightings = $sightingsStmt->fetchAll();
    
} catch (PDOException $e) {
    $todayTours = $upcomingTours = $mySightings = [];
}

// Get available bookings to assign (for demo, admin assigns guides, but we show all)
try {
    $availableStmt = $pdo->prepare("
        SELECT b.id, p.park_name, b.entry_date, b.exit_date
        FROM bookings b
        JOIN parks p ON b.park_id = p.id
        WHERE b.guide_id IS NULL AND b.status = 'confirmed'
        ORDER BY b.entry_date ASC
        LIMIT 5
    ");
    $availableStmt->execute();
    $availableBookings = $availableStmt->fetchAll();
} catch (PDOException $e) {
    $availableBookings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide Dashboard - WildTrail Lanka</title>
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
        
        .tour-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .tour-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, #2d8a3e 100%);
            color: white;
            padding: 20px;
        }
        
        .tour-body {
            padding: 20px;
        }
        
        .tour-detail {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #555;
        }
        
        .tour-detail i {
            width: 25px;
            color: var(--primary-green);
            margin-right: 10px;
        }
        
        .sighting-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }
        
        .sighting-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--accent-gold);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .animal-icon {
            font-size: 32px;
            margin-right: 15px;
        }
        
        .btn-report {
            background: var(--accent-gold);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn-report:hover {
            background: #b8941f;
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
        
        .stats-row { margin-bottom: 25px; }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            border-left: 4px solid var(--accent-gold);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 26px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #888;
            font-size: 13px;
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
            <i class="bi bi-compass"></i> Guide Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="my_schedule.php">
                    <i class="bi bi-calendar-week"></i> My Schedule
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="sightings.php">
                    <i class="bi bi-binoculars"></i> My Sightings
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
            <h1 class="page-title"><i class="bi bi-compass"></i> Guide Dashboard</h1>
            <div class="user-badge">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($guideName); ?>
            </div>
        </div>

        <!-- Message -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row stats-row">
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-number"><?php echo count($todayTours); ?></div>
                    <div class="stat-label">Today's Tours</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-number"><?php echo count($upcomingTours); ?></div>
                    <div class="stat-label">Upcoming Tours</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">🦁</div>
                    <div class="stat-number"><?php echo count($mySightings); ?></div>
                    <div class="stat-label">Sightings Reported</div>
                </div>
            </div>
        </div>

        <!-- Today's Tours -->
        <h4 class="mb-3"><i class="bi bi-calendar-day"></i> Today's Tours</h4>
        <?php if (empty($todayTours)): ?>
        <div class="tour-card">
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>No tours assigned for today</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($todayTours as $tour): ?>
        <div class="tour-card">
            <div class="tour-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($tour['park_name']); ?></h5>
                        <p class="mb-0 opacity-75"><?php echo htmlspecialchars($tour['province']); ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-light text-dark">
                            <?php echo date('M d', strtotime($tour['entry_date'])); ?> - <?php echo date('M d', strtotime($tour['exit_date'])); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="tour-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="tour-detail"><i class="bi bi-person"></i> 
                            Visitor: <strong><?php echo htmlspecialchars($tour['visitor_name']); ?></strong>
                        </div>
                        <div class="tour-detail"><i class="bi bi-telephone"></i> 
                            <?php echo htmlspecialchars($tour['visitor_phone'] ?? 'N/A'); ?>
                        </div>
                        <div class="tour-detail"><i class="bi bi-people"></i> 
                            <?php echo $tour['visitors_count']; ?> visitors
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="tour-detail"><i class="bi bi-truck"></i> 
                            Vehicle: <?php echo htmlspecialchars($tour['vehicle_name'] ?? 'Not assigned'); ?>
                            <?php if ($tour['vehicle_type']): ?>
                                (<?php echo $tour['vehicle_type']; ?>)
                            <?php endif; ?>
                        </div>
                        <?php if ($tour['driver_name']): ?>
                        <div class="tour-detail"><i class="bi bi-person-badge"></i> 
                            Driver: <?php echo htmlspecialchars($tour['driver_name']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Report Sighting Form -->
                <div class="sighting-form mt-3">
                    <h6><i class="bi bi-binoculars"></i> Report Animal Sighting</h6>
                    <form method="POST" action="dashboard.php" class="row g-2 mt-2">
                        <input type="hidden" name="booking_id" value="<?php echo $tour['id']; ?>">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="animal_name" placeholder="Animal (e.g., Leopard)" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control" name="count" value="1" min="1" placeholder="Count">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" name="sighting_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="notes" placeholder="Notes (optional)">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" name="report_sighting" class="btn-report w-100">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Recent Sightings -->
        <h4 class="mb-3 mt-4"><i class="bi bi-clock-history"></i> Recent Sightings</h4>
        <?php if (empty($mySightings)): ?>
        <div class="tour-card">
            <div class="empty-state">
                <i class="bi bi-binoculars"></i>
                <p>No sightings reported yet</p>
            </div>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($mySightings as $sighting): ?>
            <div class="col-md-6 mb-3">
                <div class="sighting-card">
                    <div class="d-flex align-items-center">
                        <div class="animal-icon">🦁</div>
                        <div>
                            <h6 class="mb-1"><strong><?php echo htmlspecialchars($sighting['animal_name']); ?></strong></h6>
                            <p class="mb-0 text-muted small">
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($sighting['park_name'] ?? 'Unknown Park'); ?> | 
                                <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($sighting['sighting_date'])); ?> | 
                                Count: <?php echo $sighting['count']; ?>
                            </p>
                            <?php if ($sighting['notes']): ?>
                            <p class="mb-0 text-muted small mt-1"><i class="bi bi-sticky"></i> <?php echo htmlspecialchars($sighting['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Guide Panel &copy; 2026
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>