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
        // Show all chat rooms (open and closed) to admin so they can view chat history
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
                WHERE 1=1";

        if ($adminId) {
            $sql .= " AND (r.admin_id = ? OR r.admin_id IS NULL)";
        }

        // Order by status (open first) then by created_at DESC
        $sql .= " ORDER BY CASE WHEN r.status = 'open' THEN 0 ELSE 1 END, r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        if ($adminId) {
            $stmt->execute([$adminId]);
        } else {
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
        try {
            $sql = "INSERT INTO chat_message (chat_room_id, sender_id, message) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$chatRoomId, $senderId, $message]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('ChatRepository addMessage error: ' . $e->getMessage());
            throw new Exception('Failed to add message: ' . $e->getMessage());
        }
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
                    CASE 
                        WHEN m.sender_id = r.member_id THEN COALESCE(u.full_name, 'Unknown')
                        WHEN u.username = 'system' THEN 'System'
                        WHEN m.sender_id = r.admin_id AND r.admin_id IS NOT NULL THEN COALESCE(u.full_name, 'Unknown')
                        ELSE 'System'
                    END as sender_name,
                    r.member_id,
                    r.admin_id,
                    CASE 
                        WHEN m.sender_id = r.member_id THEN 'member'
                        WHEN u.username = 'system' THEN 'system'
                        WHEN m.sender_id = r.admin_id AND r.admin_id IS NOT NULL THEN 'admin'
                        ELSE 'system'
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
            // For members: count messages from admin OR system (any sender that is not the member)
            $sql = "SELECT COUNT(*) as count
                    FROM chat_message cm
                    JOIN chat_room r ON cm.chat_room_id = r.chat_room_id
                    WHERE cm.sender_id != r.member_id
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
            // Admin can search by member name in all chat rooms (open and closed)
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
                    WHERE m.full_name LIKE ?
                    ORDER BY CASE WHEN r.status = 'open' THEN 0 ELSE 1 END, r.created_at DESC";
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

    /**
     * Get member by username
     * Returns member user data or null if not found
     */
    public function getMemberByUsername($username)
    {
        try {
            $sql = "SELECT * FROM users WHERE username = ? AND role = 'member' LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('ChatRepository getMemberByUsername error: ' . $e->getMessage());
            throw new Exception('Failed to retrieve member: ' . $e->getMessage());
        }
    }

    /**
     * Get existing open chat room for a member, or create a new one
     * Returns chat room ID
     */
    public function getOrCreateChatRoomForMember($memberId, $adminId = null)
    {
        try {
            // First, try to find an existing open chat room for this member
            $sql = "SELECT chat_room_id, admin_id FROM chat_room WHERE member_id = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$memberId]);
            $existingRoom = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRoom) {
                $chatRoomId = $existingRoom['chat_room_id'];
                // If admin ID is provided, assign the chat room to this admin
                // Reassign if currently unassigned (NULL) or assigned to system user
                if ($adminId) {
                    // Get system user ID to check if current admin_id is system
                    $systemUserSql = "SELECT user_id FROM users WHERE username = 'system' AND role = 'admin' LIMIT 1";
                    $systemUserStmt = $this->db->prepare($systemUserSql);
                    $systemUserStmt->execute();
                    $systemUser = $systemUserStmt->fetch(PDO::FETCH_ASSOC);
                    $systemUserId = $systemUser ? (int)$systemUser['user_id'] : null;
                    
                    // Assign to admin if unassigned or assigned to system
                    $currentAdminId = $existingRoom['admin_id'];
                    if ($currentAdminId === null || ($systemUserId && $currentAdminId == $systemUserId)) {
                        $updateSql = "UPDATE chat_room SET admin_id = ? WHERE chat_room_id = ?";
                        $updateStmt = $this->db->prepare($updateSql);
                        $updateStmt->execute([$adminId, $chatRoomId]);
                    }
                }
                return $chatRoomId;
            }

            // If no open chat room exists, create a new one
            $sql = "INSERT INTO chat_room (member_id, admin_id, status) VALUES (?, ?, 'open')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$memberId, $adminId]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('ChatRepository getOrCreateChatRoomForMember error: ' . $e->getMessage());
            throw new Exception('Failed to get or create chat room: ' . $e->getMessage());
        }
    }

    /**
     * Get or create chat room for system messages
     * This method checks both open and closed chatrooms to reuse existing ones
     * Returns chat room ID (will be reopened if it was closed)
     * IMPORTANT: Assigns chatroom to system user (admin_id = system user ID) so it shows as assigned
     */
    public function getOrCreateChatRoomForSystemMessage($memberId, $systemUserId)
    {
        try {
            // Get system user ID if not provided
            if (!$systemUserId) {
                $systemUserSql = "SELECT user_id FROM users WHERE username = 'system' AND role = 'admin' LIMIT 1";
                $systemUserStmt = $this->db->prepare($systemUserSql);
                $systemUserStmt->execute();
                $systemUser = $systemUserStmt->fetch(PDO::FETCH_ASSOC);
                if ($systemUser) {
                    $systemUserId = (int)$systemUser['user_id'];
                } else {
                    throw new Exception('System user not found');
                }
            }
            
            // First, try to find any existing chat room (open or closed) for this member
            $sql = "SELECT chat_room_id, status, admin_id FROM chat_room WHERE member_id = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$memberId]);
            $existingRoom = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingRoom) {
                $chatRoomId = $existingRoom['chat_room_id'];
                
                // Assign chatroom to system user so it shows as assigned (not unassigned)
                if ($existingRoom['admin_id'] != $systemUserId) {
                    $assignAdminSql = "UPDATE chat_room SET admin_id = ? WHERE chat_room_id = ?";
                    $assignAdminStmt = $this->db->prepare($assignAdminSql);
                    $assignAdminStmt->execute([$systemUserId, $chatRoomId]);
                    error_log("ChatRepository: Assigned chatroom {$chatRoomId} to system user {$systemUserId}");
                }
                
                // If chat room is closed, reopen it for system notifications
                if ($existingRoom['status'] === 'closed') {
                    $this->reopenChatRoom($chatRoomId);
                }
                return $chatRoomId;
            }

            // If no chat room exists at all, create a new one assigned to system user
            $sql = "INSERT INTO chat_room (member_id, admin_id, status) VALUES (?, ?, 'open')";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([$memberId, $systemUserId]);
            
            if (!$success) {
                error_log("ChatRepository: Failed to execute INSERT for chatroom creation for member {$memberId}");
                throw new Exception('Failed to create chat room: INSERT statement failed');
            }
            
            $newChatRoomId = $this->db->lastInsertId();
            
            // Verify that we got a valid ID
            if (!$newChatRoomId || $newChatRoomId == 0 || $newChatRoomId === '0') {
                error_log("ChatRepository: lastInsertId() returned invalid value: " . var_export($newChatRoomId, true) . " for member {$memberId}");
                throw new Exception('Failed to create chat room: Invalid chat room ID returned');
            }
            
            // Convert to integer to ensure consistent type
            $newChatRoomId = (int)$newChatRoomId;
            
            // Verify the chatroom was actually created by querying it back
            $verifySql = "SELECT chat_room_id FROM chat_room WHERE chat_room_id = ? LIMIT 1";
            $verifyStmt = $this->db->prepare($verifySql);
            $verifyStmt->execute([$newChatRoomId]);
            $verifiedRoom = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verifiedRoom) {
                error_log("ChatRepository: Created chatroom {$newChatRoomId} but could not verify its existence for member {$memberId}");
                throw new Exception('Failed to create chat room: Chat room was not found after creation');
            }
            
            error_log("ChatRepository: Created new chatroom {$newChatRoomId} assigned to system user {$systemUserId} for member {$memberId}");
            return $newChatRoomId;
        } catch (PDOException $e) {
            error_log('ChatRepository getOrCreateChatRoomForSystemMessage error: ' . $e->getMessage());
            throw new Exception('Failed to get or create chat room for system message: ' . $e->getMessage());
        }
    }

    /**
     * Search members by username or full name
     * Returns array of member data matching the search term
     */
    public function searchMembers($searchTerm, $limit = 10)
    {
        try {
            $searchTerm = trim($searchTerm);
            $limit = (int)$limit;
            if ($limit < 1) $limit = 10;
            if ($limit > 50) $limit = 50; // Cap at 50 results
            
            // Create search pattern with wildcards
            $searchPattern = '%' . $searchTerm . '%';
            
            // Create pattern for username starting with search term (higher priority)
            $usernameStartPattern = $searchTerm . '%';
            
            $sql = "SELECT user_id, username, full_name, email, profile_photo, status
                    FROM users
                    WHERE role = 'member'
                    AND (username LIKE ? OR full_name LIKE ?)
                    ORDER BY 
                        CASE 
                            WHEN username LIKE ? THEN 1
                            WHEN username LIKE ? THEN 2
                            WHEN full_name LIKE ? THEN 3
                            ELSE 4
                        END,
                        username ASC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            // Bind parameters: WHERE clause (2), ORDER BY CASE (3)
            $stmt->bindValue(1, $searchPattern, PDO::PARAM_STR);      // WHERE username LIKE
            $stmt->bindValue(2, $searchPattern, PDO::PARAM_STR);      // WHERE full_name LIKE
            $stmt->bindValue(3, $usernameStartPattern, PDO::PARAM_STR); // ORDER BY: username starts with (highest priority)
            $stmt->bindValue(4, $searchPattern, PDO::PARAM_STR);      // ORDER BY: username contains
            $stmt->bindValue(5, $searchPattern, PDO::PARAM_STR);      // ORDER BY: full_name contains
            $stmt->bindValue(6, $limit, PDO::PARAM_INT);              // LIMIT (must be integer)
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('ChatRepository searchMembers error: ' . $e->getMessage());
            throw new Exception('Failed to search members: ' . $e->getMessage());
        }
    }
}
