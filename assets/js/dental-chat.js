/**
 * Dental Directory Chat Interface JavaScript
 * 
 * Provides real-time chat functionality with:
 * - Conversation listing
 * - Message sending/receiving
 * - Typing indicators
 * - Message status (sent/delivered/read)
 * - Notifications for new messages
 */

(function($) {
    'use strict';

    // Chat component
    var DentalChat = {
        // Store for conversations and messages
        data: {
            conversations: [],
            messages: [],
            typingTimeout: null,
            currentPage: 1,
            totalPages: 1,
            isPolling: false,
            pollInterval: 5000, // 5 seconds
            pollTimeoutId: null,
            lastActivity: Date.now()
        },

        // Templates
        templates: {
            conversationItem: wp.template('conversation-item'),
            chatHeader: wp.template('chat-header'),
            chatMessage: wp.template('chat-message'),
            emptyConversations: wp.template('empty-conversations')
        },

        // Initialize the chat interface
        init: function() {
            // Cache DOM elements
            this.cacheDom();
            
            // Bind events
            this.bindEvents();
            
            // Load initial data
            this.loadConversations();
            
            // Start polling for new messages if viewing a conversation
            if (DENTAL_CHAT.conversation_id || DENTAL_CHAT.recipient_id) {
                this.startPolling();
            }
        },

        // Cache DOM elements
        cacheDom: function() {
            this.$container = $('.dental-chat-container');
            this.$conversationList = $('#dental-conversation-list');
            this.$chatHeader = $('#dental-chat-header');
            this.$chatMessages = $('#dental-chat-messages');
            this.$chatForm = $('#dental-chat-form');
            this.$chatInput = $('#dental-chat-input');
            this.$sendButton = $('#dental-send-message');
            this.$typingIndicator = $('#dental-typing-indicator');
            this.$searchInput = $('#chat-search-input');
        },

        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Send message on form submit
            this.$chatForm && this.$chatForm.on('submit', function(e) {
                e.preventDefault();
                self.sendMessage();
            });
            
            // Show typing indicator when user types
            this.$chatInput && this.$chatInput.on('input', function() {
                self.sendTypingStatus();
            });
            
            // Conversation click event
            this.$conversationList.on('click', '.conversation-item', function() {
                var $this = $(this);
                var conversationId = $this.data('conversation-id');
                var recipientId = $this.data('recipient-id');
                
                // Navigate to conversation
                self.navigateToConversation(conversationId, recipientId);
            });
            
            // Search conversations
            this.$searchInput && this.$searchInput.on('input', function() {
                self.searchConversations($(this).val());
            });
            
            // Refresh button click
            $(document).on('click', '.refresh-button', function() {
                self.refreshCurrentConversation();
            });
            
            // Load more messages
            this.$chatMessages.on('click', '.load-more-button', function() {
                self.loadMoreMessages();
            });
            
            // Track user activity to manage polling
            $(document).on('mousemove keydown', function() {
                self.data.lastActivity = Date.now();
                
                // Restart polling if it was stopped due to inactivity
                if (!self.data.pollTimeoutId && (DENTAL_CHAT.conversation_id || DENTAL_CHAT.recipient_id)) {
                    self.startPolling();
                }
            });
            
            // Handle subscription upgrade button
            $(document).on('click', '[data-action="upgrade-subscription"]', function(e) {
                e.preventDefault();
                self.upgradeSubscription();
            });
        },

        // Load all conversations
        loadConversations: function() {
            var self = this;
            
            // Show loading spinner
            this.$conversationList.html('<div class="loading-spinner"><div class="spinner"></div></div>');
            
            // Make API request
            $.ajax({
                url: DENTAL_CHAT.rest_url + '/messaging/conversations',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                },
                success: function(response, status, xhr) {
                    self.data.conversations = response;
                    
                    // Update total pages from header
                    self.data.totalPages = parseInt(xhr.getResponseHeader('X-WP-TotalPages') || 1);
                    
                    // Render conversations
                    self.renderConversations();
                    
                    // If viewing a conversation, load it
                    if (DENTAL_CHAT.conversation_id) {
                        self.loadConversation(DENTAL_CHAT.conversation_id);
                    } else if (DENTAL_CHAT.recipient_id) {
                        self.loadRecipientInfo(DENTAL_CHAT.recipient_id);
                    }
                },
                error: function() {
                    // Show error message
                    self.$conversationList.html('<div class="error-message">Error al cargar las conversaciones</div>');
                }
            });
        },

        // Render the conversation list
        renderConversations: function() {
            var self = this;
            var html = '';
            
            if (this.data.conversations.length === 0) {
                // Show empty state
                html = this.templates.emptyConversations({});
                this.$conversationList.html(html);
                return;
            }
            
            // Format and render each conversation
            $.each(this.data.conversations, function(i, conversation) {
                // Format time
                conversation.last_message_time = self.formatTime(conversation.last_message_date);
                
                // Set active class if this is the current conversation
                conversation.active = (DENTAL_CHAT.conversation_id === conversation.id) ? 'active' : '';
                
                // Format last message
                if (conversation.last_message && conversation.last_message.length > 40) {
                    conversation.last_message = conversation.last_message.substring(0, 40) + '...';
                }
                
                html += self.templates.conversationItem(conversation);
            });
            
            this.$conversationList.html(html);
        },

        // Search conversations
        searchConversations: function(query) {
            if (!query) {
                // If empty query, show all conversations
                this.renderConversations();
                return;
            }
            
            query = query.toLowerCase();
            var filteredConversations = this.data.conversations.filter(function(conversation) {
                return conversation.participant.display_name.toLowerCase().includes(query);
            });
            
            var html = '';
            
            if (filteredConversations.length === 0) {
                // No matches found
                html = '<div class="no-results">No se encontraron resultados</div>';
            } else {
                // Format and render matching conversations
                var self = this;
                $.each(filteredConversations, function(i, conversation) {
                    conversation.last_message_time = self.formatTime(conversation.last_message_date);
                    conversation.active = (DENTAL_CHAT.conversation_id === conversation.id) ? 'active' : '';
                    html += self.templates.conversationItem(conversation);
                });
            }
            
            this.$conversationList.html(html);
        },

        // Load a specific conversation
        loadConversation: function(conversationId) {
            var self = this;
            
            // Show loading spinner
            this.$chatHeader.html('<div class="loading-spinner"><div class="spinner"></div></div>');
            this.$chatMessages.html('<div class="loading-spinner"><div class="spinner"></div></div>');
            
            // Update URL without reloading page
            if (history.pushState && conversationId) {
                var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?conversation_id=' + conversationId;
                window.history.pushState({ path: newUrl }, '', newUrl);
                DENTAL_CHAT.conversation_id = conversationId;
                DENTAL_CHAT.recipient_id = 0;
            }
            
            // Make API request
            $.ajax({
                url: DENTAL_CHAT.rest_url + '/messaging/conversations/' + conversationId,
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                },
                success: function(response, status, xhr) {
                    // Store data
                    self.data.currentConversation = response.conversation;
                    self.data.messages = response.messages;
                    
                    // Update total pages from header
                    self.data.totalPages = parseInt(xhr.getResponseHeader('X-WP-TotalPages') || 1);
                    self.data.currentPage = 1;
                    
                    // Render header with recipient info
                    self.renderChatHeader(response.conversation.participant);
                    
                    // Render messages
                    self.renderMessages();
                    
                    // Mark messages as read
                    self.markMessagesAsRead(conversationId);
                    
                    // Highlight active conversation in sidebar
                    self.$conversationList.find('.conversation-item').removeClass('active');
                    self.$conversationList.find('[data-conversation-id="' + conversationId + '"]').addClass('active');
                },
                error: function() {
                    // Show error message
                    self.$chatMessages.html('<div class="error-message">Error al cargar la conversación</div>');
                }
            });
        },

        // Load recipient info when starting a new conversation
        loadRecipientInfo: function(recipientId) {
            var self = this;
            
            // Show loading spinner
            this.$chatHeader.html('<div class="loading-spinner"><div class="spinner"></div></div>');
            this.$chatMessages.html('<div class="no-messages">No hay mensajes aún</div>');
            
            // Make API request to get user info
            $.ajax({
                url: DENTAL_CHAT.ajaxurl,
                method: 'POST',
                data: {
                    action: 'dental_get_user_info',
                    user_id: recipientId,
                    nonce: DENTAL_CHAT.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Render header with recipient info
                        self.renderChatHeader(response.data);
                    } else {
                        // Show error message
                        self.$chatHeader.html('<div class="error-message">Usuario no encontrado</div>');
                    }
                },
                error: function() {
                    // Show error message
                    self.$chatHeader.html('<div class="error-message">Error al cargar la información del usuario</div>');
                }
            });
        },

        // Render chat header with recipient info
        renderChatHeader: function(recipient) {
            var headerData = {
                display_name: recipient.display_name,
                avatar: recipient.avatar,
                online: false, // We'll implement online status later
                id: recipient.id
            };
            
            this.$chatHeader.html(this.templates.chatHeader(headerData));
        },

        // Render messages
        renderMessages: function() {
            var self = this;
            var html = '';
            
            if (this.data.messages.length === 0) {
                // Show empty state
                html = '<div class="no-messages">No hay mensajes aún</div>';
                this.$chatMessages.html(html);
                return;
            }
            
            // Show load more button if there are more pages
            if (this.data.currentPage < this.data.totalPages) {
                html += '<div class="load-more-messages">';
                html += '<button class="load-more-button">' + DENTAL_CHAT.strings.load_more + '</button>';
                html += '</div>';
            }
            
            // Format and render each message
            $.each(this.data.messages, function(i, message) {
                var messageData = self.formatMessageData(message);
                html += self.templates.chatMessage(messageData);
            });
            
            this.$chatMessages.html(html);
            
            // Scroll to bottom
            this.scrollToBottom();
        },

        // Format message data for rendering
        formatMessageData: function(message) {
            var isSender = parseInt(message.sender_id) === DENTAL_CHAT.current_user_id;
            var messageClass = isSender ? 'outgoing' : 'incoming';
            
            // Get sender information
            var senderName = isSender ? 'Tú' : this.data.currentConversation.participant.display_name;
            var senderAvatar = isSender ? '' : this.data.currentConversation.participant.avatar;
            
            // Determine message status
            var status = 'sent'; // Default
            if (message.read) {
                status = 'read';
            } else if (message.delivered) {
                status = 'delivered';
            }
            
            return {
                id: message.id,
                message: message.message,
                time: this.formatTime(message.created_at),
                message_class: messageClass,
                is_sender: isSender,
                sender_name: senderName,
                sender_avatar: senderAvatar,
                status: status
            };
        },

        // Send a new message
        sendMessage: function() {
            var self = this;
            var message = this.$chatInput.val().trim();
            
            // Validate message
            if (!message) {
                alert(DENTAL_CHAT.strings.empty_message);
                return;
            }
            
            // Disable input while sending
            this.$chatInput.prop('disabled', true);
            this.$sendButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            // Determine recipient
            var recipientId = 0;
            if (this.data.currentConversation && this.data.currentConversation.participant) {
                recipientId = this.data.currentConversation.participant.id;
            } else if (DENTAL_CHAT.recipient_id) {
                recipientId = DENTAL_CHAT.recipient_id;
            }
            
            if (!recipientId) {
                alert('Error: No se pudo determinar el destinatario');
                this.$chatInput.prop('disabled', false);
                this.$sendButton.prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
                return;
            }
            
            // Make API request
            $.ajax({
                url: DENTAL_CHAT.rest_url + '/messaging/send',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                },
                data: {
                    recipient_id: recipientId,
                    message: message
                },
                success: function(response) {
                    // Clear input
                    self.$chatInput.val('');
                    
                    // Load the conversation with the new message
                    if (response.conversation_id) {
                        self.loadConversation(response.conversation_id);
                    }
                    
                    // Update message limit display for dentists
                    if (DENTAL_CHAT.user_type === 'dentist' && !DENTAL_CHAT.is_premium) {
                        self.updateMessageLimitInfo();
                    }
                },
                error: function(xhr) {
                    var errorMessage = DENTAL_CHAT.strings.error_sending;
                    
                    // Check for specific error messages
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    alert(errorMessage);
                    
                    // If limit reached, reload page to show upgrade button
                    if (xhr.responseJSON && xhr.responseJSON.code === 'rest_message_limit') {
                        window.location.reload();
                    }
                },
                complete: function() {
                    // Re-enable input
                    self.$chatInput.prop('disabled', false).focus();
                    self.$sendButton.prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
                }
            });
        },

        // Mark messages as read
        markMessagesAsRead: function(conversationId) {
            // Make API request
            $.ajax({
                url: DENTAL_CHAT.rest_url + '/messaging/mark-read',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                },
                data: {
                    conversation_id: conversationId
                },
                success: function() {
                    // Update unread count in conversation list
                    var $conversation = $('.conversation-item[data-conversation-id="' + conversationId + '"]');
                    $conversation.find('.unread-badge').remove();
                }
            });
        },

        // Send typing status
        sendTypingStatus: function() {
            var self = this;
            
            // Clear previous timeout
            if (this.data.typingTimeout) {
                clearTimeout(this.data.typingTimeout);
            }
            
            // Send typing status
            if (this.data.currentConversation) {
                // For now, we'll simulate this with a timeout
                // In a real implementation, this would send a notification to the server
                
                // Set timeout to clear typing status
                this.data.typingTimeout = setTimeout(function() {
                    // Clear typing status
                }, 3000);
            }
        },

        // Navigate to a conversation
        navigateToConversation: function(conversationId, recipientId) {
            if (conversationId) {
                this.loadConversation(conversationId);
            } else if (recipientId) {
                // Update URL without reloading page
                var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?recipient_id=' + recipientId;
                window.history.pushState({ path: newUrl }, '', newUrl);
                DENTAL_CHAT.conversation_id = '';
                DENTAL_CHAT.recipient_id = recipientId;
                
                this.loadRecipientInfo(recipientId);
            }
            
            // Start polling if not already
            this.startPolling();
        },

        // Refresh current conversation
        refreshCurrentConversation: function() {
            if (DENTAL_CHAT.conversation_id) {
                this.loadConversation(DENTAL_CHAT.conversation_id);
            } else {
                this.loadConversations();
            }
        },

        // Load more messages (pagination)
        loadMoreMessages: function() {
            var self = this;
            
            if (this.data.currentPage >= this.data.totalPages) {
                return;
            }
            
            // Increment page
            this.data.currentPage++;
            
            // Replace load more button with loading spinner
            this.$chatMessages.find('.load-more-messages').html('<div class="loading-spinner"><div class="spinner"></div></div>');
            
            // Make API request
            $.ajax({
                url: DENTAL_CHAT.rest_url + '/messaging/conversations/' + DENTAL_CHAT.conversation_id,
                method: 'GET',
                data: {
                    page: this.data.currentPage
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                },
                success: function(response, status, xhr) {
                    // Add new messages to existing ones
                    self.data.messages = self.data.messages.concat(response.messages);
                    
                    // Re-render messages
                    self.renderMessages();
                },
                error: function() {
                    // Show error message
                    self.$chatMessages.find('.load-more-messages').html('<div class="error-message">Error al cargar más mensajes</div>');
                }
            });
        },

        // Start polling for new messages
        startPolling: function() {
            var self = this;
            
            // Don't start multiple polling instances
            if (this.data.isPolling) {
                return;
            }
            
            this.data.isPolling = true;
            
            // Define polling function
            var pollForUpdates = function() {
                // Check if user has been inactive for 5 minutes
                if (Date.now() - self.data.lastActivity > 5 * 60 * 1000) {
                    // Stop polling if user is inactive
                    self.data.isPolling = false;
                    clearTimeout(self.data.pollTimeoutId);
                    self.data.pollTimeoutId = null;
                    return;
                }
                
                // If viewing a conversation, check for new messages
                if (DENTAL_CHAT.conversation_id) {
                    $.ajax({
                        url: DENTAL_CHAT.rest_url + '/messaging/conversations/' + DENTAL_CHAT.conversation_id,
                        method: 'GET',
                        data: {
                            page: 1 // Only get the latest messages
                        },
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                        },
                        success: function(response) {
                            // Check if there are new messages
                            if (response.messages.length > 0 && 
                                (self.data.messages.length === 0 || 
                                 response.messages[0].id !== self.data.messages[0].id)) {
                                
                                // Update messages
                                self.data.messages = response.messages;
                                self.renderMessages();
                                
                                // Mark as read
                                self.markMessagesAsRead(DENTAL_CHAT.conversation_id);
                            }
                        }
                    });
                }
                
                // Always refresh conversation list to show new messages from other conversations
                $.ajax({
                    url: DENTAL_CHAT.rest_url + '/messaging/conversations',
                    method: 'GET',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                    },
                    success: function(response) {
                        // Check if there are changes to the conversation list
                        var hasChanges = false;
                        
                        if (self.data.conversations.length !== response.length) {
                            hasChanges = true;
                        } else {
                            // Check if any conversation has new messages
                            for (var i = 0; i < response.length; i++) {
                                if (i >= self.data.conversations.length || 
                                    response[i].unread_count !== self.data.conversations[i].unread_count ||
                                    response[i].last_message !== self.data.conversations[i].last_message) {
                                    hasChanges = true;
                                    break;
                                }
                            }
                        }
                        
                        if (hasChanges) {
                            // Update conversations
                            self.data.conversations = response;
                            self.renderConversations();
                            
                            // Show notification if there are new messages
                            var totalUnread = 0;
                            for (var i = 0; i < response.length; i++) {
                                totalUnread += parseInt(response[i].unread_count || 0);
                            }
                            
                            if (totalUnread > 0 && !document.hasFocus()) {
                                // Browser notification if supported
                                self.showNotification('Nuevos mensajes', 'Tienes ' + totalUnread + ' mensaje(s) sin leer');
                                
                                // Change page title
                                document.title = '(' + totalUnread + ') ' + document.title.replace(/^\(\d+\) /, '');
                            }
                        }
                    }
                });
                
                // Continue polling
                self.data.pollTimeoutId = setTimeout(pollForUpdates, self.data.pollInterval);
            };
            
            // Start polling
            pollForUpdates();
        },

        // Show browser notification
        showNotification: function(title, message) {
            if (!("Notification" in window)) {
                return;
            }
            
            if (Notification.permission === "granted") {
                new Notification(title, {
                    body: message,
                    icon: '/wp-content/plugins/dental-directory-system/assets/images/logo-icon.png'
                });
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(function(permission) {
                    if (permission === "granted") {
                        new Notification(title, {
                            body: message,
                            icon: '/wp-content/plugins/dental-directory-system/assets/images/logo-icon.png'
                        });
                    }
                });
            }
        },

        // Update message limit info after sending a message
        updateMessageLimitInfo: function() {
            var self = this;
            
            // Make API request to get updated limit status
            $.ajax({
                url: DENTAL_CHAT.ajaxurl,
                method: 'POST',
                data: {
                    action: 'dental_get_message_limit_status',
                    nonce: DENTAL_CHAT.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var limitStatus = response.data;
                        
                        // Update message limit info
                        $('.message-limit-info').text('Mensajes: ' + limitStatus.message_count + '/' + limitStatus.limit);
                        
                        // Reload if limit reached
                        if (limitStatus.limit_reached) {
                            window.location.reload();
                        }
                    }
                }
            });
        },

        // Handle subscription upgrade
        upgradeSubscription: function() {
            window.location.href = window.location.protocol + "//" + window.location.host + window.location.pathname + '?view=subscription&action=upgrade';
        },

        // Format timestamp to human-readable time
        formatTime: function(timestamp) {
            if (!timestamp) {
                return '';
            }
            
            var date = new Date(timestamp);
            var now = new Date();
            
            // Check if same day
            if (date.toDateString() === now.toDateString()) {
                // Format as time
                return this.pad(date.getHours()) + ':' + this.pad(date.getMinutes());
            } else if (date.getFullYear() === now.getFullYear()) {
                // Format as month and day
                return date.getDate() + '/' + (date.getMonth() + 1);
            } else {
                // Format as day/month/year
                return date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear();
            }
        },

        // Pad numbers with leading zero
        pad: function(num) {
            return (num < 10 ? '0' : '') + num;
        },

        // Scroll chat messages to bottom
        scrollToBottom: function() {
            this.$chatMessages.scrollTop(this.$chatMessages[0].scrollHeight);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize chat if container exists
        if ($('.dental-chat-container').length > 0) {
            DentalChat.init();
        }
    });

})(jQuery);
