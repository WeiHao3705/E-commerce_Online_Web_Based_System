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
                    <?php if ($_SESSION['user']->role === 'admin'): ?>
                    <button class="btn-new-chat" id="adminChatByUsernameBtn" title="Chat with member by username">
                        <i class="fas fa-user-plus"></i> Chat with Member
                    </button>
                    <?php endif; ?>
                </div>
                <div class="chat-rooms-search">
                    <input type="text" id="chatRoomSearch" placeholder="<?php echo $_SESSION['user']->role === 'admin' ? 'Search by member name...' : 'Search by admin name...'; ?>" autocomplete="off">
                    <i class="fas fa-search search-icon"></i>
                    <button class="btn-clear-search" id="clearSearchBtn" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chat-rooms-items" id="chatRoomsItems"></div>
            </div>
            
            <!-- Chat Interface -->
            <div class="chat-interface" id="chatInterface">
                <div class="chat-interface-header" id="chatInterfaceHeader" style="display: none;">
                    <button class="btn-back" id="backToChatRoomsBtn" title="Back to chat rooms">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <span class="chat-interface-title" id="chatInterfaceTitle">Chat</span>
                    <?php if ($_SESSION['user']->role === 'admin'): ?>
                    <button class="btn-close-chat" id="closeChatRoomBtn" title="Close this chat room" style="display: none;">
                        <i class="fas fa-lock"></i> Close Chat
                    </button>
                    <button class="btn-reopen-chat" id="reopenChatRoomBtn" title="Reopen this chat room" style="display: none;">
                        <i class="fas fa-unlock"></i> Reopen Chat
                    </button>
                    <?php endif; ?>
                </div>
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
            
            <!-- Admin Chat by Username Form (for admins only) -->
            <?php if ($_SESSION['user']->role === 'admin'): ?>
            <div class="new-chat-form" id="adminChatByUsernameForm" style="display: none;">
                <h4>Chat with Member</h4>
                <form id="adminChatByUsernameFormElement">
                    <div class="form-group" style="position: relative;">
                        <label for="memberUsername">Member Username:</label>
                        <input type="text" id="memberUsername" placeholder="Enter member username or name..." autocomplete="off">
                        <div class="member-search-dropdown" id="memberSearchDropdown" style="display: none;"></div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Start Chat</button>
                        <button type="button" class="btn-secondary" id="cancelAdminChatBtn">Cancel</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="chat-modal-overlay" id="chatModalOverlay" style="display: none;">
        <div class="chat-modal">
            <div class="chat-modal-header">
                <h4 id="chatModalTitle">Confirm Action</h4>
                <button class="chat-modal-close" id="chatModalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="chat-modal-body">
                <p id="chatModalMessage">Are you sure you want to perform this action?</p>
            </div>
            <div class="chat-modal-footer">
                <button type="button" class="btn-modal-cancel" id="chatModalCancel">Cancel</button>
                <button type="button" class="btn-modal-confirm" id="chatModalConfirm">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Pass controller URL to JavaScript
    window.chatControllerUrl = '<?php echo $prefix; ?>controller/ChatController.php';
</script>
<script src="<?php echo $prefix; ?>js/chat.js?v=<?php echo filemtime(__DIR__ . '/../../js/chat.js'); ?>"></script>