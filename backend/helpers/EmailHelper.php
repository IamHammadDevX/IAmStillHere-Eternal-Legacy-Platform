<?php

require_once __DIR__ . '/../phpmailer/src/Exception.php';
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper
{
    private const REQUIRED_MAIL_VARIABLES = [
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
        'MAIL_ENCRYPTION',
    ];

    public static function validateConfiguration(): array
    {
        $missing = [];

        foreach (self::REQUIRED_MAIL_VARIABLES as $variable) {
            $value = getenv($variable);
            if ($value === false || $value === '') {
                $missing[] = $variable;
            }
        }

        return [
            'valid' => count($missing) === 0,
            'missing' => $missing,
        ];
    }

    private static function envValue(string $name, ?string $fallback = null): ?string
    {
        $value = getenv($name);
        if ($value === false || trim((string)$value) === '') {
            return $fallback;
        }
        return trim((string)$value);
    }


    private static function appUrl(): string
    {
        $url = self::envValue('APP_URL');
        if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
            return rtrim($url, '/');
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'iamalwayshere.com';
        $host = preg_replace('/:\d+$/', '', (string) $host);
        if (!preg_match('/^[a-z0-9.-]+$/i', $host)) {
            $host = 'iamalwayshere.com';
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
        return $scheme . '://' . $host;
    }
    private static function defaultFromAddress(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'iamalwayshere.com';
        $host = preg_replace('/:\d+$/', '', (string)$host);
        if (!preg_match('/^[a-z0-9.-]+$/i', $host)) {
            $host = 'iamalwayshere.com';
        }
        return 'noreply@' . $host;
    }

    private static function configureMailer(PHPMailer $mail): bool
    {
        $fromAddress = self::envValue('MAIL_FROM_ADDRESS', defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : self::defaultFromAddress());
        $fromName = self::envValue('MAIL_FROM_NAME', defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'IamAlwaysHere');

        if (!$fromAddress || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $fromAddress = self::defaultFromAddress();
        }

        $host = self::envValue('MAIL_HOST');
        $username = self::envValue('MAIL_USERNAME');
        $password = self::envValue('MAIL_PASSWORD');
        $port = self::envValue('MAIL_PORT');

        if ($host && $username && $password && $port) {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->Port = (int)$port;

            $encryption = strtolower((string) self::envValue('MAIL_ENCRYPTION', 'tls'));
            if ($encryption === 'tls' || $encryption === 'starttls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl' || $encryption === 'smtps') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'none') {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            } else {
                error_log('Mail configuration has unsupported MAIL_ENCRYPTION value.');
                return false;
            }
        } else {
            // cPanel fallback: use server mail transport when SMTP env vars are not configured.
            $mail->isMail();
        }

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->setFrom($fromAddress, $fromName);
        return true;
    }
    public static function sendVerificationEmail($toEmail, $toName, $verificationCode)
    {
        $mail = new PHPMailer(true);
        $subject = "Verify Your Email - IamAlwaysHere";

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

        $alt_body = "Hello $toName,\n\nThank you for registering. Please use the following code to verify your email:\n\nYour Verification Code: $verificationCode\n\nValid for 15 minutes.\n\nEnter this code on the verification page to activate your account.\n\nImportant: If you didn't request this registration, please ignore this email.\n\nBest regards,\nThe IamAlwaysHere Team";

        try {
            if (!self::configureMailer($mail)) {
                return false;
            }

            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_message;
            $mail->AltBody = $alt_body;

            $mail->send();
            return true;

        } catch (Exception $e) {
            return false;
        }
    }


    public static function sendFamilyRequestEmail($toEmail, $toName, $requesterName, $relationship, $requestId)
    {
        $mail = new PHPMailer(true);
        $subject = "Family Connection Request - IamAlwaysHere";

        // NOTE: Update URLs if not running locally
        $approveUrl = self::appUrl() . "/frontend/approve_family.php?request_id=" . $requestId . "&action=accept";
        $rejectUrl = self::appUrl() . "/frontend/approve_family.php?request_id=" . $requestId . "&action=reject";

        $html_message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .request-box { background: white; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0; border-radius: 5px; }
                .button-container { text-align: center; margin: 30px 0; }
                .button { display: inline-block; padding: 12px 30px; margin: 0 10px; text-decoration: none; border-radius: 5px; font-weight: bold; }
                .btn-accept { background: #28a745; color: white; }
                .btn-reject { background: #dc3545; color: white; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Family Connection Request</h1>
                    <p>IamAlwaysHere Memorial Platform</p>
                </div>
                <div class='content'>
                    <h2>Hello, $toName!</h2>
                    <p><strong>$requesterName</strong> would like to add you as family on IamAlwaysHere.</p>
                    
                    <div class='request-box'>
                        <p style='margin: 0;'><strong>Relationship:</strong> $relationship</p>
                        <p style='margin: 5px 0 0 0; font-size: 14px; color: #666;'>If you accept, $requesterName will be able to view your family-only content and post on your memorial page.</p>
                    </div>
                    
                    <div class='button-container'>
                        <a href='$approveUrl' class='button btn-accept'>Accept Request</a>
                        <a href='$rejectUrl' class='button btn-reject'>Decline Request</a>
                    </div>
                    
                    <p style='font-size: 14px; color: #666; text-align: center;'>You can also respond to this request by logging into your account.</p>
                    
                    <p style='margin-top: 30px;'>Best regards,<br>The IamAlwaysHere Team</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " IamAlwaysHere. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $alt_body = "Hello $toName,\n\n$requesterName would like to add you as family on IamAlwaysHere.\n\nRelationship: $relationship\n\nApprove: $approveUrl\nReject: $rejectUrl\n\nIf you accept, theyll be able to view family-only content and post on your memorial page.\n\n IamAlwaysHere Team";

        try {
            if (!self::configureMailer($mail)) {
                return false;
            }

            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_message;
            $mail->AltBody = $alt_body;

            $mail->send();
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    public static function sendPasswordResetEmail($toEmail, $toName, $resetCode, $resetToken)
    {
        $mail = new PHPMailer(true);
        $subject = "Password Reset Request - IamAlwaysHere";
        $resetUrl = self::appUrl() . "/frontend/reset_password.php?token=" . $resetToken;

        // --- HTML body ---
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
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Password Reset Request</h1>
                <p>IamAlwaysHere Memorial Platform</p>
            </div>
            <div class='content'>
                <h2>Hello, $toName!</h2>
                <p>We received a request to reset your password. Use the code below to reset your password:</p>
                
                <div class='code-box'>
                    <p style='margin: 0; font-size: 14px; color: #666;'>Your Reset Code</p>
                    <div class='code'>$resetCode</div>
                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #999;'>Valid for 30 minutes</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetUrl' class='button'>Reset Password</a>
                </div>
                
                <p><strong>Important:</strong> If you didn't request this password reset, please ignore this email. Your password will remain unchanged.</p>
                
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

        // --- Plain text version ---
        $alt_body = "Hello $toName,\n\nWe received a request to reset your password. Use the following code to reset it:\n\nReset Code: $resetCode\n\nYou can also click this link to reset your password:\n$resetUrl\n\nThis code is valid for 30 minutes.\n\nIf you didnt request this, please ignore this email.\n\nBest regards,\nThe IamAlwaysHere Team";

        try {
            // --- SMTP Configuration ---
            if (!self::configureMailer($mail)) {
                return false;
            }

            $mail->addAddress($toEmail, $toName);

            // --- Content ---
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_message;
            $mail->AltBody = $alt_body;

            $mail->send();
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    public static function sendEventNotificationEmail($toEmail, $toName, $eventDetails, $profileOwnerName)
    {
        $mail = new PHPMailer(true);
        $subject = "Event Reminder: {$eventDetails['title']} - IamAlwaysHere";

        // Event type labels
        $eventTypeLabels = [
            'birthday' => '<i class="bi bi-cake2-fill text-warning"></i> Birthday',
            'anniversary' => '<i class="bi bi-heart-fill text-danger"></i> Anniversary',
            'memorial' => '<i class="bi bi-flower1 text-info"></i> Memorial',
            'remembrance' => '<i class="bi bi-stars text-primary"></i> Remembrance',
            'celebration' => '<i class="bi bi-balloon-fill text-success"></i> Celebration',
            'other' => '<i class="bi bi-calendar-event text-secondary"></i> Event'
        ];

        $eventTypeLabel = $eventTypeLabels[$eventDetails['event_type']] ?? '<i class="bi bi-calendar-event text-secondary"></i> Event';

        $eventDate = date('l, F j, Y', strtotime($eventDetails['scheduled_date']));
        $eventTime = date('g:i A', strtotime($eventDetails['scheduled_date']));

        $message = "
            <html>
            <head>
                <meta charset='UTF-8'>
                <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .event-box { background: white; border-left: 4px solid #0dcaf0; padding: 20px; margin: 20px 0; border-radius: 5px; }
                    .event-detail { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #999; }
                    .icon { font-size: 48px; margin-bottom: 10px; }
                    i { vertical-align: middle; margin-right: 4px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <div class='icon'><i class='bi bi-calendar-event-fill'></i></div>
                        <h1>Event Reminder</h1>
                        <p>IamAlwaysHere Memorial Platform</p>
                    </div>
                    <div class='content'>
                        <h2>Hello, $toName!</h2>
                        <p>This is a reminder about an event for <strong>$profileOwnerName</strong>:</p>
                        
                        <div class='event-box'>
                            <h3 style='color: #0dcaf0; margin-top: 0;'>$eventTypeLabel</h3>
                            <h2 style='margin: 10px 0;'>{$eventDetails['title']}</h2>
                            
                            <div class='event-detail'>
                                <strong><i class='bi bi-calendar3'></i> Date:</strong> $eventDate
                            </div>
                            
                            <div class='event-detail'>
                                <strong><i class='bi bi-clock-fill'></i> Time:</strong> $eventTime
                            </div>
                            
                            " . (!empty($eventDetails['message']) ? "
                            <div class='event-detail'>
                                <strong><i class='bi bi-pencil-square'></i> Description:</strong><br>
                                {$eventDetails['message']}
                            </div>
                            " : "") . "
                        </div>
                        
                        <p style='text-align: center; margin-top: 30px;'>
                            <a href='/frontend/profile.php?user_id={$eventDetails['user_id']}' 
                            style='display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>
                                <i class='bi bi-person-circle'></i> View Profile
                            </a>
                        </p>
                        
                        <p style='margin-top: 30px;'>This is an automated reminder from IamAlwaysHere. You're receiving this because you are listed as a family member.</p>
                        
                        <p style='margin-top: 20px;'>Best regards,<br>The IamAlwaysHere Team</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " IamAlwaysHere. All rights reserved.</p>
                        <p>This is an automated email. Please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
            ";


        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: IamAlwaysHere <noreply@iamalwayshere.com>" . "\r\n";

        try {
            // --- SMTP Configuration ---
            if (!self::configureMailer($mail)) {
                return false;
            }

            $mail->addAddress($toEmail, $toName);

            // --- Content ---
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
            return true;

        } catch (Exception $e) {
            return false;
        }

        // return mail($toEmail, $subject, $message, $headers);
    }

    public static function generateVerificationCode()
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
