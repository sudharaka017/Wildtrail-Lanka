<?php
// guide/sightings.php - Guide Sightings History
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'guide') {
    header('Location: ../login.php');
    exit;
}

$guideId = $_SESSION['user_id'];
$guideName = $_SESSION['user_name'] ?? 'Guide';

try {
    $sightingsStmt = $pdo->prepare("
        SELECT s.*, p.park_name, b.entry_date, b.exit_date
        FROM sightings s
        LEFT JOIN bookings b ON s.booking_id = b.id
        LEFT JOIN parks p ON b.park_id = p.id
        WHERE b.guide_id = ?
        ORDER BY s.sighting_date DESC, s.created_at DESC
    ");
    $sightingsStmt->execute([$guideId]);
    $allSightings = $sightingsStmt->fetchAll();
    
    // Stats
    $totalSightings = count($allSightings);
    $uniqueAnimals = $pdo->prepare("SELECT COUNT(DISTINCT animal_name) FROM sightings s JOIN bookings b ON s.booking_id = b.id WHERE b.guide_id = ?");
    $uniqueAnimals->execute([$guideId]);
    $uniqueCount = $uniqueAnimals->fetchColumn();
    
} catch (PDOException $e) {
    $allSightings = [];
    $totalSightings = $uniqueCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sightings - Guide Panel</title>
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
        .stat-card { background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 15px rgba(0,0,0,0.05); border-left: 4px solid var(--accent-gold); }
        .stat-number { font-size: 28px; font-weight: bold; color: #333; }
        .stat-label { color: #888; font-size: 14px; }
        .sighting-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; border-left: 4px solid var(--accent-gold); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .animal-icon { font-size: 40px; margin-right: 15px; }
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
            <li class="nav-item"><a class="nav-link" href="my_schedule.php"><i class="bi bi-calendar-week"></i> My Schedule</a></li>
            <li class="nav-item"><a class="nav-link active" href="sightings.php"><i class="bi bi-binoculars"></i> My Sightings</a></li>
            <li class="nav-item mt-4"><a class="nav-link" href="../index.php"><i class="bi bi-globe"></i> View Website</a></li>
            <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h1 class="page-title"><i class="bi bi-binoculars"></i> My Sightings</h1>
            <div class="user-badge"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($guideName); ?></div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalSightings; ?></div>
                    <div class="stat-label">Total Sightings</div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $uniqueCount; ?></div>
                    <div class="stat-label">Unique Animals</div>
                </div>
            </div>
        </div>

        <h4 class="mb-3">All Reported Sightings</h4>
        
        <?php if (empty($allSightings)): ?>
        <div class="empty-state">
            <i class="bi bi-binoculars"></i>
            <h4>No Sightings Yet</h4>
            <p>Report sightings from your dashboard during tours.</p>
        </div>
        <?php else: ?>
        <?php foreach ($allSightings as $s): ?>
        <div class="sighting-card">
            <div class="d-flex align-items-start">
                <div class="animal-icon">🦁</div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1"><strong><?php echo htmlspecialchars($s['animal_name']); ?></strong></h5>
                            <p class="mb-1 text-muted">
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($s['park_name'] ?? 'Unknown Park'); ?> | 
                                <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($s['sighting_date'])); ?> | 
                                Count: <?php echo $s['count']; ?>
                            </p>
                            <?php if ($s['notes']): ?>
                            <p class="mb-0 text-muted small"><i class="bi bi-sticky"></i> <?php echo htmlspecialchars($s['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-light text-dark border">#<?php echo $s['id']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="footer">
            <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Guide Panel &copy; 2026
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>