<?php
session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../repository/ChatRepository.php';
require_once __DIR__ . '/../service/ChatService.php';

class ChatController
{
    private $chatService;

    public function __construct()
    {
        $database = new Database();
        $chatRepository = new ChatRepository($database);
        $this->chatService = new ChatService($chatRepository);
    }

    private function requireAuth()
    {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
    }

    // Routes chat-related requests to appropriate handler methods
    public function handleRequest()
    {
        $this->requireAuth();
        
        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        switch ($action) {
            case 'getChatRooms':
                $this->getChatRooms();
                break;
            case 'getChatRoom':
                $this->getChatRoom();
                break;
            case 'createChatRoom':
                $this->createChatRoom();
                break;
            case 'sendMessage':
                $this->sendMessage();
                break;
            case 'getMessages':
                $this->getMessages();
                break;
            case 'markAsRead':
                $this->markAsRead();
                break;
            case 'closeChatRoom':
                $this->closeChatRoom();
                break;
            case 'reopenChatRoom':
                $this->reopenChatRoom();
                break;
            case 'assignToAdmin':
                $this->assignToAdmin();
                break;
            case 'getUnreadCount':
                $this->getUnreadCount();
                break;
            case 'searchChatRooms':
                $this->searchChatRooms();
                break;
            case 'createChatRoomByUsername':
                $this->createChatRoomByUsername();
                break;
            case 'searchMembers':
                $this->searchMembers();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
    }

    // Retrieves chat rooms for current user (admin or member)
    private function getChatRooms()
    {
        header('Content-Type: application/json');
        $userId = $_SESSION['user']->user_id;
        $role = $_SESSION['user']->role;

        try {
            if ($role === 'admin') {
                $chatRooms = $this->chatService->getAdminChatRooms($userId);
            } else {
                $chatRooms = $this->chatService->getMemberChatRooms($userId);
            }

            $result = array_map(function($room) {
                return [
                    'chat_room_id' => $room->getChatRoomId(),
                    'member_id' => $room->getMemberId(),
                    'admin_id' => $room->getAdminId(),
                    'status' => $room->getStatus(),
                    'created_at' => $room->getCreatedAt(),
                    'closed_at' => $room->getClosedAt(),
                    'member_name' => $room->getMemberName(),
                    'admin_name' => $room->getAdminName(),
                    'unread_count' => $room->getUnreadCount()
                ];
            }, $chatRooms);

            echo json_encode(['success' => true, 'chat_rooms' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Retrieves specific chat room details
    private function getChatRoom()
    {
        header('Content-Type: application/json');
        $chatRoomId = $_GET['chat_room_id'] ?? null;

        if (!$chatRoomId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chat room ID required']);
            return;
        }

        try {
            $userId = $_SESSION['user']->user_id;
            $chatRoom = $this->chatService->getChatRoom($chatRoomId, $userId);
            if (!$chatRoom) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Chat room not found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'chat_room' => [
                    'chat_room_id' => $chatRoom->getChatRoomId(),
                    'member_id' => $chatRoom->getMemberId(),
                    'admin_id' => $chatRoom->getAdminId(),
                    'status' => $chatRoom->getStatus(),
                    'member_name' => $chatRoom->getMemberName(),
                    'admin_name' => $chatRoom->getAdminName(),
                    'unread_count' => $chatRoom->getUnreadCount()
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Creates a new chat room for member
    private function createChatRoom()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $userId = $_SESSION['user']->user_id;
        $role = $_SESSION['user']->role;

        if ($role !== 'member') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Only members can create chat rooms']);
            return;
        }

        $message = $_POST['message'] ?? '';

        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Message is required']);
            return;
        }

        try {
            $chatRoomId = $this->chatService->createChatRoom($userId);
            $this->chatService->sendMessage($chatRoomId, $userId, $message);
            
            echo json_encode(['success' => true, 'chat_room_id' => $chatRoomId]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Sends a message in a chat room
    private function sendMessage()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $chatRoomId = $_POST['chat_room_id'] ?? null;
        $message = $_POST['message'] ?? '';
        $userId = $_SESSION['user']->user_id;
        $userRole = $_SESSION['user']->role ?? 'member';

        if (!$chatRoomId || empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chat room ID and message are required']);
            return;
        }

        try {
            $messageId = $this->chatService->sendMessage($chatRoomId, $userId, $message, $userRole);
            echo json_encode(['success' => true, 'message_id' => $messageId]);
        } catch (Exception $e) {
            // Check if it's a validation error (closed chat room, not found, etc.)
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Cannot send message to a closed chat room') !== false ||
                strpos($errorMessage, 'Chat room not found') !== false) {
                http_response_code(400); // Bad Request for validation errors
            } else {
                http_response_code(500); // Server error for other exceptions
            }
            echo json_encode(['success' => false, 'error' => $errorMessage]);
        }
    }

    // Retrieves messages for a chat room
    private function getMessages()
    {
        header('Content-Type: application/json');
        $chatRoomId = isset($_GET['chat_room_id']) ? (int)$_GET['chat_room_id'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

        if (!$chatRoomId || $chatRoomId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chat room ID required']);
            return;
        }

        try {
            $messages = $this->chatService->getMessages($chatRoomId, $limit);
            $userId = $_SESSION['user']->user_id;

            // Mark messages as read
            $this->chatService->markAsRead($chatRoomId, $userId);

            $result = array_map(function($msg) {
                return [
                    'message_id' => $msg->getMessageId(),
                    'chat_room_id' => $msg->getChatRoomId(),
                    'sender_id' => $msg->getSenderId(),
                    'sender_role' => $msg->getSenderRole(),
                    'message' => $msg->getMessage(),
                    'is_read' => (bool)$msg->getIsRead(),
                    'created_at' => $msg->getCreatedAt(),
                    'sender_name' => $msg->getSenderName()
                ];
            }, $messages);

            echo json_encode(['success' => true, 'messages' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            error_log('ChatController getMessages error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            echo json_encode([
                'success' => false, 
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ]);
        } catch (Error $e) {
            http_response_code(500);
            error_log('ChatController getMessages fatal error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            echo json_encode([
                'success' => false, 
                'error' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ]);
        }
    }

    // Marks messages in chat room as read
    private function markAsRead()
    {
        header('Content-Type: application/json');
        $chatRoomId = $_POST['chat_room_id'] ?? null;
        $userId = $_SESSION['user']->user_id;

        if (!$chatRoomId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chat room ID required']);
            return;
        }

        try {
            $this->chatService->markAsRead($chatRoomId, $userId);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Closes a chat room
    private function closeChatRoom()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $chatRoomId = $_POST['chat_room_id'] ?? null;

        if (!$chatRoomId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chat room ID required']);
            return;
        }

        try {
            $this->chatService->closeChatRoom($chatRoomId);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Reopens a closed chat room (admin only)
    private function reopenChatRoom()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $chatRoomId = $_POST['chat_room_id'] ?? null;

        if (!$chatRoomId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chat room ID required']);
            return;
        }

        $role = $_SESSION['user']->role;
        if ($role !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Only admins can reopen chat rooms']);
            return;
        }

        try {
            $this->chatService->reopenChatRoom($chatRoomId);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Assigns chat room to an admin (admin only)
    private function assignToAdmin()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $role = $_SESSION['user']->role;
        if ($role !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Only admins can assign chat rooms']);
            return;
        }

        $chatRoomId = $_POST['chat_room_id'] ?? null;
        $adminId = $_POST['admin_id'] ?? $_SESSION['user']->user_id;

        if (!$chatRoomId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Chat room ID required']);
            return;
        }

        try {
            $this->chatService->assignChatRoomToAdmin($chatRoomId, $adminId);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Returns count of unread messages for current user
    private function getUnreadCount()
    {
        header('Content-Type: application/json');
        $userId = $_SESSION['user']->user_id;
        $role = $_SESSION['user']->role;

        try {
            $count = $this->chatService->getUnreadCount($userId, $role);
            echo json_encode(['success' => true, 'count' => $count]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Searches chat rooms by keyword
    private function searchChatRooms()
    {
        header('Content-Type: application/json');
        $searchTerm = $_GET['search'] ?? $_POST['search'] ?? '';
        $userId = $_SESSION['user']->user_id;
        $role = $_SESSION['user']->role;

        if (empty(trim($searchTerm))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Search term is required']);
            return;
        }

        try {
            $chatRooms = $this->chatService->searchChatRooms($searchTerm, $userId, $role);

            $result = array_map(function($room) {
                return [
                    'chat_room_id' => $room->getChatRoomId(),
                    'member_id' => $room->getMemberId(),
                    'admin_id' => $room->getAdminId(),
                    'status' => $room->getStatus(),
                    'created_at' => $room->getCreatedAt(),
                    'closed_at' => $room->getClosedAt(),
                    'member_name' => $room->getMemberName(),
                    'admin_name' => $room->getAdminName(),
                    'unread_count' => $room->getUnreadCount()
                ];
            }, $chatRooms);

            echo json_encode(['success' => true, 'chat_rooms' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Creates chat room by member username (admin only)
    private function createChatRoomByUsername()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $role = $_SESSION['user']->role;
        if ($role !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Only admins can create chat rooms by username']);
            return;
        }

        $username = trim($_POST['username'] ?? '');
        if (empty($username)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username is required']);
            return;
        }

        try {
            $adminId = $_SESSION['user']->user_id;
            $chatRoomId = $this->chatService->createChatRoomByUsername($username, $adminId);
            echo json_encode(['success' => true, 'chat_room_id' => $chatRoomId]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Searches members by username or name (admin only)
    private function searchMembers()
    {
        header('Content-Type: application/json');
        
        $role = $_SESSION['user']->role ?? '';
        if ($role !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Only admins can search members']);
            return;
        }

        $searchTerm = trim($_GET['search'] ?? $_POST['search'] ?? '');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        if (empty($searchTerm)) {
            echo json_encode(['success' => true, 'members' => []]);
            return;
        }

        try {
            $members = $this->chatService->searchMembers($searchTerm, $limit);
            echo json_encode(['success' => true, 'members' => $members]);
        } catch (Exception $e) {
            http_response_code(500);
            error_log('ChatController searchMembers error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            echo json_encode([
                'success' => false, 
                'error' => 'Failed to search members: ' . $e->getMessage()
            ]);
        }
    }
}

// Handle the request
$controller = new ChatController();
$controller->handleRequest();