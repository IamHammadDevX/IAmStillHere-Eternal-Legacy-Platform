<?php

require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailHelper {
    
    // --- SMTP CONFIGURATION CONSTANTS ---
    // IMPORTANT: Replace these placeholder values with your actual SMTP server settings.
    // For security, it's best to load these from a secure configuration file (e.g., .env).
    private const SMTP_HOST = ''; // E.g., 'smtp.gmail.com' or 'smtp.sendgrid.net'
    private const SMTP_USERNAME = ''; 
    private const SMTP_PASSWORD = ''; 
    private const SMTP_PORT = 587; // Common ports: 587 (TLS/STARTTLS) or 465 (SSL/SMTPS)
    // Use PHPMailer::ENCRYPTION_STARTTLS for port 587 or PHPMailer::ENCRYPTION_SMTPS for port 465
    private const SMTP_SECURE = PHPMailer::ENCRYPTION_STARTTLS; 

    // Sender details
    private const FROM_EMAIL = 'noreply@iamalwayshere.com';
    private const FROM_NAME = 'IamAlwaysHere';


    public static function sendVerificationEmail($toEmail, $toName, $verificationCode) {
        
        $mail = new PHPMailer(true); // Passing 'true' enables exceptions for error handling
        
        $subject = "Verify Your Email - IamAlwaysHere";
        
        // --- Existing HTML Message (no change to content, just moved to a variable) ---
        $html_message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .code-box { background: white; border: 2px dashed #667eea; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Verification</h1>
                </div>
                <div class='content'>
                    <p>Hello $toName,</p>
                    <p>Thank you for registering. Please use the code below:</p>
                    
                    <div class='code-box'>
                        <p style='margin: 0; font-size: 14px; color: #666;'>Your Verification Code</p>
                        <div class='code'>$verificationCode</div>
                        <p style='margin: 10px 0 0 0; font-size: 12px; color: #999;'>Valid for 15 minutes</p>
                    </div>
                    
                    <p>Enter this code on the verification page to activate your account.</p>
                    <p><strong>Important:</strong> If you didn't request this registration, please ignore this email.</p>
                    
                    <p style='margin-top: 30px;'>Best regards,<br>The IamAlwaysHere Team</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " IamAlwaysHere. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text alternative for non-HTML clients
        $alt_body = "Hello $toName,\n\nThank you for registering. Please use the following code to verify your email:\n\nYour Verification Code: $verificationCode\n\nValid for 15 minutes.\n\nEnter this code on the verification page to activate your account.\n\nImportant: If you didn't request this registration, please ignore this email.\n\nBest regards,\nThe IamAlwaysHere Team";
        
        try {
            // --- Server settings ---
            $mail->isSMTP(); // Send using SMTP
            $mail->Host       = self::SMTP_HOST; // Set the SMTP server
            $mail->SMTPAuth   = true; // Enable SMTP authentication                               
            $mail->Username   = self::SMTP_USERNAME; // SMTP username             
            $mail->Password   = self::SMTP_PASSWORD; // SMTP password                         
            $mail->SMTPSecure = self::SMTP_SECURE; // Enable TLS encryption (or SMTPS)
            $mail->Port       = self::SMTP_PORT; // TCP port to connect to                                    
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment to enable verbose debug output
            
            // --- Recipients ---
            $mail->setFrom(self::FROM_EMAIL, self::FROM_NAME);
            $mail->addAddress($toEmail, $toName); // Add a recipient

            // --- Content ---
            $mail->isHTML(true); // Set email format to HTML                                  
            $mail->Subject = $subject;
            $mail->Body    = $html_message;
            $mail->AltBody = $alt_body; // Plain text alternative

            $mail->send();
            return true; // Email sent successfully

        } catch (Exception $e) {
            // Log or handle the error
            // error_log("Mailer Error for $toEmail: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function generateVerificationCode() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}