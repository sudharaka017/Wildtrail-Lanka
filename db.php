<?php
// db.php - Database Connection File
// This file connects to MySQL database

// Step 1: Define database settings
$host = "localhost";     // Where the database lives (your computer)
$dbname = "wildtrail_db"; // The database name we created
$username = "root";      // Default XAMPP username
$password = "";          // Default XAMPP password (empty)

// Step 2: Try to connect
try {
    // Create a new PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set error mode to show problems
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array (easier to use)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // If connection fails, show error and stop
    die("Database connection failed: " . $e->getMessage());
}

// Step 3: Start session (needed for login system)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// This file is now ready to be included in other pages
?>