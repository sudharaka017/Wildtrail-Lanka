<?php
// guide/my_schedule.php - Guide Schedule
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'guide') {
    header('Location: ../login.php');
    exit;
}

$guideId = $_SESSION['user_id'];
$guideName = $_SESSION['user_name'] ?? 'Guide';

try {
    $scheduleStmt = $pdo->prepare("
        SELECT b.*, p.park_name, p.province, u.full_name as visitor_name, u.phone as visitor_phone,
               v.vehicle_name, v.vehicle_type, v.plate_number, dr.full_name as driver_name
        FROM bookings b
        JOIN parks p ON b.park_id = p.id
        JOIN users u ON b.visitor_id = u.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN users dr ON v.driver_id = dr.id
        WHERE b.guide_id = ?
        ORDER BY b.entry_date ASC
    ");
    $scheduleStmt->execute([$guideId]);
    $schedule = $scheduleStmt->fetchAll();
} catch (PDOException $e) {
    $schedule = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Guide Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-bg: #1a1a2e; --sidebar-hover: #16213e; --primary-green: #1a5f2a; --accent-gold: #d4a017; }
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
        .schedule-card { background: white; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 15px; border-left: 5px solid var(--accent-gold); }
        .schedule-header { padding: 15px 20px; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center; }
        .schedule-body { padding: 20px; }
        .schedule-detail { display: flex; align-items: center; margin-bottom: 8px; color: #555; font-size: 14px; }
        .schedule-detail i { width: 25px; color: var(--primary-green); margin-right: 10px; }
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .month-header { background: var(--primary-green); color: white; padding: 12px 20px; border-radius: 8px; margin: 20px 0 15px; font-weight: bold; }
        .empty-state { text-align: center; padding: 50px; color: #888; }
        .empty-state i { font-size: 64px; margin-bottom: 20px; color: #ddd; }
        .footer { text-align: center; color: #888; padding: 30px; font-size: 13px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand"><i class="bi bi-compass"></i> Guide Panel</div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link active" href="my_schedule.php"><i class="bi bi-calendar-week"></i> My Schedule</a></li>
            <li class="nav-item"><a class="nav-link" href="sightings.php"><i class="bi bi-binoculars"></i> My Sightings</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="../index.php"><i class="bi bi-globe"></i> View Website</a></li>
            <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h1 class="page-title"><i class="bi bi-calendar-week"></i> My Schedule</h1>
            <div class="user-badge"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($guideName); ?></div>
        </div>

        <?php if (empty($schedule)): ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <h4>No Tours Scheduled</h4>
            <p>You don't have any assigned tours yet.</p>
        </div>
        <?php else: 
            $currentMonth = '';
            foreach ($schedule as $item):
                $itemMonth = date('F Y', strtotime($item['entry_date']));
                if ($itemMonth !== $currentMonth):
                    $currentMonth = $itemMonth;
        ?>
        <div class="month-header"><i class="bi bi-calendar-month"></i> <?php echo $currentMonth; ?></div>
        <?php endif; ?>
        
        <div class="schedule-card">
            <div class="schedule-header">
                <span class="fw-bold"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($item['park_name']); ?></span>
                <span class="status-badge status-<?php echo $item['status']; ?>"><?php echo ucfirst($item['status']); ?></span>
            </div>
            <div class="schedule-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="schedule-detail"><i class="bi bi-calendar"></i> <?php echo date('M d', strtotime($item['entry_date'])); ?> - <?php echo date('M d, Y', strtotime($item['exit_date'])); ?></div>
                        <div class="schedule-detail"><i class="bi bi-person"></i> Visitor: <strong><?php echo htmlspecialchars($item['visitor_name']); ?></strong></div>
                        <div class="schedule-detail"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($item['visitor_phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="schedule-detail"><i class="bi bi-people"></i> <?php echo $item['visitors_count']; ?> visitors</div>
                        <div class="schedule-detail"><i class="bi bi-truck"></i> Vehicle: <?php echo htmlspecialchars($item['vehicle_name'] ?? 'Not assigned'); ?> <?php echo $item['vehicle_type'] ? '('.$item['vehicle_type'].')' : ''; ?></div>
                        <?php if ($item['driver_name']): ?>
                        <div class="schedule-detail"><i class="bi bi-person-badge"></i> Driver: <?php echo htmlspecialchars($item['driver_name']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>

        <div class="footer">
            <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Guide Panel &copy; 2026
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>