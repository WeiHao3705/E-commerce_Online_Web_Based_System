<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Only show chatbot for logged-in users
if (empty($_SESSION['user'])) return;

$currentFileDir = dirname(__FILE__); // Gets web/views/chat/
$webRootDir = dirname(dirname($currentFileDir)); // Gets web/ (go up 2 levels)
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$prefix = $webBasePath;
?>
<link rel="stylesheet" href="<?php echo $prefix; ?>css/chat.css?v=<?php echo filemtime(__DIR__ . '/../../css/chat.css'); ?>">
<div class="chatbot-container" id="chatbotContainer">
    <div class="chatbot-toggle" id="chatbotToggle">
        <i class="fas fa-comments"></i>
        <span class="unread-badge" id="unreadBadge" style="display: none;">0</span>
    </div>
    
    <div class="chatbot-window" id="chatbotWindow">
        <div class="chatbot-header">
            <h3>Support Chat</h3>
            <div class="chatbot-actions">
                <button class="btn-icon" id="minimizeBtn" title="Minimize">
                    <i class="fas fa-minus"></i>
                </button>
                <button class="btn-icon" id="closeBtn" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="chatbot-body">
            <!-- Chat Rooms List (for members and admins) -->
            <div class="chat-rooms-list" id="chatRoomsList" style="display: none;">
                <div class="chat-rooms-header">
                    <h4><?php echo $_SESSION['user']->role === 'admin' ? 'All Chat Rooms' : 'Your Chat Rooms'; ?></h4>
                    <?php if ($_SESSION['user']->role === 'member'): ?>
                    <button class="btn-new-chat" id="newChatBtn">
                        <i class="fas fa-plus"></i> New Chat
                    </button>
                    <?php endif; ?>
                </div>
                <div class="chat-rooms-items" id="chatRoomsItems"></div>
            </div>
            
            <!-- Chat Interface -->
            <div class="chat-interface" id="chatInterface">
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input-container">
                    <form id="chatForm">
                        <input type="text" id="messageInput" placeholder="Type your message..." autocomplete="off">
                        <button type="submit" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- New Chat Form (for members only) -->
            <?php if ($_SESSION['user']->role === 'member'): ?>
            <div class="new-chat-form" id="newChatForm" style="display: none;">
                <h4>Start New Conversation</h4>
                <form id="newChatFormElement">
                    <textarea id="initialMessage" placeholder="Type your message..." rows="4"></textarea>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Send</button>
                        <button type="button" class="btn-secondary" id="cancelNewChatBtn">Cancel</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Pass controller URL to JavaScript
    window.chatControllerUrl = '<?php echo $prefix; ?>controller/ChatController.php';
</script>
<script src="<?php echo $prefix; ?>js/chat.js?v=<?php echo filemtime(__DIR__ . '/../../js/chat.js'); ?>"></script>