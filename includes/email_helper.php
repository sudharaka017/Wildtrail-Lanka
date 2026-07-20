<?php
// includes/email_helper.php - Email Notification Helper

function sendBookingConfirmation($toEmail, $toName, $bookingDetails) {
    $subject = "🦁 WildTrail Lanka - Booking Confirmation #" . str_pad($bookingDetails['id'], 4, '0', STR_PAD_LEFT);
    
    // Email headers
    $headers = "From: WildTrail Lanka <bookings@wildtraillanka.com>\r\n";
    $headers .= "Reply-To: bookings@wildtraillanka.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Email body
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .body { padding: 30px; }
            .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
            .detail-label { color: #888; }
            .detail-value { font-weight: bold; color: #333; }
            .total { background: #f0f9f0; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: center; }
            .total-amount { font-size: 24px; font-weight: bold; color: #1a5f2a; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #888; font-size: 12px; }
            .status-pending { color: #856404; }
            .status-confirmed { color: #155724; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🦁 WildTrail Lanka</h1>
                <p style="margin: 10px 0 0;">Booking Confirmation</p>
            </div>
            <div class="body">
                <p>Hello <strong>' . htmlspecialchars($toName) . '</strong>,</p>
                <p>Thank you for booking with WildTrail Lanka! Here are your booking details:</p>
                
                <div class="detail-row">
                    <span class="detail-label">Booking ID</span>
                    <span class="detail-value">#' . str_pad($bookingDetails['id'], 4, '0', STR_PAD_LEFT) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">National Park</span>
                    <span class="detail-value">' . htmlspecialchars($bookingDetails['park_name']) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Safari Dates</span>
                    <span class="detail-value">' . date('M d, Y', strtotime($bookingDetails['entry_date'])) . ' - ' . date('M d, Y', strtotime($bookingDetails['exit_date'])) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Visitors</span>
                    <span class="detail-value">' . $bookingDetails['visitors_count'] . ' people</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value status-' . $bookingDetails['status'] . '">' . ucfirst($bookingDetails['status']) . '</span>
                </div>
                
                <div class="total">
                    <p style="margin: 0 0 5px; color: #888;">Total Amount</p>
                    <div class="total-amount">Rs. ' . number_format($bookingDetails['total_amount'], 2) . '</div>
                </div>
                
                <p style="margin-top: 20px; color: #666; font-size: 14px;">
                    <i>Please bring this confirmation to the park entrance. You can view your full ticket with QR code by logging into your account.</i>
                </p>
            </div>
            <div class="footer">
                <p>WildTrail Lanka Tourism Booking System</p>
                <p>Discover the wild beauty of Sri Lanka</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Try to send email
    $sent = mail($toEmail, $subject, $message, $headers);
    
    return [
        'sent' => $sent,
        'subject' => $subject,
        'body' => $message,
        'to' => $toEmail
    ];
}

function sendStatusUpdateEmail($toEmail, $toName, $bookingDetails, $newStatus) {
    $subject = "🦁 WildTrail Lanka - Booking Status Update #" . str_pad($bookingDetails['id'], 4, '0', STR_PAD_LEFT);
    
    $headers = "From: WildTrail Lanka <bookings@wildtraillanka.com>\r\n";
    $headers .= "Reply-To: bookings@wildtraillanka.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $statusMessages = [
        'confirmed' => 'Great news! Your booking has been <strong>CONFIRMED</strong>.',
        'completed' => 'Your safari has been marked as <strong>COMPLETED</strong>. Thank you for visiting!',
        'cancelled' => 'Your booking has been <strong>CANCELLED</strong>. Contact us for more information.'
    ];
    
    $statusMsg = $statusMessages[$newStatus] ?? 'Your booking status has been updated.';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%); color: white; padding: 30px; text-align: center; }
            .body { padding: 30px; }
            .status-box { background: #f0f9f0; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #888; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🦁 WildTrail Lanka</h1>
                <p>Booking Status Update</p>
            </div>
            <div class="body">
                <p>Hello <strong>' . htmlspecialchars($toName) . '</strong>,</p>
                <div class="status-box">
                    <h3 style="color: #1a5f2a; margin: 0;">' . $statusMsg . '</h3>
                    <p style="margin: 10px 0 0; color: #666;">Booking #' . str_pad($bookingDetails['id'], 4, '0', STR_PAD_LEFT) . '</p>
                </div>
                <p style="color: #666;">Park: <strong>' . htmlspecialchars($bookingDetails['park_name']) . '</strong></p>
                <p style="color: #666;">Dates: ' . date('M d, Y', strtotime($bookingDetails['entry_date'])) . ' - ' . date('M d, Y', strtotime($bookingDetails['exit_date'])) . '</p>
            </div>
            <div class="footer">
                <p>WildTrail Lanka Tourism Booking System</p>
            </div>
        </div>
    </body>
    </html>';
    
    $sent = mail($toEmail, $subject, $message, $headers);
    
    return [
        'sent' => $sent,
        'subject' => $subject,
        'body' => $message,
        'to' => $toEmail
    ];
}
?>