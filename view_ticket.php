<?php
// view_ticket.php - Booking Ticket with QR Code
require_once 'db.php';
require_once 'includes/qr_generator.php';

// Security: Only logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$bookingId = (int)($_GET['id'] ?? 0);
if ($bookingId <= 0) {
    header('Location: my_bookings.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Get booking details
try {
    if ($userRole === 'admin') {
        // Admin can view any booking
        $stmt = $pdo->prepare("
            SELECT b.*, p.park_name, p.province, p.description as park_description,
                   u.full_name as visitor_name, u.email as visitor_email, u.phone as visitor_phone,
                   v.vehicle_name, v.vehicle_type, v.plate_number,
                   g.full_name as guide_name
            FROM bookings b
            LEFT JOIN parks p ON b.park_id = p.id
            LEFT JOIN users u ON b.visitor_id = u.id
            LEFT JOIN vehicles v ON b.vehicle_id = v.id
            LEFT JOIN users g ON b.guide_id = g.id
            WHERE b.id = ?
        ");
        $stmt->execute([$bookingId]);
    } else {
        // Visitors can only view their own bookings
        $stmt = $pdo->prepare("
            SELECT b.*, p.park_name, p.province, p.description as park_description,
                   u.full_name as visitor_name, u.email as visitor_email, u.phone as visitor_phone,
                   v.vehicle_name, v.vehicle_type, v.plate_number,
                   g.full_name as guide_name
            FROM bookings b
            LEFT JOIN parks p ON b.park_id = p.id
            LEFT JOIN users u ON b.visitor_id = u.id
            LEFT JOIN vehicles v ON b.vehicle_id = v.id
            LEFT JOIN users g ON b.guide_id = g.id
            WHERE b.id = ? AND b.visitor_id = ?
        ");
        $stmt->execute([$bookingId, $userId]);
    }
    
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header('Location: my_bookings.php');
        exit;
    }
    
    // Generate QR code
    $qrData = getBookingQRData($booking);
    $qrCodeUrl = generateQRCode($qrData, 250);
    
} catch (PDOException $e) {
    header('Location: my_bookings.php');
    exit;
}

$visitorName = $_SESSION['user_name'] ?? 'Visitor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Ticket - WildTrail Lanka</title>
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
        
        .ticket-container {
            max-width: 800px;
            margin: 40px auto;
        }
        
        .ticket-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .ticket-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .ticket-header h2 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .ticket-id {
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .ticket-body {
            padding: 40px;
        }
        
        .qr-section {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .qr-code {
            width: 250px;
            height: 250px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .qr-label {
            margin-top: 15px;
            color: #666;
            font-size: 14px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #888;
            font-size: 14px;
        }
        
        .detail-value {
            font-weight: bold;
            color: #333;
            text-align: right;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .btn-print {
            background: var(--primary-green);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            width: 100%;
        }
        
        .btn-print:hover {
            background: var(--dark-green);
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 60px;
            opacity: 0.05;
            font-weight: bold;
            pointer-events: none;
        }
        
        @media print {
            body { background: white; }
            .no-print { display: none !important; }
            .ticket-card { box-shadow: none; border: 2px solid #333; }
        }
        
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
    <nav class="navbar navbar-expand-lg navbar-dark no-print" style="background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);">
        <div class="container">
            <a class="navbar-brand" href="index.php" style="font-weight: bold; font-size: 22px;">
                <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $userRole === 'admin' ? 'admin/bookings.php' : 'my_bookings.php'; ?>">
                            <i class="bi bi-arrow-left"></i> Back to Bookings
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="ticket-container">
        <div class="ticket-card" style="position: relative;">
            
            <!-- Watermark -->
            <div class="watermark">WILDTRAIL</div>
            
            <!-- Header -->
            <div class="ticket-header">
                <h2><i class="bi bi-ticket-perforated"></i> Safari Ticket</h2>
                <div class="ticket-id mt-2">Booking #<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></div>
            </div>
            
            <!-- Body -->
            <div class="ticket-body">
                
                <!-- QR Code -->
                <div class="qr-section">
                    <img src="<?php echo $qrCodeUrl; ?>" class="qr-code" alt="Booking QR Code">
                    <div class="qr-label">
                        <i class="bi bi-qr-code"></i> Scan to verify ticket
                    </div>
                </div>
                
                <!-- Booking Details -->
                <h5 class="mb-3" style="color: var(--primary-green);"><i class="bi bi-info-circle"></i> Booking Details</h5>
                
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-geo-alt"></i> National Park</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['park_name'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-calendar"></i> Safari Dates</span>
                    <span class="detail-value">
                        <?php echo date('M d, Y', strtotime($booking['entry_date'])); ?> - 
                        <?php echo date('M d, Y', strtotime($booking['exit_date'])); ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-people"></i> Number of Visitors</span>
                    <span class="detail-value"><?php echo $booking['visitors_count']; ?> people</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-person"></i> Visitor Name</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['visitor_name'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-envelope"></i> Email</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['visitor_email'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-truck"></i> Vehicle</span>
                    <span class="detail-value">
                        <?php 
                        if ($booking['vehicle_name']) {
                            echo htmlspecialchars($booking['vehicle_name']) . ' (' . $booking['vehicle_type'] . ')';
                        } else {
                            echo 'Not assigned';
                        }
                        ?>
                    </span>
                </div>
                
                <?php if ($booking['guide_name']): ?>
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-compass"></i> Guide</span>
                    <span class="detail-value"><?php echo htmlspecialchars($booking['guide_name']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-cash"></i> Total Amount</span>
                    <span class="detail-value" style="color: var(--primary-green); font-size: 18px;">
                        Rs. <?php echo number_format($booking['total_amount'], 2); ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-shield-check"></i> Status</span>
                    <span class="detail-value">
                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label"><i class="bi bi-credit-card"></i> Payment</span>
                    <span class="detail-value">
                        <span class="badge bg-<?php echo $booking['payment_status'] === 'paid' ? 'success' : ($booking['payment_status'] === 'unpaid' ? 'danger' : 'secondary'); ?>">
                            <?php echo ucfirst($booking['payment_status']); ?>
                        </span>
                    </span>
                </div>
                
                <!-- Print Button -->
                <div class="mt-4 no-print">
                    <button onclick="window.print()" class="btn-print">
                        <i class="bi bi-printer"></i> Print Ticket
                    </button>
                </div>
                
            </div>
        </div>
        
        <!-- Back Button -->
        <div class="text-center mt-4 no-print">
            <a href="<?php echo $userRole === 'admin' ? 'admin/bookings.php' : 'my_bookings.php'; ?>" class="btn-back">
                <i class="bi bi-arrow-left"></i> Back to Bookings
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer no-print">
        <p><i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Tourism Booking System</p>
        <p class="mb-0" style="opacity: 0.7; font-size: 14px;">Discover the wild beauty of Sri Lanka</p>
    </footer>

</body>
</html>