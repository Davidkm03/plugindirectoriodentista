<?php
/**
 * API Base Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/API
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * API Base Class
 *
 * Base class for all API endpoints
 *
 * @since 1.0.0
 */
class Dental_API {

    /**
     * API namespace
     *
     * @var string
     */
    protected $namespace = 'dental-directory/v1';

    /**
     * Constructor
     */
    public function __construct() {
        // Register REST API routes
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register API routes
     */
    public function register_routes() {
        // To be implemented by child classes
    }

    /**
     * Check if user is authorized
     *
     * @param WP_REST_Request $request Request object.
     * @return bool
     */
    protected function is_authorized( $request ) {
        return is_user_logged_in();
    }

    /**
     * Check if nonce is valid
     *
     * @param WP_REST_Request $request Request object.
     * @param string          $nonce_name Nonce name.
     * @return bool
     */
    protected function verify_nonce( $request, $nonce_name = 'wp_rest' ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        
        if ( ! $nonce ) {
            return false;
        }
        
        return wp_verify_nonce( $nonce, $nonce_name );
    }

    /**
     * Get current user ID
     *
     * @return int
     */
    protected function get_current_user_id() {
        return get_current_user_id();
    }

    /**
     * Check if current user is a dentist
     *
     * @return bool
     */
    protected function is_dentist() {
        return function_exists( 'dental_is_dentist' ) ? dental_is_dentist() : false;
    }

    /**
     * Check if current user is a patient
     *
     * @return bool
     */
    protected function is_patient() {
        return function_exists( 'dental_is_patient' ) ? dental_is_patient() : false;
    }

    /**
     * Send success response
     *
     * @param mixed $data Response data.
     * @param int   $status_code HTTP status code.
     * @return WP_REST_Response
     */
    protected function success_response( $data, $status_code = 200 ) {
        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $data,
        ), $status_code );
    }

    /**
     * Send error response
     *
     * @param string $message Error message.
     * @param string $code Error code.
     * @param int    $status_code HTTP status code.
     * @return WP_REST_Response
     */
    protected function error_response( $message, $code = 'error', $status_code = 400 ) {
        return new WP_REST_Response( array(
            'success' => false,
            'code'    => $code,
            'message' => $message,
        ), $status_code );
    }
}
