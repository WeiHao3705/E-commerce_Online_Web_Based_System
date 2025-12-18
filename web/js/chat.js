$(document).ready(function() {
    const Chatbot = {
        isOpen: false,
        currentChatRoomId: null,
        userRole: null,
        userId: null,
        pollInterval: null,
        
        init: function() {
            // Get user data from body attributes or session
            this.userRole = $('body').data('user-role') || 'member';
            this.userId = $('body').data('user-id') || null;
            
            this.bindEvents();
            this.loadUnreadCount();
            this.startPolling();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Toggle chatbot
            $('#chatbotToggle').on('click', function() {
                self.toggle();
            });
            
            // Close button
            $('#closeBtn').on('click', function() {
                self.close();
            });
            
            // Minimize button
            $('#minimizeBtn').on('click', function() {
                self.close();
            });
            
            // Send message
            $('#chatForm').on('submit', function(e) {
                e.preventDefault();
                self.sendMessage();
            });
            
            // Enter key to send message
            $('#messageInput').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
            
            // New chat button
            $('#newChatBtn').on('click', function() {
                self.showNewChatForm();
            });
            
            // Cancel new chat
            $('#cancelNewChatBtn').on('click', function() {
                self.hideNewChatForm();
            });
            
            // New chat form submit
            $('#newChatFormElement').on('submit', function(e) {
                e.preventDefault();
                self.createNewChatRoom();
            });
            
            // Enter key in initial message textarea
            $('#initialMessage').on('keypress', function(e) {
                if (e.which === 13 && e.ctrlKey) {
                    e.preventDefault();
                    self.createNewChatRoom();
                }
            });
            
            // Back to chat rooms button
            $('#backToChatRoomsBtn').on('click', function() {
                self.showChatRoomsList();
            });
        },
        
        toggle: function() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        },
        
        open: function() {
            this.isOpen = true;
            $('#chatbotWindow').addClass('active');
            
            if (this.userRole === 'member') {
                this.loadChatRooms();
            } else {
                this.loadAdminChatRooms();
            }
        },
        
        close: function() {
            this.isOpen = false;
            $('#chatbotWindow').removeClass('active');
            this.currentChatRoomId = null;
        },
        
        loadUnreadCount: function() {
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'GET',
                data: { action: 'getUnreadCount' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.count > 0) {
                            $('#unreadBadge').text(response.count).show();
                        } else {
                            $('#unreadBadge').hide();
                        }
                    }
                },
                error: function() {
                    // Silently fail - don't show error for unread count
                }
            });
        },
        
        loadChatRooms: function() {
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'GET',
                data: { action: 'getChatRooms' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.renderChatRooms(response.chat_rooms);
                    } else {
                        self.showError('Failed to load chat rooms');
                    }
                },
                error: function() {
                    self.showError('Failed to load chat rooms. Please try again.');
                }
            });
        },
        
        loadAdminChatRooms: function() {
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'GET',
                data: { action: 'getChatRooms' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.renderAdminChatRooms(response.chat_rooms);
                    } else {
                        self.showError('Failed to load chat rooms');
                    }
                },
                error: function() {
                    self.showError('Failed to load chat rooms. Please try again.');
                }
            });
        },
        
        renderChatRooms: function(chatRooms) {
            const $container = $('#chatRoomsItems');
            $container.empty();
            
            if (chatRooms.length === 0) {
                $container.html('<div class="empty-state"><i class="fas fa-inbox"></i><p>No chat rooms yet</p><p class="empty-hint">Start a new conversation to get help</p></div>');
                this.showNewChatForm();
                return;
            }
            
            chatRooms.forEach(function(room) {
                const $item = $('<div class="chat-room-item"></div>');
                $item.data('chat-room-id', room.chat_room_id);
                
                const time = this.formatTime(room.created_at);
                const status = room.status === 'closed' ? '<span class="status-badge closed">Closed</span>' : '';
                
                $item.html(`
                    <div class="chat-room-item-header">
                        <span class="chat-room-item-title">Chat Room #${room.chat_room_id}</span>
                        <span class="chat-room-item-time">${time}</span>
                    </div>
                    <div class="chat-room-item-footer">
                        ${status}
                        ${room.unread_count > 0 ? `<span class="chat-room-item-unread">${room.unread_count} unread</span>` : ''}
                    </div>
                `);
                
                $item.on('click', function() {
                    Chatbot.loadChatRoom(room.chat_room_id);
                });
                
                $container.append($item);
            }.bind(this));
            
            $('#chatRoomsList').show();
            $('#chatInterface').hide();
        },
        
        renderAdminChatRooms: function(chatRooms) {
            const $container = $('#chatRoomsItems');
            $container.empty();
            
            if (chatRooms.length === 0) {
                $container.html('<div class="empty-state"><i class="fas fa-inbox"></i><p>No open chat rooms</p></div>');
                return;
            }
            
            chatRooms.forEach(function(room) {
                const $item = $('<div class="chat-room-item"></div>');
                $item.data('chat-room-id', room.chat_room_id);
                
                const time = this.formatTime(room.created_at);
                const memberName = room.member_name || 'Unknown Member';
                const status = room.status === 'closed' ? '<span class="status-badge closed">Closed</span>' : '<span class="status-badge open">Open</span>';
                const adminAssigned = room.admin_id ? `<span class="admin-assigned">Assigned</span>` : '<span class="admin-unassigned">Unassigned</span>';
                
                $item.html(`
                    <div class="chat-room-item-header">
                        <span class="chat-room-item-title">${memberName}</span>
                        <span class="chat-room-item-time">${time}</span>
                    </div>
                    <div class="chat-room-item-footer">
                        ${status}
                        ${adminAssigned}
                        ${room.unread_count > 0 ? `<span class="chat-room-item-unread">${room.unread_count} unread</span>` : ''}
                    </div>
                `);
                
                $item.on('click', function() {
                    Chatbot.loadChatRoom(room.chat_room_id);
                    // Auto-assign to current admin if not assigned
                    if (!room.admin_id) {
                        Chatbot.assignToAdmin(room.chat_room_id);
                    }
                });
                
                $container.append($item);
            }.bind(this));
            
            $('#chatRoomsList').show();
            $('#chatInterface').hide();
        },
        
        showChatRoomsList: function() {
            this.currentChatRoomId = null;
            $('#chatRoomsList').show();
            $('#chatInterface').hide();
            $('#chatInterfaceHeader').hide();
            $('#newChatForm').hide();
            
            // Reload chat rooms
            if (this.userRole === 'admin') {
                this.loadAdminChatRooms();
            } else {
                this.loadChatRooms();
            }
        },
        
        loadChatRoom: function(chatRoomId) {
            const self = this;
            this.currentChatRoomId = chatRoomId;
            
            // Update active chat room
            $('.chat-room-item').removeClass('active');
            const $activeRoom = $(`.chat-room-item[data-chat-room-id="${chatRoomId}"]`);
            $activeRoom.addClass('active');
            
            // Update chat interface title
            const roomTitle = $activeRoom.find('.chat-room-item-title').text() || 'Chat';
            $('#chatInterfaceTitle').text(roomTitle);
            
            // Hide chat rooms list, show chat interface
            $('#chatRoomsList').hide();
            $('#chatInterface').show();
            $('#chatInterfaceHeader').show();
            $('#newChatForm').hide();
            
            // Show loading
            $('#chatMessages').html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading messages...</div>');
            
            // Load messages
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'GET',
                data: { 
                    action: 'getMessages',
                    chat_room_id: chatRoomId 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.renderMessages(response.messages);
                        self.loadUnreadCount();
                    } else {
                        const errorMsg = response.error || 'Failed to load messages';
                        self.showError(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = 'Failed to load messages. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                    self.showError(errorMsg);
                }
            });
        },
        
        renderMessages: function(messages) {
            const $container = $('#chatMessages');
            $container.empty();
            
            if (messages.length === 0) {
                $container.html('<div class="empty-state"><i class="fas fa-comments"></i><p>No messages yet</p><p class="empty-hint">Start the conversation!</p></div>');
                return;
            }
            
            messages.forEach(function(msg) {
                const isSent = msg.sender_role === this.userRole;
                const time = this.formatTime(msg.created_at);
                
                const $message = $('<div class="message"></div>');
                $message.addClass(isSent ? 'sent' : 'received');
                
                $message.html(`
                    <div class="message-bubble">${this.escapeHtml(msg.message)}</div>
                    <div class="message-info">
                        <span class="sender-name">${msg.sender_name || 'Unknown'}</span>
                        <span class="message-time">${time}</span>
                        ${isSent && msg.is_read ? '<i class="fas fa-check-double read-icon"></i>' : ''}
                    </div>
                `);
                
                $container.append($message);
            }.bind(this));
            
            // Scroll to bottom
            this.scrollToBottom();
        },
        
        sendMessage: function() {
            const message = $('#messageInput').val().trim();
            if (!message) return;
            
            if (!this.currentChatRoomId) {
                this.showError('Please select a chat room first');
                return;
            }
            
            // Disable input while sending
            const $input = $('#messageInput');
            const $sendBtn = $('#sendBtn');
            $input.prop('disabled', true);
            $sendBtn.prop('disabled', true);
            
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'POST',
                data: {
                    action: 'sendMessage',
                    chat_room_id: this.currentChatRoomId,
                    message: message
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $input.val('').prop('disabled', false);
                        $sendBtn.prop('disabled', false);
                        self.loadChatRoom(self.currentChatRoomId);
                    } else {
                        $input.prop('disabled', false);
                        $sendBtn.prop('disabled', false);
                        self.showError('Error: ' + (response.error || 'Failed to send message'));
                    }
                },
                error: function() {
                    $input.prop('disabled', false);
                    $sendBtn.prop('disabled', false);
                    self.showError('Failed to send message. Please try again.');
                }
            });
        },
        
        showNewChatForm: function() {
            $('#chatRoomsList').hide();
            $('#chatInterface').hide();
            $('#newChatForm').show();
            $('#initialMessage').focus();
        },
        
        hideNewChatForm: function() {
            $('#newChatForm').hide();
            $('#initialMessage').val('');
        },
        
        createNewChatRoom: function() {
            const message = $('#initialMessage').val().trim();
            
            if (!message) {
                this.showError('Please enter a message');
                return;
            }
            
            // Disable form while creating
            const $form = $('#newChatFormElement');
            const $submitBtn = $form.find('button[type="submit"]');
            $form.find('input, textarea, button').prop('disabled', true);
            
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'POST',
                data: {
                    action: 'createChatRoom',
                    message: message
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.hideNewChatForm();
                        self.loadChatRoom(response.chat_room_id);
                    } else {
                        $form.find('input, textarea, button').prop('disabled', false);
                        self.showError('Error: ' + (response.error || 'Failed to create chat room'));
                    }
                },
                error: function() {
                    $form.find('input, textarea, button').prop('disabled', false);
                    self.showError('Failed to create chat room. Please try again.');
                }
            });
        },
        
        assignToAdmin: function(chatRoomId) {
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'POST',
                data: {
                    action: 'assignToAdmin',
                    chat_room_id: chatRoomId
                },
                dataType: 'json',
                success: function(response) {
                    // Assignment successful - can update UI if needed
                },
                error: function() {
                    // Silently fail - assignment is not critical
                }
            });
        },
        
        closeChatRoom: function(chatRoomId) {
            if (!confirm('Are you sure you want to close this chat room?')) {
                return;
            }
            
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'POST',
                data: {
                    action: 'closeChatRoom',
                    chat_room_id: chatRoomId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.currentChatRoomId = null;
                        if (self.userRole === 'member') {
                            self.loadChatRooms();
                        } else {
                            self.loadAdminChatRooms();
                        }
                    } else {
                        self.showError('Failed to close chat room');
                    }
                },
                error: function() {
                    self.showError('Failed to close chat room. Please try again.');
                }
            });
        },
        
        startPolling: function() {
            const self = this;
            // Poll for new messages every 5 seconds
            this.pollInterval = setInterval(function() {
                if (self.isOpen && self.currentChatRoomId) {
                    // Reload current chat room messages
                    self.loadChatRoom(self.currentChatRoomId);
                }
                // Always update unread count
                self.loadUnreadCount();
            }, 5000);
        },
        
        stopPolling: function() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },
        
        scrollToBottom: function() {
            const $container = $('#chatMessages');
            $container.scrollTop($container[0].scrollHeight);
        },
        
        formatTime: function(dateString) {
            if (!dateString) return '';
            
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const seconds = Math.floor(diff / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);
            
            if (seconds < 60) {
                return 'Just now';
            } else if (minutes < 60) {
                return `${minutes}m ago`;
            } else if (hours < 24) {
                return `${hours}h ago`;
            } else if (days < 7) {
                return `${days}d ago`;
            } else {
                return date.toLocaleDateString();
            }
        },
        
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },
        
        showError: function(message) {
            // Show error message in chat interface
            const $container = $('#chatMessages');
            const $error = $('<div class="error-message"><i class="fas fa-exclamation-circle"></i> ' + this.escapeHtml(message) + '</div>');
            $container.append($error);
            setTimeout(function() {
                $error.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Initialize chatbot
    Chatbot.init();
    
    // Make Chatbot globally accessible if needed
    window.Chatbot = Chatbot;
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        Chatbot.stopPolling();
    });
});