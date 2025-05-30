<?php
/**
 * Template for the chat modal
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

// Get recipient ID
$recipient_id = isset( $_GET['dentist_id'] ) ? absint( $_GET['dentist_id'] ) : 0;
if ( ! $recipient_id && isset( $_POST['recipient_id'] ) ) {
    $recipient_id = absint( $_POST['recipient_id'] );
}

// For dentists, check message limits
$limit_status = array();
if ( $is_dentist ) {
    require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
    $message_limits = new Dental_Message_Limits();
    $limit_status = $message_limits->get_dentist_limit_status( $current_user_id );
}
?>

<!-- Chat Modal -->
<div class="dental-chat-modal" id="dental-chat-modal">
    <div class="chat-modal-content">
        <div class="chat-modal-header">
            <h3><?php esc_html_e( 'Chat', 'dental-directory-system' ); ?></h3>
            <span class="chat-modal-close">&times;</span>
        </div>
        <div class="chat-modal-body">
            <div class="dental-chat-container modal-chat" data-user-id="<?php echo esc_attr( $current_user_id ); ?>" data-user-type="<?php echo $is_dentist ? 'dentist' : 'patient'; ?>">
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
                        <div class="chat-limit-reached">
                            <p><?php esc_html_e( 'Has alcanzado tu límite mensual de mensajes.', 'dental-directory-system' ); ?></p>
                            <a href="#" class="dental-button upgrade-button" data-action="upgrade-subscription">
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
                                <span class="message-limit-info">
                                    <?php if ( $is_dentist && !empty($limit_status) && !$limit_status['is_premium'] ) : ?>
                                        <?php echo sprintf( 
                                            esc_html__( 'Mensajes: %d/%d', 'dental-directory-system' ), 
                                            esc_html( $limit_status['message_count'] ), 
                                            esc_html( $limit_status['limit'] ) 
                                        ); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Templates for JS rendering -->
<script type="text/template" id="tmpl-chat-header-modal">
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

<script type="text/template" id="tmpl-chat-message-modal">
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

<!-- Pass data to JS -->
<script type="text/javascript">
    var DENTAL_CHAT_MODAL = {
        ajaxurl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
        rest_url: '<?php echo esc_url_raw( rest_url( 'dental-directory/v1' ) ); ?>',
        nonce: '<?php echo esc_js( $nonce ); ?>',
        current_user_id: <?php echo esc_js( $current_user_id ); ?>,
        user_type: '<?php echo $is_dentist ? 'dentist' : 'patient'; ?>',
        recipient_id: <?php echo esc_js( $recipient_id ); ?>,
        is_premium: <?php echo ( $is_dentist && isset( $limit_status['is_premium'] ) ) ? ( $limit_status['is_premium'] ? 'true' : 'false' ) : 'true'; ?>,
        limit_reached: <?php echo ( $is_dentist && isset( $limit_status['limit_reached'] ) ) ? ( $limit_status['limit_reached'] ? 'true' : 'false' ) : 'false'; ?>,
        strings: {
            sending: '<?php esc_html_e( 'Enviando...', 'dental-directory-system' ); ?>',
            empty_message: '<?php esc_html_e( 'Por favor escribe un mensaje.', 'dental-directory-system' ); ?>',
            error_sending: '<?php esc_html_e( 'Error al enviar el mensaje. Inténtalo de nuevo.', 'dental-directory-system' ); ?>',
            typing: '<?php esc_html_e( 'está escribiendo...', 'dental-directory-system' ); ?>'
        }
    };
</script>
