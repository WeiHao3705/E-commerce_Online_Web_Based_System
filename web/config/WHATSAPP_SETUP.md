# WhatsApp Notification Setup

This guide explains how to configure WhatsApp notifications for restock alerts.

## Configuration

Edit `web/config/whatsapp_config.php` to configure your WhatsApp API settings.

## Option 1: Twilio WhatsApp API (Recommended)

1. Sign up for a Twilio account at https://www.twilio.com
2. Get your Account SID and Auth Token from the Twilio Console
3. Get a WhatsApp-enabled phone number from Twilio
4. Update `whatsapp_config.php`:

```php
return [
    'enabled' => true,
    'api_url' => 'https://api.twilio.com/2010-04-01/Accounts/{AccountSid}/Messages.json',
    'api_key' => 'YOUR_ACCOUNT_SID',
    'auth_token' => 'YOUR_AUTH_TOKEN',
    'from_number' => '+1234567890', // Your Twilio WhatsApp number
    'provider' => 'twilio',
];
```

## Option 2: Generic HTTP API

If you're using a different WhatsApp API provider:

```php
return [
    'enabled' => true,
    'api_url' => 'https://your-api-provider.com/api/send',
    'api_key' => 'YOUR_API_KEY',
    'auth_token' => '', // Not needed for generic API
    'from_number' => '+1234567890',
    'provider' => 'generic',
];
```

## Phone Number Format

Phone numbers are automatically normalized to international format:
- Local numbers (e.g., "0123456789") → "+60123456789" (Malaysia default)
- Numbers with country code (e.g., "+60123456789") → Used as-is
- Invalid numbers will be skipped

## Testing

1. Ensure members have phone numbers in their profile (`contact_no` field)
2. Restock a product that's in a member's wishlist and was out of stock
3. Check if WhatsApp message is sent (errors will be logged but won't fail the restock)

## Notes

- WhatsApp notifications are sent in addition to chat notifications
- If WhatsApp fails, the chat notification still succeeds
- Phone numbers must be in valid format (10-15 digits with country code)

