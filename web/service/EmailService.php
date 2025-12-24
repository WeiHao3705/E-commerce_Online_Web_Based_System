<?php
require_once __DIR__ . '/../lib/PHPMailer.php';
require_once __DIR__ . '/../lib/SMTP.php';

class EmailService {
    public function sendOtpEmail($toEmail, $toName, $otp) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = 'Your Password Reset OTP - NGEAR';
            $mail->Body = '<p>Dear ' . htmlspecialchars($toName) . ',</p>' .
                '<p>Your OTP for password reset is: <b style="font-size:22px;">' . htmlspecialchars($otp) . '</b></p>' .
                '<p>This OTP is valid for 3 minutes. If you did not request a password reset, please ignore this email.</p>' .
                '<p>Thank you,<br>NGEAR Sports Store</p>';
            $mail->AltBody = 'Your OTP for password reset is: ' . $otp;
            $mail->send();
        } catch (Exception $e) {
            error_log('Failed to send OTP email: ' . $e->getMessage());
        }
    }
    private $smtpHost = 'smtp.gmail.com';
    private $smtpUsername = '6403360weihao@gmail.com';
    private $smtpPassword = 'kkch bjlp clpk kfyw';
    private $smtpPort = 587;
    private $fromEmail = 'info@ngear.com';
    private $fromName = 'NGEAR Sports Store';

    public function sendVerificationEmail($toEmail, $toName, $verificationToken) {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';
            
            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $toName);
            
            // Get base URL dynamically
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            
            // Calculate base path dynamically (same method as views)
            // EmailService is in web/service/, so go up one level to get web root
            $currentFileDir = dirname(__FILE__); // Gets web/service/
            $webRootDir = dirname($currentFileDir); // Gets web/
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
            
            if ($docRoot) {
                $relativePath = str_replace($docRoot, '', $webRootDir);
                $webBasePath = str_replace('\\', '/', $relativePath);
                // Ensure path starts with / and ends with /
                if (substr($webBasePath, 0, 1) !== '/') {
                    $webBasePath = '/' . $webBasePath;
                }
                if (substr($webBasePath, -1) !== '/') {
                    $webBasePath .= '/';
                }
            } else {
                // Fallback to hardcoded path if DOCUMENT_ROOT is not available
                $webBasePath = '/E-commerce_Online_Web_Based_System/web/';
            }
            
            // Use secure verification endpoint with shorter parameter name
            $verificationUrl = $protocol . '://' . $host . $webBasePath . 'verify-email.php?t=' . urlencode($verificationToken);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email Address - NGEAR';
            $mail->Body = $this->getVerificationEmailTemplate($toName, $verificationUrl);
            $mail->AltBody = "Hello {$toName},\n\nPlease verify your email by clicking this link:\n{$verificationUrl}\n\nIf you didn't create an account, please ignore this email.\n\nBest regards,\nNGEAR Team";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
            throw new Exception("Failed to send verification email: " . $mail->ErrorInfo);
        }
    }
    
    private function getVerificationEmailTemplate($name, $verificationUrl) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #FF523B; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 12px 30px; background-color: #FF523B; color: #ffffff !important; text-decoration: none !important; border-radius: 5px; margin: 20px 0; font-weight: 500; }
                .button:link, .button:visited, .button:hover, .button:active { color: #ffffff !important; text-decoration: none !important; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>NGEAR</h1>
                    <p>Email Verification</p>
                </div>
                <div class='content'>
                    <h2>Hello {$name}!</h2>
                    <p>Thank you for registering with NGEAR. Please verify your email address to complete your registration.</p>
                    <p style='text-align: center;'>
                        <a href='{$verificationUrl}' class='button' style='color: #ffffff; text-decoration: none;'>Verify Email Address</a>
                    </p>
                    <p><strong>This link will expire in 24 hours.</strong></p>
                    <p>If you didn't create an account with NGEAR, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " NGEAR. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}