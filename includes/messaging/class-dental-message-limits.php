<?php
/**
 * Message Limits Manager Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Messaging
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class for managing message limits and counters
 *
 * @since 1.0.0
 */
class Dental_Message_Limits {

    /**
     * Database instance
     *
     * @var Dental_Database
     */
    private $db;

    /**
     * Default monthly limit for free tier
     *
     * @var int
     */
    private $free_tier_limit = 5;

    /**
     * Table name for message counters
     *
     * @var string
     */
    private $counters_table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb, $dental_database;
        if ( ! $dental_database ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/class-dental-database.php';
            $dental_database = new Dental_Database();
        }
        $this->db = $dental_database;
        
        $this->counters_table = $wpdb->prefix . 'dental_message_counters';
        
        // Register AJAX handlers
        add_action( 'wp_ajax_dental_get_message_limit_status', array( $this, 'ajax_get_message_limit_status' ) );
        
        // Register cron for monthly reset
        add_action( 'dental_monthly_message_reset', array( $this, 'reset_monthly_counters' ) );
        // Legacy hook support from installer
        add_action( 'dental_reset_monthly_counters', array( $this, 'reset_monthly_counters' ) );
        
        // Schedule monthly reset if not already scheduled
        if ( ! wp_next_scheduled( 'dental_monthly_message_reset' ) ) {
            // Schedule at midnight on the first day of each month
            $next_month = strtotime( 'first day of next month midnight' );
            wp_schedule_event( $next_month, 'monthly', 'dental_monthly_message_reset' );
        }
        
        // Register REST API endpoint
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route(
            'dental-directory/v1',
            '/messaging/limit-status',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_limit_status' ),
                'permission_callback' => array( $this, 'check_dentist_permission' ),
            )
        );
    }
    
    /**
     * Check if user has permission to access the limit status endpoint
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error True if user has permission, WP_Error otherwise.
     */
    public function check_dentist_permission( $request ) {
        // Must be logged in
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Necesitas iniciar sesión para acceder a este recurso.', 'dental-directory-system' ),
                array( 'status' => 401 )
            );
        }

        // Must be a dentist
        $current_user_id = get_current_user_id();
        if ( ! dental_is_dentist( $current_user_id ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Solo los dentistas pueden acceder a este recurso.', 'dental-directory-system' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Get the message limit status for a dentist
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function get_limit_status( $request ) {
        $user_id = get_current_user_id();
        $status = $this->get_dentist_limit_status( $user_id );
        
        return new WP_REST_Response( $status, 200 );
    }
    
    /**
     * AJAX handler for getting message limit status
     */
    public function ajax_get_message_limit_status() {
        // Check nonce
        if ( ! check_ajax_referer( 'dental_dashboard_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Verificación de seguridad fallida', 'dental-directory-system' ) ) );
            exit;
        }
        
        // Check if user is dentist
        $user_id = get_current_user_id();
        if ( ! dental_is_dentist( $user_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Solo los dentistas tienen límites de mensajes', 'dental-directory-system' ) ) );
            exit;
        }
        
        $status = $this->get_dentist_limit_status( $user_id );
        wp_send_json_success( $status );
    }
    
    /**
     * Get the message limit status for a dentist
     *
     * @param int $dentist_id Dentist user ID.
     * @return array Status information.
     */
    public function get_dentist_limit_status( $dentist_id ) {
        // Check subscription type
        $subscription_type = dental_get_subscription_type( $dentist_id );
        $is_premium = ( 'premium' === $subscription_type );
        
        // Get current month message count
        $current_month = date( 'Y-m' );
        $message_count = $this->get_dentist_month_count( $dentist_id, $current_month );
        
        // For free tier, check limits
        $limit_reached = false;
        $limit = 0;
        $remaining = 0;
        
        if ( ! $is_premium ) {
            $limit = $this->free_tier_limit;
            $remaining = max( 0, $limit - $message_count );
            $limit_reached = ( $message_count >= $limit );
        }
        
        // Format response
        return array(
            'subscription_type' => $subscription_type,
            'is_premium'        => $is_premium,
            'current_month'     => $current_month,
            'message_count'     => $message_count,
            'limit'             => $limit,
            'remaining'         => $remaining,
            'limit_reached'     => $limit_reached,
            'next_reset'        => $this->get_next_reset_date(),
        );
    }
    
    /**
     * Get the next reset date (first day of next month)
     *
     * @return string Next reset date in Y-m-d format.
     */
    public function get_next_reset_date() {
        return date( 'Y-m-d', strtotime( 'first day of next month' ) );
    }
    
    /**
     * Get the message count for a dentist in a specific month
     *
     * @param int    $dentist_id Dentist user ID.
     * @param string $month      Month in Y-m format.
     * @return int Message count.
     */
    public function get_dentist_month_count( $dentist_id, $month ) {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT count FROM {$this->counters_table} WHERE dentist_id = %d AND month = %s",
                $dentist_id,
                $month
            )
        );
        
        return $count ? intval( $count ) : 0;
    }
    
    /**
     * Increment the message count for a dentist
     *
     * @param int $dentist_id Dentist user ID.
     * @return bool True on success, false on failure.
     */
    public function increment_dentist_message_count( $dentist_id ) {
        global $wpdb;
        
        // Check subscription type (don't count if premium)
        $subscription_type = dental_get_subscription_type( $dentist_id );
        if ( 'premium' === $subscription_type ) {
            return true; // No need to count for premium users
        }
        
        // Get current month
        $current_month = date( 'Y-m' );
        $now = current_time( 'mysql' );
        
        // Check if entry exists for this month
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->counters_table} WHERE dentist_id = %d AND month = %s",
                $dentist_id,
                $current_month
            )
        );
        
        if ( $existing ) {
            // Update existing entry
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$this->counters_table} SET count = count + 1, updated_at = %s WHERE dentist_id = %d AND month = %s",
                    $now,
                    $dentist_id,
                    $current_month
                )
            );
        } else {
            // Insert new entry
            $result = $wpdb->insert(
                $this->counters_table,
                array(
                    'dentist_id' => $dentist_id,
                    'month'      => $current_month,
                    'count'      => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ),
                array( '%d', '%s', '%d', '%s', '%s' )
            );
        }
        
        return false !== $result;
    }
    
    /**
     * Check if a dentist has reached their message limit
     *
     * @param int $dentist_id Dentist user ID.
     * @return bool True if limit reached, false otherwise.
     */
    public function has_reached_limit( $dentist_id ) {
        // Premium users have no limit
        $subscription_type = dental_get_subscription_type( $dentist_id );
        if ( 'premium' === $subscription_type ) {
            return false;
        }
        
        // Check current month count against limit
        $current_month = date( 'Y-m' );
        $count = $this->get_dentist_month_count( $dentist_id, $current_month );
        
        return $count >= $this->free_tier_limit;
    }
    
    /**
     * Reset the monthly counters for all dentists (called by cron)
     *
     * @return bool True on success, false on failure.
     */
    public function reset_monthly_counters() {
        global $wpdb;
        
        // Current month in Y-m format
        $current_month = date( 'Y-m' );
        
        // Only reset counters from previous months
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->counters_table} WHERE month != %s",
                $current_month
            )
        );
        
        // Log the reset
        if ( $result !== false ) {
            error_log( sprintf( 'Dental Directory: Reset %d message counters on %s', $result, current_time( 'mysql' ) ) );
        } else {
            error_log( sprintf( 'Dental Directory: Failed to reset message counters on %s', current_time( 'mysql' ) ) );
        }
        
        return $result !== false;
    }
}

// Initialize the class
new Dental_Message_Limits();
