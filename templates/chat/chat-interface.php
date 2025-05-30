<?php
/**
 * Template for the main chat interface
 *
 * @package    DentalDirectorySystem
 * @subpackage Templates/Chat
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get current user ID and type
$current_user_id = get_current_user_id();
$is_dentist = dental_is_dentist( $current_user_id );
$is_patient = dental_is_patient( $current_user_id );

// Security nonce for AJAX
$nonce = wp_create_nonce( 'dental_dashboard_nonce' );

// Check if viewing a specific conversation
$conversation_id = isset( $_GET['conversation_id'] ) ? sanitize_text_field( $_GET['conversation_id'] ) : '';
$recipient_id = isset( $_GET['recipient_id'] ) ? absint( $_GET['recipient_id'] ) : 0;

// For dentists, check message limits
$limit_status = array();
if ( $is_dentist ) {
    require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
    $message_limits = new Dental_Message_Limits();
    $limit_status = $message_limits->get_dentist_limit_status( $current_user_id );
}
?>

<?php
// Check if we need to display limit warnings (only for dentists)
if ( $is_dentist && class_exists( 'Dental_Message_Notifications' ) ) {
    $notifications = Dental_Message_Notifications::get_instance();
    // Display warning banner if approaching limit
    $notifications->render_limit_warning_banner();
}
?>
<div class="dental-chat-container" data-user-id="<?php echo esc_attr( $current_user_id ); ?>" data-user-type="<?php echo $is_dentist ? 'dentist' : 'patient'; ?>">
    <div class="dental-chat-sidebar">
        <div class="chat-sidebar-header">
            <h3><?php esc_html_e( 'Conversaciones', 'dental-directory-system' ); ?></h3>
            <div class="chat-search">
                <input type="text" id="chat-search-input" placeholder="<?php esc_attr_e( 'Buscar...', 'dental-directory-system' ); ?>">
                <i class="fas fa-search"></i>
            </div>
        </div>
        
        <div class="conversation-list" id="dental-conversation-list">
            <div class="loading-spinner">
                <div class="spinner"></div>
            </div>
        </div>
    </div>

    <div class="dental-chat-main">
        <?php if ( empty( $conversation_id ) && empty( $recipient_id ) ) : ?>
            <div class="chat-empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3><?php esc_html_e( 'Selecciona una conversación', 'dental-directory-system' ); ?></h3>
                <p><?php esc_html_e( 'Elige una conversación de la lista o inicia una nueva.', 'dental-directory-system' ); ?></p>
            </div>
        <?php else : ?>
            <div class="chat-header" id="dental-chat-header">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
            <div class="chat-messages" id="dental-chat-messages">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
            <div class="chat-input-area" id="dental-chat-input-area">
            <?php if ( $is_dentist && !empty($limit_status) && !$limit_status['is_premium'] && $limit_status['limit_reached'] ) : ?>
                <div class="dental-limit-block">
                    <div class="icon-container">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h4><?php esc_html_e( 'Límite de mensajes alcanzado', 'dental-directory-system' ); ?></h4>
                    <p><?php esc_html_e( 'Has alcanzado tu límite mensual de mensajes en el plan gratuito.', 'dental-directory-system' ); ?></p>
                    <a href="#" class="dental-button-premium" onclick="DentalAlerts.showUpgradeModal(); return false;">
                        <?php esc_html_e( 'Actualizar a Premium', 'dental-directory-system' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="typing-indicator" id="dental-typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <form id="dental-chat-form">
                    <div class="chat-input-container">
                        <textarea id="dental-chat-input" placeholder="<?php esc_attr_e( 'Escribe un mensaje...', 'dental-directory-system' ); ?>"></textarea>
                        <button type="submit" id="dental-send-message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div class="chat-input-actions">
                        <?php if ( $is_dentist && !empty($limit_status) && !$limit_status['is_premium'] ) : ?>
                            <div class="message-limit-info">
                                <div class="message-limit-count">
                                    <span><?php esc_html_e( 'Plan Gratuito', 'dental-directory-system' ); ?></span>
                                    <span>
                                        <?php echo sprintf( 
                                            esc_html__( '%d/%d mensajes', 'dental-directory-system' ), 
                                            esc_html( $limit_status['message_count'] ), 
                                            esc_html( $limit_status['limit'] ) 
                                        ); ?>
                                    </span>
                                </div>
                                <div class="message-limit-progress-container">
                                    <div class="message-limit-progress-bar <?php echo $limit_status['message_count'] >= ($limit_status['limit'] * 0.75) ? 'progress-warning' : 'progress-good'; ?>" 
                                         data-count="<?php echo esc_attr( $limit_status['message_count'] ); ?>" 
                                         data-limit="<?php echo esc_attr( $limit_status['limit'] ); ?>" 
                                         style="width: <?php echo esc_attr( ($limit_status['message_count'] / $limit_status['limit']) * 100 ); ?>%">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Templates for JS rendering -->
<script type="text/template" id="tmpl-conversation-item">
    <div class="conversation-item {{data.active}}" data-conversation-id="{{data.id}}" data-recipient-id="{{data.participant.id}}">
        <div class="conversation-avatar">
            <img src="{{data.participant.avatar}}" alt="{{data.participant.display_name}}">
            <span class="status-indicator {{data.online}}"></span>
        </div>
        <div class="conversation-content">
            <div class="conversation-header">
                <h4 class="participant-name">{{data.participant.display_name}}</h4>
                <span class="conversation-time">{{data.last_message_time}}</span>
            </div>
            <p class="conversation-preview">{{data.last_message}}</p>
        </div>
        {{#if data.unread_count}}
        <div class="unread-badge">
            <span>{{data.unread_count}}</span>
        </div>
        {{/if}}
    </div>
</script>

<script type="text/template" id="tmpl-chat-header">
    <div class="chat-recipient">
        <div class="recipient-avatar">
            <img src="{{data.avatar}}" alt="{{data.display_name}}">
            <span class="status-indicator {{data.online}}"></span>
        </div>
        <div class="recipient-info">
            <h4>{{data.display_name}}</h4>
            <span class="recipient-status">
                {{#if data.online}}
                <?php esc_html_e( 'En línea', 'dental-directory-system' ); ?>
                {{else}}
                <?php esc_html_e( 'Fuera de línea', 'dental-directory-system' ); ?>
                {{/if}}
            </span>
        </div>
    </div>
    <div class="chat-actions">
        <button class="chat-action-button refresh-button" title="<?php esc_attr_e( 'Actualizar', 'dental-directory-system' ); ?>">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</script>

<script type="text/template" id="tmpl-chat-message">
    <div class="chat-message {{data.message_class}}" data-message-id="{{data.id}}">
        <div class="message-avatar">
            <img src="{{data.sender_avatar}}" alt="{{data.sender_name}}">
        </div>
        <div class="message-content">
            <div class="message-bubble">
                <div class="message-text">{{data.message}}</div>
            </div>
            <div class="message-meta">
                <span class="message-time">{{data.time}}</span>
                {{#if data.is_sender}}
                <span class="message-status {{data.status}}">
                    <i class="fas fa-check"></i>
                    <i class="fas fa-check"></i>
                </span>
                {{/if}}
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="tmpl-empty-conversations">
    <div class="empty-conversations">
        <div class="empty-state-icon">
            <i class="fas fa-comments"></i>
        </div>
        <h3><?php esc_html_e( 'No hay conversaciones', 'dental-directory-system' ); ?></h3>
        <p><?php esc_html_e( 'Aún no has iniciado ninguna conversación.', 'dental-directory-system' ); ?></p>
    </div>
</script>

<!-- Pass data to JS -->
<script type="text/javascript">
    var DENTAL_CHAT = {
        ajaxurl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
        rest_url: '<?php echo esc_url_raw( rest_url( 'dental-directory/v1' ) ); ?>',
        nonce: '<?php echo esc_js( $nonce ); ?>',
        current_user_id: <?php echo esc_js( $current_user_id ); ?>,
        user_type: '<?php echo $is_dentist ? 'dentist' : 'patient'; ?>',
        conversation_id: '<?php echo esc_js( $conversation_id ); ?>',
        recipient_id: <?php echo esc_js( $recipient_id ); ?>,
        is_premium: <?php echo ( $is_dentist && isset( $limit_status['is_premium'] ) ) ? ( $limit_status['is_premium'] ? 'true' : 'false' ) : 'true'; ?>,
        limit_reached: <?php echo ( $is_dentist && isset( $limit_status['limit_reached'] ) ) ? ( $limit_status['limit_reached'] ? 'true' : 'false' ) : 'false'; ?>,
        strings: {
            sending: '<?php esc_html_e( 'Enviando...', 'dental-directory-system' ); ?>',
            empty_message: '<?php esc_html_e( 'Por favor escribe un mensaje.', 'dental-directory-system' ); ?>',
            error_sending: '<?php esc_html_e( 'Error al enviar el mensaje. Inténtalo de nuevo.', 'dental-directory-system' ); ?>',
            typing: '<?php esc_html_e( 'está escribiendo...', 'dental-directory-system' ); ?>',
            load_more: '<?php esc_html_e( 'Cargar mensajes anteriores', 'dental-directory-system' ); ?>'
        }
    };
</script>
