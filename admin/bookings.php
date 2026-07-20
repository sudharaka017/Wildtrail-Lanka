<?php
// admin/bookings.php - Admin Bookings Management
require_once '../db.php';
require_once '../includes/email_helper.php';

// Security: Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$adminName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$messageType = '';

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['booking_id']) && isset($_POST['new_status'])) {
    $bookingId = (int)$_POST['booking_id'];
    $newStatus = $_POST['new_status'];
    $allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    
     if (in_array($newStatus, $allowedStatuses)) {
        try {
            // Get booking and visitor info first
            $infoStmt = $pdo->prepare("
                SELECT b.*, u.email, u.full_name, p.park_name 
                FROM bookings b 
                JOIN users u ON b.visitor_id = u.id 
                JOIN parks p ON b.park_id = p.id 
                WHERE b.id = ?
            ");
            $infoStmt->execute([$bookingId]);
            $bookingInfo = $infoStmt->fetch();
            
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $bookingId]);
            
            // Send status update email
            if ($bookingInfo) {
                sendStatusUpdateEmail($bookingInfo['email'], $bookingInfo['full_name'], $bookingInfo, $newStatus);
            }
            
            $message = 'Booking status updated successfully!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Error updating status!';
            $messageType = 'danger';
        }
    }
}

// Handle payment update
if (isset($_POST['update_payment']) && isset($_POST['booking_id']) && isset($_POST['payment_status'])) {
    $bookingId = (int)$_POST['booking_id'];
    $paymentStatus = $_POST['payment_status'];
    $allowedPayments = ['unpaid', 'paid', 'refunded'];
    
    if (in_array($paymentStatus, $allowedPayments)) {
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET payment_status = ? WHERE id = ?");
            $stmt->execute([$paymentStatus, $bookingId]);
            $message = 'Payment status updated successfully!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Error updating payment status!';
            $messageType = 'danger';
        }
    }
}

// Get all bookings with details
try {
    $bookings = $pdo->query("
        SELECT b.*, 
               v.full_name as visitor_name, v.email as visitor_email, v.phone as visitor_phone,
               p.park_name, p.province,
               veh.vehicle_name, veh.vehicle_type, veh.plate_number,
               g.full_name as guide_name
        FROM bookings b
        LEFT JOIN users v ON b.visitor_id = v.id
        LEFT JOIN parks p ON b.park_id = p.id
        LEFT JOIN vehicles veh ON b.vehicle_id = veh.id
        LEFT JOIN users g ON b.guide_id = g.id
        ORDER BY b.created_at DESC
    ")->fetchAll();
    
    // Stats
    $totalBookings = count($bookings);
    $pendingCount = 0;
    $confirmedCount = 0;
    $completedCount = 0;
    $cancelledCount = 0;
    $totalRevenue = 0;
    
    foreach ($bookings as $b) {
        if ($b['status'] == 'pending') $pendingCount++;
        elseif ($b['status'] == 'confirmed') $confirmedCount++;
        elseif ($b['status'] == 'completed') $completedCount++;
        elseif ($b['status'] == 'cancelled') $cancelledCount++;
        
        if ($b['payment_status'] == 'paid') {
            $totalRevenue += $b['total_amount'];
        }
    }
    
} catch (PDOException $e) {
    $bookings = [];
    $totalBookings = $pendingCount = $confirmedCount = $completedCount = $cancelledCount = 0;
    $totalRevenue = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Admin Panel</title>
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
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #888;
            font-size: 13px;
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
        
        .payment-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .payment-unpaid { background: #ffebee; color: #c62828; }
        .payment-paid { background: #e8f5e9; color: #2e7d32; }
        .payment-refunded { background: #f3e5f5; color: #7b1fa2; }
        
        .btn-action {
            padding: 5px 15px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .modal-header {
            background: var(--primary-green);
            color: white;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
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
                <a class="nav-link active" href="bookings.php">
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
            <h1 class="page-title"><i class="bi bi-calendar-check"></i> Booking Management</h1>
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
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?php echo $pendingCount; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo $confirmedCount; ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-number"><?php echo $completedCount; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-number"><?php echo $cancelledCount; ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-number">Rs. <?php echo number_format($totalRevenue, 0); ?></div>
                    <div class="stat-label">Revenue</div>
                </div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="table-card">
            <div class="table-header">
                <span><i class="bi bi-list"></i> All Bookings</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Visitor</th>
                            <th>Park</th>
                            <th>Dates</th>
                            <th>Visitors</th>
                            <th>Vehicle</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <i class="bi bi-calendar-x" style="font-size: 48px;"></i>
                                <p class="mt-3">No bookings yet</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($booking['visitor_name'] ?? 'N/A'); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($booking['visitor_email'] ?? ''); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($booking['park_name'] ?? 'N/A'); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($booking['province'] ?? ''); ?></small>
                            </td>
                            <td>
                                <?php echo date('M d', strtotime($booking['entry_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($booking['exit_date'])); ?>
                            </td>
                            <td><?php echo $booking['visitors_count']; ?></td>
                            <td>
                                <?php 
                                if ($booking['vehicle_name']) {
                                    $icon = $booking['vehicle_type'] == 'Jeep' ? '🚙' : ($booking['vehicle_type'] == 'Van' ? '🚐' : '🚌');
                                    echo $icon . ' ' . htmlspecialchars($booking['vehicle_name']);
                                } else {
                                    echo '<span class="text-muted">Not assigned</span>';
                                }
                                ?>
                            </td>
                            <td><strong>Rs. <?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="payment-badge payment-<?php echo $booking['payment_status']; ?>">
                                    <?php echo ucfirst($booking['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $booking['id']; ?>">
                                    <i class="bi bi-pencil"></i> Status
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Status Update Modals -->
        <?php foreach ($bookings as $booking): ?>
        <div class="modal fade" id="statusModal<?php echo $booking['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Booking #<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <p><strong>Visitor:</strong> <?php echo htmlspecialchars($booking['visitor_name'] ?? 'N/A'); ?></p>
                            <p><strong>Park:</strong> <?php echo htmlspecialchars($booking['park_name'] ?? 'N/A'); ?></p>
                            <p><strong>Amount:</strong> Rs. <?php echo number_format($booking['total_amount'], 2); ?></p>
                        </div>
                        
                        <form method="POST" action="bookings.php">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Booking Status</strong></label>
                                <select class="form-select" name="new_status">
                                    <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                    <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>✅ Confirmed</option>
                                    <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>🏆 Completed</option>
                                    <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="update_status" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Update Status
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        
                        <form method="POST" action="bookings.php">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label"><strong>Payment Status</strong></label>
                                <select class="form-select" name="payment_status">
                                    <option value="unpaid" <?php echo $booking['payment_status'] == 'unpaid' ? 'selected' : ''; ?>>🔴 Unpaid</option>
                                    <option value="paid" <?php echo $booking['payment_status'] == 'paid' ? 'selected' : ''; ?>>🟢 Paid</option>
                                    <option value="refunded" <?php echo $booking['payment_status'] == 'refunded' ? 'selected' : ''; ?>>🟣 Refunded</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="update_payment" class="btn btn-success w-100">
                                <i class="bi bi-credit-card"></i> Update Payment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Footer -->
        <div class="footer">
            <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Admin Panel &copy; 2026
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>