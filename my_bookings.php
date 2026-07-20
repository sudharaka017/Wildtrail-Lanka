<?php
// my_bookings.php - Visitor Bookings History
require_once 'db.php';

// Security: Only logged-in visitors
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['user_role'] !== 'visitor') {
    header('Location: index.php');
    exit;
}

$visitorId = $_SESSION['user_id'];
$visitorName = $_SESSION['user_name'] ?? 'Visitor';

// Get all bookings for this visitor
try {
    $bookings = $pdo->prepare("
        SELECT b.*, p.park_name, p.province, v.vehicle_name, v.vehicle_type, v.plate_number,
               g.full_name as guide_name
        FROM bookings b 
        LEFT JOIN parks p ON b.park_id = p.id 
        LEFT JOIN vehicles v ON b.vehicle_id = v.id 
        LEFT JOIN users g ON b.guide_id = g.id 
        WHERE b.visitor_id = ? 
        ORDER BY b.created_at DESC
    ");
    $bookings->execute([$visitorId]);
    $bookingList = $bookings->fetchAll();
    
} catch (PDOException $e) {
    $bookingList = [];
}

// Count stats
$totalBookings = count($bookingList);
$pendingCount = 0;
$confirmedCount = 0;
$completedCount = 0;

// Show success message if coming from booking
$showSuccess = isset($_GET['success']) && $_GET['success'] == '1';
$emailSent = isset($_GET['email']) && $_GET['email'] == '1';
$emailFailed = isset($_GET['email']) && $_GET['email'] == '0';
$emailPreview = $_SESSION['email_preview'] ?? null;
unset($_SESSION['email_preview']);
foreach ($bookingList as $b) {
    if ($b['status'] == 'pending') $pendingCount++;
    elseif ($b['status'] == 'confirmed') $confirmedCount++;
    elseif ($b['status'] == 'completed') $completedCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - WildTrail Lanka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-green: #1a5f2a;
            --light-green: #2d8a3e;
            --dark-green: #144a20;
            --accent-gold: #d4a017;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 22px;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white !important;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: white;
            padding: 50px 0;
            text-align: center;
            margin-bottom: 40px;
            border-radius: 0 0 30px 30px;
        }
        
        .page-header h2 {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stats-row {
            margin-bottom: 40px;
        }
        
        .stat-box {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-label {
            color: #888;
            font-size: 14px;
        }
        
        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 25px;
            transition: transform 0.3s;
        }
        
        .booking-card:hover {
            transform: translateY(-3px);
        }
        
        .booking-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .booking-id {
            font-weight: bold;
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .booking-body {
            padding: 25px;
        }
        
        .booking-detail {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            color: #555;
        }
        
        .booking-detail i {
            width: 25px;
            color: var(--primary-green);
            margin-right: 10px;
        }
        
        .booking-detail strong {
            color: #333;
            margin-right: 5px;
        }
        
        .booking-footer {
            background: #f8f9fa;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .amount-text {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-green);
        }
        
        .btn-ticket {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
        }
        
        .btn-ticket:hover {
            background: var(--dark-green);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        
        .empty-state i {
            font-size: 80px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
        }
        
        .btn-book-now {
            background: var(--primary-green);
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }
        
        .btn-book-now:hover {
            background: var(--dark-green);
            color: white;
        }
        
        .payment-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .payment-unpaid { background: #ffebee; color: #c62828; }
        .payment-paid { background: #e8f5e9; color: #2e7d32; }
        .payment-refunded { background: #f3e5f5; color: #7b1fa2; }
        
        .footer {
            background: var(--dark-green);
            color: white;
            padding: 30px;
            text-align: center;
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
                           <?php if ($showSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle"></i> Booking submitted successfully!
            <?php if ($emailSent): ?>
                <br><i class="bi bi-envelope-check"></i> Confirmation email sent to your inbox.
            <?php elseif ($emailFailed): ?>
                <br><i class="bi bi-envelope-x"></i> Email could not be sent (XAMPP mail not configured).
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($emailPreview): ?>
        <div class="alert alert-info mb-4">
            <h6><i class="bi bi-envelope"></i> Email Preview (would be sent in production):</h6>
            <div style="max-height: 300px; overflow: auto; border: 1px solid #dee2e6; border-radius: 8px; margin-top: 10px;">
                <iframe srcdoc="<?php echo htmlspecialchars($emailPreview); ?>" style="width: 100%; height: 250px; border: none;"></iframe>
            </div>
        </div>
        <?php endif; ?>
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="book_ticket.php"><i class="bi bi-ticket-perforated"></i> Book Safari</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_bookings.php"><i class="bi bi-calendar-check"></i> My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
                <span class="navbar-text ms-3 text-white">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($visitorName); ?>
                </span>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h2><i class="bi bi-calendar-check"></i> My Bookings</h2>
            <p>Track and manage all your safari adventures</p>
        </div>
    </div>

    <div class="container">
        
        <!-- Stats Row -->
        <div class="row stats-row">
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-icon">📋</div>
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?php echo $pendingCount; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo $confirmedCount; ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-box">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-number"><?php echo $completedCount; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>

        <!-- Bookings List -->
        <?php if (empty($bookingList)): ?>
        
        <!-- Empty State -->
        <div class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <h3>No Bookings Yet</h3>
            <p class="text-muted mb-4">You haven't booked any safaris yet. Start your adventure today!</p>
            <a href="book_ticket.php" class="btn-book-now">
                <i class="bi bi-ticket-perforated"></i> Book Your First Safari
            </a>
        </div>
        
        <?php else: ?>
        
        <?php foreach ($bookingList as $booking): ?>
        <div class="booking-card">
            <div class="booking-header">
                <span class="booking-id">Booking #<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></span>
                <span class="status-badge status-<?php echo $booking['status']; ?>">
                    <?php echo ucfirst($booking['status']); ?>
                </span>
            </div>
            <div class="booking-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="booking-detail">
                            <i class="bi bi-geo-alt"></i>
                            <strong>Park:</strong> <?php echo htmlspecialchars($booking['park_name'] ?? 'N/A'); ?>
                            <span class="text-muted">(<?php echo htmlspecialchars($booking['province'] ?? ''); ?>)</span>
                        </div>
                        <div class="booking-detail">
                            <i class="bi bi-calendar"></i>
                            <strong>Dates:</strong> 
                            <?php echo date('M d, Y', strtotime($booking['entry_date'])); ?> - 
                            <?php echo date('M d, Y', strtotime($booking['exit_date'])); ?>
                        </div>
                        <div class="booking-detail">
                            <i class="bi bi-people"></i>
                            <strong>Visitors:</strong> <?php echo $booking['visitors_count']; ?> people
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="booking-detail">
                            <i class="bi bi-truck"></i>
                            <strong>Vehicle:</strong> 
                            <?php 
                            if ($booking['vehicle_name']) {
                                $icon = $booking['vehicle_type'] == 'Jeep' ? '🚙' : ($booking['vehicle_type'] == 'Van' ? '🚐' : '🚌');
                                echo $icon . ' ' . htmlspecialchars($booking['vehicle_name']);
                            } else {
                                echo '<span class="text-muted">Not assigned</span>';
                            }
                            ?>
                        </div>
                        <div class="booking-detail">
                            <i class="bi bi-compass"></i>
                            <strong>Guide:</strong> 
                            <?php echo $booking['guide_name'] ? htmlspecialchars($booking['guide_name']) : '<span class="text-muted">Not assigned</span>'; ?>
                        </div>
                        <div class="booking-detail">
                            <i class="bi bi-credit-card"></i>
                            <strong>Payment:</strong>
                            <span class="payment-badge payment-<?php echo $booking['payment_status']; ?>">
                                <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="booking-footer">
                <div class="amount-text">
                    Total: Rs. <?php echo number_format($booking['total_amount'], 2); ?>
                </div>
                <div>
                    <a href="view_ticket.php?id=<?php echo $booking['id']; ?>" class="btn-ticket">
                        <i class="bi bi-ticket"></i> View Ticket
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p><i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Tourism Booking System</p>
        <p class="mb-0" style="opacity: 0.7; font-size: 14px;">Discover the wild beauty of Sri Lanka</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>