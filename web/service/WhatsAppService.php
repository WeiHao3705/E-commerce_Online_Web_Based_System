<?php

/**
 * WhatsApp Service for sending notifications
 * Supports multiple WhatsApp API providers (Twilio, WhatsApp Business API, etc.)
 */
class WhatsAppService {
    private $conn;
    private $apiUrl;
    private $apiKey;
    private $authToken;
    private $fromNumber;
    private $provider;
    private $enabled;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadConfig();
    }
    
    /**
     * Load WhatsApp configuration from config file, database, or environment
     */
    private function loadConfig() {
        // Try to load from config file first
        $configFile = __DIR__ . '/../config/whatsapp_config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $this->enabled = $config['enabled'] ?? false;
            $this->apiUrl = $config['api_url'] ?? '';
            $this->apiKey = $config['api_key'] ?? '';
            $this->authToken = $config['auth_token'] ?? $config['api_key'] ?? '';
            $this->fromNumber = $config['from_number'] ?? '';
            $this->provider = $config['provider'] ?? 'twilio';
        } else {
            // Try to load from database config table (if exists)
            try {
                $sql = "SELECT config_value FROM system_config WHERE config_key = 'whatsapp_enabled' LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->enabled = ($result && $result['config_value'] === '1');
            } catch (Exception $e) {
                $this->enabled = false;
            }
            
            // Load API configuration from database
            try {
                $sql = "SELECT config_key, config_value FROM system_config WHERE config_key IN ('whatsapp_api_url', 'whatsapp_api_key', 'whatsapp_auth_token', 'whatsapp_from_number', 'whatsapp_provider')";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                
                $this->apiUrl = $configs['whatsapp_api_url'] ?? '';
                $this->apiKey = $configs['whatsapp_api_key'] ?? '';
                $this->authToken = $configs['whatsapp_auth_token'] ?? $configs['whatsapp_api_key'] ?? '';
                $this->fromNumber = $configs['whatsapp_from_number'] ?? '';
                $this->provider = $configs['whatsapp_provider'] ?? 'twilio';
            } catch (Exception $e) {
                // Use environment variables or defaults
                $this->apiUrl = $_ENV['WHATSAPP_API_URL'] ?? '';
                $this->apiKey = $_ENV['WHATSAPP_API_KEY'] ?? '';
                $this->authToken = $_ENV['WHATSAPP_AUTH_TOKEN'] ?? $_ENV['WHATSAPP_API_KEY'] ?? '';
                $this->fromNumber = $_ENV['WHATSAPP_FROM_NUMBER'] ?? '';
                $this->provider = $_ENV['WHATSAPP_PROVIDER'] ?? 'twilio';
            }
        }
    }
    
    /**
     * Send WhatsApp message
     * @param string $toPhoneNumber Phone number in international format (e.g., +60123456789)
     * @param string $message Message to send
     * @return array Result with success status and message
     */
    public function sendMessage($toPhoneNumber, $message) {
        if (!$this->enabled) {
            return [
                'success' => false,
                'error' => 'WhatsApp notifications are disabled'
            ];
        }
        
        if (empty($this->apiUrl) || empty($this->apiKey) || empty($this->fromNumber)) {
            return [
                'success' => false,
                'error' => 'WhatsApp API configuration is missing'
            ];
        }
        
        // Normalize phone number (ensure it starts with +)
        $normalizedPhone = $this->normalizePhoneNumber($toPhoneNumber);
        if (!$normalizedPhone) {
            return [
                'success' => false,
                'error' => 'Invalid phone number format'
            ];
        }
        
        try {
            // Use Twilio-style API (can be adapted for other providers)
            $result = $this->sendViaTwilio($normalizedPhone, $message);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to send WhatsApp message: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send message via API (Twilio, Generic, etc.)
     */
    private function sendViaTwilio($toPhoneNumber, $message) {
        $url = $this->apiUrl;
        
        if ($this->provider === 'twilio' || strpos($url, 'twilio.com') !== false) {
            // Twilio WhatsApp API
            $accountSid = $this->apiKey;
            $authToken = $this->authToken;
            
            // Replace {AccountSid} placeholder if present
            $url = str_replace('{AccountSid}', $accountSid, $url);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ':' . $authToken);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'From' => 'whatsapp:' . $this->fromNumber,
                'To' => 'whatsapp:' . $toPhoneNumber,
                'Body' => $message
            ]));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return [
                    'success' => false,
                    'error' => 'CURL error: ' . $curlError
                ];
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'message' => 'WhatsApp message sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'WhatsApp API returned error (HTTP ' . $httpCode . '): ' . $response
                ];
            }
        } else {
            // Generic HTTP POST API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'to' => $toPhoneNumber,
                'from' => $this->fromNumber,
                'message' => $message
            ]));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return [
                    'success' => false,
                    'error' => 'CURL error: ' . $curlError
                ];
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'message' => 'WhatsApp message sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'WhatsApp API returned error (HTTP ' . $httpCode . '): ' . $response
                ];
            }
        }
    }
    
    /**
     * Normalize phone number to international format
     * @param string $phoneNumber Phone number in any format
     * @return string|false Normalized phone number with + prefix, or false if invalid
     */
    private function normalizePhoneNumber($phoneNumber) {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // If it doesn't start with +, assume it's a local number and add country code
        if (substr($cleaned, 0, 1) !== '+') {
            // Default to Malaysia country code (+60) if no country code
            // Remove leading 0 if present (common in Malaysian numbers)
            if (substr($cleaned, 0, 1) === '0') {
                $cleaned = substr($cleaned, 1);
            }
            $cleaned = '+60' . $cleaned;
        }
        
        // Validate: should be 10-15 digits after +
        $digits = substr($cleaned, 1);
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return false;
        }
        
        return $cleaned;
    }
    
    /**
     * Enable or disable WhatsApp notifications
     */
    public function setEnabled($enabled) {
        $this->enabled = (bool)$enabled;
        // Optionally save to database
        try {
            $sql = "INSERT INTO system_config (config_key, config_value) VALUES ('whatsapp_enabled', :value)
                    ON DUPLICATE KEY UPDATE config_value = :value";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':value' => $enabled ? '1' : '0']);
        } catch (Exception $e) {
            // Config table might not exist, ignore
        }
    }
}

