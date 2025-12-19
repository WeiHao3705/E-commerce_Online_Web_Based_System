<?php
require_once __DIR__ . '/../repository/ChatRepository.php';
require_once __DIR__ . '/../DTO/ChatDTO.php';

class ChatService
{
    private $chatRepository;

    public function __construct(ChatRepository $chatRepository)
    {
        $this->chatRepository = $chatRepository;
    }

    public function createChatRoom($memberId)
    {
        return $this->chatRepository->createChatRoom($memberId);
    }

    public function getChatRoom($chatRoomId, $currentUserId = null)
    {
        $data = $this->chatRepository->getChatRoomById($chatRoomId, $currentUserId);
        return $data ? new ChatRoomDTO($data) : null;
    }

    public function getMemberChatRooms($memberId)
    {
        $data = $this->chatRepository->getMemberChatRooms($memberId);
        return array_map(function($item) {
            return new ChatRoomDTO($item);
        }, $data);
    }

    public function getAdminChatRooms($adminId = null)
    {
        $data = $this->chatRepository->getAdminChatRooms($adminId);
        return array_map(function($item) {
            return new ChatRoomDTO($item);
        }, $data);
    }

    public function assignChatRoomToAdmin($chatRoomId, $adminId)
    {
        return $this->chatRepository->assignChatRoomToAdmin($chatRoomId, $adminId);
    }

    public function sendMessage($chatRoomId, $senderId, $message, $senderRole = null)
    {
        // Get chat room to check status and admin assignment (pass senderId for unread_count calculation)
        $chatRoom = $this->chatRepository->getChatRoomById($chatRoomId, $senderId);
        
        if (!$chatRoom) {
            throw new Exception('Chat room not found');
        }
        
        // Check if chat room is closed
        if ($chatRoom['status'] === 'closed') {
            throw new Exception('Cannot send message to a closed chat room');
        }
        
        // If sender is admin and chat room is not assigned, assign it to them
        if ($senderRole === 'admin' && empty($chatRoom['admin_id'])) {
            $this->chatRepository->assignChatRoomToAdmin($chatRoomId, $senderId);
        }
        
        return $this->chatRepository->addMessage($chatRoomId, $senderId, $message);
    }

    public function getMessages($chatRoomId, $limit = 50)
    {
        $data = $this->chatRepository->getMessagesByChatRoom($chatRoomId, $limit);
        if (empty($data)) {
            return [];
        }
        return array_map(function($item) {
            try {
                return new ChatMessageDTO($item);
            } catch (Exception $e) {
                error_log('ChatMessageDTO creation error: ' . $e->getMessage() . ' | Data: ' . json_encode($item));
                throw $e;
            }
        }, $data);
    }

    public function markAsRead($chatRoomId, $currentUserId)
    {
        return $this->chatRepository->markMessagesAsRead($chatRoomId, $currentUserId);
    }

    public function closeChatRoom($chatRoomId)
    {
        return $this->chatRepository->closeChatRoom($chatRoomId);
    }

    public function reopenChatRoom($chatRoomId)
    {
        return $this->chatRepository->reopenChatRoom($chatRoomId);
    }

    public function getUnreadCount($userId, $role)
    {
        return $this->chatRepository->getUnreadCountForUser($userId, $role);
    }

    public function searchChatRooms($searchTerm, $userId, $role)
    {
        $data = $this->chatRepository->searchChatRooms($searchTerm, $userId, $role);
        return array_map(function($item) {
            return new ChatRoomDTO($item);
        }, $data);
    }

    /**
     * Create or get existing chat room for a member by username (admin only)
     * Returns chat room ID
     */
    public function createChatRoomByUsername($username, $adminId)
    {
        // Get member by username
        $member = $this->chatRepository->getMemberByUsername($username);
        
        if (!$member) {
            throw new Exception('Member not found with username: ' . $username);
        }

        // Get or create chat room for this member
        return $this->chatRepository->getOrCreateChatRoomForMember($member['user_id'], $adminId);
    }

    /**
     * Search members by username or full name
     * Returns array of member data
     */
    public function searchMembers($searchTerm, $limit = 10)
    {
        return $this->chatRepository->searchMembers($searchTerm, $limit);
    }
}