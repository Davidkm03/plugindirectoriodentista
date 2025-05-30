<?php
/**
 * Subscription Admin Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Subscription Admin Class
 *
 * Handles administration of subscriptions
 *
 * @since 1.0.0
 */
class Dental_Subscription_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_subscriptions_page' ) );
        
        // Handle admin actions
        add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
    }

    /**
     * Add subscription management page to admin menu
     */
    public function add_subscriptions_page() {
        add_submenu_page(
            'dental-directory',
            __( 'Suscripciones', 'dental-directory-system' ),
            __( 'Suscripciones', 'dental-directory-system' ),
            'manage_options',
            'dental-subscriptions',
            array( $this, 'render_subscriptions_page' )
        );
    }

    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        // Check for admin nonce
        if ( isset( $_POST['dental_subscription_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dental_subscription_nonce'] ) ), 'dental_subscription_action' ) ) {
            
            // Handle different actions
            if ( isset( $_POST['action'] ) ) {
                $action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
                $subscription_id = isset( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;
                
                if ( $subscription_id ) {
                    global $wpdb;
                    $subscriptions_table = $wpdb->prefix . 'dental_subscriptions';
                    
                    switch ( $action ) {
                        case 'activate':
                            $wpdb->update(
                                $subscriptions_table,
                                array(
                                    'status'     => 'active',
                                    'updated_at' => current_time( 'mysql' ),
                                ),
                                array( 'id' => $subscription_id ),
                                array( '%s', '%s' ),
                                array( '%d' )
                            );
                            
                            // Get user ID
                            $user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$subscriptions_table} WHERE id = %d", $subscription_id ) );
                            if ( $user_id ) {
                                update_user_meta( $user_id, 'dental_is_premium', 1 );
                                update_user_meta( $user_id, 'dental_is_featured', 1 );
                            }
                            break;
                            
                        case 'deactivate':
                            $wpdb->update(
                                $subscriptions_table,
                                array(
                                    'status'     => 'inactive',
                                    'updated_at' => current_time( 'mysql' ),
                                ),
                                array( 'id' => $subscription_id ),
                                array( '%s', '%s' ),
                                array( '%d' )
                            );
                            
                            // Get user ID
                            $user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$subscriptions_table} WHERE id = %d", $subscription_id ) );
                            if ( $user_id ) {
                                delete_user_meta( $user_id, 'dental_is_premium' );
                                delete_user_meta( $user_id, 'dental_is_featured' );
                            }
                            break;
                            
                        case 'delete':
                            $wpdb->delete(
                                $subscriptions_table,
                                array( 'id' => $subscription_id ),
                                array( '%d' )
                            );
                            break;
                    }
                    
                    // Redirect to prevent form resubmission
                    wp_safe_redirect( admin_url( 'admin.php?page=dental-subscriptions&action=updated' ) );
                    exit;
                }
            }
        }
    }

    /**
     * Render subscription management page
     */
    public function render_subscriptions_page() {
        // Get current action
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        
        // Show notification if action completed
        if ( 'updated' === $action ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Suscripción actualizada correctamente.', 'dental-directory-system' ) . '</p></div>';
        }
        
        // Get subscriptions
        global $wpdb;
        $subscriptions_table = $wpdb->prefix . 'dental_subscriptions';
        
        $subscriptions = $wpdb->get_results(
            "SELECT s.*, u.display_name
             FROM {$subscriptions_table} s
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
             ORDER BY s.updated_at DESC"
        );
        
        // Display subscriptions table
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Gestión de Suscripciones', 'dental-directory-system' ); ?></h1>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=dental-subscriptions&sync=1' ) ); ?>" class="button">
                        <?php esc_html_e( 'Sincronizar con WooCommerce', 'dental-directory-system' ); ?>
                    </a>
                </div>
                <br class="clear">
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'dental-directory-system' ); ?></th>
                        <th><?php esc_html_e( 'Dentista', 'dental-directory-system' ); ?></th>
                        <th><?php esc_html_e( 'Plan', 'dental-directory-system' ); ?></th>
                        <th><?php esc_html_e( 'Estado', 'dental-directory-system' ); ?></th>
                        <th><?php esc_html_e( 'Fecha de inicio', 'dental-directory-system' ); ?></th>
                        <th><?php esc_html_e( 'Fecha de expiración', 'dental-directory-system' ); ?></th>
                        <th><?php esc_html_e( 'ID de WC', 'dental-directory-system' ); ?></th>
                        <th><?php esc_html_e( 'Acciones', 'dental-directory-system' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $subscriptions ) ) : ?>
                        <tr>
                            <td colspan="8"><?php esc_html_e( 'No hay suscripciones disponibles.', 'dental-directory-system' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $subscriptions as $subscription ) : ?>
                            <tr>
                                <td><?php echo esc_html( $subscription->id ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $subscription->user_id ) ); ?>">
                                        <?php echo esc_html( $subscription->display_name ); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php 
                                    $plan_name = isset( $subscription->plan_name ) ? $subscription->plan_name : '';
                                    if ( empty( $plan_name ) && isset( $subscription->plan_id ) ) {
                                        $plan_name = $subscription->plan_id;
                                    }
                                    echo esc_html( ucfirst( $plan_name ) ); 
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch ( $subscription->status ) {
                                        case 'active':
                                            $status_class = 'active';
                                            $status_text = __( 'Activa', 'dental-directory-system' );
                                            break;
                                        case 'inactive':
                                        case 'expired':
                                            $status_class = 'inactive';
                                            $status_text = __( 'Expirada', 'dental-directory-system' );
                                            break;
                                        case 'pending':
                                            $status_class = 'pending';
                                            $status_text = __( 'Pendiente', 'dental-directory-system' );
                                            break;
                                        case 'payment_failed':
                                            $status_class = 'error';
                                            $status_text = __( 'Pago fallido', 'dental-directory-system' );
                                            break;
                                        case 'cancelled':
                                            $status_class = 'cancelled';
                                            $status_text = __( 'Cancelada', 'dental-directory-system' );
                                            break;
                                        default:
                                            $status_text = ucfirst( $subscription->status );
                                    }
                                    ?>
                                    <span class="subscription-status <?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( $status_text ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $date_field = isset( $subscription->date_start ) ? 'date_start' : 'created_at';
                                    echo esc_html( mysql2date( get_option( 'date_format' ), $subscription->$date_field ) );
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $expiry_date = isset( $subscription->date_expiry ) ? $subscription->date_expiry : 
                                                  (isset( $subscription->expiry_date ) ? $subscription->expiry_date : '');
                                    
                                    if ( ! empty( $expiry_date ) && '0000-00-00 00:00:00' !== $expiry_date ) {
                                        echo esc_html( mysql2date( get_option( 'date_format' ), $expiry_date ) );
                                    } else {
                                        if ( 'free' === $plan_name ) {
                                            esc_html_e( 'No expira', 'dental-directory-system' );
                                        } else {
                                            esc_html_e( 'No establecida', 'dental-directory-system' );
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $wc_sub_id = isset( $subscription->wc_sub_id ) ? $subscription->wc_sub_id : 0;
                                    if ( $wc_sub_id && class_exists( 'WooCommerce' ) ) {
                                        echo '<a href="' . esc_url( admin_url( 'post.php?post=' . $wc_sub_id . '&action=edit' ) ) . '">' . esc_html( $wc_sub_id ) . '</a>';
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field( 'dental_subscription_action', 'dental_subscription_nonce' ); ?>
                                        <input type="hidden" name="subscription_id" value="<?php echo esc_attr( $subscription->id ); ?>">
                                        
                                        <?php if ( 'active' !== $subscription->status ) : ?>
                                            <button type="submit" name="action" value="activate" class="button button-small">
                                                <?php esc_html_e( 'Activar', 'dental-directory-system' ); ?>
                                            </button>
                                        <?php else : ?>
                                            <button type="submit" name="action" value="deactivate" class="button button-small">
                                                <?php esc_html_e( 'Desactivar', 'dental-directory-system' ); ?>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="action" value="delete" class="button button-small" onclick="return confirm('<?php esc_attr_e( '¿Estás seguro de querer eliminar esta suscripción? Esta acción no se puede deshacer.', 'dental-directory-system' ); ?>')">
                                            <?php esc_html_e( 'Eliminar', 'dental-directory-system' ); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <style>
                .subscription-status {
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 3px;
                    font-weight: 500;
                }
                .subscription-status.active {
                    background-color: #dff0d8;
                    color: #3c763d;
                }
                .subscription-status.inactive,
                .subscription-status.cancelled {
                    background-color: #f2dede;
                    color: #a94442;
                }
                .subscription-status.pending {
                    background-color: #fcf8e3;
                    color: #8a6d3b;
                }
                .subscription-status.error {
                    background-color: #f2dede;
                    color: #a94442;
                }
            </style>
        </div>
        <?php
    }
}

// Initialize the class
new Dental_Subscription_Admin();
