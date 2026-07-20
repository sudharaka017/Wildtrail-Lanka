<?php
// book_ticket.php - Visitor Ticket Booking System
require_once 'db.php';
require_once 'includes/email_helper.php';

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
$message = '';
$messageType = '';

// Get all active parks and guides
try {
    $parks = $pdo->query("SELECT * FROM parks WHERE status = 'active' ORDER BY park_name")->fetchAll();
    $vehicles = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' ORDER BY vehicle_name")->fetchAll();
    $guides = $pdo->query("SELECT id, full_name FROM users WHERE role = 'guide' AND status = 'active' ORDER BY full_name")->fetchAll();
} catch (PDOException $e) {
    $parks = [];
    $vehicles = [];
    $guides = [];
}

// Process booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parkId = (int)($_POST['park_id'] ?? 0);
    $entryDate = $_POST['entry_date'] ?? '';
    $exitDate = $_POST['exit_date'] ?? '';
    $visitorsCount = (int)($_POST['visitors_count'] ?? 1);
    $vehicleId = !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
    $guideId = !empty($_POST['guide_id']) ? (int)$_POST['guide_id'] : null;

    // Validation
    $errors = [];
    if ($parkId <= 0) $errors[] = 'Please select a park!';
    if (empty($entryDate)) $errors[] = 'Please select entry date!';
    if (empty($exitDate)) $errors[] = 'Please select exit date!';
    if ($visitorsCount < 1) $errors[] = 'At least 1 visitor required!';
    if ($entryDate > $exitDate) $errors[] = 'Exit date must be after entry date!';
    if (strtotime($entryDate) < strtotime(date('Y-m-d'))) $errors[] = 'Entry date cannot be in the past!';

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'danger';
    } else {
        // Calculate total amount (simple pricing)
        $parkStmt = $pdo->prepare("SELECT entry_fee FROM parks WHERE id = ?");
        $parkStmt->execute([$parkId]);
        $parkFee = $parkStmt->fetchColumn() ?: 0;
        
        $vehicleFee = 0;
        if ($vehicleId) {
            $vehicleStmt = $pdo->prepare("SELECT vehicle_type FROM vehicles WHERE id = ?");
            $vehicleStmt->execute([$vehicleId]);
            $vType = $vehicleStmt->fetchColumn();
            $vehicleFee = ($vType == 'Jeep') ? 5000 : (($vType == 'Van') ? 8000 : 12000);
        }
        
        $days = max(1, (strtotime($exitDate) - strtotime($entryDate)) / 86400);
        $totalAmount = ($parkFee * $visitorsCount * $days) + $vehicleFee;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO bookings (visitor_id, park_id, vehicle_id, guide_id, entry_date, exit_date, visitors_count, total_amount, status, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')
            ");
            $stmt->execute([$visitorId, $parkId, $vehicleId, $guideId, $entryDate, $exitDate, $visitorsCount, $totalAmount]);
            
                        // Get booking details for email
            $bookingDetails = [
                'id' => $pdo->lastInsertId(),
                'park_name' => $parks[array_search($parkId, array_column($parks, 'id'))]['park_name'] ?? 'N/A',
                'entry_date' => $entryDate,
                'exit_date' => $exitDate,
                'visitors_count' => $visitorsCount,
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ];
            
            // Send confirmation email
            $emailResult = sendBookingConfirmation($_SESSION['user_email'], $_SESSION['user_name'], $bookingDetails);
            
            // Store email result in session to show on next page
            if (!$emailResult['sent']) {
                $_SESSION['email_preview'] = $emailResult['body'];
            }
            
            // Redirect to prevent duplicate submission on refresh
            header('Location: my_bookings.php?success=1' . ($emailResult['sent'] ? '&email=1' : '&email=0'));
            exit;
        } catch (PDOException $e) {
            $message = 'Error creating booking. Please try again.';
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Safari - WildTrail Lanka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-green: #1a5f2a;
            --light-green: #2d8a3e;
            --dark-green: #144a20;
            --accent-gold: #d4a017;
        }
        
                .park-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
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
        
        .booking-container {
            max-width: 900px;
            margin: 40px auto;
        }
        
        .booking-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .booking-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .booking-header h2 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .booking-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .booking-body {
            padding: 40px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: var(--primary-green);
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(26, 95, 42, 0.15);
        }
        
        .park-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            height: 100%;
        }
        
        .park-card:hover {
            border-color: var(--primary-green);
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .park-card.selected {
            border-color: var(--primary-green);
            background: #f0f9f0;
        }
        
        .park-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .park-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .park-fee {
            color: var(--primary-green);
            font-weight: bold;
        }
        
        .btn-book {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: bold;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(26, 95, 42, 0.3);
            color: white;
        }
        
        .price-summary {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }
        
        .price-total {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-green);
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
            margin-top: 15px;
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
                        <a class="nav-link" href="index.php"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="book_ticket.php"><i class="bi bi-ticket-perforated"></i> Book Safari</a>
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
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($visitorName); ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="booking-container">
        <div class="booking-card">
            
            <!-- Header -->
            <div class="booking-header">
                <h2><i class="bi bi-ticket-perforated"></i> Book Your Safari</h2>
                <p>Experience the wild beauty of Sri Lanka's national parks</p>
            </div>
            
            <!-- Body -->
            <div class="booking-body">
                
                <!-- Message -->
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="book_ticket.php" id="bookingForm">
                    
                    <!-- Select Park -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-geo-alt"></i> 1. Select National Park
                        </div>
                        <div class="row">
                            <?php if (empty($parks)): ?>
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> No parks available at the moment. Please check back later.
                                </div>
                            </div>
                            <?php else: ?>
                            <?php foreach ($parks as $park): ?>
                                                        <div class="col-md-6 col-lg-3 mb-3">
                                <div class="park-card" onclick="selectPark(<?php echo $park['id']; ?>, <?php echo $park['entry_fee']; ?>)">
                                    <input type="radio" name="park_id" value="<?php echo $park['id']; ?>" 
                                           id="park_<?php echo $park['id']; ?>" class="d-none" required>
                                    <?php if (!empty($park['image']) && file_exists('images/' . $park['image'])): ?>
                                    <img src="images/<?php echo htmlspecialchars($park['image']); ?>" class="park-image" alt="<?php echo htmlspecialchars($park['park_name']); ?>">
                                    <?php else: ?>
                                    <div class="park-icon">🌿</div>
                                    <?php endif; ?>
                                    <div class="park-name"><?php echo htmlspecialchars($park['park_name']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($park['province']); ?></div>
                                    <div class="park-fee mt-2">Rs. <?php echo number_format($park['entry_fee'], 2); ?>/person</div>
                                </div>
                            </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Dates & Visitors -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-calendar"></i> 2. Trip Details
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Entry Date</label>
                                <input type="date" class="form-control" name="entry_date" id="entry_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required onchange="calculatePrice()">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Exit Date</label>
                                <input type="date" class="form-control" name="exit_date" id="exit_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required onchange="calculatePrice()">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Number of Visitors</label>
                                <input type="number" class="form-control" name="visitors_count" id="visitors_count" 
                                       min="1" max="50" value="1" required onchange="calculatePrice()">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vehicle -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-truck"></i> 3. Select Vehicle (Optional)
                        </div>
                        <select class="form-select" name="vehicle_id" id="vehicle_id" onchange="calculatePrice()">
                            <option value="">-- No Vehicle (Self-drive) --</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>" data-type="<?php echo $vehicle['vehicle_type']; ?>">
                                <?php 
                                $icon = $vehicle['vehicle_type'] == 'Jeep' ? '🚙' : ($vehicle['vehicle_type'] == 'Van' ? '🚐' : '🚌');
                                echo $icon . ' ' . htmlspecialchars($vehicle['vehicle_name']) . ' (' . $vehicle['vehicle_type'] . ' - ' . $vehicle['capacity'] . ' seats)';
                                ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Guide -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-compass"></i> 4. Select Guide (Optional)
                        </div>
                        <select class="form-select" name="guide_id" id="guide_id">
                            <option value="">-- No Guide --</option>
                            <?php foreach ($guides as $guide): ?>
                            <option value="<?php echo $guide['id']; ?>">
                                <?php echo '🧭 ' . htmlspecialchars($guide['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Price Summary -->
                    <div class="price-summary">
                        <div class="section-title mb-3">
                            <i class="bi bi-cash-stack"></i> Price Summary
                        </div>
                        <div class="price-row">
                            <span>Park Entry Fee:</span>
                            <span id="parkFee">Rs. 0.00</span>
                        </div>
                        <div class="price-row">
                            <span>Vehicle Fee:</span>
                            <span id="vehicleFee">Rs. 0.00</span>
                        </div>
                        <div class="price-row">
                            <span>Duration:</span>
                            <span id="duration">0 days</span>
                        </div>
                        <div class="price-total">
                            <span>Total Amount:</span>
                            <span id="totalAmount">Rs. 0.00</span>
                        </div>
                    </div>
                    
                    <!-- Submit -->
                    <div class="mt-4">
                        <button type="submit" class="btn-book">
                            <i class="bi bi-check-circle"></i> Confirm Booking
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p><i class="bi bi-globe-asia-australia"></i> WildTrail Lanka Tourism Booking System</p>
        <p class="mb-0" style="opacity: 0.7; font-size: 14px;">Discover the wild beauty of Sri Lanka</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let selectedParkFee = 0;
        let selectedVehicleFee = 0;
        
        function selectPark(parkId, fee) {
            // Remove selected class from all cards
            document.querySelectorAll('.park-card').forEach(card => card.classList.remove('selected'));
            // Add selected class to clicked card
            document.querySelector(`#park_${parkId}`).closest('.park-card').classList.add('selected');
            // Check the radio button
            document.querySelector(`#park_${parkId}`).checked = true;
            selectedParkFee = fee;
            calculatePrice();
        }
        
        function calculatePrice() {
            const entryDate = document.getElementById('entry_date').value;
            const exitDate = document.getElementById('exit_date').value;
            const visitors = parseInt(document.getElementById('visitors_count').value) || 1;
            const vehicleSelect = document.getElementById('vehicle_id');
            const vehicleType = vehicleSelect.options[vehicleSelect.selectedIndex].getAttribute('data-type');
            
            // Calculate vehicle fee
            selectedVehicleFee = 0;
            if (vehicleType === 'Jeep') selectedVehicleFee = 5000;
            else if (vehicleType === 'Van') selectedVehicleFee = 8000;
            else if (vehicleType === 'Bus') selectedVehicleFee = 12000;
            
            // Calculate days
            let days = 1;
            if (entryDate && exitDate) {
                const start = new Date(entryDate);
                const end = new Date(exitDate);
                days = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)));
            }
            
            // Calculate totals
            const parkTotal = selectedParkFee * visitors * days;
            const total = parkTotal + selectedVehicleFee;
            
            // Update display
            document.getElementById('parkFee').textContent = 'Rs. ' + parkTotal.toFixed(2);
            document.getElementById('vehicleFee').textContent = 'Rs. ' + selectedVehicleFee.toFixed(2);
            document.getElementById('duration').textContent = days + ' day' + (days > 1 ? 's' : '');
            document.getElementById('totalAmount').textContent = 'Rs. ' + total.toFixed(2);
        }
    </script>
</body>
</html>