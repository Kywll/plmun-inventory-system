<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Adjust path based on where you put the PHPMailer folder
require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

/**
 * Sends a notification email to the user using Gmail SMTP.
 * 
 * @param string $to Email address of the recipient
 * @param string $subject Subject of the email
 * @param string $message Body of the email (HTML supported)
 * @return bool True if mail was sent, false otherwise
 */
function send_notification_email($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'plmunsupplyinventorymanagement@gmail.com';
        $mail->Password   = 'APP_PASSWORD';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('plmunsupplyinventorymanagement@gmail.com', 'PLMun Inventory System');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error if needed: error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Generates an HTML email template for status updates.
 */
function get_status_email_template($name, $item_name, $status, $action_message) {
    return "
    <html>
    <head>
        <style>
            .container { font-family: Arial, sans-serif; padding: 20px; color: #333; }
            .header { background: #198754; color: white; padding: 10px; border-radius: 5px; text-align: center; }
            .content { margin-top: 20px; line-height: 1.6; }
            .footer { margin-top: 30px; font-size: 12px; color: #777; border-top: 1px solid #ddd; padding-top: 10px; }
            .status-badge { display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: bold; }
            .Approved { background: #d1e7dd; color: #0f5132; }
            .Declined { background: #f8d7da; color: #842029; }
            .Completed { background: #cfe2ff; color: #084298; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>PLMun Supply Inventory System</h2>
            </div>
            <div class='content'>
                <p>Hello <strong>$name</strong>,</p>
                <p>The status of your request for <strong>$item_name</strong> has been updated to:</p>
                <p class='status-badge $status'>$status</p>
                <p>$action_message</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply.</p>
                <p>&copy; 2026 PLMun Supply Inventory Management</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
