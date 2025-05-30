<?php
/**
 * Field Validation Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Field Validation Class
 *
 * Handles real-time field validations for registration forms
 *
 * @since 1.0.0
 */
class Dental_Field_Validation {

    /**
     * Constructor
     */
    public function __construct() {
        // Field validation handlers
        add_action( 'wp_ajax_nopriv_dental_check_username', array( $this, 'ajax_check_username' ) );
        add_action( 'wp_ajax_nopriv_dental_check_email', array( $this, 'ajax_check_email' ) );
        add_action( 'wp_ajax_dental_check_username', array( $this, 'ajax_check_username' ) );
        add_action( 'wp_ajax_dental_check_email', array( $this, 'ajax_check_email' ) );
        
        // Add validation nonce to localized script data
        add_filter( 'dental_script_vars', array( $this, 'add_validation_nonce' ) );
    }
    
    /**
     * Add validation nonce to localized script data
     *
     * @param array $vars Script variables
     * @return array Modified script variables
     */
    public function add_validation_nonce( $vars ) {
        $vars['validation_nonce'] = wp_create_nonce( 'dental_field_validation' );
        
        // Add validation texts
        $vars['texts']['username_checking'] = __( 'Checking username availability...', 'dental-directory-system' );
        $vars['texts']['username_available'] = __( 'Username is available', 'dental-directory-system' );
        $vars['texts']['username_not_available'] = __( 'Username is already taken', 'dental-directory-system' );
        $vars['texts']['username_invalid'] = __( 'Username must be at least 4 characters and contain only letters, numbers, and underscores', 'dental-directory-system' );
        $vars['texts']['username_required'] = __( 'Username is required', 'dental-directory-system' );
        
        $vars['texts']['email_checking'] = __( 'Checking email...', 'dental-directory-system' );
        $vars['texts']['email_available'] = __( 'Email is valid', 'dental-directory-system' );
        $vars['texts']['email_not_available'] = __( 'Email is already registered', 'dental-directory-system' );
        $vars['texts']['email_invalid'] = __( 'Please enter a valid email address', 'dental-directory-system' );
        $vars['texts']['email_required'] = __( 'Email is required', 'dental-directory-system' );
        
        $vars['texts']['password_weak'] = __( 'Password is too weak', 'dental-directory-system' );
        $vars['texts']['password_medium'] = __( 'Password strength: medium', 'dental-directory-system' );
        $vars['texts']['password_strong'] = __( 'Password strength: strong', 'dental-directory-system' );
        $vars['texts']['password_very_strong'] = __( 'Password strength: very strong', 'dental-directory-system' );
        $vars['texts']['password_required'] = __( 'Password is required', 'dental-directory-system' );
        
        $vars['texts']['password_match'] = __( 'Passwords match', 'dental-directory-system' );
        $vars['texts']['password_not_match'] = __( 'Passwords do not match', 'dental-directory-system' );
        $vars['texts']['password_confirm_required'] = __( 'Please confirm your password', 'dental-directory-system' );
        
        return $vars;
    }

    /**
     * Check if username is available
     * 
     * AJAX handler for username availability check
     * 
     * @return void
     */
    public function ajax_check_username() {
        // Check nonce for security
        check_ajax_referer( 'dental_field_validation', 'security' );
        
        // Get username
        $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
        
        if ( empty( $username ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please enter a username', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check if username is valid format
        if ( !preg_match('/^[a-zA-Z0-9_]{4,}$/', $username) ) {
            wp_send_json_error( array(
                'message' => __( 'Username must be at least 4 characters and contain only letters, numbers, and underscores', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check if username already exists
        if ( username_exists( $username ) ) {
            wp_send_json_error( array(
                'message' => __( 'Username is already taken', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Username is available
        wp_send_json_success( array(
            'message' => __( 'Username is available', 'dental-directory-system' ),
        ) );
    }
    
    /**
     * Check if email is available
     * 
     * AJAX handler for email availability check
     * 
     * @return void
     */
    public function ajax_check_email() {
        // Check nonce for security
        check_ajax_referer( 'dental_field_validation', 'security' );
        
        // Get email
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        
        if ( empty( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please enter an email address', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check if email is valid format
        if ( !is_email( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please enter a valid email address', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check if email already exists
        if ( email_exists( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Email is already registered', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Email is available
        wp_send_json_success( array(
            'message' => __( 'Email is valid', 'dental-directory-system' ),
        ) );
    }
}

// Initialize the class
new Dental_Field_Validation();
