<?php
require_once __DIR__ . '/../DTO/ChatDTO.php';

class ChatRepository
{

    private $db;

    public function __construct(Database $databaseConnection)
    {
        $this->db = $databaseConnection->getConnection();
    }

    public function createChatRoom($memberId)
    {
        $sql = "INSERT INTO chat_room (member_id, status) VALUES (?, 'open')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        return $this->db->lastInsertId();
    }

    public function getChatRoomById($chatRoomId, $currentUserId = null)
    {
        try {
            $sql = "SELECT r.*, 
                    m.full_name as member_name, 
                    a.full_name as admin_name,
                    (SELECT COUNT(*) FROM chat_message cm 
                     WHERE cm.chat_room_id = r.chat_room_id 
                     AND cm.is_read = FALSE 
                     AND cm.sender_id != ?) as unread_count
                    FROM chat_room r
                    LEFT JOIN users m ON r.member_id = m.user_id
                    LEFT JOIN users a ON r.admin_id = a.user_id
                    WHERE r.chat_room_id = ?";
            $stmt = $this->db->prepare($sql);
            // Use provided userId or fallback to session (for backward compatibility)
            $userId = $currentUserId ?? (isset($_SESSION['user']) ? $_SESSION['user']->user_id : 0);
            $stmt->execute([$userId, $chatRoomId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('ChatRepository getChatRoomById error: ' . $e->getMessage());
            throw new Exception('Failed to retrieve chat room: ' . $e->getMessage());
        }
    }

    public function getMemberChatRooms($memberId)
    {
        $sql = "SELECT r.*, 
                m.full_name as member_name, 
                a.full_name as admin_name,
                (SELECT COUNT(*) FROM chat_message cm 
                 WHERE cm.chat_room_id = r.chat_room_id 
                 AND cm.is_read = FALSE 
                 AND cm.sender_id != r.member_id) as unread_count
                FROM chat_room r
                LEFT JOIN users m ON r.member_id = m.user_id
                LEFT JOIN users a ON r.admin_id = a.user_id
                WHERE r.member_id = ?
                ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminChatRooms($adminId = null)
    {
        $sql = "SELECT r.*, 
                m.full_name as member_name, 
                a.full_name as admin_name,
                (SELECT COUNT(*) FROM chat_message cm 
                 WHERE cm.chat_room_id = r.chat_room_id 
                 AND cm.is_read = FALSE 
                 AND cm.sender_id = r.member_id) as unread_count
                FROM chat_room r
                LEFT JOIN users m ON r.member_id = m.user_id
                LEFT JOIN users a ON r.admin_id = a.user_id
                WHERE r.status = 'open'";

        if ($adminId) {
            $sql .= " AND (r.admin_id = ? OR r.admin_id IS NULL)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$adminId]);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignChatRoomToAdmin($chatRoomId, $adminId)
    {
        $sql = "UPDATE chat_room SET admin_id = ? WHERE chat_room_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$adminId, $chatRoomId]);
    }

    public function addMessage($chatRoomId, $senderId, $message)
    {
        $sql = "INSERT INTO chat_message (chat_room_id, sender_id, message) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$chatRoomId, $senderId, $message]);
        return $this->db->lastInsertId();
    }

    public function getMessagesByChatRoom($chatRoomId, $limit = 50)
    {
        try {
            $sql = "SELECT m.message_id,
                    m.chat_room_id,
                    m.sender_id,
                    m.message,
                    m.is_read,
                    m.created_at,
                    COALESCE(u.full_name, 'Unknown') as sender_name,
                    r.member_id,
                    r.admin_id,
                    CASE 
                        WHEN m.sender_id = r.member_id THEN 'member'
                        WHEN m.sender_id = r.admin_id AND r.admin_id IS NOT NULL THEN 'admin'
                        ELSE 'member'
                    END as sender_role
                    FROM chat_message m
                    LEFT JOIN users u ON m.sender_id = u.user_id
                    LEFT JOIN chat_room r ON m.chat_room_id = r.chat_room_id
                    WHERE m.chat_room_id = ?
                    ORDER BY m.created_at ASC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, (int)$chatRoomId, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results ? $results : [];
        } catch (PDOException $e) {
            error_log('ChatRepository getMessagesByChatRoom error: ' . $e->getMessage());
            throw new Exception('Failed to retrieve messages: ' . $e->getMessage());
        }
    }

    public function markMessagesAsRead($chatRoomId, $currentUserId)
    {
        // Mark messages as read where sender is not the current user
        $sql = "UPDATE chat_message 
                SET is_read = TRUE 
                WHERE chat_room_id = ? AND sender_id != ? AND is_read = FALSE";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$chatRoomId, $currentUserId]);
    }

    public function closeChatRoom($chatRoomId)
    {
        $sql = "UPDATE chat_room SET status = 'closed', closed_at = CURRENT_TIMESTAMP WHERE chat_room_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$chatRoomId]);
    }

    public function reopenChatRoom($chatRoomId)
    {
        $sql = "UPDATE chat_room SET status = 'open', closed_at = NULL WHERE chat_room_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$chatRoomId]);
    }

    public function getUnreadCountForUser($userId, $role)
    {
        if ($role === 'admin') {
            $sql = "SELECT COUNT(*) as count
                    FROM chat_message cm
                    JOIN chat_room r ON cm.chat_room_id = r.chat_room_id
                    WHERE cm.sender_id = r.member_id
                    AND cm.is_read = FALSE
                    AND r.status = 'open'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            $sql = "SELECT COUNT(*) as count
                    FROM chat_message cm
                    JOIN chat_room r ON cm.chat_room_id = r.chat_room_id
                    WHERE cm.sender_id = r.admin_id
                    AND cm.is_read = FALSE
                    AND r.member_id = ?
                    AND r.status = 'open'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    public function searchChatRooms($searchTerm, $userId, $role)
    {
        $searchTerm = '%' . $searchTerm . '%';
        
        if ($role === 'admin') {
            // Admin can only search by member name
            $sql = "SELECT r.*, 
                    m.full_name as member_name, 
                    a.full_name as admin_name,
                    (SELECT COUNT(*) FROM chat_message cm 
                     WHERE cm.chat_room_id = r.chat_room_id 
                     AND cm.is_read = FALSE 
                     AND cm.sender_id = r.member_id) as unread_count
                    FROM chat_room r
                    LEFT JOIN users m ON r.member_id = m.user_id
                    LEFT JOIN users a ON r.admin_id = a.user_id
                    WHERE r.status = 'open'
                    AND m.full_name LIKE ?
                    ORDER BY r.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$searchTerm]);
        } else {
            // Member can only search by admin name
            $sql = "SELECT r.*, 
                    m.full_name as member_name, 
                    a.full_name as admin_name,
                    (SELECT COUNT(*) FROM chat_message cm 
                     WHERE cm.chat_room_id = r.chat_room_id 
                     AND cm.is_read = FALSE 
                     AND cm.sender_id != r.member_id) as unread_count
                    FROM chat_room r
                    LEFT JOIN users m ON r.member_id = m.user_id
                    LEFT JOIN users a ON r.admin_id = a.user_id
                    WHERE r.member_id = ?
                    AND a.full_name LIKE ?
                    ORDER BY r.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $searchTerm]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
