<?php
/**
 * Message Notifications Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Messaging
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Message Notifications Class
 *
 * Manages notifications for message limits and provides methods for displaying
 * alert banners, warning modals, and upgrade prompts.
 *
 * @since 1.0.0
 */
class Dental_Message_Notifications {

    /**
     * Singleton instance
     *
     * @var Dental_Message_Notifications
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Dental_Message_Notifications Instance.
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Add notification hooks
        add_action( 'wp_ajax_dental_dismiss_limit_warning', array( $this, 'ajax_dismiss_limit_warning' ) );
        add_action( 'wp_footer', array( $this, 'render_upgrade_modal' ) );
        add_action( 'dental_after_send_message', array( $this, 'check_limits_after_message' ), 10, 2 );
    }

    /**
     * Check if the user should see a limit warning
     *
     * @param int $user_id User ID.
     * @return bool Whether to show warning.
     */
    public function should_show_limit_warning( $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        // Only show for dentists
        if ( ! dental_is_dentist( $user_id ) ) {
            return false;
        }

        // Don't show for premium users
        if ( 'premium' === dental_get_subscription_type( $user_id ) ) {
            return false;
        }

        // Get message limit status
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
        $message_limits = new Dental_Message_Limits();
        $limit_status = $message_limits->get_dentist_limit_status( $user_id );

        // Only show warning if approaching limit (75% or more)
        $warning_threshold = $limit_status['limit'] * 0.75;
        $show_warning = $limit_status['message_count'] >= $warning_threshold && !$limit_status['limit_reached'];
        
        // Don't show if dismissed recently (within 24 hours)
        if ( $show_warning ) {
            $dismissed_until = get_user_meta( $user_id, 'dental_limit_warning_dismissed_until', true );
            if ( $dismissed_until && $dismissed_until > time() ) {
                $show_warning = false;
            }
        }

        return $show_warning;
    }

    /**
     * Check if the user has reached their limit
     *
     * @param int $user_id User ID.
     * @return bool Whether limit is reached.
     */
    public function has_reached_limit( $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        // Only applies to dentists
        if ( ! dental_is_dentist( $user_id ) ) {
            return false;
        }

        // No limits for premium
        if ( 'premium' === dental_get_subscription_type( $user_id ) ) {
            return false;
        }

        // Get message limit status
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
        $message_limits = new Dental_Message_Limits();
        $limit_status = $message_limits->get_dentist_limit_status( $user_id );

        return $limit_status['limit_reached'];
    }
    
    /**
     * AJAX handler for dismissing limit warnings
     */
    public function ajax_dismiss_limit_warning() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dental_dashboard_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Error de verificación de seguridad', 'dental-directory-system' ) ) );
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Set dismissal for 24 hours
        $dismissed_until = time() + ( 24 * HOUR_IN_SECONDS );
        update_user_meta( $user_id, 'dental_limit_warning_dismissed_until', $dismissed_until );
        
