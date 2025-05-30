<?php
/**
 * WooCommerce Subscription Integration Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Subscription
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class for integrating with WooCommerce Subscriptions
 *
 * @since 1.0.0
 */
class Dental_WooCommerce_Subscription {

    /**
     * Constructor
     */
    public function __construct() {
        // Check if WooCommerce is active
        if ( ! $this->is_woocommerce_active() ) {
            return;
        }

        // Register hooks
        $this->register_hooks();
        
        // Schedule sync task if not already scheduled
        $this->schedule_sync();
    }

    /**
     * Register hooks
     */
    private function register_hooks() {
        // Create subscription after successful payment
        add_action( 'woocommerce_subscription_status_active', array( $this, 'activate_dental_subscription' ), 10, 1 );
        
        // Update subscription status when WooCommerce subscription status changes
        add_action( 'woocommerce_subscription_status_updated', array( $this, 'update_dental_subscription' ), 10, 3 );
        
        // Add custom endpoint for redirecting to WooCommerce subscription product
        add_action( 'wp_ajax_dental_create_wc_subscription', array( $this, 'create_wc_subscription' ) );
        
        // Filter to add custom data to WooCommerce order
        add_filter( 'woocommerce_checkout_create_order', array( $this, 'add_custom_data_to_order' ), 10, 2 );
        
        // Handle failed payment
        add_action( 'woocommerce_subscription_payment_failed', array( $this, 'handle_payment_failed' ), 10, 1 );
        
        // Handle cancellation
        add_action( 'woocommerce_subscription_status_cancelled', array( $this, 'handle_subscription_cancellation' ), 10, 1 );
        
        // Handle subscription deletion
        add_action( 'woocommerce_subscription_deleted', array( $this, 'handle_subscription_deletion' ), 10, 1 );
        
        // Add webhooks if admin
        if ( is_admin() ) {
            add_action( 'admin_init', array( $this, 'register_webhooks' ) );
        }
        
        // Setup REST API endpoint for webhooks
        add_action( 'rest_api_init', array( $this, 'register_webhook_endpoint' ) );
        
        // Add limit reached notice
        add_action( 'dental_message_limit_reached', array( $this, 'send_limit_reached_notification' ), 10, 1 );
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    public function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Check if WooCommerce Subscriptions is active
     *
     * @return bool
     */
    public function is_woocommerce_subscriptions_active() {
        return class_exists( 'WC_Subscriptions' );
    }

    /**
     * Get the premium subscription product ID
     *
     * @param string $plan_type Type of plan (monthly or yearly)
     * @return int|bool Product ID or false if not found
     */
    public function get_subscription_product_id( $plan_type = 'monthly' ) {
        // Get product ID from options
        $product_id = get_option( 'dental_premium_' . $plan_type . '_product_id', false );
        
        if ( $product_id ) {
            return absint( $product_id );
        }
        
        // Try to find product by SKU
        $sku = 'dental-premium-' . $plan_type;
        $product_id = wc_get_product_id_by_sku( $sku );
        
        if ( $product_id ) {
            return absint( $product_id );
        }
        
        return false;
    }

    /**
     * Create a WooCommerce subscription checkout URL and redirect
     */
    public function create_wc_subscription() {
        // Check nonce
        check_ajax_referer( 'dental_subscription_nonce', 'security' );
        
        // Check if user is logged in and is a dentist
        if ( ! is_user_logged_in() || ! dental_is_dentist() ) {
            wp_send_json_error( array(
                'message' => __( 'Acceso no autorizado.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Get plan type
        $plan_type = isset( $_POST['plan_type'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_type'] ) ) : 'monthly';
        
        // Validate plan type
        if ( ! in_array( $plan_type, array( 'monthly', 'yearly' ), true ) ) {
            wp_send_json_error( array(
                'message' => __( 'Tipo de plan no válido.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Get product ID
        $product_id = $this->get_subscription_product_id( $plan_type );
        
        if ( ! $product_id ) {
            wp_send_json_error( array(
                'message' => __( 'Producto de suscripción no encontrado. Por favor contacte al administrador.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Add to cart and redirect to checkout
        WC()->cart->empty_cart();
        WC()->cart->add_to_cart( $product_id );
        
        // Add custom data to session
        WC()->session->set( 'dental_subscription_plan', $plan_type );
        WC()->session->set( 'dental_user_id', get_current_user_id() );
        
        wp_send_json_success( array(
            'redirect' => wc_get_checkout_url(),
        ) );
    }

    /**
     * Add custom data to WooCommerce order
     *
     * @param WC_Order $order Order object
     * @param array    $data  Checkout data
     */
    public function add_custom_data_to_order( $order, $data ) {
        // Get data from session
        $plan_type = WC()->session->get( 'dental_subscription_plan' );
        $user_id = WC()->session->get( 'dental_user_id' );
        
        if ( $plan_type ) {
            $order->update_meta_data( '_dental_subscription_plan', $plan_type );
        }
        
        if ( $user_id ) {
            $order->update_meta_data( '_dental_user_id', $user_id );
        }
        
        return $order;
    }

    /**
     * Activate dental subscription when WooCommerce subscription becomes active
     *
     * @param WC_Subscription $subscription WooCommerce subscription object
     */
    public function activate_dental_subscription( $subscription ) {
        // Get the subscription plan type
        $plan_type = $subscription->get_meta( '_dental_subscription_plan', true );
        $user_id = $subscription->get_meta( '_dental_user_id', true );
        
        // If no user ID is stored, use the subscription user ID
        if ( ! $user_id ) {
            $user_id = $subscription->get_user_id();
        }
        
        // Ensure we have a valid user
        if ( ! $user_id || ! dental_is_dentist( $user_id ) ) {
            return;
        }
        
        // Calculate expiration
        $duration = ( 'yearly' === $plan_type ) ? 12 : 1; // months
        $expiry_date = date( 'Y-m-d H:i:s', strtotime( "+{$duration} months" ) );
        
        // Update dental subscription in our custom table
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'dental_subscriptions';
        
        // Check if subscription already exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$subscriptions_table} WHERE user_id = %d AND status = 'active' LIMIT 1",
                $user_id
            )
        );
        
        if ( $existing ) {
            // Update existing subscription
            $wpdb->update(
                $subscriptions_table,
                array(
                    'plan_name'    => 'premium',
                    'status'       => 'active',
                    'date_expiry'  => $expiry_date,
                    'wc_sub_id'    => $subscription->get_id(),
                    'updated_at'   => current_time( 'mysql' ),
                ),
                array( 'user_id' => $user_id ),
                array( '%s', '%s', '%s', '%d', '%s' ),
                array( '%d' )
            );
        } else {
            // Create new subscription
            $wpdb->insert(
                $subscriptions_table,
                array(
                    'user_id'      => $user_id,
                    'plan_name'    => 'premium',
                    'status'       => 'active',
                    'date_start'   => current_time( 'mysql' ),
                    'date_expiry'  => $expiry_date,
                    'wc_sub_id'    => $subscription->get_id(),
                    'created_at'   => current_time( 'mysql' ),
                    'updated_at'   => current_time( 'mysql' ),
                ),
                array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
            );
        }
        
        // Mark user as premium
        update_user_meta( $user_id, 'dental_is_premium', 1 );
        update_user_meta( $user_id, 'dental_is_featured', 1 );
    }

    /**
     * Update dental subscription when WooCommerce subscription status changes
     *
     * @param WC_Subscription $subscription WooCommerce subscription object
     * @param string          $new_status   New status
     * @param string          $old_status   Old status
     */
    public function update_dental_subscription( $subscription, $new_status, $old_status ) {
        // Skip if activation is handled separately
        if ( 'active' === $new_status ) {
            return;
        }
        
        // Get the dental user ID
        $user_id = $subscription->get_meta( '_dental_user_id', true );
        
        // If no user ID is stored, use the subscription user ID
        if ( ! $user_id ) {
            $user_id = $subscription->get_user_id();
        }
        
        // Ensure we have a valid user
        if ( ! $user_id || ! dental_is_dentist( $user_id ) ) {
            return;
        }
        
        // Update dental subscription status based on WooCommerce subscription status
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'dental_subscriptions';
        
        $dental_status = 'active'; // Default
        
        switch ( $new_status ) {
            case 'cancelled':
            case 'expired':
                $dental_status = 'expired';
                break;
            case 'on-hold':
                $dental_status = 'on-hold';
                break;
            case 'pending':
                $dental_status = 'pending';
                break;
        }
        
        // Update subscription
        $wpdb->update(
            $subscriptions_table,
            array(
                'status'     => $dental_status,
                'updated_at' => current_time( 'mysql' ),
            ),
            array(
                'user_id'    => $user_id,
                'wc_sub_id'  => $subscription->get_id(),
            ),
            array( '%s', '%s' ),
            array( '%d', '%d' )
        );
        
        // If subscription is cancelled or expired, remove premium status
        if ( in_array( $new_status, array( 'cancelled', 'expired' ), true ) ) {
            delete_user_meta( $user_id, 'dental_is_premium' );
            delete_user_meta( $user_id, 'dental_is_featured' );
        }
    }
    
    /**
     * Handle subscription cancellation
     *
     * @param WC_Subscription $subscription WooCommerce subscription object
     */
    public function handle_subscription_cancellation( $subscription ) {
        // Get the dental user ID
        $user_id = $subscription->get_meta( '_dental_user_id', true );
        
        // If no user ID is stored, use the subscription user ID
        if ( ! $user_id ) {
            $user_id = $subscription->get_user_id();
        }
        
        // Ensure we have a valid user
        if ( ! $user_id || ! dental_is_dentist( $user_id ) ) {
            return;
        }
        
        // Update our subscription table
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'dental_subscriptions';
        
        $wpdb->update(
            $subscriptions_table,
            array(
                'status'     => 'cancelled',
                'updated_at' => current_time( 'mysql' ),
            ),
            array(
                'user_id'    => $user_id,
                'wc_sub_id'  => $subscription->get_id(),
            ),
            array( '%s', '%s' ),
            array( '%d', '%d' )
        );
        
        // Remove premium status
        delete_user_meta( $user_id, 'dental_is_premium' );
        delete_user_meta( $user_id, 'dental_is_featured' );
        
        // Send cancellation notification
        $this->send_cancellation_notification( $user_id );
    }
    
    /**
     * Handle subscription deletion
     *
     * @param int $subscription_id WooCommerce subscription ID
     */
    public function handle_subscription_deletion( $subscription_id ) {
        // Get subscription
        $subscription = wcs_get_subscription( $subscription_id );
        
        if ( ! $subscription ) {
            return;
        }
        
        // Handle as cancellation
        $this->handle_subscription_cancellation( $subscription );
    }
    
    /**
     * Handle payment failed
     *
     * @param WC_Subscription $subscription WooCommerce subscription object
     */
    public function handle_payment_failed( $subscription ) {
        // Get the dental user ID
        $user_id = $subscription->get_meta( '_dental_user_id', true );
        
        // If no user ID is stored, use the subscription user ID
        if ( ! $user_id ) {
            $user_id = $subscription->get_user_id();
        }
        
        // Ensure we have a valid user
        if ( ! $user_id || ! dental_is_dentist( $user_id ) ) {
            return;
        }
        
        // Update our subscription table
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'dental_subscriptions';
        
        $wpdb->update(
            $subscriptions_table,
            array(
                'status'     => 'payment_failed',
                'updated_at' => current_time( 'mysql' ),
            ),
            array(
                'user_id'    => $user_id,
                'wc_sub_id'  => $subscription->get_id(),
            ),
            array( '%s', '%s' ),
            array( '%d', '%d' )
        );
        
        // Send payment failed notification
        $this->send_payment_failed_notification( $user_id );
    }
    
    /**
     * Register webhooks for WooCommerce subscription events
     */
    public function register_webhooks() {
        // Check if we've already registered webhooks
        $webhooks_registered = get_option( 'dental_wc_webhooks_registered', false );
        
        if ( $webhooks_registered ) {
            return;
        }
        
        // Check if API is available
        if ( ! class_exists( 'WC_Webhook' ) ) {
            return;
        }
        
        try {
            // Register webhook for subscription updated
            $webhook_updated = new WC_Webhook();
            $webhook_updated->set_name( 'Dental Directory - Subscription Updated' );
            $webhook_updated->set_topic( 'subscription.updated' );
            $webhook_updated->set_delivery_url( rest_url( 'dental-directory/v1/wc-webhook' ) );
            $webhook_updated->set_status( 'active' );
            $webhook_updated->set_secret( wp_generate_password( 50, true, true ) );
            $webhook_updated->save();
            
            // Register webhook for subscription created
            $webhook_created = new WC_Webhook();
            $webhook_created->set_name( 'Dental Directory - Subscription Created' );
            $webhook_created->set_topic( 'subscription.created' );
            $webhook_created->set_delivery_url( rest_url( 'dental-directory/v1/wc-webhook' ) );
            $webhook_created->set_status( 'active' );
            $webhook_created->set_secret( wp_generate_password( 50, true, true ) );
            $webhook_created->save();
            
            // Save that we've registered webhooks
            update_option( 'dental_wc_webhooks_registered', true );
        } catch ( Exception $e ) {
            // Log error but don't halt plugin activation
            error_log( 'Error registering WooCommerce webhooks: ' . $e->getMessage() );
        }
    }
    
    /**
     * Register REST API endpoint for webhook processing
     */
    public function register_webhook_endpoint() {
        register_rest_route( 'dental-directory/v1', '/wc-webhook', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'process_webhook' ),
            'permission_callback' => '__return_true', // No permissions check as it's coming from WooCommerce
        ) );
    }
    
    /**
     * Process webhook from WooCommerce
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function process_webhook( $request ) {
        $params = $request->get_params();
        
        // Log webhook for debugging
        error_log( 'WooCommerce Webhook received: ' . wp_json_encode( $params ) );
        
        // Check if this is a subscription event
        if ( isset( $params['topic'] ) && strpos( $params['topic'], 'subscription.' ) === 0 ) {
            // Get subscription ID
            if ( isset( $params['id'] ) ) {
                $subscription_id = $params['id'];
                
                // Process based on topic
                switch ( $params['topic'] ) {
                    case 'subscription.created':
                        // Subscription created, nothing to do as we handle activation separately
                        break;
                        
                    case 'subscription.updated':
                    case 'subscription.status_changed':
                        // Get the subscription
                        $subscription = wcs_get_subscription( $subscription_id );
                        if ( $subscription ) {
                            // Get status
                            $status = $subscription->get_status();
                            $old_status = isset( $params['old_status'] ) ? $params['old_status'] : '';
                            
                            // Process status change
                            $this->update_dental_subscription( $subscription, $status, $old_status );
                        }
                        break;
                        
                    case 'subscription.deleted':
                        // Handle deletion
                        $this->handle_subscription_deletion( $subscription_id );
                        break;
                }
            }
        }
        
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
    
    /**
     * Schedule periodic sync between WooCommerce and our subscription tables
     */
    public function schedule_sync() {
        if ( ! wp_next_scheduled( 'dental_woocommerce_sync' ) ) {
            wp_schedule_event( time(), 'daily', 'dental_woocommerce_sync' );
            add_action( 'dental_woocommerce_sync', array( $this, 'sync_subscriptions' ) );
        }
    }
    
    /**
     * Sync subscriptions between WooCommerce and our custom tables
     */
    public function sync_subscriptions() {
        if ( ! $this->is_woocommerce_subscriptions_active() ) {
            return;
        }
        
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'dental_subscriptions';
        
        // Get all users with the dentist role
        $dentist_users = get_users( array( 'role' => 'dentist' ) );
        
        foreach ( $dentist_users as $user ) {
            // Get WooCommerce subscriptions for this user
            $wc_subscriptions = wcs_get_users_subscriptions( $user->ID );
            
            // Skip if no subscriptions
            if ( empty( $wc_subscriptions ) ) {
                continue;
            }
            
            // Process each subscription
            foreach ( $wc_subscriptions as $subscription ) {
                // Get status
                $status = $subscription->get_status();
                
                // Only process active subscriptions
                if ( 'active' === $status ) {
                    // Check if we have this subscription in our table
                    $existing = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM {$subscriptions_table} WHERE user_id = %d AND wc_sub_id = %d LIMIT 1",
                            $user->ID,
                            $subscription->get_id()
                        )
                    );
                    
                    if ( ! $existing ) {
                        // Create new entry in our table
                        $this->activate_dental_subscription( $subscription );
                    } else if ( $existing->status !== 'active' ) {
                        // Update status to match WooCommerce
                        $wpdb->update(
                            $subscriptions_table,
                            array(
                                'status'     => 'active',
                                'updated_at' => current_time( 'mysql' ),
                            ),
                            array(
                                'id' => $existing->id,
                            ),
                            array( '%s', '%s' ),
                            array( '%d' )
                        );
                        
                        // Ensure user meta is set
                        update_user_meta( $user->ID, 'dental_is_premium', 1 );
                        update_user_meta( $user->ID, 'dental_is_featured', 1 );
                    }
                }
            }
        }
    }
    
    /**
     * Send notification when limit is reached
     *
     * @param int $user_id User ID
     */
    public function send_limit_reached_notification( $user_id ) {
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return;
        }
        
        $subject = __( 'Has alcanzado tu límite de mensajes gratuitos', 'dental-directory-system' );
        
        // Get dentist dashboard URL
        $dashboard_page_id = get_option( 'dental_page_dashboard_dentista' );
        $dashboard_url = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();
        $dashboard_url = add_query_arg( 'view', 'subscription', $dashboard_url );
        
        // HTML email content
        $message = '<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">';
        $message .= '<h2 style="color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">' . __( 'Límite de mensajes alcanzado', 'dental-directory-system' ) . '</h2>';
        $message .= '<p>' . sprintf( __( 'Hola %s,', 'dental-directory-system' ), esc_html( $user->display_name ) ) . '</p>';
        $message .= '<p>' . __( 'Has alcanzado tu límite de 5 mensajes gratuitos este mes. Para seguir comunicándote con tus pacientes, te recomendamos actualizar a un plan premium.', 'dental-directory-system' ) . '</p>';
        $message .= '<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $dashboard_url ) . '" style="background-color: #3498db; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 4px; display: inline-block; font-weight: bold;">' . __( 'Actualizar a Premium', 'dental-directory-system' ) . '</a></p>';
        $message .= '<p>' . __( 'Beneficios del plan premium:', 'dental-directory-system' ) . '</p>';
        $message .= '<ul>';
        $message .= '<li>' . __( 'Mensajes ilimitados con tus pacientes', 'dental-directory-system' ) . '</li>';
        $message .= '<li>' . __( 'Perfil destacado en los resultados de búsqueda', 'dental-directory-system' ) . '</li>';
        $message .= '<li>' . __( 'Atención prioritaria', 'dental-directory-system' ) . '</li>';
        $message .= '</ul>';
        $message .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #7f8c8d;">';
        $message .= sprintf( __( 'Saludos,', 'dental-directory-system' ) . '<br>%s', get_bloginfo( 'name' ) );
        $message .= '</div></div>';
        
        // Send email
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->user_email, $subject, $message, $headers );
    }
    
    /**
     * Send notification when payment fails
     *
     * @param int $user_id User ID
     */
    public function send_payment_failed_notification( $user_id ) {
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return;
        }
        
        $subject = __( 'Pago de suscripción fallido', 'dental-directory-system' );
        
        // Get WooCommerce account URL
        $account_url = wc_get_page_permalink( 'myaccount' );
        $subscription_url = $account_url . 'subscriptions/';
        
        // HTML email content
        $message = '<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">';
        $message .= '<h2 style="color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">' . __( 'Pago de suscripción fallido', 'dental-directory-system' ) . '</h2>';
        $message .= '<p>' . sprintf( __( 'Hola %s,', 'dental-directory-system' ), esc_html( $user->display_name ) ) . '</p>';
        $message .= '<p>' . __( 'El pago de tu suscripción premium ha fallado. Es posible que necesites actualizar tu información de pago para continuar disfrutando de los beneficios premium.', 'dental-directory-system' ) . '</p>';
        $message .= '<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $subscription_url ) . '" style="background-color: #e74c3c; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 4px; display: inline-block; font-weight: bold;">' . __( 'Actualizar método de pago', 'dental-directory-system' ) . '</a></p>';
        $message .= '<p>' . __( 'Si no actualizas tu método de pago, tu suscripción podría ser cancelada y perderías los siguientes beneficios:', 'dental-directory-system' ) . '</p>';
        $message .= '<ul>';
        $message .= '<li>' . __( 'Mensajes ilimitados con tus pacientes', 'dental-directory-system' ) . '</li>';
        $message .= '<li>' . __( 'Perfil destacado en los resultados de búsqueda', 'dental-directory-system' ) . '</li>';
        $message .= '<li>' . __( 'Atención prioritaria', 'dental-directory-system' ) . '</li>';
        $message .= '</ul>';
        $message .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #7f8c8d;">';
        $message .= sprintf( __( 'Saludos,', 'dental-directory-system' ) . '<br>%s', get_bloginfo( 'name' ) );
        $message .= '</div></div>';
        
        // Send email
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->user_email, $subject, $message, $headers );
    }
    
    /**
     * Send notification when subscription is cancelled
     *
     * @param int $user_id User ID
     */
    public function send_cancellation_notification( $user_id ) {
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return;
        }
        
        $subject = __( 'Suscripción cancelada', 'dental-directory-system' );
        
        // Get dentist dashboard URL
        $dashboard_page_id = get_option( 'dental_page_dashboard_dentista' );
        $dashboard_url = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url();
        $dashboard_url = add_query_arg( 'view', 'subscription', $dashboard_url );
        
        // HTML email content
        $message = '<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">';
        $message .= '<h2 style="color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">' . __( 'Suscripción cancelada', 'dental-directory-system' ) . '</h2>';
        $message .= '<p>' . sprintf( __( 'Hola %s,', 'dental-directory-system' ), esc_html( $user->display_name ) ) . '</p>';
        $message .= '<p>' . __( 'Tu suscripción premium ha sido cancelada. Ahora estás en el plan gratuito con un límite de 5 mensajes por mes.', 'dental-directory-system' ) . '</p>';
        $message .= '<p>' . __( 'Si deseas volver a disfrutar de los beneficios premium, puedes suscribirte nuevamente en cualquier momento.', 'dental-directory-system' ) . '</p>';
        $message .= '<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $dashboard_url ) . '" style="background-color: #3498db; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 4px; display: inline-block; font-weight: bold;">' . __( 'Volver a Premium', 'dental-directory-system' ) . '</a></p>';
        $message .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #7f8c8d;">';
        $message .= sprintf( __( 'Saludos,', 'dental-directory-system' ) . '<br>%s', get_bloginfo( 'name' ) );
        $message .= '</div></div>';
        
        // Send email
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->user_email, $subject, $message, $headers );
    }
}

// Initialize the class
new Dental_WooCommerce_Subscription();
