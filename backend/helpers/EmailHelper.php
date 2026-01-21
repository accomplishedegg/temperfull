<?php
// backend/helpers/EmailHelper.php

class EmailHelper {
    public static function sendWelcomeEmail($toEmail, $name, $password) {
        $subject = "Welcome to Temper Search - Credentials";
        $message = "Hello $name,\n\n";
        $message .= "Your account has been approved and created.\n\n";
        $message .= "Email: $toEmail\n";
        $message .= "Password: $password\n\n";
        $message .= "Please login and change your password.\n";
        $message .= "Regards,\nTemper Search Team";
        
        return self::send($toEmail, $subject, $message);
    }
    
    public static function sendOtpEmail($toEmail, $otp) {
        $subject = "Your Login OTP - Temper Search";
        $message = "Hello,\n\n";
        $message .= "Your One Time Password (OTP) for login is: $otp\n\n";
        $message .= "This OTP is valid for 10 minutes.\n";
        $message .= "Regards,\nTemper Search Team";
        
        return self::send($toEmail, $subject, $message);
    }

    private static function send($toEmail, $subject, $message) {
        $headers = 'From: ' . SMTPConfig::$FROM_NAME . ' <' . SMTPConfig::$FROM_EMAIL . '>' . "\r\n" .
            'Reply-To: ' . SMTPConfig::$FROM_EMAIL . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        // In a real environment, you might use PHPMailer with SMTP details from SMTPConfig
        // mail($toEmail, $subject, $message, $headers);
        
        // For development/debugging, we can simulate or log
        error_log("Sending Email to $toEmail: Subject: $subject Body: $message");
        
        return true; 
    }
}
?>
