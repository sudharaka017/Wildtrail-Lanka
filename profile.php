<?php
// profile.php - Visitor Profile Page
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
$message = '';
$messageType = '';

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$visitorId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = null;
}

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    if (empty($fullName)) {
        $message = 'Full name is required!';
        $messageType = 'danger';
    } else {
        try {
            // Update name and phone
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone, $visitorId]);
            
            // Update password if provided
            if (!empty($currentPassword) && !empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    $message = 'New password must be at least 6 characters!';
                    $messageType = 'danger';
                } elseif (password_verify($currentPassword, $user['password'])) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $visitorId]);
                    $message = 'Profile and password updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Current password is incorrect!';
                    $messageType = 'danger';
                }
            } else {
                $message = 'Profile updated successfully!';
                $messageType = 'success';
            }
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$visitorId]);
            $user = $stmt->fetch();
            $_SESSION['user_name'] = $user['full_name'];
            
        } catch (PDOException $e) {
            $message = 'Error updating profile!';
            $messageType = 'danger';
        }
    }
}

// Get booking stats
try {
    $totalBookings = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE visitor_id = ?");
    $totalBookings->execute([$visitorId]);
    $bookingCount = $totalBookings->fetchColumn();
    
    $totalSpent = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE visitor_id = ? AND payment_status = 'paid'");
    $totalSpent->execute([$visitorId]);
    $spentAmount = $totalSpent->fetchColumn();
} catch (PDOException $e) {
    $bookingCount = 0;
    $spentAmount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - WildTrail Lanka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-green: #1a5f2a;
            --light-green: #2d8a3e;
            --dark-green: #144a20;
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
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            color: white;
            padding: 50px 0;
            text-align: center;
            margin-bottom: 40px;
            border-radius: 0 0 30px 30px;
        }
        
        .avatar-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            color: var(--primary-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
            margin: 0 auto 20px;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 25px;
        }
        
        .profile-card h4 {
            color: var(--primary-green);
            margin-bottom: 25px;
            font-weight: bold;
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(26, 95, 42, 0.25);
        }
        
        .btn-update {
            background: var(--primary-green);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
        }
        
        .btn-update:hover {
            background: var(--dark-green);
        }
        
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: var(--dark-green);
        }
        
        .stat-label {
            color: #888;
            font-size: 14px;
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
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="avatar-circle">
            <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
        </div>
        <h2><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></h2>
        <p class="mb-0 opacity-75"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
        <span class="badge bg-light text-dark mt-2">
            <i class="bi bi-person"></i> Visitor
        </span>
    </div>

    <div class="container">
        
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $bookingCount; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="stat-box">
                    <div class="stat-number">Rs. <?php echo number_format($spentAmount, 0); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>
        </div>

        <!-- Edit Profile -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="profile-card">
                    <h4><i class="bi bi-pencil-square"></i> Edit Profile</h4>
                    
                    <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show mb-4" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="profile.php">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email (cannot change)</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                            <small class="text-muted">Contact admin to change email</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3 text-muted">Change Password (optional)</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" 
                                   placeholder="Enter current password to change">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" 
                                   placeholder="Min 6 characters">
                        </div>
                        
                        <button type="submit" class="btn-update">
                            <i class="bi bi-check-circle"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once 'includes/footer.php'; ?>

</body>
</html>