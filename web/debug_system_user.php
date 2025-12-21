<?php
// Quick diagnostic script to check system user
require_once __DIR__ . '/database/connection.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== System User Diagnostic ===\n\n";

// Check if system user exists
$stmt = $conn->query("SELECT user_id, username, full_name, email, role, status FROM users WHERE username = 'system' AND role = 'admin'");
$systemUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($systemUser) {
    echo "✓ System user found:\n";
    echo "  - User ID: " . $systemUser['user_id'] . "\n";
    echo "  - Username: " . $systemUser['username'] . "\n";
    echo "  - Full Name: " . $systemUser['full_name'] . "\n";
    echo "  - Role: " . $systemUser['role'] . "\n";
    echo "  - Status: " . $systemUser['status'] . "\n\n";
} else {
    echo "✗ System user NOT found!\n";
    echo "  Please run sql/insert_system_user.sql to create it.\n\n";
}

// Check recent restock messages
echo "=== Recent Restock Messages ===\n\n";
$stmt = $conn->query("
    SELECT 
        cm.message_id,
        cm.sender_id,
        cm.message,
        cm.created_at,
        u.username as sender_username,
        u.full_name as sender_name,
        r.chat_room_id,
        r.admin_id,
        r.member_id,
        CASE 
            WHEN cm.sender_id = r.member_id THEN 'member'
            WHEN cm.sender_id = r.admin_id AND r.admin_id IS NOT NULL THEN 'admin'
            ELSE 'system'
        END as calculated_role
    FROM chat_message cm
    LEFT JOIN users u ON cm.sender_id = u.user_id
    LEFT JOIN chat_room r ON cm.chat_room_id = r.chat_room_id
    WHERE cm.message LIKE '%wishlist%' OR cm.message LIKE '%back in stock%'
    ORDER BY cm.created_at DESC
    LIMIT 5
");

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($messages)) {
    echo "No restock messages found.\n";
} else {
    foreach ($messages as $msg) {
        echo "Message ID: " . $msg['message_id'] . "\n";
        echo "  Sender ID: " . $msg['sender_id'] . "\n";
        echo "  Sender Username: " . ($msg['sender_username'] ?? 'NULL') . "\n";
        echo "  Sender Name: " . ($msg['sender_name'] ?? 'NULL') . "\n";
        echo "  Calculated Role: " . $msg['calculated_role'] . "\n";
        echo "  Chatroom Admin ID: " . ($msg['admin_id'] ?? 'NULL') . "\n";
        echo "  Message: " . substr($msg['message'], 0, 50) . "...\n";
        echo "  Created: " . $msg['created_at'] . "\n\n";
    }
}

echo "\n=== System User ID Check ===\n";
if ($systemUser) {
    echo "Expected System User ID: " . $systemUser['user_id'] . "\n";
    if (!empty($messages)) {
        foreach ($messages as $msg) {
            if ((int)$msg['sender_id'] === (int)$systemUser['user_id']) {
                echo "✓ Message {$msg['message_id']} correctly uses System User ID\n";
            } else {
                echo "✗ Message {$msg['message_id']} uses wrong sender_id: {$msg['sender_id']} (expected: {$systemUser['user_id']})\n";
            }
        }
    }
}

