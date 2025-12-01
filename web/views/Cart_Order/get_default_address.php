<?php
session_start();
require __DIR__ . '/../../database/connection.php';

// Set response header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Query to get user's address and user details
    $query = "
        SELECT 
            u.full_name,
            u.email,
            u.contact_no,
            a.address1,
            a.address2,
            a.city,
            a.postcode,
            a.state
        FROM users u
        LEFT JOIN address a ON u.user_id = a.user_id
        WHERE u.user_id = :user_id
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug: Print to console via error_log (check PHP error logs)
    error_log("=== GET DEFAULT ADDRESS DEBUG ===");
    error_log("User ID from session: " . $userId);
    error_log("Query executed successfully");
    error_log("Fetched result: " . json_encode($result));
    error_log("Result count: " . ($result ? count($result) : 0));
    
    if ($result) {
        error_log("User found: " . ($result['full_name'] ?? 'No name'));
        error_log("Address1 value: " . ($result['address1'] ?? 'NULL/Empty'));
        error_log("Address1 empty check: " . (empty($result['address1']) ? 'YES - EMPTY' : 'NO - HAS VALUE'));
        
        // Check if address exists (address1 is required field)
        if (!empty($result['address1'])) {
            error_log("SUCCESS: Address found, returning data");
            // Return address data as JSON
            echo json_encode([
                'success' => true,
                'fullName' => $result['full_name'] ?? '',
                'phone' => $result['contact_no'] ?? '',
                'email' => $result['email'] ?? '',
                'address1' => $result['address1'] ?? '',
                'address2' => $result['address2'] ?? '',
                'city' => $result['city'] ?? '',
                'postcode' => $result['postcode'] ?? '',
                'state' => $result['state'] ?? ''
            ]);
        } else {
            error_log("ERROR: User exists but no address in database");
            // User exists but no address saved
            echo json_encode([
                'success' => false, 
                'error' => 'No address saved for this user. Please enter your address manually.'
            ]);
        }
    } else {
        error_log("ERROR: No user found with user_id: " . $userId);
        echo json_encode(['success' => false, 'error' => 'User not found in database']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
