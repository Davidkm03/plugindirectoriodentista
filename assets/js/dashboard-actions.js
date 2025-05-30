/**
 * Dashboard Actions JavaScript
 *
 * Handles all dashboard interaction with AJAX endpoints
 *
 * @package    DentalDirectorySystem
 */

(function($) {
    'use strict';
    
    // Store conversation data
    var dentalDashboard = {
        currentConversation: '',
        currentRecipient: 0,
        messagesLoaded: false,
        messagePollingInterval: null,
        ajaxUrl: dental_ajax_object.ajax_url,
        ajaxNonce: dental_ajax_object.nonce,
        isDentist: dental_ajax_object.is_dentist || false,
        isPatient: dental_ajax_object.is_patient || false,
        userId: dental_ajax_object.user_id || 0,
        translations: dental_ajax_object.translations || {}
    };
    
    /**
     * Initialize dashboard actions
     */
    function initDashboardActions() {
        // Init messages
        initMessageActions();
        
        // Init favorites
        initFavoritesActions();
        
        // Init subscription
        initSubscriptionActions();
        
        // Handle tab navigation
        handleTabNavigation();
    }
    
    /**
     * Initialize message actions
     */
    function initMessageActions() {
        // Load conversation on click
        $(document).on('click', '.dental-conversation-item', function(e) {
            e.preventDefault();
            var $item = $(this);
            var conversationId = $item.data('conversation-id') || '';
            var recipientId = $item.data('recipient-id') || 0;
            
            // Update active state
            $('.dental-conversation-item').removeClass('active');
            $item.addClass('active');
            
            // Load conversation
            loadConversation(conversationId, recipientId);
        });
        
        // Send message on form submit
        $(document).on('submit', '#dental-reply-form', function(e) {
            e.preventDefault();
            
            var message = $('#dental-reply-input').val().trim();
            if (message === '') {
                return;
            }
            
            sendMessage(message, dentalDashboard.currentRecipient, dentalDashboard.currentConversation);
        });
        
        // Auto-load first conversation if available
        var $firstConversation = $('.dental-conversation-item:first');
        if ($firstConversation.length) {
            $firstConversation.trigger('click');
        }
        
        // Start message polling if on messages tab
        if ($('.dental-messages-container').length) {
            startMessagePolling();
        }
    }
    
    /**
     * Load conversation
     * 
     * @param {string} conversationId Conversation ID
     * @param {number} recipientId    Recipient user ID
     */
    function loadConversation(conversationId, recipientId) {
        // Show loading state
        $('.dental-conversation-container').html('<div class="dental-loading"><span class="dashicons dashicons-update"></span> ' + (dentalDashboard.translations.loading || 'Loading...') + '</div>');
        
        // Save current conversation data
        dentalDashboard.currentConversation = conversationId;
        dentalDashboard.currentRecipient = recipientId;
        
        // Determine data to send based on user type
        var data = {
            action: 'dental_load_conversation',
            security: dentalDashboard.ajaxNonce,
            conversation_id: conversationId
        };
        
        if (dentalDashboard.isDentist) {
            data.patient_id = recipientId;
        } else if (dentalDashboard.isPatient) {
            data.dentist_id = recipientId;
        }
        
        // Make AJAX request
        $.post(dentalDashboard.ajaxUrl, data, function(response) {
            if (response.success) {
                // Update conversation container
                $('.dental-conversation-container').html(response.data.html);
                
                // Update current conversation ID
                dentalDashboard.currentConversation = response.data.conversation_id;
                
                // Scroll to bottom of conversation
                var $messages = $('#dental-conversation-messages');
                if ($messages.length) {
                    $messages.scrollTop($messages[0].scrollHeight);
                }
                
                // Mark conversation as read in sidebar
                $('.dental-conversation-item[data-conversation-id="' + response.data.conversation_id + '"]')
                    .removeClass('unread')
                    .find('.dental-conversation-unread')
                    .remove();
                
                dentalDashboard.messagesLoaded = true;
            } else {
                // Show error
                $('.dental-conversation-container').html(
                    '<div class="dental-error">' + 
                    (response.data.message || dentalDashboard.translations.error || 'Error loading conversation.') + 
                    '</div>'
                );
            }
        }).fail(function() {
            // Show error on AJAX failure
            $('.dental-conversation-container').html(
                '<div class="dental-error">' + 
                (dentalDashboard.translations.ajax_error || 'Error connecting to server. Please try again later.') + 
                '</div>'
            );
        });
    }
    
    /**
     * Send message
     * 
     * @param {string} message         Message content
     * @param {number} recipientId     Recipient user ID
     * @param {string} conversationId  Conversation ID
     */
    function sendMessage(message, recipientId, conversationId) {
        // Disable form during sending
        var $form = $('#dental-reply-form');
        var $input = $('#dental-reply-input');
        var $button = $('#dental-reply-btn');
        
        $input.prop('disabled', true);
        $button.prop('disabled', true);
        $button.html('<span class="dashicons dashicons-update"></span> ' + (dentalDashboard.translations.sending || 'Sending...'));
        
        var data = {
            action: 'dental_send_message',
            security: dentalDashboard.ajaxNonce,
            message: message,
            recipient_id: recipientId,
            conversation_id: conversationId
        };
        
        $.post(dentalDashboard.ajaxUrl, data, function(response) {
            // Re-enable form
            $input.prop('disabled', false).val('').focus();
            $button.prop('disabled', false).text(dentalDashboard.translations.send || 'Send');
            
            if (response.success) {
                // Append message to conversation
                $('#dental-conversation-messages').append(response.data.message_html);
                
                // Scroll to bottom of conversation
                var $messages = $('#dental-conversation-messages');
                if ($messages.length) {
                    $messages.scrollTop($messages[0].scrollHeight);
                }
                
                // Update message count if needed (for dentists on free plan)
                if (response.data.monthly_count !== undefined) {
                    var countText = response.data.monthly_count + ' ' + (dentalDashboard.translations.of_messages || 'of 5 messages sent this month.');
                    $('.dental-reply-limit-warning p').html(
                        '<i class="dashicons dashicons-warning"></i> ' + 
                        dentalDashboard.translations.free_plan + ': ' + 
                        countText + 
                        ' <a href="' + window.location.href.split('?')[0] + '?view=upgrade">' + 
                        dentalDashboard.translations.upgrade + 
                        '</a>'
                    );
                }
            } else {
                // Show error
                if (response.data.limit_reached) {
                    // Show limit reached error
                    $('.dental-reply-limit-error').show();
                } else {
                    // Show generic error
                    alert(response.data.message || dentalDashboard.translations.send_error || 'Error sending message.');
                }
            }
        }).fail(function() {
            // Re-enable form
            $input.prop('disabled', false);
            $button.prop('disabled', false).text(dentalDashboard.translations.send || 'Send');
            
            // Show error
            alert(dentalDashboard.translations.ajax_error || 'Error connecting to server. Please try again later.');
        });
    }
    
    /**
     * Start message polling
     */
    function startMessagePolling() {
        // Poll for new messages every 30 seconds
        dentalDashboard.messagePollingInterval = setInterval(function() {
            if (dentalDashboard.messagesLoaded && dentalDashboard.currentConversation) {
                pollNewMessages();
            }
        }, 30000); // 30 seconds
    }
    
    /**
     * Poll for new messages
     */
    function pollNewMessages() {
        // Only poll if we have a current conversation
        if (!dentalDashboard.currentConversation || !dentalDashboard.currentRecipient) {
            return;
        }
        
        var data = {
            action: 'dental_load_conversation',
            security: dentalDashboard.ajaxNonce,
            conversation_id: dentalDashboard.currentConversation
        };
        
        if (dentalDashboard.isDentist) {
            data.patient_id = dentalDashboard.currentRecipient;
        } else if (dentalDashboard.isPatient) {
            data.dentist_id = dentalDashboard.currentRecipient;
        }
        
        $.post(dentalDashboard.ajaxUrl, data, function(response) {
            if (response.success) {
                // Replace conversation with updated HTML
                $('.dental-conversation-container').html(response.data.html);
                
                // Scroll to bottom of conversation
                var $messages = $('#dental-conversation-messages');
                if ($messages.length) {
                    $messages.scrollTop($messages[0].scrollHeight);
                }
            }
        });
    }
    
    /**
     * Initialize favorites actions
     */
    function initFavoritesActions() {
        // Toggle favorite on dentist card
        $(document).on('click', '.dental-favorite-toggle', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var dentistId = $button.data('dentist-id');
            
            // Prevent multiple clicks
            if ($button.hasClass('dental-processing')) {
                return;
            }
            
            // Add processing class
            $button.addClass('dental-processing');
            
            toggleFavorite(dentistId, $button);
        });
        
        // Remove favorite from favorites list
        $(document).on('click', '.dental-favorite-remove', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var dentistId = $button.data('dentist-id');
            var $item = $button.closest('.dental-favorite-item');
            
            // Prevent multiple clicks
            if ($button.hasClass('dental-processing')) {
                return;
            }
            
            // Add processing class
            $button.addClass('dental-processing');
            
            removeFavorite(dentistId, $item);
        });
    }
    
    /**
     * Toggle dentist as favorite
     * 
     * @param {number} dentistId Dentist user ID
     * @param {object} $button   jQuery button element
     */
    function toggleFavorite(dentistId, $button) {
        var data = {
            action: 'dental_toggle_favorite',
            security: dentalDashboard.ajaxNonce,
            dentist_id: dentistId
        };
        
        $.post(dentalDashboard.ajaxUrl, data, function(response) {
            // Remove processing class
            $button.removeClass('dental-processing');
            
            if (response.success) {
                if (response.data.status === 'added') {
                    // Update button to filled heart
                    $button.html('<span class="dashicons dashicons-heart"></span>');
                    $button.attr('title', dentalDashboard.translations.remove_favorite || 'Remove from favorites');
                    $button.removeClass('dental-favorite-add').addClass('dental-favorite-active');
                } else {
                    // Update button to empty heart
                    $button.html('<span class="dashicons dashicons-heart-empty"></span>');
                    $button.attr('title', dentalDashboard.translations.add_favorite || 'Add to favorites');
                    $button.removeClass('dental-favorite-active').addClass('dental-favorite-add');
                }
                
                // Show temporary notification
                showNotification(response.data.message, 'success');
            } else {
                // Show error
                showNotification(response.data.message, 'error');
            }
        }).fail(function() {
            // Remove processing class
            $button.removeClass('dental-processing');
            
            // Show error
            showNotification(dentalDashboard.translations.ajax_error || 'Error connecting to server.', 'error');
        });
    }
    
    /**
     * Remove dentist from favorites
     * 
     * @param {number} dentistId Dentist user ID
     * @param {object} $item     jQuery list item element
     */
    function removeFavorite(dentistId, $item) {
        var data = {
            action: 'dental_remove_favorite',
            security: dentalDashboard.ajaxNonce,
            dentist_id: dentistId
        };
        
        $.post(dentalDashboard.ajaxUrl, data, function(response) {
            if (response.success) {
                // Remove item with animation
                $item.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if list is empty
                    if ($('.dental-favorite-item').length === 0) {
                        $('.dental-favorites-list').html(
                            '<div class="dental-empty-state">' + 
                            '<p>' + (dentalDashboard.translations.no_favorites || 'You have no favorite dentists yet.') + '</p>' +
                            '<a href="' + (dentalDashboard.translations.find_dentists_url || '') + '" class="dental-btn dental-btn-primary">' + 
                            (dentalDashboard.translations.find_dentists || 'Find Dentists') + 
                            '</a>' +
                            '</div>'
                        );
                    }
                    
                    // Show temporary notification
                    showNotification(response.data.message, 'success');
                });
            } else {
                // Remove processing class from button
                $item.find('.dental-favorite-remove').removeClass('dental-processing');
                
                // Show error
                showNotification(response.data.message, 'error');
            }
        }).fail(function() {
            // Remove processing class from button
            $item.find('.dental-favorite-remove').removeClass('dental-processing');
            
            // Show error
            showNotification(dentalDashboard.translations.ajax_error || 'Error connecting to server.', 'error');
        });
    }
    
    /**
     * Initialize subscription actions
     */
    function initSubscriptionActions() {
        // Handle subscription plan selection
        $(document).on('click', '.dental-plan-select', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var plan = $button.data('plan');
            
            // Prevent multiple clicks
            if ($button.hasClass('dental-processing')) {
                return;
            }
            
            // Ask for confirmation
            if (confirm(dentalDashboard.translations.confirm_subscription || 'Are you sure you want to subscribe to this plan? You will be redirected to the payment gateway.')) {
                // Add processing class
                $button.addClass('dental-processing')
                    .html('<span class="dashicons dashicons-update"></span> ' + 
                         (dentalDashboard.translations.processing || 'Processing...'));
                
                subscribeToPlan(plan);
            }
        });
        
        // Handle subscription renewal
        $(document).on('click', '.dental-subscription-renew', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            
            // Prevent multiple clicks
            if ($button.hasClass('dental-processing')) {
                return;
            }
            
            // Ask for confirmation
            if (confirm(dentalDashboard.translations.confirm_renewal || 'Are you sure you want to renew your subscription? You will be redirected to the payment gateway.')) {
                // Add processing class
                $button.addClass('dental-processing')
                    .html('<span class="dashicons dashicons-update"></span> ' + 
                         (dentalDashboard.translations.processing || 'Processing...'));
                
                renewSubscription();
            }
        });
    }
    
    /**
     * Subscribe to a plan
     * 
     * @param {string} plan Plan type (monthly|yearly)
     */
    function subscribeToPlan(plan) {
        var data = {
            action: 'dental_subscribe_plan',
            security: dentalDashboard.ajaxNonce,
            plan: plan
        };
        
        $.post(dentalDashboard.ajaxUrl, data, function(response) {
            // Remove processing class from all buttons
            $('.dental-plan-select').removeClass('dental-processing')
                .html(dentalDashboard.translations.select_plan || 'Select Plan');
            
            if (response.success) {
                // In a real implementation, we would redirect to payment gateway
                // window.location.href = response.data.payment_url;
                
                // For now, show success message and refresh page
                showNotification(response.data.message, 'success');
                
                // Refresh page after 2 seconds
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                // Show error
                showNotification(response.data.message, 'error');
            }
        }).fail(function() {
            // Remove processing class from all buttons
            $('.dental-plan-select').removeClass('dental-processing')
                .html(dentalDashboard.translations.select_plan || 'Select Plan');
            
            // Show error
            showNotification(dentalDashboard.translations.ajax_error || 'Error connecting to server.', 'error');
        });
    }
    
    /**
     * Renew subscription
     */
    function renewSubscription() {
        var data = {
            action: 'dental_renew_subscription',
            security: dentalDashboard.ajaxNonce
        };
        
        $.post(dentalDashboard.ajaxUrl, data, function(response) {
            // Remove processing class
            $('.dental-subscription-renew').removeClass('dental-processing')
                .html(dentalDashboard.translations.renew || 'Renew');
            
            if (response.success) {
                // In a real implementation, we would redirect to payment gateway
                // window.location.href = response.data.payment_url;
                
                // For now, show success message and refresh page
                showNotification(response.data.message, 'success');
                
                // Refresh page after 2 seconds
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                // Show error
                showNotification(response.data.message, 'error');
            }
        }).fail(function() {
            // Remove processing class
            $('.dental-subscription-renew').removeClass('dental-processing')
                .html(dentalDashboard.translations.renew || 'Renew');
            
            // Show error
            showNotification(dentalDashboard.translations.ajax_error || 'Error connecting to server.', 'error');
        });
    }
    
    /**
     * Handle tab navigation
     */
    function handleTabNavigation() {
        // Handle tab clicks
        $(document).on('click', '.dental-tab-link', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var target = $link.data('target');
            
            // Already active
            if ($link.hasClass('active')) {
                return;
            }
            
            // Update active tab
            $('.dental-tab-link').removeClass('active');
            $link.addClass('active');
            
            // Update content visibility
            $('.dental-tab-content').hide();
            $('#' + target).show();
            
            // Update URL if history API is available
            if (window.history && window.history.pushState) {
                var newUrl = updateQueryStringParameter(window.location.href, 'view', target);
                window.history.pushState({ path: newUrl }, '', newUrl);
            }
            
            // Stop message polling when not on messages tab
            if (target !== 'messages') {
                if (dentalDashboard.messagePollingInterval) {
                    clearInterval(dentalDashboard.messagePollingInterval);
                    dentalDashboard.messagePollingInterval = null;
                }
            } else {
                // Restart polling on messages tab
                if (!dentalDashboard.messagePollingInterval) {
                    startMessagePolling();
                }
            }
            
            // If we're on search tab, focus the search input
            if (target === 'find-dentist') {
                setTimeout(function() {
                    $('#dental-search-input').focus();
                }, 100);
            }
        });
        
        // Handle view parameter in URL
        var view = getParameterByName('view');
        if (view) {
            $('.dental-tab-link[data-target="' + view + '"]').trigger('click');
        }
    }
    
    /**
     * Show notification
     * 
     * @param {string} message Message content
     * @param {string} type    Notification type (success|error|info)
     */
    function showNotification(message, type) {
        // Remove any existing notifications
        $('.dental-notification').remove();
        
        // Create notification element
        var $notification = $('<div class="dental-notification dental-notification-' + type + '">' + message + '</div>');
        
        // Add to document
        $('body').append($notification);
        
        // Show notification with animation
        setTimeout(function() {
            $notification.addClass('dental-notification-show');
        }, 10);
        
        // Hide notification after 3 seconds
        setTimeout(function() {
            $notification.removeClass('dental-notification-show');
            
            // Remove from DOM after animation
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }
    
    /**
     * Get parameter from URL by name
     * 
     * @param {string} name Parameter name
     * @return {string|null} Parameter value
     */
    function getParameterByName(name) {
        var url = window.location.href;
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
    
    /**
     * Update query string parameter in URL
     * 
     * @param {string} uri   Current URL
     * @param {string} key   Parameter key
     * @param {string} value Parameter value
     * @return {string} Updated URL
     */
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp('([?&])' + key + '=.*?(&|$)', 'i');
        var separator = uri.indexOf('?') !== -1 ? '&' : '?';
        
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + '=' + value + '$2');
        } else {
            return uri + separator + key + '=' + value;
        }
    }
    
    // Initialize on document ready
    $(function() {
        initDashboardActions();
    });
    
})(jQuery);
