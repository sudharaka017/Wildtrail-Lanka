<?php
// index.php - Visitor Home Page / Dashboard
require_once 'db.php';

// Check if user is logged in, if not redirect to login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$userName = $_SESSION['user_name'] ?? 'Visitor';
$userRole = $_SESSION['user_role'] ?? 'visitor';

// Only visitors should see this page (admins go to admin dashboard)
if ($userRole !== 'visitor') {
    header('Location: login.php');
    exit;
}

// Get some statistics for the dashboard
try {
    // Count total parks
    $parksCount = $pdo->query("SELECT COUNT(*) FROM parks WHERE status = 'active'")->fetchColumn();
    
    // Count user's bookings
    $bookingsStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE visitor_id = ?");
    $bookingsStmt->execute([$_SESSION['user_id']]);
    $myBookings = $bookingsStmt->fetchColumn();
    
    // Get recent bookings
    $recentStmt = $pdo->prepare("
        SELECT b.*, p.park_name, v.vehicle_name 
        FROM bookings b 
        LEFT JOIN parks p ON b.park_id = p.id 
        LEFT JOIN vehicles v ON b.vehicle_id = v.id 
        WHERE b.visitor_id = ? 
        ORDER BY b.created_at DESC 
        LIMIT 5
    ");
    $recentStmt->execute([$_SESSION['user_id']]);
    $recentBookings = $recentStmt->fetchAll();
    
} catch (PDOException $e) {
    $parksCount = 0;
    $myBookings = 0;
    $recentBookings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WildTrail Lanka</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-green: #1a5f2a;
            --light-green: #2d8a3e;
            --dark-green: #144a20;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 24px;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 5px solid var(--primary-green);
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            font-size: 40px;
            color: var(--primary-green);
            margin-bottom: 15px;
        }
        
        .stats-number {
            font-size: 32px;
            font-weight: bold;
            color: var(--dark-green);
        }
        
        .stats-label {
            color: #666;
            font-size: 14px;
        }
        
        .action-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            color: inherit;
        }
        
        .action-icon {
            font-size: 50px;
            color: var(--primary-green);
            margin-bottom: 20px;
        }
        
        .action-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--dark-green);
            margin-bottom: 10px;
        }
        
        .action-desc {
            color: #666;
            font-size: 14px;
        }
        
        .booking-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--primary-green);
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .footer {
            background: var(--dark-green);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
            text-align: center;
        }
        
        .welcome-text {
            font-size: 18px;
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="book_ticket.php"><i class="bi bi-ticket-perforated"></i> Book Safari</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_bookings.php"><i class="bi bi-calendar-check"></i> My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
                <span class="navbar-text ms-3 text-white">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($userName); ?>
                </span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-3">Welcome back, <?php echo htmlspecialchars($userName); ?>! 🦁</h1>
            <p class="welcome-text">Ready for your next wild adventure in Sri Lanka?</p>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Row -->
        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="stats-card text-center">
                    <div class="stats-icon"><i class="bi bi-tree"></i></div>
                    <div class="stats-number"><?php echo $parksCount; ?></div>
                    <div class="stats-label">National Parks Available</div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card text-center">
                    <div class="stats-icon"><i class="bi bi-ticket-perforated"></i></div>
                    <div class="stats-number"><?php echo $myBookings; ?></div>
                    <div class="stats-label">My Bookings</div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card text-center">
                    <div class="stats-icon"><i class="bi bi-star"></i></div>
                    <div class="stats-number">4.8</div>
                    <div class="stats-label">Average Rating</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h3 class="mb-4 text-center" style="color: var(--dark-green); font-weight: bold;">
            <i class="bi bi-lightning"></i> Quick Actions
        </h3>
        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <a href="book_ticket.php" class="action-card">
                    <div class="action-icon"><i class="bi bi-ticket-perforated"></i></div>
                    <div class="action-title">Book a Safari</div>
                    <div class="action-desc">Reserve your spot at Sri Lanka's best national parks</div>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="my_bookings.php" class="action-card">
                    <div class="action-icon"><i class="bi bi-calendar-check"></i></div>
                    <div class="action-title">View My Bookings</div>
                    <div class="action-desc">Check status and manage your upcoming trips</div>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="parks.php" class="action-card">
                    <div class="action-icon"><i class="bi bi-map"></i></div>
                    <div class="action-title">Explore Parks</div>
                    <div class="action-desc">Discover wildlife and park information</div>
                </a>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="booking-table mb-5">
            <div class="table-header">
                <i class="bi bi-clock-history"></i> Recent Bookings
            </div>
            <div class="p-4">
                <?php if (empty($recentBookings)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 48px;"></i>
                        <p class="mt-3">No bookings yet. <a href="book_ticket.php" style="color: var(--primary-green); font-weight: bold;">Book your first safari!</a></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Park</th>
                                    <th>Vehicle</th>
                                    <th>Date</th>
                                    <th>Visitors</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['park_name'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['vehicle_name'] ?? 'Not assigned'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['entry_date'])); ?></td>
                                    <td><?php echo $booking['visitors_count']; ?></td>
                                    <td>Rs. <?php echo number_format($booking['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="mb-2"><i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Tourism Booking System</p>
            <p class="mb-0" style="opacity: 0.7; font-size: 14px;">Discover the wild beauty of Sri Lanka</p>
            <p class="mt-2" style="opacity: 0.5; font-size: 12px;">&copy; 2026 WildTrail Lanka. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>