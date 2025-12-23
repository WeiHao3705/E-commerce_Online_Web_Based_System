<?php

require_once __DIR__ . '/../../vendor/autoload.php';
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

class TwoFactorService
{
    private $tfa;
    private $issuer;

    public function __construct()
    {
        $this->issuer = 'NGEAR Admin';
        // Version 3.0 requires QR code provider as first parameter
        // Using QRServerProvider (uses goqr.me API, more reliable than Google Charts)
        // First parameter: verifyssl (false for local dev, true for production)
        $qrCodeProvider = new QRServerProvider(false);
        $this->tfa = new TwoFactorAuth($qrCodeProvider, $this->issuer);
    }

    /**
     * Generate a new TOTP secret for a user
     */
    public function generateSecret(): string
    {
        return $this->tfa->createSecret();
    }

    /**
     * Get QR code data URL for Google Authenticator setup
     */
    public function getQRCodeDataUrl(string $secret, string $username): string
    {
        try {
            $label = $this->issuer . ':' . $username;
            $qrCodeUri = $this->tfa->getQRCodeImageAsDataUri($label, $secret);
            
            // Validate the data URI format
            if (empty($qrCodeUri) || strpos($qrCodeUri, 'data:image') !== 0) {
                error_log("QR code URI invalid format: " . substr($qrCodeUri, 0, 100));
                return '';
            }
            
            // Check if base64 data is present
            if (strpos($qrCodeUri, 'base64,') === false) {
                error_log("QR code URI missing base64 data");
                return '';
            }
            
            return $qrCodeUri;
        } catch (Exception $e) {
            error_log("QR code generation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            // Return empty string if QR code generation fails
            // The manual entry code will still work
            return '';
        }
    }

    /**
     * Get manual entry code (for backup if QR code doesn't work)
     * Returns the full otpauth URI
     */
    public function getManualEntryCode(string $secret, string $username): string
    {
        $label = $this->issuer . ':' . $username;
        return $this->tfa->getQRText($label, $secret);
    }

    /**
     * Get just the secret key for manual entry (Google Authenticator format)
     * Returns the base32 secret without the otpauth:// URI
     */
    public function getSecretKey(string $secret): string
    {
        return $secret;
    }

    /**
     * Verify a TOTP code against a secret
     * Accepts codes from current and previous time window for clock drift tolerance
     */
    public function verifyCode(string $secret, string $code): bool
    {
        try {
            // Verify with tolerance of 1 time window (30 seconds) on each side
            return $this->tfa->verifyCode($secret, $code, 1);
        } catch (Exception $e) {
            error_log("2FA verification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Encrypt secret before storing in database
     */
    public function encryptSecret(string $secret): string
    {
        // Use a simple encryption key - in production, store this in environment variables
        $key = $this->getEncryptionKey();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($secret, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt secret from database
     */
    public function decryptSecret(string $encryptedSecret): string
    {
        try {
            $key = $this->getEncryptionKey();
            $data = base64_decode($encryptedSecret);
            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
        } catch (Exception $e) {
            error_log("2FA decryption error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Get encryption key (in production, use environment variable)
     */
    private function getEncryptionKey(): string
    {
        // For production, use: return $_ENV['2FA_ENCRYPTION_KEY'] ?? '';
        // For now, use a default key - CHANGE THIS IN PRODUCTION
        $defaultKey = 'your-secret-encryption-key-change-in-production-32chars';
        return hash('sha256', $defaultKey, true);
    }
}

