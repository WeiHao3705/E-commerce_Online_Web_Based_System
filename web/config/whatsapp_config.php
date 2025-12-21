<?php
/**
 * WhatsApp Configuration
 * 
 * Configure your WhatsApp API settings here or use environment variables
 * 
 * Supported providers:
 * - Twilio WhatsApp API
 * - Generic HTTP API
 * - WhatsApp Business API
 */

return [
    // Enable/disable WhatsApp notifications
    'enabled' => false, // Set to true to enable WhatsApp notifications
    
    // API Configuration
    'api_url' => '', // e.g., 'https://api.twilio.com/2010-04-01/Accounts/{AccountSid}/Messages.json'
    'api_key' => '', // API key or Account SID
    'auth_token' => '', // Auth token (for Twilio)
    'from_number' => '', // WhatsApp number in format: +1234567890
    
    // Provider type: 'twilio', 'generic', 'whatsapp_business'
    'provider' => 'twilio',
];

