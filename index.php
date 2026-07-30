<?php
// index.php - Visitor Home Page / Dashboard
require_once 'db.php';

// Populate user info if available; allow public (not-logged-in) visitors to view the main page
$userName = $_SESSION['user_name'] ?? 'Visitor';
$userRole = $_SESSION['user_role'] ?? 'visitor';

// If an admin/staff is logged in, send them to their dashboard
if (isset($_SESSION['user_id']) && $userRole !== 'visitor') {
    header('Location: admin/dashboard.php');
    exit;
}

// Get some statistics for the dashboard
try {
    // Count total parks
    $parksCount = $pdo->query("SELECT COUNT(*) FROM parks WHERE status = 'active'")->fetchColumn();
    
    // If a visitor is logged in, fetch their bookings; otherwise defaults
    if (isset($_SESSION['user_id'])) {
        // Count user's bookings
        $bookingsStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE visitor_id = ?");
        $bookingsStmt->execute([$_SESSION['user_id']]);
        $myBookings = $bookingsStmt->fetchColumn();
        
        // Get recent bookings
        $recentStmt = $pdo->prepare("\
            SELECT b.*, p.park_name, v.vehicle_name \
            FROM bookings b \
            LEFT JOIN parks p ON b.park_id = p.id \
            LEFT JOIN vehicles v ON b.vehicle_id = v.id \
            WHERE b.visitor_id = ? \
            ORDER BY b.created_at DESC \
            LIMIT 5
        ");
        $recentStmt->execute([$_SESSION['user_id']]);
        $recentBookings = $recentStmt->fetchAll();
    } else {
        $myBookings = 0;
        $recentBookings = [];
    }
    
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

    <?php include 'details.php'; ?>

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