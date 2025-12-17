<?php 

class ChatRoomDTO
{
    private $chatRoomId;
    private $memberId;
    private $adminId;
    private $status;
    private $createdAt;
    private $closedAt;
    private $memberName;
    private $adminName;
    private $unreadCount;

    public function __construct($data)
    {
        $this->chatRoomId = $data['chat_room_id'] ?? null;
        $this->memberId = $data['member_id'] ?? null;
        $this->adminId = $data['admin_id'] ?? null;
        $this->status = $data['status'] ?? 'open';
        $this->createdAt = $data['created_at'] ?? null;
        $this->closedAt = $data['closed_at'] ?? null;
        $this->memberName = $data['member_name'] ?? null;
        $this->adminName = $data['admin_name'] ?? null;
        $this->unreadCount = $data['unread_count'] ?? 0;
    }

    // Getters
    public function getChatRoomId() { return $this->chatRoomId; }
    public function getMemberId() { return $this->memberId; }
    public function getAdminId() { return $this->adminId; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getClosedAt() { return $this->closedAt; }
    public function getMemberName() { return $this->memberName; }
    public function getAdminName() { return $this->adminName; }
    public function getUnreadCount() { return $this->unreadCount; }
}

class ChatMessageDTO
{
    private $messageId;
    private $chatRoomId;
    private $senderId;
    private $senderRole;
    private $message;
    private $isRead;
    private $createdAt;
    private $senderName;

    public function __construct($data)
    {
        $this->messageId = $data['message_id'] ?? null;
        $this->chatRoomId = $data['chat_room_id'] ?? null;
        $this->senderId = $data['sender_id'] ?? null;
        $this->senderRole = $data['sender_role'] ?? 'member'; // Default to member if not set
        $this->message = $data['message'] ?? null;
        // Handle is_read as boolean (MySQL returns 0/1)
        $isRead = $data['is_read'] ?? false;
        $this->isRead = is_bool($isRead) ? $isRead : (bool)$isRead;
        $this->createdAt = $data['created_at'] ?? null;
        $this->senderName = $data['sender_name'] ?? null;
    }

    // Getters
    public function getMessageId() { return $this->messageId; }
    public function getChatRoomId() { return $this->chatRoomId; }
    public function getSenderId() { return $this->senderId; }
    public function getSenderRole() { return $this->senderRole; }
    public function getMessage() { return $this->message; }
    public function getIsRead() { return $this->isRead; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getSenderName() { return $this->senderName; }
}