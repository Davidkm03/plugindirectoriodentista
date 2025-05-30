<?php
/**
 * Dashboard Actions Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Dashboard Actions Class
 *
 * Handles AJAX actions for the dashboard functionality
 *
 * @since 1.0.0
 */
class Dental_Dashboard_Actions {

    /**
     * Database instance
     *
     * @var Dental_Database
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        // Get database instance
        global $dental_database;
        if ( ! $dental_database ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/class-dental-database.php';
            $dental_database = new Dental_Database();
        }
        $this->db = $dental_database;

        // Register AJAX handlers for message actions
        add_action( 'wp_ajax_dental_load_conversation', array( $this, 'ajax_load_conversation' ) );
        add_action( 'wp_ajax_dental_send_message', array( $this, 'ajax_send_message' ) );
        add_action( 'wp_ajax_dental_mark_messages_read', array( $this, 'ajax_mark_messages_read' ) );
        
        // Register AJAX handlers for favorites
        add_action( 'wp_ajax_dental_toggle_favorite', array( $this, 'ajax_toggle_favorite' ) );
        add_action( 'wp_ajax_dental_remove_favorite', array( $this, 'ajax_remove_favorite' ) );
        
        // Register AJAX handlers for subscription
        add_action( 'wp_ajax_dental_subscribe_plan', array( $this, 'ajax_subscribe_plan' ) );
        add_action( 'wp_ajax_dental_renew_subscription', array( $this, 'ajax_renew_subscription' ) );
        
        // Register AJAX handlers for chat
        add_action( 'wp_ajax_dental_get_user_info', array( $this, 'ajax_get_user_info' ) );
        add_action( 'wp_ajax_dental_get_message_limit_status', array( $this, 'ajax_get_message_limit_status' ) );
    }
    
    /**
     * AJAX handler for getting user information for chat
     */
    public function ajax_get_user_info() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dental_dashboard_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid security token' ) );
            return;
        }

        // Get user ID
        $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'User ID is required' ) );
            return;
        }
        
        // Get user data
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            wp_send_json_error( array( 'message' => __( 'Usuario no encontrado', 'dental-directory-system' ) ) );
            exit;
        }
        
        // Check if user is dentist or patient
        $is_dentist = dental_is_dentist( $user_id );
        $is_patient = dental_is_patient( $user_id );
        
        if ( ! $is_dentist && ! $is_patient ) {
            wp_send_json_error( array( 'message' => __( 'El usuario no es dentista ni paciente', 'dental-directory-system' ) ) );
            exit;
        }
        
        // Format user data for response
        $user_data = array(
            'id'           => $user_id,
            'display_name' => $user->display_name,
            'avatar'       => get_avatar_url( $user_id ),
            'role'         => $is_dentist ? 'dentist' : 'patient',
        );
        
        wp_send_json_success( $user_data );
    }
    
    /**
     * AJAX handler for getting message limit status for dentists
     * 
     * @return void
     */
    public function ajax_get_message_limit_status() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dental_dashboard_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Token de seguridad inválido', 'dental-directory-system' ) ) );
            exit;
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if user is a dentist
        if ( ! dental_is_dentist( $user_id ) ) {
            wp_send_json_error( array( 'message' => __( 'El usuario no es un dentista', 'dental-directory-system' ) ) );
            exit;
        }
        
        // Get message limit status
        if ( ! class_exists( 'Dental_Message_Limits' ) ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
        }
        
        $message_limits = new Dental_Message_Limits();
        $limit_status = $message_limits->get_dentist_limit_status( $user_id );
        
        wp_send_json_success( $limit_status );
    }
    
    /**
     * AJAX handler for loading a conversation
     * 
     * @return void
     */
    public function ajax_load_conversation() {
        // Check nonce
        check_ajax_referer('dental_ajax_nonce', 'security');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Acceso no autorizado.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Get conversation parameters
        $conversation_id = isset($_POST['conversation_id']) ? sanitize_text_field(wp_unslash($_POST['conversation_id'])) : '';
        $user_id = get_current_user_id();
        
        // Determine if user is dentist or patient
        $is_dentist = dental_is_dentist($user_id);
        $is_patient = dental_is_patient($user_id);
        
        if ($is_dentist) {
            $patient_id = isset($_POST['patient_id']) ? absint($_POST['patient_id']) : 0;
            $other_user_id = $patient_id;
        } elseif ($is_patient) {
            $dentist_id = isset($_POST['dentist_id']) ? absint($_POST['dentist_id']) : 0;
            $other_user_id = $dentist_id;
        } else {
            wp_send_json_error(array(
                'message' => __('Tipo de usuario no válido.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Validate other user id
        if (!$other_user_id) {
            wp_send_json_error(array(
                'message' => __('Usuario no válido.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Get messages for conversation
        global $wpdb;
        $messages_table = $wpdb->prefix . 'dental_messages';
        
        // Create conversation ID if not exists
        if (empty($conversation_id)) {
            if ($is_dentist) {
                $conversation_id = 'dc_' . $user_id . '_' . $patient_id;
            } else {
                $conversation_id = 'dc_' . $dentist_id . '_' . $user_id;
            }
        }
        
        // Get messages
        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$messages_table} 
                WHERE conversation_id = %s 
                ORDER BY created_at ASC",
                $conversation_id
            )
        );
        
        // Mark messages as read
        $this->mark_messages_read($conversation_id, $user_id);
        
        // Get other user data
        $other_user = get_userdata($other_user_id);
        
        // Build HTML for conversation
        ob_start();
        ?>
        <div class="dental-conversation-header">
            <div class="dental-conversation-user">
                <div class="dental-user-avatar">
                    <?php echo get_avatar($other_user_id, 40); ?>
                </div>
                <div class="dental-user-info">
                    <h3><?php echo esc_html($other_user->display_name); ?></h3>
                    <p class="dental-user-type">
                        <?php echo $is_dentist ? esc_html__('Paciente', 'dental-directory-system') : esc_html__('Dentista', 'dental-directory-system'); ?>
                    </p>
                </div>
            </div>
            <div class="dental-conversation-actions">
                <a href="<?php echo esc_url(get_author_posts_url($other_user_id)); ?>" class="dental-btn dental-btn-sm">
                    <i class="dashicons dashicons-id"></i>
                    <?php esc_html_e('Ver Perfil', 'dental-directory-system'); ?>
                </a>
            </div>
        </div>
        
        <div class="dental-conversation-messages" id="dental-conversation-messages">
            <?php if (empty($messages)) : ?>
            <div class="dental-empty-conversation">
                <p><?php esc_html_e('No hay mensajes en esta conversación. Sé el primero en enviar un mensaje.', 'dental-directory-system'); ?></p>
            </div>
            <?php else : ?>
                <?php foreach ($messages as $message) : 
                    $is_own = $message->sender_id == $user_id;
                    ?>
                    <div class="dental-message <?php echo $is_own ? 'dental-message-own' : ''; ?>">
                        <div class="dental-message-content">
                            <?php echo wp_kses_post(wpautop($message->message)); ?>
                        </div>
                        <div class="dental-message-meta">
                            <span class="dental-message-time">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($message->created_at))); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($is_dentist) : 
            // Check subscription and message limits for dentists
            $subscription_type = dental_get_subscription_type($user_id);
            $is_premium = ($subscription_type === 'premium');
            
            if (!$is_premium) {
                // Check monthly message limit for free plan
                $current_month = date('Y-m');
                $monthly_messages = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$messages_table} WHERE dentist_id = %d AND sender_id = %d AND DATE_FORMAT(created_at, '%%Y-%%m') = %s",
                        $user_id, $user_id, $current_month
                    )
                );
                
                if ($monthly_messages >= 5) :
                    // User reached limit
                    ?>
                    <div class="dental-reply-limit-reached">
                        <div class="dental-limit-icon">
                            <i class="dashicons dashicons-lock"></i>
                        </div>
                        <h3><?php esc_html_e('Límite de mensajes alcanzado', 'dental-directory-system'); ?></h3>
                        <p>
                            <?php esc_html_e('Has alcanzado el límite de mensajes mensuales para tu plan gratuito.', 'dental-directory-system'); ?>
                            <a href="<?php echo esc_url(add_query_arg('view', 'upgrade', remove_query_arg(array('dentist_id', 'patient_id')))); ?>" class="dental-upgrade-link">
                                <?php esc_html_e('Actualizar a Premium', 'dental-directory-system'); ?>
                            </a>
                        </p>
                    </div>
                <?php else : ?>
                    <div class="dental-reply-limit-warning">
                        <p>
                            <i class="dashicons dashicons-warning"></i>
                            <?php printf(
                                esc_html__('Plan gratuito: %d de 5 mensajes enviados este mes.', 'dental-directory-system'),
                                $monthly_messages
                            ); ?>
                            <a href="<?php echo esc_url(add_query_arg('view', 'upgrade', remove_query_arg(array('dentist_id', 'patient_id')))); ?>">
                                <?php esc_html_e('Actualizar', 'dental-directory-system'); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
            <?php } ?>
        <?php endif; ?>
        
        <form id="dental-reply-form" class="dental-reply-form">
            <div class="dental-form-group">
                <textarea id="dental-reply-input" placeholder="<?php esc_attr_e('Escribe tu mensaje...', 'dental-directory-system'); ?>" required></textarea>
            </div>
            <button type="submit" id="dental-reply-btn" class="dental-btn dental-btn-primary">
                <?php esc_html_e('Enviar', 'dental-directory-system'); ?>
            </button>
            
            <?php if ($is_dentist && !$is_premium && $monthly_messages >= 5) : ?>
            <div class="dental-reply-limit-error" style="display: none;">
                <p>
                    <i class="dashicons dashicons-warning"></i>
                    <?php esc_html_e('Has alcanzado el límite mensual de mensajes. Por favor, actualiza a Premium para continuar enviando mensajes.', 'dental-directory-system'); ?>
                </p>
            </div>
            <?php endif; ?>
        </form>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'conversation_id' => $conversation_id,
        ));
    }
    
    /**
     * AJAX handler for sending a message
     * 
     * @return void
     */
    public function ajax_send_message() {
        // Check nonce
        check_ajax_referer('dental_ajax_nonce', 'security');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Acceso no autorizado.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Get message parameters
        $conversation_id = isset($_POST['conversation_id']) ? sanitize_text_field(wp_unslash($_POST['conversation_id'])) : '';
        $recipient_id = isset($_POST['recipient_id']) ? absint($_POST['recipient_id']) : 0;
        $message_content = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
        $user_id = get_current_user_id();
        
        // Validate message
        if (empty($message_content)) {
            wp_send_json_error(array(
                'message' => __('El mensaje no puede estar vacío.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Determine if user is dentist or patient
        $is_dentist = dental_is_dentist($user_id);
        $is_patient = dental_is_patient($user_id);
        
        if ($is_dentist) {
            $dentist_id = $user_id;
            $patient_id = $recipient_id;
            
            // Check subscription and message limits for dentists
            $subscription_type = dental_get_subscription_type($user_id);
            $is_premium = ($subscription_type === 'premium');
            
            if (!$is_premium) {
                // Check monthly message limit for free plan
                global $wpdb;
                $messages_table = $wpdb->prefix . 'dental_messages';
                $current_month = date('Y-m');
                
                $monthly_messages = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$messages_table} WHERE dentist_id = %d AND sender_id = %d AND DATE_FORMAT(created_at, '%%Y-%%m') = %s",
                        $dentist_id, $dentist_id, $current_month
                    )
                );
                
                if ($monthly_messages >= 5) {
                    wp_send_json_error(array(
                        'message' => __('Has alcanzado el límite mensual de mensajes para tu plan gratuito.', 'dental-directory-system'),
                        'limit_reached' => true,
                    ));
                    return;
                }
            }
        } elseif ($is_patient) {
            $patient_id = $user_id;
            $dentist_id = $recipient_id;
        } else {
            wp_send_json_error(array(
                'message' => __('Tipo de usuario no válido.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Create conversation ID if not exists
        if (empty($conversation_id)) {
            $conversation_id = 'dc_' . $dentist_id . '_' . $patient_id;
        }
        
        // Insert message
        global $wpdb;
        $messages_table = $wpdb->prefix . 'dental_messages';
        
        $result = $wpdb->insert(
            $messages_table,
            array(
                'conversation_id' => $conversation_id,
                'dentist_id' => $dentist_id,
                'patient_id' => $patient_id,
                'sender_id' => $user_id,
                'receiver_id' => $recipient_id,
                'message' => $message_content,
                'is_read' => 0,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s')
        );
        
        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Error al enviar el mensaje. Inténtalo de nuevo.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Build HTML for new message
        ob_start();
        ?>
        <div class="dental-message dental-message-own">
            <div class="dental-message-content">
                <?php echo wp_kses_post(wpautop($message_content)); ?>
            </div>
            <div class="dental-message-meta">
                <span class="dental-message-time">
                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'))); ?>
                </span>
            </div>
        </div>
        <?php
        $message_html = ob_get_clean();
        
        // Return success response with message HTML
        $response = array(
            'message' => __('Mensaje enviado correctamente.', 'dental-directory-system'),
            'message_html' => $message_html,
        );
        
        // Add monthly count for dentists with free plan
        if ($is_dentist && !$is_premium) {
            $response['monthly_count'] = $monthly_messages + 1;
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Mark messages as read
     * 
     * @param string $conversation_id Conversation ID
     * @param int    $user_id         User ID
     * @return void
     */
    private function mark_messages_read($conversation_id, $user_id) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'dental_messages';
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$messages_table} 
                SET is_read = 1 
                WHERE conversation_id = %s 
                AND receiver_id = %d 
                AND is_read = 0",
                $conversation_id,
                $user_id
            )
        );
    }
    
    /**
     * AJAX handler for marking messages as read
     * 
     * @return void
     */
    public function ajax_mark_messages_read() {
        // Check nonce
        check_ajax_referer('dental_ajax_nonce', 'security');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('Acceso no autorizado.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Get parameters
        $conversation_id = isset($_POST['conversation_id']) ? sanitize_text_field(wp_unslash($_POST['conversation_id'])) : '';
        $user_id = get_current_user_id();
        
        if (empty($conversation_id)) {
            wp_send_json_error(array(
                'message' => __('ID de conversación no válido.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Mark messages as read
        $this->mark_messages_read($conversation_id, $user_id);
        
        wp_send_json_success(array(
            'message' => __('Mensajes marcados como leídos.', 'dental-directory-system'),
        ));
    }
    
    /**
     * AJAX handler for toggling a dentist as favorite
     * 
     * @return void
     */
    public function ajax_toggle_favorite() {
        // Check nonce
        check_ajax_referer('dental_ajax_nonce', 'security');
        
        // Check if user is logged in and is a patient
        if (!is_user_logged_in() || !dental_is_patient()) {
            wp_send_json_error(array(
                'message' => __('Acceso no autorizado.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Get parameters
        $dentist_id = isset($_POST['dentist_id']) ? absint($_POST['dentist_id']) : 0;
        $patient_id = get_current_user_id();
        
        // Validate dentist ID
        if (!$dentist_id || !dental_is_dentist($dentist_id)) {
            wp_send_json_error(array(
                'message' => __('Dentista no válido.', 'dental-directory-system'),
            ));
            return;
        }
        
        global $wpdb;
        $favorites_table = $wpdb->prefix . 'dental_favorites';
        
        // Check if already favorited
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$favorites_table} WHERE patient_id = %d AND dentist_id = %d",
                $patient_id, $dentist_id
            )
        );
        
        if ($exists) {
            // Remove from favorites
            $result = $wpdb->delete(
                $favorites_table,
                array(
                    'patient_id' => $patient_id,
                    'dentist_id' => $dentist_id,
                ),
                array('%d', '%d')
            );
            
            if (!$result) {
                wp_send_json_error(array(
                    'message' => __('Error al quitar de favoritos. Inténtalo de nuevo.', 'dental-directory-system'),
                ));
                return;
            }
            
            wp_send_json_success(array(
                'message' => __('Removido de favoritos correctamente.', 'dental-directory-system'),
                'status' => 'removed',
            ));
        } else {
            // Add to favorites
            $result = $wpdb->insert(
                $favorites_table,
                array(
                    'patient_id' => $patient_id,
                    'dentist_id' => $dentist_id,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%d', '%s')
            );
            
            if (!$result) {
                wp_send_json_error(array(
                    'message' => __('Error al añadir a favoritos. Inténtalo de nuevo.', 'dental-directory-system'),
                ));
                return;
            }
            
            wp_send_json_success(array(
                'message' => __('Añadido a favoritos correctamente.', 'dental-directory-system'),
                'status' => 'added',
            ));
        }
    }
    
    /**
     * AJAX handler for removing a dentist from favorites
     * 
     * @return void
     */
    public function ajax_remove_favorite() {
        // Check nonce
        check_ajax_referer('dental_ajax_nonce', 'security');
        
        // Check if user is logged in and is a patient
        if (!is_user_logged_in() || !dental_is_patient()) {
            wp_send_json_error(array(
                'message' => __('Acceso no autorizado.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Get parameters
        $dentist_id = isset($_POST['dentist_id']) ? absint($_POST['dentist_id']) : 0;
        $patient_id = get_current_user_id();
        
        // Validate dentist ID
        if (!$dentist_id) {
            wp_send_json_error(array(
                'message' => __('Dentista no válido.', 'dental-directory-system'),
            ));
            return;
        }
        
        global $wpdb;
        $favorites_table = $wpdb->prefix . 'dental_favorites';
        
        // Remove from favorites
        $result = $wpdb->delete(
            $favorites_table,
            array(
                'patient_id' => $patient_id,
                'dentist_id' => $dentist_id,
            ),
            array('%d', '%d')
        );
        
        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Error al quitar de favoritos. Inténtalo de nuevo.', 'dental-directory-system'),
            ));
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('Removido de favoritos correctamente.', 'dental-directory-system'),
        ));
    }
    
    /**
     * AJAX handler for initiating a subscription plan
     * 
     * @return void
     */
    public function ajax_subscribe_plan() {
        // Check nonce
        check_ajax_referer('dental_ajax_nonce', 'security');
        
        // Check if user is logged in and is a dentist
        if (!is_user_logged_in() || !dental_is_dentist()) {
            wp_send_json_error(array(
                'message' => __('Acceso no autorizado.', 'dental-directory-system'),
            ));
            return;
        }
        
        $user_id = get_current_user_id();
        $plan_type = isset($_POST['plan']) ? sanitize_text_field(wp_unslash($_POST['plan'])) : 'monthly';
        
        // Validate plan type
        if (!in_array($plan_type, array('monthly', 'yearly'), true)) {
            wp_send_json_error(array(
                'message' => __('Tipo de plan no válido.', 'dental-directory-system'),
            ));
            return;
        }
        
        // Check if WooCommerce is active
        if (class_exists('WooCommerce')) {
            // Redirect to WooCommerce subscription process
            // First, verify if we have the WC integration class
            if (!class_exists('Dental_WooCommerce_Subscription')) {
                require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/subscription/class-dental-woocommerce-subscription.php';
            }
            
            $wc_subscription = new Dental_WooCommerce_Subscription();
            
            // Get subscription product ID
            $product_id = $wc_subscription->get_subscription_product_id($plan_type);
            
            if (!$product_id) {
                wp_send_json_error(array(
                    'message' => __('Producto de suscripción no encontrado. Por favor contacte al administrador.', 'dental-directory-system'),
                ));
                return;
            }
            
            // Add to cart and redirect to checkout
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($product_id);
            
            // Add custom data to session
            WC()->session->set('dental_subscription_plan', $plan_type);
            WC()->session->set('dental_user_id', $user_id);
            
            wp_send_json_success(array(
                'redirect' => wc_get_checkout_url(),
                'message' => __('Redirigiendo al proceso de pago...', 'dental-directory-system'),
            ));
        } else {
            // WooCommerce not active, error message
            wp_send_json_error(array(
                'message' => __('No se puede procesar la suscripción. WooCommerce no está activo.', 'dental-directory-system'),
            ));
        }
    }
    
    /**
     * AJAX handler for renewing a subscription
     * 
     * @return void
     */
    public function ajax_renew_subscription() {
        // Check nonce
        check_ajax_referer('dental_ajax_nonce', 'security');
        
        // Check if user is logged in and is a dentist
        if (!is_user_logged_in() || !dental_is_dentist()) {
            wp_send_json_error(array(
                'message' => __('Acceso no autorizado.', 'dental-directory-system'),
            ));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check if WooCommerce is active
        if (class_exists('WooCommerce')) {
            // Get current subscription (we need to know if it's monthly or yearly)
            global $wpdb;
            $subscriptions_table = $wpdb->prefix . 'dental_subscriptions';
            
            // Get current subscription
            $current_subscription = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$subscriptions_table} WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
                    $user_id
                )
            );
            
            // Default to monthly plan if we can't determine
            $plan_type = 'monthly';
            
            // Determine plan type from current subscription if available
            if ($current_subscription && !empty($current_subscription->plan_name)) {
                // Check if there's a duration indicator in the plan name
                if (strpos($current_subscription->plan_name, 'yearly') !== false) {
                    $plan_type = 'yearly';
                }
            }
            
            // Redirect to WooCommerce subscription process
            // First, verify if we have the WC integration class
            if (!class_exists('Dental_WooCommerce_Subscription')) {
                require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/subscription/class-dental-woocommerce-subscription.php';
            }
            
            $wc_subscription = new Dental_WooCommerce_Subscription();
            
            // Get subscription product ID
            $product_id = $wc_subscription->get_subscription_product_id($plan_type);
            
            if (!$product_id) {
                wp_send_json_error(array(
                    'message' => __('Producto de suscripción no encontrado. Por favor contacte al administrador.', 'dental-directory-system'),
                ));
                return;
            }
            
            // Add to cart and redirect to checkout
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($product_id);
            
            // Add custom data to session
            WC()->session->set('dental_subscription_plan', $plan_type);
            WC()->session->set('dental_user_id', $user_id);
            WC()->session->set('dental_is_renewal', true);
            
            wp_send_json_success(array(
                'redirect' => wc_get_checkout_url(),
                'message' => __('Redirigiendo al proceso de pago para renovar tu suscripción...', 'dental-directory-system'),
            ));
        } else {
            // WooCommerce not active, error message
            wp_send_json_error(array(
                'message' => __('No se puede procesar la renovación. WooCommerce no está activo.', 'dental-directory-system'),
            ));
        }
    }
}
