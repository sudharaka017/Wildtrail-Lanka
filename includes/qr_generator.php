<?php
// includes/qr_generator.php - QR Code Generator for Bookings
// This generates a QR code image using Google's Chart API (no installation needed)

function generateQRCode($data, $size = 200) {
    // Use Google Chart API to generate QR code
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
    return $url;
}

function getBookingQRData($booking) {
    // Create formatted data for QR code
    $data = "WILDTRAIL BOOKING\n";
    $data .= "==================\n";
    $data .= "Booking ID: #" . str_pad($booking['id'], 4, '0', STR_PAD_LEFT) . "\n";
    $data .= "Park: " . ($booking['park_name'] ?? 'N/A') . "\n";
    $data .= "Visitor: " . ($booking['visitor_name'] ?? 'N/A') . "\n";
    $data .= "Dates: " . date('M d, Y', strtotime($booking['entry_date'])) . " - " . date('M d, Y', strtotime($booking['exit_date'])) . "\n";
    $data .= "Visitors: " . ($booking['visitors_count'] ?? 'N/A') . "\n";
    $data .= "Amount: Rs. " . number_format($booking['total_amount'] ?? 0, 2) . "\n";
    $data .= "Status: " . ucfirst($booking['status'] ?? 'N/A') . "\n";
    $data .= "==================\n";
    $data .= "Verified by WildTrail Lanka";
    return $data;
}
?>