        wp_send_json_success();
    }
    
    /**
     * Check limits after a message is sent and trigger notifications if needed
     *
     * @param int    $sender_id   Sender user ID.
     * @param string $message_id  Message ID.
     */
    public function check_limits_after_message( $sender_id, $message_id ) {
        // Only check for dentists
        if ( ! dental_is_dentist( $sender_id ) ) {
            return;
        }
        
        // Get message limit status
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
        $message_limits = new Dental_Message_Limits();
        $limit_status = $message_limits->get_dentist_limit_status( $sender_id );
        
        // Limit just reached (this message caused it)
        if ( $limit_status['limit_reached'] && $limit_status['message_count'] === $limit_status['limit'] ) {
            $this->send_limit_reached_notification( $sender_id );
        }
        // Approaching limit (75% or more)
        elseif ( $limit_status['message_count'] >= ( $limit_status['limit'] * 0.75 ) ) {
            $this->send_approaching_limit_notification( $sender_id, $limit_status );
        }
    }
    
    /**
     * Send a notification when approaching message limit
     *
     * @param int   $user_id      User ID.
     * @param array $limit_status Limit status data.
     */
    private function send_approaching_limit_notification( $user_id, $limit_status ) {
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return;
        }
        
        // Check if we've already sent a notification recently
        $last_notification = get_user_meta( $user_id, 'dental_approaching_limit_notification', true );
        if ( $last_notification && ( time() - $last_notification ) < ( 24 * HOUR_IN_SECONDS ) ) {
            return;
        }
        
        // Update last notification time
        update_user_meta( $user_id, 'dental_approaching_limit_notification', time() );
        
        // Calculate remaining messages
        $remaining = $limit_status['limit'] - $limit_status['message_count'];
        
        $subject = __( 'Te estás acercando a tu límite mensual de mensajes', 'dental-directory-system' );
        
        // Get dashboard URL
        $dashboard_page_id = get_option( 'dental_page_dashboard_dentista' );
        $dashboard_url = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();
        $dashboard_url = add_query_arg( 'view', 'subscription', $dashboard_url );
        
        // HTML email content
        $message = '<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">';
        $message .= '<h2 style="color: #e67e22; border-bottom: 1px solid #eee; padding-bottom: 10px;">' . __( 'Alerta de límite de mensajes', 'dental-directory-system' ) . '</h2>';
        $message .= '<p>' . sprintf( __( 'Hola %s,', 'dental-directory-system' ), esc_html( $user->display_name ) ) . '</p>';
        $message .= '<p>' . sprintf( __( 'Te informamos que estás llegando a tu límite mensual de mensajes. Actualmente has enviado %1$d de %2$d mensajes permitidos en tu plan gratuito.', 'dental-directory-system' ), $limit_status['message_count'], $limit_status['limit'] ) . '</p>';
        $message .= '<p>' . sprintf( __( 'Te quedan solamente %d mensajes para este mes.', 'dental-directory-system' ), $remaining ) . '</p>';
        $message .= '<p>' . __( 'Para seguir comunicándote con tus pacientes sin limitaciones, te recomendamos actualizar a nuestra suscripción premium.', 'dental-directory-system' ) . '</p>';
        $message .= '<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $dashboard_url ) . '" style="background-color: #e67e22; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 4px; display: inline-block; font-weight: bold;">' . __( 'Actualizar a Premium', 'dental-directory-system' ) . '</a></p>';
        $message .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #7f8c8d;">';
        $message .= sprintf( __( 'Saludos,', 'dental-directory-system' ) . '<br>%s', get_bloginfo( 'name' ) );
        $message .= '</div></div>';
        
        // Send email
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->user_email, $subject, $message, $headers );
    }
    
    /**
     * Send a notification when limit is reached
     *
     * @param int $user_id User ID.
     */
    private function send_limit_reached_notification( $user_id ) {
        // First check if WooCommerce integration is available
        if ( class_exists( 'Dental_WooCommerce_Subscription' ) ) {
            $wc_subscription = new Dental_WooCommerce_Subscription();
            
            // If the class has a method to send notifications, use it
            if ( method_exists( $wc_subscription, 'send_limit_reached_notification' ) ) {
                $wc_subscription->send_limit_reached_notification( $user_id );
                return;
            }
        }
        
        // Fallback notification if WooCommerce integration is not available
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return;
        }
        
        $subject = __( 'Has alcanzado tu límite mensual de mensajes', 'dental-directory-system' );
        
        // Get dashboard URL
        $dashboard_page_id = get_option( 'dental_page_dashboard_dentista' );
        $dashboard_url = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();
        $dashboard_url = add_query_arg( 'view', 'subscription', $dashboard_url );
        
        // HTML email content
        $message = '<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">';
        $message .= '<h2 style="color: #e74c3c; border-bottom: 1px solid #eee; padding-bottom: 10px;">' . __( 'Límite de mensajes alcanzado', 'dental-directory-system' ) . '</h2>';
        $message .= '<p>' . sprintf( __( 'Hola %s,', 'dental-directory-system' ), esc_html( $user->display_name ) ) . '</p>';
        $message .= '<p>' . __( 'Has alcanzado tu límite mensual de 5 mensajes en tu plan gratuito. No podrás enviar más mensajes hasta el próximo mes, a menos que actualices a nuestra suscripción premium.', 'dental-directory-system' ) . '</p>';
        $message .= '<p>' . __( 'Con la suscripción premium, podrás disfrutar de mensajes ilimitados con tus pacientes, además de otras ventajas exclusivas.', 'dental-directory-system' ) . '</p>';
        $message .= '<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $dashboard_url ) . '" style="background-color: #e74c3c; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 4px; display: inline-block; font-weight: bold;">' . __( 'Actualizar a Premium', 'dental-directory-system' ) . '</a></p>';
        $message .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #7f8c8d;">';
        $message .= sprintf( __( 'Saludos,', 'dental-directory-system' ) . '<br>%s', get_bloginfo( 'name' ) );
        $message .= '</div></div>';
        
        // Send email
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->user_email, $subject, $message, $headers );
    }

    /**
     * Render the warning banner for approaching limits
     */
    public function render_limit_warning_banner() {
        if ( ! $this->should_show_limit_warning() ) {
            return;
        }

        // Get limit status
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
        $message_limits = new Dental_Message_Limits();
        $limit_status = $message_limits->get_dentist_limit_status( get_current_user_id() );
        $remaining = $limit_status['limit'] - $limit_status['message_count'];
        
        // Get dashboard URL
        $dashboard_page_id = get_option( 'dental_page_dashboard_dentista' );
        $dashboard_url = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();
        $upgrade_url = add_query_arg( 'view', 'subscription', $dashboard_url );
        ?>
        <div class="dental-limit-warning-banner">
            <div class="limit-warning-content">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="limit-warning-message">
                    <strong><?php esc_html_e( '¡Atención!', 'dental-directory-system' ); ?></strong>
                    <?php if ( $remaining === 1 ) : ?>
                        <?php esc_html_e( 'Te queda solo 1 mensaje en tu plan gratuito este mes.', 'dental-directory-system' ); ?>
                    <?php else : ?>
                        <?php printf( esc_html__( 'Te quedan solo %d mensajes en tu plan gratuito este mes.', 'dental-directory-system' ), $remaining ); ?>
                    <?php endif; ?>
                </div>
                <div class="limit-warning-actions">
                    <a href="<?php echo esc_url( $upgrade_url ); ?>" class="dental-button dental-button-warning">
                        <?php esc_html_e( 'Actualizar a Premium', 'dental-directory-system' ); ?>
                    </a>
                    <button class="dental-dismiss-warning" data-nonce="<?php echo esc_attr( wp_create_nonce( 'dental_dashboard_nonce' ) ); ?>">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the upgrade modal in footer
     */
    public function render_upgrade_modal() {
        // Only show for logged in dentists
        if ( ! is_user_logged_in() || ! dental_is_dentist() ) {
            return;
        }
        
        // Don't show for premium users
        if ( 'premium' === dental_get_subscription_type() ) {
            return;
        }
        
        // Get dashboard URL
        $dashboard_page_id = get_option( 'dental_page_dashboard_dentista' );
        $dashboard_url = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();
        $upgrade_url = add_query_arg( 'view', 'subscription', $dashboard_url );
        ?>
        <div id="dental-upgrade-modal" class="dental-modal" style="display: none;">
            <div class="dental-modal-content">
                <div class="dental-modal-header">
                    <h3><?php esc_html_e( 'Actualiza a Premium', 'dental-directory-system' ); ?></h3>
                    <span class="dental-modal-close">&times;</span>
                </div>
                <div class="dental-modal-body">
                    <div class="upgrade-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h4><?php esc_html_e( 'Mensajes Ilimitados y Más Beneficios', 'dental-directory-system' ); ?></h4>
                    <p><?php esc_html_e( 'Has alcanzado el límite de mensajes de tu plan gratuito. Actualiza a premium para disfrutar de:', 'dental-directory-system' ); ?></p>
                    <ul class="premium-benefits">
                        <li><i class="fas fa-check"></i> <?php esc_html_e( 'Mensajes ilimitados con tus pacientes', 'dental-directory-system' ); ?></li>
                        <li><i class="fas fa-check"></i> <?php esc_html_e( 'Perfil destacado en los resultados de búsqueda', 'dental-directory-system' ); ?></li>
                        <li><i class="fas fa-check"></i> <?php esc_html_e( 'Acceso a estadísticas detalladas', 'dental-directory-system' ); ?></li>
                        <li><i class="fas fa-check"></i> <?php esc_html_e( 'Soporte prioritario', 'dental-directory-system' ); ?></li>
                    </ul>
                    <div class="upgrade-actions">
                        <a href="<?php echo esc_url( $upgrade_url ); ?>" class="dental-button dental-button-premium">
                            <?php esc_html_e( 'Actualizar Ahora', 'dental-directory-system' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize
Dental_Message_Notifications::get_instance();
