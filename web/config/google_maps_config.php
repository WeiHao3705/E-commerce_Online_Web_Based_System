<?php
// Google Maps API Configuration

// Load environment variables from .env file
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Google Maps API Key from environment (or use direct configuration below)
$apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';

// For direct configuration (if not using .env file, uncomment and add your key):
if (empty($apiKey)) {
    $apiKey = 'AIzaSyAPpT7JGGZT1l01WzvBCiMZ9hhVGy66PR0';
}

define('GOOGLE_MAPS_API_KEY', $apiKey);

