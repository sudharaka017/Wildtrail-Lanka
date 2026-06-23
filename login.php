<?php
// login.php - User Login Page
// Include the database connection
require_once 'db.php';

// Variable for messages
$message = '';
$messageType = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get email and password from form
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Check if fields are empty
    if (empty($email) || empty($password)) {
        $message = 'Please enter both email and password!';
        $messageType = 'danger';
    } else {
        // Look for user in database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Check if user exists and password is correct
        if ($user && password_verify($password, $user['password'])) {
            
            // Save user info in session (like a memory while browsing)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect to correct dashboard based on role
            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'driver':
                    header('Location: driver/dashboard.php');
                    break;
                case 'guide':
                    header('Location: guide/dashboard.php');
                    break;
                case 'visitor':
                default:
                    header('Location: index.php');
                    break;
            }
            exit; // Stop the script after redirect
            
        } else {
            $message = 'Invalid email or password!';
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
    <title>Login - WildTrail Lanka</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
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
        .btn-login {
            background-color: #1a5f2a;
            border: none;
            color: white;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
        }
        .btn-login:hover {
            background-color: #144a20;
        }
        .form-control:focus {
            border-color: #1a5f2a;
            box-shadow: 0 0 0 0.2rem rgba(26, 95, 42, 0.25);
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #1a5f2a;
            text-decoration: none;
            font-weight: bold;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .role-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }
        .role-info strong {
            color: #1a5f2a;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Logo -->
        <div class="logo-text">🦁 WildTrail Lanka</div>
        <div class="tagline">Welcome Back, Explorer!</div>
        
        <!-- Show message if there is one -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="POST" action="login.php" novalidate>
            
            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       placeholder="yourname@email.com"
                       required>
            </div>
            
            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="Enter your password"
                       required>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" class="btn-login">Sign In</button>
            
        </form>
        
        <!-- Register Link -->
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        
        <!-- Demo Login Info -->
        <div class="role-info">
            <strong>Demo Accounts:</strong><br>
            Admin: admin@wildtrail.com / admin123<br>
            (Register as visitor, or create other roles in database)
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>