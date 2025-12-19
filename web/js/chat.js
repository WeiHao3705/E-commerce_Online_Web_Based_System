$(document).ready(function() {
    const Chatbot = {
        isOpen: false,
        currentChatRoomId: null,
        userRole: null,
        userId: null,
        pollInterval: null,
        memberSearchTimeout: null,
        
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
            
            // Admin chat by username button
            $('#adminChatByUsernameBtn').on('click', function() {
                self.showAdminChatByUsernameForm();
            });
            
            // Cancel admin chat by username
            $('#cancelAdminChatBtn').on('click', function() {
                self.hideAdminChatByUsernameForm();
            });
            
            // Admin chat by username form submit
            $('#adminChatByUsernameFormElement').on('submit', function(e) {
                e.preventDefault();
                self.createChatRoomByUsername();
            });
            
            // Enter key in username input (only submit if no dropdown is shown)
            $('#memberUsername').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    // If dropdown is visible and has results, don't submit - let user select first
                    if ($('#memberSearchDropdown').is(':visible') && $('#memberSearchDropdown .member-search-item').length > 0) {
                        // Select first item if arrow keys are used
                        return;
                    }
                    self.createChatRoomByUsername();
                }
            });
            
            // Input event for member search with debouncing
            $('#memberUsername').on('input', function() {
                const searchTerm = $(this).val().trim();
                clearTimeout(self.memberSearchTimeout);
                
                if (searchTerm.length === 0) {
                    $('#memberSearchDropdown').hide().empty();
                    return;
                }
                
                // Debounce search - wait 300ms after user stops typing
                self.memberSearchTimeout = setTimeout(function() {
                    self.searchMembers(searchTerm);
                }, 300);
            });
            
            // Handle clicking outside dropdown to close it
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#adminChatByUsernameForm').length && 
                    !$(e.target).closest('#memberSearchDropdown').length) {
                    $('#memberSearchDropdown').hide();
                }
            });
            
            // Prevent dropdown from closing when clicking inside it
            $('#memberSearchDropdown').on('click', function(e) {
                e.stopPropagation();
            });
            
            // Back to chat rooms button
            $('#backToChatRoomsBtn').on('click', function() {
                self.showChatRoomsList();
            });
            
            // Close chat room button (admin only)
            $('#closeChatRoomBtn').on('click', function() {
                if (self.currentChatRoomId) {
                    self.showConfirmModal(
                        'Close Chat Room',
                        'Are you sure you want to close this chat room? Once closed, no new messages can be sent.',
                        function() {
                            self.closeChatRoom(self.currentChatRoomId);
                        }
                    );
                }
            });
            
            // Reopen chat room button (admin only)
            $('#reopenChatRoomBtn').on('click', function() {
                if (self.currentChatRoomId) {
                    self.showConfirmModal(
                        'Reopen Chat Room',
                        'Are you sure you want to reopen this chat room? Messages can be sent again once reopened.',
                        function() {
                            self.reopenChatRoom(self.currentChatRoomId);
                        }
                    );
                }
            });
            
            // Modal close handlers
            $('#chatModalClose, #chatModalCancel').on('click', function() {
                self.hideConfirmModal();
            });
            
            // Modal overlay click to close
            $('#chatModalOverlay').on('click', function(e) {
                if (e.target === this) {
                    self.hideConfirmModal();
                }
            });
            
            // Search chat rooms
            $('#chatRoomSearch').on('input', function() {
                const searchTerm = $(this).val().trim();
                if (searchTerm.length > 0) {
                    self.searchChatRooms(searchTerm);
                    $('#clearSearchBtn').show();
                } else {
                    self.clearSearch();
                    $('#clearSearchBtn').hide();
                }
            });
            
            // Clear search
            $('#clearSearchBtn').on('click', function() {
                $('#chatRoomSearch').val('');
                self.clearSearch();
                $(this).hide();
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
            
            // Hide all forms initially
            $('#newChatForm').hide();
            $('#adminChatByUsernameForm').hide();
            
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
        
        searchChatRooms: function(searchTerm) {
            const self = this;
            if (!searchTerm || searchTerm.trim().length === 0) {
                this.clearSearch();
                return;
            }
            
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'GET',
                data: { 
                    action: 'searchChatRooms',
                    search: searchTerm
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (self.userRole === 'admin') {
                            self.renderAdminChatRooms(response.chat_rooms);
                        } else {
                            self.renderChatRooms(response.chat_rooms);
                        }
                    } else {
                        self.showError('Search failed: ' + (response.error || 'Unknown error'));
                    }
                },
                error: function() {
                    self.showError('Failed to search chat rooms. Please try again.');
                }
            });
        },
        
        clearSearch: function() {
            if (this.userRole === 'admin') {
                this.loadAdminChatRooms();
            } else {
                this.loadChatRooms();
            }
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
                // Show admin name for members, or "Unassigned" if no admin yet
                const adminName = room.admin_name || 'Unassigned';
                
                $item.html(`
                    <div class="chat-room-item-header">
                        <span class="chat-room-item-title">${adminName}</span>
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
                $container.html('<div class="empty-state"><i class="fas fa-inbox"></i><p>No chat rooms</p></div>');
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
            $('#adminChatByUsernameForm').hide();
            // Hide close/reopen buttons when navigating away
            $('#closeChatRoomBtn').hide();
            $('#reopenChatRoomBtn').hide();
            
            // Clear any active room highlighting
            $('.chat-room-item').removeClass('active');
            
            // Reload chat rooms to ensure list is up to date
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
            $('#adminChatByUsernameForm').hide();
            
            // Show loading
            $('#chatMessages').html('<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading messages...</div>');
            
            // Load chatroom details to check status (for admin close/reopen buttons and to disable input for closed rooms)
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'GET',
                data: { 
                    action: 'getChatRoom',
                    chat_room_id: chatRoomId 
                },
                dataType: 'json',
                success: function(roomResponse) {
                    if (roomResponse.success && roomResponse.chat_room) {
                        // Show close button if chatroom is open, reopen button if closed (admin only)
                        if (self.userRole === 'admin') {
                            if (roomResponse.chat_room.status === 'open') {
                                $('#closeChatRoomBtn').show();
                                $('#reopenChatRoomBtn').hide();
                            } else {
                                $('#closeChatRoomBtn').hide();
                                $('#reopenChatRoomBtn').show();
                            }
                        }
                        
                        // Enable/disable message input based on chat room status (for all users)
                        if (roomResponse.chat_room.status === 'open') {
                            $('#messageInput').prop('disabled', false);
                            $('#messageInput').attr('placeholder', 'Type your message...');
                        } else {
                            $('#messageInput').prop('disabled', true);
                            $('#messageInput').attr('placeholder', 'This chat room is closed');
                        }
                    }
                },
                error: function() {
                    // Silently fail - not critical
                    if (self.userRole === 'admin') {
                        $('#closeChatRoomBtn').hide();
                        $('#reopenChatRoomBtn').hide();
                    }
                }
            });
            
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
                        // Refocus input if it's enabled and chat room is open
                        setTimeout(function() {
                            if (!$('#messageInput').prop('disabled')) {
                                $('#messageInput').focus();
                            }
                        }, 100);
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
                        // Refocus input field after sending message
                        setTimeout(function() {
                            $input.focus();
                        }, 100);
                    } else {
                        $input.prop('disabled', false);
                        $sendBtn.prop('disabled', false);
                        $input.focus();
                        self.showError('Error: ' + (response.error || 'Failed to send message'));
                    }
                },
                error: function(xhr, status, error) {
                    // Try to parse JSON error response
                    let errorMessage = 'Failed to send message. Please try again.';
                    let isClosedChatRoom = false;
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMessage = response.error;
                            // Check if this is a closed chat room error
                            if (response.error.includes('Cannot send message to a closed chat room')) {
                                isClosedChatRoom = true;
                            }
                        }
                    } catch (e) {
                        // If parsing fails, use default message
                    }
                    
                    // If chat room is closed, keep input disabled
                    if (isClosedChatRoom) {
                        $input.prop('disabled', true);
                        $input.attr('placeholder', 'This chat room is closed');
                        $sendBtn.prop('disabled', true);
                    } else {
                        $input.prop('disabled', false);
                        $sendBtn.prop('disabled', false);
                        $input.focus();
                    }
                    
                    self.showError(errorMessage);
                }
            });
        },
        
        showNewChatForm: function() {
            // Hide other views
            $('#chatRoomsList').hide();
            $('#chatInterface').hide();
            $('#chatInterfaceHeader').hide();
            
            // Show new chat form
            $('#newChatForm').show();
            
            // Clear any previous input
            $('#initialMessage').val('');
            
            // Reset form state
            const $form = $('#newChatFormElement');
            $form.find('input, textarea, button').prop('disabled', false);
            
            // Focus on input
            $('#initialMessage').focus();
        },
        
        hideNewChatForm: function() {
            $('#newChatForm').hide();
            $('#initialMessage').val('');
            // Reset form state
            const $form = $('#newChatFormElement');
            $form.find('input, textarea, button').prop('disabled', false);
        },
        
        showAdminChatByUsernameForm: function() {
            // Hide other views
            $('#chatRoomsList').hide();
            $('#chatInterface').hide();
            $('#chatInterfaceHeader').hide();
            $('#newChatForm').hide();
            
            // Show admin chat by username form
            $('#adminChatByUsernameForm').show();
            
            // Clear any previous input
            $('#memberUsername').val('');
            
            // Reset form state
            const $form = $('#adminChatByUsernameFormElement');
            $form.find('input, button').prop('disabled', false);
            
            // Focus on input
            $('#memberUsername').focus();
        },
        
        hideAdminChatByUsernameForm: function() {
            $('#adminChatByUsernameForm').hide();
            $('#memberUsername').val('');
            $('#memberSearchDropdown').hide().empty();
            // Reset form state
            const $form = $('#adminChatByUsernameFormElement');
            $form.find('input, button').prop('disabled', false);
        },
        
        createChatRoomByUsername: function() {
            const username = $('#memberUsername').val().trim();
            
            if (!username) {
                this.showError('Please enter a member username');
                return;
            }
            
            // Disable form while creating
            const $form = $('#adminChatByUsernameFormElement');
            $form.find('input, button').prop('disabled', true);
            
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'POST',
                data: {
                    action: 'createChatRoomByUsername',
                    username: username
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Clear and hide the form
                        $('#memberUsername').val('');
                        self.hideAdminChatByUsernameForm();
                        
                        // Refresh chat rooms list to show the new/existing chat
                        self.loadAdminChatRooms();
                        
                        // Load the chat room
                        self.loadChatRoom(response.chat_room_id);
                        
                        // Show success message
                        self.showSuccess('Chat room opened successfully');
                    } else {
                        $form.find('input, button').prop('disabled', false);
                        self.showError('Error: ' + (response.error || 'Failed to create chat room'));
                    }
                },
                error: function(xhr) {
                    $form.find('input, button').prop('disabled', false);
                    let errorMsg = 'Failed to create chat room. Please try again.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMsg = response.error;
                        }
                    } catch (e) {
                        // Use default error message
                    }
                    self.showError(errorMsg);
                }
            });
        },
        
        searchMembers: function(searchTerm) {
            const self = this;
            
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'GET',
                data: {
                    action: 'searchMembers',
                    search: searchTerm,
                    limit: 10
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.renderMemberSearchResults(response.members);
                    } else {
                        $('#memberSearchDropdown').hide();
                    }
                },
                error: function() {
                    $('#memberSearchDropdown').hide();
                }
            });
        },
        
        renderMemberSearchResults: function(members) {
            const $dropdown = $('#memberSearchDropdown');
            $dropdown.empty();
            
            if (!members || members.length === 0) {
                $dropdown.html('<div class="member-search-item member-search-no-results">No members found</div>');
                $dropdown.show();
                return;
            }
            
            members.forEach(function(member) {
                const $item = $('<div class="member-search-item"></div>');
                const statusBadge = member.status === 'active' 
                    ? '<span class="member-status-badge active">Active</span>'
                    : '<span class="member-status-badge inactive">' + (member.status || 'Inactive') + '</span>';
                
                $item.html(`
                    <div class="member-search-item-content">
                        <div class="member-search-item-name">
                            <strong>${this.escapeHtml(member.username || 'N/A')}</strong>
                            ${statusBadge}
                        </div>
                        <div class="member-search-item-fullname">${this.escapeHtml(member.full_name || '')}</div>
                        ${member.email ? '<div class="member-search-item-email">' + this.escapeHtml(member.email) + '</div>' : ''}
                    </div>
                `);
                
                $item.data('username', member.username);
                $item.data('user-id', member.user_id);
                
                // Click handler to select member
                $item.on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const username = $(this).data('username');
                    $('#memberUsername').val(username);
                    $dropdown.hide().empty();
                    // Clear any search timeout
                    if (this.memberSearchTimeout) {
                        clearTimeout(this.memberSearchTimeout);
                        this.memberSearchTimeout = null;
                    }
                });
                
                $dropdown.append($item);
            }.bind(this));
            
            $dropdown.show();
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
                        // Clear and hide the form
                        $('#initialMessage').val('');
                        self.hideNewChatForm();
                        
                        // Refresh chat rooms list to show the new chat
                        self.loadChatRooms();
                        
                        // Load the newly created chat room
                        self.loadChatRoom(response.chat_room_id);
                        
                        // Show success message
                        self.showSuccess('Chat room created successfully');
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
                        // Hide close button, show reopen button
                        $('#closeChatRoomBtn').hide();
                        $('#reopenChatRoomBtn').show();
                        
                        // Disable message input
                        $('#messageInput').prop('disabled', true);
                        $('#messageInput').attr('placeholder', 'This chat room is closed');
                        
                        // Show success message
                        self.showSuccess('Chat room closed successfully');
                        
                        // Keep chat history visible - don't redirect away
                        // Just refresh the chat room to update status and refresh chat rooms list
                        if (self.currentChatRoomId === chatRoomId) {
                            // Reload chat room to update status display
                            self.loadChatRoom(chatRoomId);
                        }
                        
                        // Refresh chat rooms list to update status badges
                        if (self.userRole === 'admin') {
                            self.loadAdminChatRooms();
                        } else {
                            self.loadChatRooms();
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
        
        reopenChatRoom: function(chatRoomId) {
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'POST',
                data: {
                    action: 'reopenChatRoom',
                    chat_room_id: chatRoomId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Hide reopen button, show close button
                        $('#reopenChatRoomBtn').hide();
                        $('#closeChatRoomBtn').show();
                        
                        // Enable message input
                        $('#messageInput').prop('disabled', false);
                        $('#messageInput').attr('placeholder', 'Type your message...');
                        
                        // Show success message
                        self.showSuccess('Chat room reopened successfully');
                        
                        // Reload the chatroom to update status
                        if (self.currentChatRoomId === chatRoomId) {
                            self.loadChatRoom(chatRoomId);
                        }
                        
                        // Refresh chat rooms list
                        if (self.userRole === 'admin') {
                            self.loadAdminChatRooms();
                        } else {
                            self.loadChatRooms();
                        }
                    } else {
                        self.showError('Failed to reopen chat room');
                    }
                },
                error: function() {
                    self.showError('Failed to reopen chat room. Please try again.');
                }
            });
        },
        
        showConfirmModal: function(title, message, onConfirm) {
            const self = this;
            $('#chatModalTitle').text(title);
            $('#chatModalMessage').text(message);
            $('#chatModalOverlay').show();
            
            // Store the confirm callback
            this.modalConfirmCallback = onConfirm;
            
            // Remove any existing handlers and set up confirm button handler
            $('#chatModalConfirm').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (self.modalConfirmCallback) {
                    self.modalConfirmCallback();
                }
                self.hideConfirmModal();
                return false;
            });
        },
        
        hideConfirmModal: function() {
            $('#chatModalOverlay').hide();
            this.modalConfirmCallback = null;
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
        },
        
        showSuccess: function(message) {
            // Show success message in chat interface
            const $container = $('#chatMessages');
            const $success = $('<div class="success-message"><i class="fas fa-check-circle"></i> ' + this.escapeHtml(message) + '</div>');
            $container.append($success);
            setTimeout(function() {
                $success.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
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