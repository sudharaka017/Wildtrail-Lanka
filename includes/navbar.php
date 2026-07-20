<?php
// includes/navbar.php - Reusable Navigation Bar
// This file should be included at the top of every visitor page
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div class="container">
        <a class="navbar-brand" href="index.php" style="font-weight: bold; font-size: 22px;">
            <i class="bi bi-globe-asia-australia"></i> WildTrail Lanka
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'book_ticket.php' ? 'active' : ''; ?>" href="book_ticket.php">
                        <i class="bi bi-ticket-perforated"></i> Book Safari
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_bookings.php' ? 'active' : ''; ?>" href="my_bookings.php">
                        <i class="bi bi-calendar-check"></i> My Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
            <span class="navbar-text ms-3 text-white">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?>
            </span>
        </div>
    </div>
</nav>