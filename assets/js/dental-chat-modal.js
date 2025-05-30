/**
 * Dental Directory Chat Modal JavaScript
 * 
 * Provides functionality for the chat modal in dentist profiles
 */

(function($) {
    'use strict';

    // Chat Modal component
    var DentalChatModal = {
        // Store for chat data
        data: {
            conversation: null,
            messages: [],
            typingTimeout: null,
            pollInterval: 5000, // 5 seconds
            pollTimeoutId: null,
            isPolling: false
        },

        // Templates
        templates: {
            chatHeader: wp.template('chat-header-modal'),
            chatMessage: wp.template('chat-message-modal')
        },

        // Initialize the chat modal
        init: function() {
            // Cache DOM elements
            this.cacheDom();
            
            // Bind events
            this.bindEvents();
        },

        // Cache DOM elements
        cacheDom: function() {
            this.$modal = $('#dental-chat-modal');
            this.$modalClose = $('.chat-modal-close');
            this.$chatButtons = $('.dental-chat-button');
            this.$chatForm = $('#dental-chat-form');
            this.$chatInput = $('#dental-chat-input');
            this.$sendButton = $('#dental-send-message');
            this.$chatHeader = $('#dental-chat-header');
            this.$chatMessages = $('#dental-chat-messages');
            this.$typingIndicator = $('#dental-typing-indicator');
        },

        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Open modal when chat button is clicked
            this.$chatButtons.on('click', function(e) {
                e.preventDefault();
                
                // Get recipient ID from data attribute
                var recipientId = $(this).data('recipient-id');
                
                // Open modal and load recipient info
                self.openModal(recipientId);
            });
            
            // Close modal when close button is clicked
            this.$modalClose.on('click', function() {
                self.closeModal();
            });
            
            // Close modal when clicking outside the modal content
            this.$modal.on('click', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });
            
            // Close modal when ESC key is pressed
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.$modal.is(':visible')) {
                    self.closeModal();
                }
            });
            
            // Send message on form submit
            this.$chatForm && this.$chatForm.on('submit', function(e) {
                e.preventDefault();
                self.sendMessage();
            });
            
            // Show typing indicator when user types
            this.$chatInput && this.$chatInput.on('input', function() {
                self.sendTypingStatus();
            });
            
            // Refresh button click
            $(document).on('click', '.refresh-button', function() {
                self.refreshConversation();
            });
        },

        // Open the chat modal
        openModal: function(recipientId) {
            // Show modal
            this.$modal.css('display', 'block');
            
            // Load recipient info
            this.loadRecipientInfo(recipientId);
            
            // Start polling for new messages
            this.startPolling(recipientId);
            
            // Focus on chat input
            if (this.$chatInput) {
                setTimeout(function() {
                    this.$chatInput.focus();
                }.bind(this), 300);
            }
        },

        // Close the chat modal
        closeModal: function() {
            // Hide modal
            this.$modal.css('display', 'none');
            
            // Stop polling
            this.stopPolling();
            
            // Clear chat data
            this.data.conversation = null;
            this.data.messages = [];
        },

        // Load recipient info
        loadRecipientInfo: function(recipientId) {
            var self = this;
            
            // Show loading spinner
            this.$chatHeader.html('<div class="loading-spinner"><div class="spinner"></div></div>');
            this.$chatMessages.html('<div class="loading-spinner"><div class="spinner"></div></div>');
            
            // Make API request to get user info
            $.ajax({
                url: DENTAL_CHAT_MODAL.ajaxurl,
                method: 'POST',
                data: {
                    action: 'dental_get_user_info',
                    user_id: recipientId,
                    nonce: DENTAL_CHAT_MODAL.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Store recipient info
                        self.data.recipient = response.data;
                        
                        // Render header with recipient info
                        self.renderChatHeader(response.data);
                        
                        // Check if conversation exists
                        self.checkExistingConversation(recipientId);
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

        // Check if a conversation exists with the recipient
        checkExistingConversation: function(recipientId) {
            var self = this;
            
            // Make API request to get conversations
            $.ajax({
                url: DENTAL_CHAT_MODAL.rest_url + '/messaging/conversations',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                },
                success: function(response) {
                    var conversationFound = false;
                    
                    // Check if conversation with recipient exists
                    for (var i = 0; i < response.length; i++) {
                        if (response[i].participant.id == recipientId) {
                            // Conversation found, load it
                            self.loadConversation(response[i].id);
                            conversationFound = true;
                            break;
                        }
                    }
                    
                    // If no conversation found, show empty state
                    if (!conversationFound) {
                        self.$chatMessages.html('<div class="no-messages">No hay mensajes aún</div>');
                    }
                },
                error: function() {
                    // Show error message
                    self.$chatMessages.html('<div class="error-message">Error al cargar las conversaciones</div>');
                }
            });
        },

        // Load a specific conversation
        loadConversation: function(conversationId) {
            var self = this;
            
            // Make API request
            $.ajax({
                url: DENTAL_CHAT_MODAL.rest_url + '/messaging/conversations/' + conversationId,
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                },
                success: function(response) {
                    // Store data
                    self.data.conversation = response.conversation;
                    self.data.messages = response.messages;
                    
                    // Render messages
                    self.renderMessages();
                    
                    // Mark messages as read
                    self.markMessagesAsRead(conversationId);
                },
                error: function() {
                    // Show error message
                    self.$chatMessages.html('<div class="error-message">Error al cargar la conversación</div>');
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
            var isSender = parseInt(message.sender_id) === DENTAL_CHAT_MODAL.current_user_id;
            var messageClass = isSender ? 'outgoing' : 'incoming';
            
            // Get sender information
            var senderName = isSender ? 'Tú' : this.data.recipient.display_name;
            var senderAvatar = isSender ? '' : this.data.recipient.avatar;
            
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
                alert(DENTAL_CHAT_MODAL.strings.empty_message);
                return;
            }
            
            // Disable input while sending
            this.$chatInput.prop('disabled', true);
            this.$sendButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            
            // Get recipient ID
            var recipientId = this.data.recipient.id;
            
            // Make API request
            $.ajax({
                url: DENTAL_CHAT_MODAL.rest_url + '/messaging/send',
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
                    if (DENTAL_CHAT_MODAL.user_type === 'dentist' && !DENTAL_CHAT_MODAL.is_premium) {
                        self.updateMessageLimitInfo();
                    }
                },
                error: function(xhr) {
                    var errorMessage = DENTAL_CHAT_MODAL.strings.error_sending;
                    
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
                url: DENTAL_CHAT_MODAL.rest_url + '/messaging/mark-read',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wp.api.settings.nonce);
                },
                data: {
                    conversation_id: conversationId
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
            
            // For now, we'll simulate this with a timeout
            // In a real implementation, this would send a notification to the server
            
            // Set timeout to clear typing status
            this.data.typingTimeout = setTimeout(function() {
                // Clear typing status
            }, 3000);
        },

        // Refresh current conversation
        refreshConversation: function() {
            if (this.data.conversation) {
                this.loadConversation(this.data.conversation.id);
            } else {
                this.checkExistingConversation(this.data.recipient.id);
            }
        },

        // Start polling for new messages
        startPolling: function(recipientId) {
            var self = this;
            
            // Don't start multiple polling instances
            if (this.data.isPolling) {
                return;
            }
            
            this.data.isPolling = true;
            
            // Define polling function
            var pollForUpdates = function() {
                // If conversation exists, check for new messages
                if (self.data.conversation) {
                    $.ajax({
                        url: DENTAL_CHAT_MODAL.rest_url + '/messaging/conversations/' + self.data.conversation.id,
                        method: 'GET',
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
                                self.markMessagesAsRead(self.data.conversation.id);
                            }
                        }
                    });
                } else if (recipientId) {
                    // Check if a conversation has been created
                    self.checkExistingConversation(recipientId);
                }
                
                // Continue polling if modal is still open
                if (self.$modal.is(':visible')) {
                    self.data.pollTimeoutId = setTimeout(pollForUpdates, self.data.pollInterval);
                } else {
                    self.stopPolling();
                }
            };
            
            // Start polling
            pollForUpdates();
        },

        // Stop polling for new messages
        stopPolling: function() {
            if (this.data.pollTimeoutId) {
                clearTimeout(this.data.pollTimeoutId);
                this.data.pollTimeoutId = null;
            }
            
            this.data.isPolling = false;
        },

        // Update message limit info after sending a message
        updateMessageLimitInfo: function() {
            var self = this;
            
            // Make API request to get updated limit status
            $.ajax({
                url: DENTAL_CHAT_MODAL.ajaxurl,
                method: 'POST',
                data: {
                    action: 'dental_get_message_limit_status',
                    nonce: DENTAL_CHAT_MODAL.nonce
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
        // Initialize chat modal if modal exists
        if ($('#dental-chat-modal').length > 0) {
            DentalChatModal.init();
        }
    });

})(jQuery);
