<?php
// 404.php - Page Not Found
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - WildTrail Lanka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            opacity: 0.3;
            line-height: 1;
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .btn-home {
            background: white;
            color: #1a5f2a;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-home:hover {
            background: #f0f0f0;
            color: #1a5f2a;
        }
    </style>
</head>
<body>
    <div>
        <div class="error-icon">🦁</div>
        <div class="error-code">404</div>
        <h2 class="mb-3">Page Not Found</h2>
        <p class="opacity-75">Oops! It seems you've wandered off the trail.</p>
        <a href="index.php" class="btn-home">
            <i class="bi bi-house"></i> Back to Home
        </a>
    </div>
</body>
</html>