$(document).ready(function() {
    const Chatbot = {
        isOpen: false,
        currentChatRoomId: null,
        userRole: null,
        userId: null,
        pollInterval: null,
        memberSearchTimeout: null,
        isDragging: false,
        dragOffset: { x: 0, y: 0 },
        togglePosition: { x: null, y: null },
        dragStartPos: null,
        hasDragged: false,
        
        init: function() {
            // Get user data from body attributes or session
            this.userRole = $('body').data('user-role') || 'member';
            this.userId = $('body').data('user-id') || null;
            
            this.loadTogglePosition();
            this.bindEvents();
            this.loadUnreadCount();
            this.startPolling();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Toggle chatbot (only if not dragging)
            $('#chatbotToggle').on('click', function(e) {
                // Only toggle if we didn't just drag
                if (!self.hasDragged) {
                    self.toggle();
                }
                // Reset flag after click
                self.hasDragged = false;
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
            
            // Admin chat by username form submit - prevent default and handle manually
            $('#adminChatByUsernameFormElement').on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Only submit if dropdown is not visible (user explicitly submitted)
                if (!$('#memberSearchDropdown').is(':visible')) {
                    self.createChatRoomByUsername();
                }
                return false;
            });
            
            // Enter key in username input - prevent default form submission when searching
            $('#memberUsername').on('keydown', function(e) {
                // Only handle Enter key
                if (e.which !== 13 && e.keyCode !== 13) {
                    return; // Let other keys work normally
                }
                
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const searchTerm = $(this).val().trim();
                
                // If dropdown is visible with results, select the first item instead of submitting
                if ($('#memberSearchDropdown').is(':visible') && $('#memberSearchDropdown .member-search-item:not(.member-search-no-results)').length > 0) {
                    const $firstItem = $('#memberSearchDropdown .member-search-item:first');
                    if ($firstItem.length) {
                        $firstItem.trigger('click');
                    }
                    return false;
                }
                
                // Only submit if there's a value and dropdown is NOT visible (user explicitly wants to submit)
                // Don't submit if user is in the middle of searching
                if (searchTerm.length > 0 && !$('#memberSearchDropdown').is(':visible')) {
                    // Check if there's a pending search - if so, wait for it
                    if (self.memberSearchTimeout) {
                        // Wait for search to complete, then submit
                        clearTimeout(self.memberSearchTimeout);
                        setTimeout(function() {
                            self.createChatRoomByUsername();
                        }, 400);
                    } else {
                        // No pending search, submit immediately
                        self.createChatRoomByUsername();
                    }
                }
                return false;
            });
            
            // Input event for member search with debouncing
            $('#memberUsername').on('input', function(e) {
                // Prevent any form submission while typing
                e.stopPropagation();
                
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
            
            // Initialize drag functionality for toggle button
            self.initToggleDrag();
            
            // Handle window resize to keep toggle button in bounds
            $(window).on('resize', function() {
                if (self.togglePosition.x !== null && self.togglePosition.y !== null) {
                    self.constrainTogglePosition();
                }
            });
        },
        
        initToggleDrag: function() {
            const self = this;
            const $toggle = $('#chatbotToggle');
            
            // Remove any existing handlers to avoid duplicates
            $(document).off('mousemove.chattoggle mouseup.chattoggle');
            $toggle.off('mousedown.chattoggle');
            
            // Bind mousedown to toggle button
            $toggle.on('mousedown.chattoggle', function(e) {
                // Don't start drag if clicking on the unread badge
                if ($(e.target).closest('.unread-badge').length) {
                    return true; // Allow normal click behavior
                }
                
                self.isDragging = false; // Will be set to true after mouse moves
                self.hasDragged = false; // Reset drag flag
                const toggleRect = $toggle[0].getBoundingClientRect();
                
                // Calculate offset from mouse to toggle center
                self.dragOffset = {
                    x: e.clientX - toggleRect.left - toggleRect.width / 2,
                    y: e.clientY - toggleRect.top - toggleRect.height / 2
                };
                
                // Store initial mouse position
                self.dragStartPos = {
                    x: e.clientX,
                    y: e.clientY
                };
                
                $toggle.css('cursor', 'grabbing');
                $toggle.css('user-select', 'none');
                
                e.preventDefault();
                e.stopPropagation();
            });
            
            $(document).on('mousemove.chattoggle', function(e) {
                if (self.dragStartPos === null) return;
                
                // Calculate distance moved
                const deltaX = Math.abs(e.clientX - self.dragStartPos.x);
                const deltaY = Math.abs(e.clientY - self.dragStartPos.y);
                
                // Start dragging if mouse moved more than 5px (to distinguish from click)
                if (!self.isDragging && (deltaX > 5 || deltaY > 5)) {
                    self.isDragging = true;
                    self.hasDragged = true; // Mark that we've dragged
                    $toggle.css('transition', 'none'); // Disable transitions during drag
                }
                
                if (self.isDragging) {
                    const $toggle = $('#chatbotToggle');
                    const toggleWidth = $toggle.outerWidth();
                    const toggleHeight = $toggle.outerHeight();
                    
                    // Calculate new position (center of toggle)
                    let newX = e.clientX - self.dragOffset.x - toggleWidth / 2;
                    let newY = e.clientY - self.dragOffset.y - toggleHeight / 2;
                    
                    // Constrain to viewport
                    const minX = 0;
                    const maxX = window.innerWidth - toggleWidth;
                    const minY = 0;
                    const maxY = window.innerHeight - toggleHeight;
                    
                    newX = Math.max(minX, Math.min(maxX, newX));
                    newY = Math.max(minY, Math.min(maxY, newY));
                    
                    // Store position
                    self.togglePosition.x = newX;
                    self.togglePosition.y = newY;
                    
                    // Update position
                    $toggle.css({
                        position: 'fixed',
                        left: newX + 'px',
                        top: newY + 'px',
                        right: 'auto',
                        bottom: 'auto'
                    });
                    
                    // Update chat window position relative to toggle
                    self.updateChatWindowPosition();
                    
                    e.preventDefault();
                }
            });
            
            $(document).on('mouseup.chattoggle', function(e) {
                if (self.dragStartPos !== null) {
                    const wasDragging = self.isDragging;
                    self.isDragging = false;
                    self.dragStartPos = null;
                    
                    $('#chatbotToggle').css({
                        'cursor': 'pointer',
                        'user-select': '',
                        'transition': '' // Re-enable transitions
                    });
                    
                    if (wasDragging) {
                        self.saveTogglePosition();
                    }
                }
            });
        },
        
        constrainTogglePosition: function() {
            const $toggle = $('#chatbotToggle');
            
            if (this.togglePosition.x === null || this.togglePosition.y === null) return;
            
            const toggleWidth = $toggle.outerWidth();
            const toggleHeight = $toggle.outerHeight();
            
            // Calculate viewport constraints
            const maxX = window.innerWidth - toggleWidth;
            const maxY = window.innerHeight - toggleHeight;
            
            // Constrain position
            let newX = Math.max(0, Math.min(maxX, this.togglePosition.x));
            let newY = Math.max(0, Math.min(maxY, this.togglePosition.y));
            
            if (newX !== this.togglePosition.x || newY !== this.togglePosition.y) {
                this.togglePosition.x = newX;
                this.togglePosition.y = newY;
                $toggle.css({
                    position: 'fixed',
                    left: newX + 'px',
                    top: newY + 'px',
                    right: 'auto',
                    bottom: 'auto'
                });
                this.updateChatWindowPosition();
                this.saveTogglePosition();
            }
        },
        
        updateChatWindowPosition: function() {
            const $toggle = $('#chatbotToggle');
            const $window = $('#chatbotWindow');
            
            if (!$toggle.length || !$window.length) return;
            
            const toggleRect = $toggle[0].getBoundingClientRect();
            const windowWidth = $window.outerWidth();
            const windowHeight = $window.outerHeight();
            
            // Position window above toggle, aligned to right
            let windowX = toggleRect.right - windowWidth;
            let windowY = toggleRect.top - windowHeight - 10;
            
            // If window would go off screen, position it below toggle
            if (windowY < 0) {
                windowY = toggleRect.bottom + 10;
            }
            
            // If window would go off right edge, align to left
            if (windowX < 0) {
                windowX = toggleRect.left;
            }
            
            // If window would go off left edge, align to right
            if (windowX + windowWidth > window.innerWidth) {
                windowX = window.innerWidth - windowWidth;
            }
            
            $window.css({
                position: 'fixed',
                left: windowX + 'px',
                top: windowY + 'px',
                right: 'auto',
                bottom: 'auto'
            });
        },
        
        loadTogglePosition: function() {
            const saved = localStorage.getItem('chatbotTogglePosition');
            if (saved) {
                try {
                    const savedPos = JSON.parse(saved);
                    if (savedPos.x !== null && savedPos.y !== null) {
                        this.togglePosition = savedPos;
                        const $toggle = $('#chatbotToggle');
                        $toggle.css({
                            position: 'fixed',
                            left: this.togglePosition.x + 'px',
                            top: this.togglePosition.y + 'px',
                            right: 'auto',
                            bottom: 'auto'
                        });
                        this.updateChatWindowPosition();
                    }
                } catch (e) {
                    // Invalid saved data, use defaults
                    this.togglePosition = { x: null, y: null };
                }
            }
        },
        
        saveTogglePosition: function() {
            localStorage.setItem('chatbotTogglePosition', JSON.stringify(this.togglePosition));
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
            
            // Update chat window position relative to toggle
            this.updateChatWindowPosition();
            
            // Hide all forms initially
            $('#newChatForm').hide();
            $('#adminChatByUsernameForm').hide();
            
            if (this.userRole === 'member') {
                this.loadChatRooms(false);
            } else {
                this.loadAdminChatRooms(false);
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
        
        loadChatRooms: function(suppressAutoNewChat) {
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'GET',
                data: { action: 'getChatRooms' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.renderChatRooms(response.chat_rooms, false, suppressAutoNewChat);
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
                            self.renderAdminChatRooms(response.chat_rooms, true, false);
                        } else {
                            self.renderChatRooms(response.chat_rooms, true, false);
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
                this.loadAdminChatRooms(false);
            } else {
                this.loadChatRooms(false);
            }
        },
        
        loadAdminChatRooms: function(suppressAutoNewChat) {
            const self = this;
            $.ajax({
                url: window.chatControllerUrl || 'controller/ChatController.php',
                method: 'GET',
                data: { action: 'getChatRooms' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        self.renderAdminChatRooms(response.chat_rooms, false, suppressAutoNewChat);
                    } else {
                        self.showError('Failed to load chat rooms');
                    }
                },
                error: function() {
                    self.showError('Failed to load chat rooms. Please try again.');
                }
            });
        },
        
        renderChatRooms: function(chatRooms, isSearchResult, suppressAutoNewChat) {
            const $container = $('#chatRoomsItems');
            $container.empty();
            
            if (chatRooms.length === 0) {
                if (isSearchResult) {
                    // Show "no results" message during search, don't redirect to new chat form
                    $container.html('<div class="empty-state"><i class="fas fa-search"></i><p>No chat rooms found</p><p class="empty-hint">Try a different search term</p></div>');
                } else {
                    // Show empty state message
                    $container.html('<div class="empty-state"><i class="fas fa-inbox"></i><p>No chat rooms yet</p><p class="empty-hint">Start a new conversation to get help</p></div>');
                    // Only auto-show new chat form if not suppressed (i.e., not coming from cancel)
                    if (!suppressAutoNewChat) {
                    this.showNewChatForm();
                }
                }
                // Ensure chat rooms list is visible even when empty
                $('#chatRoomsList').show();
                $('#chatInterface').hide();
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
        
        renderAdminChatRooms: function(chatRooms, isSearchResult, suppressAutoNewChat) {
            const $container = $('#chatRoomsItems');
            $container.empty();
            
            if (chatRooms.length === 0) {
                if (isSearchResult) {
                    // Show "no results" message during search
                    $container.html('<div class="empty-state"><i class="fas fa-search"></i><p>No chat rooms found</p><p class="empty-hint">Try a different search term</p></div>');
                } else {
                    // Show regular empty state when loading initial list
                    $container.html('<div class="empty-state"><i class="fas fa-inbox"></i><p>No chat rooms</p></div>');
                }
                // Ensure chat rooms list is visible even when empty
                $('#chatRoomsList').show();
                $('#chatInterface').hide();
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
        
        showChatRoomsList: function(suppressAutoNewChat) {
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
                this.loadAdminChatRooms(suppressAutoNewChat);
            } else {
                this.loadChatRooms(suppressAutoNewChat);
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
                
                // System messages should always be displayed as received
                const isSystem = msg.sender_role === 'system';
                const displayAsSent = isSent && !isSystem;
                
                const $message = $('<div class="message"></div>');
                $message.addClass(displayAsSent ? 'sent' : 'received');
                
                // Add special styling for system messages
                if (isSystem) {
                    $message.addClass('system-message');
                }
                
                $message.html(`
                    <div class="message-bubble">${this.escapeHtml(msg.message)}</div>
                    <div class="message-info">
                        <span class="sender-name">${msg.sender_name || 'Unknown'}</span>
                        <span class="message-time">${time}</span>
                        ${displayAsSent && msg.is_read ? '<i class="fas fa-check-double read-icon"></i>' : ''}
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
            
            // Show chat rooms list again, suppressing auto-show of new chat form
            this.showChatRoomsList(true);
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
            
            // Show chat rooms list again, suppressing auto-show of new chat form
            this.showChatRoomsList(true);
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
                        self.loadAdminChatRooms(false);
                        
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
                    if (self.memberSearchTimeout) {
                        clearTimeout(self.memberSearchTimeout);
                        self.memberSearchTimeout = null;
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
                        self.loadChatRooms(false);
                        
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
                            self.loadAdminChatRooms(false);
                        } else {
                            self.loadChatRooms(false);
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
                            self.loadAdminChatRooms(false);
                        } else {
                            self.loadChatRooms(false);
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