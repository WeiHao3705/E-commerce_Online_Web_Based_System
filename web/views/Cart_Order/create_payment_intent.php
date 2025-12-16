<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();

// Redirect admins to AdminDashboard - they should not access member pages
if (isset($_SESSION['user']) && isset($_SESSION['user']->role) && $_SESSION['user']->role === 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Admins cannot access member payment pages']);
    exit;
}

header('Content-Type: application/json');

require __DIR__ . '/../../../vendor/autoload.php';
require __DIR__ . '/../../config/stripe_config.php';
require __DIR__ . '/../../database/connection.php';

// Set Stripe API key
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        throw new Exception('User not logged in');
    }
    
    $amount = $input['amount'] ?? 0;
    $orderData = $input['orderData'] ?? [];
    
    if ($amount <= 0) {
        throw new Exception('Invalid amount');
    }
    
    // Create Stripe Payment Intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => (int)round($amount * 100), // Stripe uses cents (integer)
        'currency' => STRIPE_CURRENCY,
        'payment_method_types' => ['card'],
        'metadata' => [
            'user_id' => $userId,
            'order_type' => 'ecommerce'
        ]
    ]);
    
    // Store payment intent ID in session for later verification
    $_SESSION['payment_intent_id'] = $paymentIntent->id;
    $_SESSION['pending_order_data'] = $orderData;
    
    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent->client_secret,
        'paymentIntentId' => $paymentIntent->id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
