<?php
/**
 * Dentist Messages Dashboard Template Part
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get user data and messages
$user_id = get_current_user_id();
$subscription_type = dental_get_subscription_type($user_id);
$is_premium = ($subscription_type === 'premium');

// Get message stats
global $wpdb;
$messages_table = $wpdb->prefix . 'dental_messages';
$current_month = date('Y-m');
$monthly_messages = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$messages_table} WHERE dentist_id = %d AND DATE_FORMAT(created_at, '%%Y-%%m') = %s",
        $user_id, $current_month
    )
);

// Get messages grouped by patient
$messages_query = $wpdb->prepare(
    "SELECT m.*, 
        p.display_name as patient_name, 
        (SELECT MAX(created_at) FROM {$messages_table} WHERE conversation_id = m.conversation_id) as last_message_date,
        (SELECT COUNT(*) FROM {$messages_table} WHERE conversation_id = m.conversation_id AND is_read = 0 AND receiver_id = %d) as unread_count
    FROM {$messages_table} m 
    LEFT JOIN {$wpdb->users} p ON m.patient_id = p.ID 
    WHERE m.dentist_id = %d 
    GROUP BY m.conversation_id 
    ORDER BY last_message_date DESC",
    $user_id, $user_id
);

$conversations = $wpdb->get_results($messages_query);
?>

<div class="dental-dashboard-header">
    <h1><?php esc_html_e('Mis Mensajes', 'dental-directory-system'); ?></h1>
    
    <?php if (!$is_premium && $monthly_messages >= 5): ?>
    <div class="dental-alert dental-alert-warning">
        <p>
            <strong><?php esc_html_e('Límite de mensajes alcanzado:', 'dental-directory-system'); ?></strong> 
            <?php esc_html_e('Has alcanzado el límite mensual de 5 mensajes para tu plan gratuito.', 'dental-directory-system'); ?>
            <a href="<?php echo esc_url(add_query_arg('view', 'upgrade', get_permalink())); ?>" class="dental-upgrade-link">
                <?php esc_html_e('Actualizar a Premium para mensajes ilimitados', 'dental-directory-system'); ?> →
            </a>
        </p>
    </div>
    <?php endif; ?>
</div>

<div class="dental-messages-container">
    <!-- Message count and search controls -->
    <div class="dental-messages-header">
        <div class="dental-messages-count">
            <?php printf(esc_html(_n('%d conversación', '%d conversaciones', count($conversations), 'dental-directory-system')), count($conversations)); ?>
        </div>
        <div class="dental-messages-search">
            <input type="text" id="dental-message-search" placeholder="<?php esc_attr_e('Buscar mensajes...', 'dental-directory-system'); ?>" class="dental-search-input">
            <i class="dashicons dashicons-search dental-search-icon"></i>
        </div>
    </div>
    
    <!-- Messages list -->
    <div class="dental-messages-content">
        <div class="dental-messages-list">
            <?php if (empty($conversations)): ?>
                <div class="dental-empty-messages">
                    <div class="dental-empty-icon">
                        <i class="dashicons dashicons-email"></i>
                    </div>
                    <h3><?php esc_html_e('No tienes mensajes', 'dental-directory-system'); ?></h3>
                    <p><?php esc_html_e('Cuando los pacientes te contacten, los mensajes aparecerán aquí.', 'dental-directory-system'); ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conversation): 
                    $patient_id = $conversation->patient_id;
                    $has_unread = $conversation->unread_count > 0;
                ?>
                    <div class="dental-message-item <?php echo $has_unread ? 'has-unread' : ''; ?>" data-conversation="<?php echo esc_attr($conversation->conversation_id); ?>" data-patient="<?php echo esc_attr($patient_id); ?>">
                        <div class="dental-message-avatar">
                            <?php echo get_avatar($patient_id, 50); ?>
                        </div>
                        <div class="dental-message-details">
                            <div class="dental-message-header">
                                <span class="dental-message-sender"><?php echo esc_html($conversation->patient_name); ?></span>
                                <span class="dental-message-time"><?php echo esc_html(human_time_diff(strtotime($conversation->last_message_date), current_time('timestamp'))); ?></span>
                            </div>
                            <div class="dental-message-preview">
                                <?php 
                                // Get last message preview
                                $last_message = $wpdb->get_var(
                                    $wpdb->prepare(
                                        "SELECT message FROM {$messages_table} 
                                        WHERE conversation_id = %s 
                                        ORDER BY created_at DESC LIMIT 1",
                                        $conversation->conversation_id
                                    )
                                );
                                echo wp_trim_words(esc_html($last_message), 10, '...');
                                ?>
                            </div>
                        </div>
                        <?php if ($has_unread): ?>
                            <div class="dental-unread-badge"><?php echo esc_html($conversation->unread_count); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Messages detail view - will be populated with AJAX -->
        <div class="dental-message-detail" id="dental-message-detail">
            <div class="dental-message-placeholder">
                <div class="dental-placeholder-icon">
                    <i class="dashicons dashicons-arrow-left-alt"></i>
                </div>
                <p><?php esc_html_e('Selecciona una conversación para ver los mensajes', 'dental-directory-system'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle message click to load conversation
    $('.dental-message-item').on('click', function() {
        const conversationId = $(this).data('conversation');
        const patientId = $(this).data('patient');
        
        // Mark item as selected
        $('.dental-message-item').removeClass('selected');
        $(this).addClass('selected');
        $(this).removeClass('has-unread');
        $(this).find('.dental-unread-badge').remove();
        
        // Show loading state
        $('#dental-message-detail').html('<div class="dental-loading"><div class="dental-spinner"></div><p><?php esc_html_e("Cargando mensajes...", "dental-directory-system"); ?></p></div>');
        
        // Load conversation via AJAX
        $.ajax({
            url: dental_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'dental_load_conversation',
                conversation_id: conversationId,
                patient_id: patientId,
                security: dental_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#dental-message-detail').html(response.data.html);
                    // Scroll to bottom of messages
                    const messagesContainer = document.querySelector('.dental-conversation-messages');
                    if (messagesContainer) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                    
                    // Initialize message reply
                    initMessageReply(conversationId, patientId);
                } else {
                    $('#dental-message-detail').html('<div class="dental-error"><?php esc_html_e("Error al cargar los mensajes.", "dental-directory-system"); ?></div>');
                }
            },
            error: function() {
                $('#dental-message-detail').html('<div class="dental-error"><?php esc_html_e("Error de conexión al cargar los mensajes.", "dental-directory-system"); ?></div>');
            }
        });
    });
    
    // Initialize message reply functionality
    function initMessageReply(conversationId, patientId) {
        $('#dental-reply-form').on('submit', function(e) {
            e.preventDefault();
            
            const messageText = $('#dental-reply-input').val().trim();
            if (!messageText) return;
            
            // Check if limit reached for free users
            <?php if (!$is_premium && $monthly_messages >= 5): ?>
            $('.dental-reply-limit-error').show();
            return;
            <?php endif; ?>
            
            // Show sending state
            $('#dental-reply-btn').prop('disabled', true).text('<?php esc_html_e("Enviando...", "dental-directory-system"); ?>');
            
            // Send message via AJAX
            $.ajax({
                url: dental_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'dental_send_message',
                    conversation_id: conversationId,
                    recipient_id: patientId,
                    message: messageText,
                    security: dental_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Add message to conversation
                        const newMessage = response.data.message_html;
                        $('.dental-conversation-messages').append(newMessage);
                        
                        // Clear input and reset button
                        $('#dental-reply-input').val('');
                        $('#dental-reply-btn').prop('disabled', false).text('<?php esc_html_e("Enviar", "dental-directory-system"); ?>');
                        
                        // Scroll to bottom of messages
                        const messagesContainer = document.querySelector('.dental-conversation-messages');
                        if (messagesContainer) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                        
                        // Update message count if needed
                        <?php if (!$is_premium): ?>
                        if (response.data.monthly_count) {
                            if (response.data.monthly_count >= 5) {
                                $('.dental-reply-limit-reached').show();
                                $('#dental-reply-form').hide();
                            }
                        }
                        <?php endif; ?>
                    } else {
                        alert(response.data.message || '<?php esc_html_e("Error al enviar el mensaje.", "dental-directory-system"); ?>');
                        $('#dental-reply-btn').prop('disabled', false).text('<?php esc_html_e("Enviar", "dental-directory-system"); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e("Error de conexión al enviar el mensaje.", "dental-directory-system"); ?>');
                    $('#dental-reply-btn').prop('disabled', false).text('<?php esc_html_e("Enviar", "dental-directory-system"); ?>');
                }
            });
        });
    }
    
    // Message search functionality
    $('#dental-message-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.dental-message-item').each(function() {
            const senderName = $(this).find('.dental-message-sender').text().toLowerCase();
            const messageText = $(this).find('.dental-message-preview').text().toLowerCase();
            
            if (senderName.indexOf(searchTerm) > -1 || messageText.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Show empty state if no messages match
        if ($('.dental-message-item:visible').length === 0) {
            if ($('.dental-no-results').length === 0) {
                $('.dental-messages-list').append('<div class="dental-no-results"><p><?php esc_html_e("No se encontraron mensajes que coincidan con tu búsqueda.", "dental-directory-system"); ?></p></div>');
            }
        } else {
            $('.dental-no-results').remove();
        }
    });
});
</script>
