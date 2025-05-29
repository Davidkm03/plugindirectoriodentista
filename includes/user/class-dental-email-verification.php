<?php
/**
 * Email Verification Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Email Verification Class
 *
 * Handles user email verification
 *
 * @since 1.0.0
 */
class Dental_Email_Verification {

    /**
     * Constructor
     */
    public function __construct() {
        // Register verification endpoint
        add_action( 'init', array( $this, 'register_verification_endpoint' ) );
        
        // Handle email verification links
        add_action( 'template_redirect', array( $this, 'process_email_verification' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_dental_resend_verification', array( $this, 'ajax_resend_verification' ) );
        add_action( 'wp_ajax_nopriv_dental_resend_verification', array( $this, 'ajax_resend_verification' ) );
    }

    /**
     * Register verification endpoint
     * 
     * @return void
     */
    public function register_verification_endpoint() {
        add_rewrite_endpoint( 'dental-verify', EP_ROOT );
        
        // Flush rewrite rules only if needed
        if ( get_option( 'dental_verification_rewrite_flush', false ) === false ) {
            flush_rewrite_rules();
            update_option( 'dental_verification_rewrite_flush', true );
        }
    }

    /**
     * Process email verification
     * 
     * @return void
     */
    public function process_email_verification() {
        global $wp_query;
        
        // Check if we're on the verification endpoint
        if ( ! isset( $wp_query->query_vars['dental-verify'] ) ) {
            return;
        }
        
        // Get verification parameters
        $user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
        $code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
        
        // Validate parameters
        if ( empty( $user_id ) || empty( $code ) ) {
            wp_safe_redirect( home_url( '/verification-failed' ) );
            exit;
        }
        
        // Verify the code
        $stored_code = get_user_meta( $user_id, 'dental_verification_code', true );
        $expiry = get_user_meta( $user_id, 'dental_verification_expiry', true );
        
        // Check if code matches and hasn't expired
        if ( $stored_code === $code && $expiry > time() ) {
            // Mark user as verified
            update_user_meta( $user_id, 'dental_email_verified', true );
            delete_user_meta( $user_id, 'dental_verification_code' );
            delete_user_meta( $user_id, 'dental_verification_expiry' );
            
            // Get login page URL
            $login_page_id = get_option( 'dental_page_login' );
            $redirect = $login_page_id ? get_permalink( $login_page_id ) : home_url();
            
            // Add success parameter
            $redirect = add_query_arg( 'verified', '1', $redirect );
            
            // Redirect to login page
            wp_safe_redirect( $redirect );
            exit;
        } else {
            // Verification failed
            wp_safe_redirect( home_url( '/verification-failed' ) );
            exit;
        }
    }

    /**
     * Generate verification code
     * 
     * @return string Verification code
     */
    public function generate_code() {
        return wp_generate_password( 32, false );
    }

    /**
     * Send verification email
     * 
     * @param int    $user_id User ID
     * @param string $email   User email
     * @return bool True if email sent successfully, false otherwise
     */
    public function send_verification_email( $user_id, $email ) {
        // Generate verification code
        $code = $this->generate_code();
        
        // Set expiry (24 hours from now)
        $expiry = time() + ( 24 * 60 * 60 );
        
        // Store verification data
        update_user_meta( $user_id, 'dental_verification_code', $code );
        update_user_meta( $user_id, 'dental_verification_expiry', $expiry );
        
        // Get user info
        $user = get_userdata( $user_id );
        $display_name = $user->display_name;
        
        // Generate verification URL
        $verification_url = home_url( '/dental-verify' );
        $verification_url = add_query_arg(
            array(
                'user_id' => $user_id,
                'code'    => $code,
            ),
            $verification_url
        );
        
        // Email subject
        $subject = sprintf(
            __( 'Verify your email for %s', 'dental-directory-system' ),
            get_bloginfo( 'name' )
        );
        
        // Email content
        $message = sprintf(
            __( 'Hello %s,', 'dental-directory-system' ),
            $display_name
        ) . "\n\n";
        
        $message .= __( 'Thank you for registering! Please verify your email address by clicking the link below:', 'dental-directory-system' ) . "\n\n";
        $message .= $verification_url . "\n\n";
        $message .= __( 'This link will expire in 24 hours.', 'dental-directory-system' ) . "\n\n";
        $message .= __( 'If you did not create this account, please ignore this email.', 'dental-directory-system' ) . "\n\n";
        $message .= sprintf(
            __( 'Regards,', 'dental-directory-system' ) . "\n%s",
            get_bloginfo( 'name' )
        );
        
        // Send email
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        
        // Create HTML version of the message
        $html_message = '<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">';
        $html_message .= '<h2 style="color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">' . sprintf( __( 'Verify your %s account', 'dental-directory-system' ), get_bloginfo( 'name' ) ) . '</h2>';
        $html_message .= '<p>' . sprintf( __( 'Hello %s,', 'dental-directory-system' ), esc_html( $display_name ) ) . '</p>';
        $html_message .= '<p>' . __( 'Thank you for registering! Please verify your email address by clicking the button below:', 'dental-directory-system' ) . '</p>';
        $html_message .= '<p style="text-align: center; margin: 30px 0;"><a href="' . esc_url( $verification_url ) . '" style="background-color: #3498db; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 4px; display: inline-block; font-weight: bold;">' . __( 'Verify Email Address', 'dental-directory-system' ) . '</a></p>';
        $html_message .= '<p style="font-size: 12px; color: #7f8c8d;">' . __( 'If the button doesn\'t work, copy and paste this link into your browser:', 'dental-directory-system' ) . '<br>';
        $html_message .= '<a href="' . esc_url( $verification_url ) . '">' . esc_url( $verification_url ) . '</a></p>';
        $html_message .= '<p>' . __( 'This link will expire in 24 hours.', 'dental-directory-system' ) . '</p>';
        $html_message .= '<p>' . __( 'If you did not create this account, please ignore this email.', 'dental-directory-system' ) . '</p>';
        $html_message .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #7f8c8d;">';
        $html_message .= sprintf( __( 'Regards,', 'dental-directory-system' ) . '<br>%s', get_bloginfo( 'name' ) );
        $html_message .= '</div></div>';
        
        return wp_mail( $email, $subject, $html_message, $headers );
    }

    /**
     * Check if user's email is verified
     * 
     * @param int $user_id User ID
     * @return bool True if verified, false otherwise
     */
    public function is_email_verified( $user_id ) {
        return (bool) get_user_meta( $user_id, 'dental_email_verified', true );
    }

    /**
     * Handle AJAX request to resend verification email
     * 
     * @return void
     */
    public function ajax_resend_verification() {
        // Check nonce
        check_ajax_referer( 'dental_resend_verification_nonce', 'security' );
        
        // Get user ID
        $user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        
        // Validate user ID
        if ( empty( $user_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid user ID.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Get user data
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            wp_send_json_error( array(
                'message' => __( 'User not found.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Send verification email
        $sent = $this->send_verification_email( $user_id, $user->user_email );
        
        if ( $sent ) {
            wp_send_json_success( array(
                'message' => __( 'Verification email sent successfully. Please check your inbox.', 'dental-directory-system' ),
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Failed to send verification email. Please try again later.', 'dental-directory-system' ),
            ) );
        }
    }
}

// Initialize the class
new Dental_Email_Verification();
