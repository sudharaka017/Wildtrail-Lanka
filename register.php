<?php
// register.php - Visitor Registration Page
// Include the database connection file
require_once 'db.php';

// Variable to show success or error messages
$message = '';
$messageType = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get the data from the form and clean it
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation: Check if any field is empty
    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        $message = 'Please fill in all fields!';
        $messageType = 'danger';
    }
    // Validation: Check if passwords match
    elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match!';
        $messageType = 'danger';
    }
    // Validation: Check password length
    elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters!';
        $messageType = 'danger';
    }
    // Validation: Check email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address!';
        $messageType = 'danger';
    }
    else {
        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->rowCount() > 0) {
            $message = 'This email is already registered. Please login!';
            $messageType = 'danger';
        } else {
            // Hash the password for security (very important!)
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert the new user into database
            $insertStmt = $pdo->prepare("
                INSERT INTO users (full_name, email, phone, password, role) 
                VALUES (?, ?, ?, ?, 'visitor')
            ");
            
            try {
                $insertStmt->execute([$fullName, $email, $phone, $hashedPassword]);
                
                $message = 'Registration successful! You can now login.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Something went wrong. Please try again.';
                $messageType = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - WildTrail Lanka</title>
    <!-- Bootstrap 5 CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .logo-text {
            color: #1a5f2a;
            font-weight: bold;
            font-size: 28px;
            text-align: center;
            margin-bottom: 10px;
        }
        .tagline {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .btn-register {
            background-color: #1a5f2a;
            border: none;
            color: white;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
        }
        .btn-register:hover {
            background-color: #144a20;
        }
        .form-control:focus {
            border-color: #1a5f2a;
            box-shadow: 0 0 0 0.2rem rgba(26, 95, 42, 0.25);
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #1a5f2a;
            text-decoration: none;
            font-weight: bold;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="register-card">
        <!-- Logo -->
        <div class="logo-text">🦁 WildTrail Lanka</div>
        <div class="tagline">Join the Adventure</div>
        
        <!-- Show message if there is one -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Registration Form -->
        <form method="POST" action="register.php" novalidate>
            
            <!-- Full Name -->
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" 
                       class="form-control" 
                       id="full_name" 
                       name="full_name" 
                       placeholder="Enter your full name"
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                       required>
            </div>
            
            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       placeholder="yourname@email.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required>
            </div>
            
            <!-- Phone -->
            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" 
                       class="form-control" 
                       id="phone" 
                       name="phone" 
                       placeholder="077 123 4567"
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                       required>
            </div>
            
            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="At least 6 characters"
                       required>
            </div>
            
            <!-- Confirm Password -->
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" 
                       class="form-control" 
                       id="confirm_password" 
                       name="confirm_password" 
                       placeholder="Type password again"
                       required>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn-register">Create Account</button>
            
        </form>
        
        <!-- Login Link -->
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>