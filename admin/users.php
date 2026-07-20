<?php
// admin/users.php - User Management
require_once '../db.php';

// Security: Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$messageType = '';

// Toggle user status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    
    // Don't allow admin to deactivate themselves
    if ($id == $_SESSION['user_id']) {
        $message = 'You cannot deactivate your own account!';
        $messageType = 'warning';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'User status updated!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Error updating user!';
            $messageType = 'danger';
        }
    }
}

// Filter by role
$roleFilter = $_GET['role'] ?? '';
$whereClause = '';
$params = [];

if (!empty($roleFilter) && in_array($roleFilter, ['visitor', 'driver', 'guide', 'admin'])) {
    $whereClause = "WHERE role = ?";
    $params[] = $roleFilter;
}

// Get all users
try {
    $sql = "SELECT * FROM users $whereClause ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Stats
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $inactiveUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();
    $visitorCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'visitor'")->fetchColumn();
    $driverCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'driver'")->fetchColumn();
    $guideCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guide'")->fetchColumn();
    
} catch (PDOException $e) {
    $users = [];
    $totalUsers = $activeUsers = $inactiveUsers = 0;
    $visitorCount = $driverCount = $guideCount = 0;
}

// Role badge colors
$roleColors = [
    'admin' => 'bg-danger',
    'visitor' => 'bg-primary',
    'driver' => 'bg-warning text-dark',
    'guide' => 'bg-info text-dark'
];

$roleIcons = [
    'admin' => 'bi-shield-lock',
    'visitor' => 'bi-person',
    'driver' => 'bi-truck',
    'guide' => 'bi-compass'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin Panel</title>
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
        
        .stats-row { margin-bottom: 25px; }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            text-align: center;
            border-left: 4px solid var(--primary-green);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
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
        
        .filter-bar {
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-btn {
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border: 2px solid #e9ecef;
            color: #666;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-green);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            margin-right: 12px;
        }
        
        .role-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        .btn-toggle {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            border: none;
        }
        
        .btn-deactivate {
            background: #fff3cd;
            color: #856404;
        }
        
        .btn-activate {
            background: #d4edda;
            color: #155724;
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
                <a class="nav-link active" href="users.php">
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
            <h1 class="page-title"><i class="bi bi-people"></i> User Management</h1>
            <div class="user-badge">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($adminName); ?>
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
            <div class="col-md-2 col-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">🟢</div>
                    <div class="stat-number"><?php echo $activeUsers; ?></div>
                    <div class="stat-label">Active</div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">🔴</div>
                    <div class="stat-number"><?php echo $inactiveUsers; ?></div>
                    <div class="stat-label">Inactive</div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">🧑</div>
                    <div class="stat-number"><?php echo $visitorCount; ?></div>
                    <div class="stat-label">Visitors</div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">🚗</div>
                    <div class="stat-number"><?php echo $driverCount; ?></div>
                    <div class="stat-label">Drivers</div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">🧭</div>
                    <div class="stat-number"><?php echo $guideCount; ?></div>
                    <div class="stat-label">Guides</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <span class="text-muted"><i class="bi bi-funnel"></i> Filter by role:</span>
            <a href="users.php" class="filter-btn <?php echo empty($roleFilter) ? 'active' : ''; ?>">All</a>
            <a href="users.php?role=visitor" class="filter-btn <?php echo $roleFilter == 'visitor' ? 'active' : ''; ?>">Visitors</a>
            <a href="users.php?role=driver" class="filter-btn <?php echo $roleFilter == 'driver' ? 'active' : ''; ?>">Drivers</a>
            <a href="users.php?role=guide" class="filter-btn <?php echo $roleFilter == 'guide' ? 'active' : ''; ?>">Guides</a>
            <a href="users.php?role=admin" class="filter-btn <?php echo $roleFilter == 'admin' ? 'active' : ''; ?>">Admins</a>
        </div>

        <!-- Users Table -->
        <div class="table-card">
            <div class="table-header">
                <span><i class="bi bi-list"></i> All Users</span>
                <span class="badge bg-white text-dark"><?php echo count($users); ?> Showing</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-people" style="font-size: 48px;"></i>
                                <p class="mt-3">No users found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        <div class="text-muted small">ID: #<?php echo $user['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="role-badge <?php echo $roleColors[$user['role']] ?? 'bg-secondary'; ?>">
                                    <i class="bi <?php echo $roleIcons[$user['role']] ?? 'bi-person'; ?>"></i>
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <?php if ($user['status'] == 'active'): ?>
                                    <a href="users.php?toggle=<?php echo $user['id']; ?>" 
                                       class="btn-toggle btn-deactivate"
                                       onclick="return confirm('Deactivate this user?');">
                                        <i class="bi bi-pause-circle"></i> Deactivate
                                    </a>
                                    <?php else: ?>
                                    <a href="users.php?toggle=<?php echo $user['id']; ?>" 
                                       class="btn-toggle btn-activate"
                                       onclick="return confirm('Activate this user?');">
                                        <i class="bi bi-play-circle"></i> Activate
                                    </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-person-check"></i> You</span>
                                <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>