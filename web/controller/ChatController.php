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
            case 'assignToAdmin':
                $this->assignToAdmin();
                break;
            case 'getUnreadCount':
                $this->getUnreadCount();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
    }

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
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

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
}

// Handle the request
$controller = new ChatController();
$controller->handleRequest();