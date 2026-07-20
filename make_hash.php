<?php
// make_hash.php - Creates a password hash for admin
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password: admin123<br>";
echo "Hash: " . $hash . "<br><br>";
echo "Copy the hash above and update the database.";
?>