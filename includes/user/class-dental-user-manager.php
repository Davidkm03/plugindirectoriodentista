<?php
/**
 * User Manager Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * User Manager Class
 *
 * Handles all user management operations
 *
 * @since 1.0.0
 */
class Dental_User_Manager {

    /**
     * User roles instance
     *
     * @var Dental_User_Roles
     */
    private $roles;

    /**
     * User permissions instance
     *
     * @var Dental_User_Permissions
     */
    private $permissions;

    /**
     * Constructor
     */
    public function __construct() {
        // Load dependencies
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/class-dental-user-roles.php';
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/class-dental-user-permissions.php';
        
        // Initialize components
        $this->roles = new Dental_User_Roles();
        $this->permissions = new Dental_User_Permissions();
        
        // Register hooks
        add_action( 'init', array( $this, 'register_hooks' ) );
    }

    /**
     * Register hooks
     *
     * @return void
     */
    public function register_hooks() {
        // Handle custom redirects after login
        add_filter( 'login_redirect', array( $this, 'custom_login_redirect' ), 10, 3 );
        
        // Handle user registration
        add_action( 'wp_ajax_nopriv_dental_register_user', array( $this, 'ajax_register_user' ) );
        
        // Add permissions check to restricted pages
        add_action( 'template_redirect', array( $this, 'check_page_access_permissions' ) );
    }

    /**
     * Setup user roles during plugin activation
     *
     * @return void
     */
    public function setup_roles() {
        $this->roles->create_roles();
    }

    /**
     * Register a new dentist
     *
     * @param array $user_data User registration data.
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    public function register_dentist( $user_data ) {
        // Sanitize input data
        $username = sanitize_user( $user_data['username'] );
        $email = sanitize_email( $user_data['email'] );
        $password = $user_data['password']; // Password will be hashed by wp_insert_user
        
        // Basic validation
        if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
            return new WP_Error( 'missing_fields', __( 'All fields are required.', 'dental-directory-system' ) );
        }
        
        // Create user with dentist role
        $user_id = wp_insert_user(
            array(
                'user_login'    => $username,
                'user_email'    => $email,
                'user_pass'     => $password,
                'display_name'  => sanitize_text_field( $user_data['display_name'] ?? $username ),
                'role'          => Dental_User_Roles::ROLE_DENTIST,
                'user_nicename' => sanitize_title( $user_data['display_name'] ?? $username ),
            )
        );
        
        // Check if user creation was successful
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        // Store additional metadata
        if ( ! empty( $user_data['speciality'] ) ) {
            update_user_meta( $user_id, 'dental_speciality', sanitize_text_field( $user_data['speciality'] ) );
        }
        
        if ( ! empty( $user_data['license'] ) ) {
            update_user_meta( $user_id, 'dental_license', sanitize_text_field( $user_data['license'] ) );
        }
        
        // Initialize a free subscription for the dentist (5 messages/month limit)
        $this->initialize_free_subscription( $user_id );
        
        // Generate and store email verification token
        $this->generate_verification_token( $user_id );
        
        return $user_id;
    }

    /**
     * Register a new patient
     *
     * @param array $user_data User registration data.
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    public function register_patient( $user_data ) {
        // Sanitize input data
        $username = sanitize_user( $user_data['username'] );
        $email = sanitize_email( $user_data['email'] );
        $password = $user_data['password']; // Password will be hashed by wp_insert_user
        
        // Basic validation
        if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
            return new WP_Error( 'missing_fields', __( 'All fields are required.', 'dental-directory-system' ) );
        }
        
        // Create user with patient role
        $user_id = wp_insert_user(
            array(
                'user_login'    => $username,
                'user_email'    => $email,
                'user_pass'     => $password,
                'display_name'  => sanitize_text_field( $user_data['display_name'] ?? $username ),
                'role'          => Dental_User_Roles::ROLE_PATIENT,
                'user_nicename' => sanitize_title( $user_data['display_name'] ?? $username ),
            )
        );
        
        // Check if user creation was successful
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }
        
        // Store additional metadata
        if ( ! empty( $user_data['city'] ) ) {
            update_user_meta( $user_id, 'dental_city', sanitize_text_field( $user_data['city'] ) );
        }
        
        if ( ! empty( $user_data['age'] ) ) {
            update_user_meta( $user_id, 'dental_age', absint( $user_data['age'] ) );
        }
        
        // Generate and store email verification token
        $this->generate_verification_token( $user_id );
        
        return $user_id;
    }

    /**
     * Generate verification token for user
     *
     * @param int $user_id User ID.
     * @return string Generated verification token
     */
    private function generate_verification_token( $user_id ) {
        // Generate a verification token
        $token = wp_generate_password( 32, false );
        
        // Store in user meta
        update_user_meta( $user_id, 'dental_verification_token', $token );
        update_user_meta( $user_id, 'dental_verification_timestamp', time() );
        update_user_meta( $user_id, 'dental_verified', 0 );
        
        // Send verification email
        $this->send_verification_email( $user_id, $token );
        
        return $token;
    }

    /**
     * Send verification email to user
     *
     * @param int    $user_id User ID.
     * @param string $token   Verification token.
     * @return bool Whether the email was sent
     */
    private function send_verification_email( $user_id, $token ) {
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return false;
        }
        
        $verify_url = add_query_arg(
            array(
                'dental_verify' => '1',
                'user_id'       => $user_id,
                'token'         => $token,
            ),
            home_url()
        );
        
        $subject = __( 'Verify your account', 'dental-directory-system' );
        
        $message = sprintf(
            /* translators: %1$s: site name, %2$s: verification URL */
            __( 'Hi %1$s, 

Thank you for registering with %2$s! 

Please click the link below to verify your email address:

%3$s

If you did not create an account, please ignore this email.

Regards,
%2$s Team', 'dental-directory-system' ),
            $user->display_name,
            get_bloginfo( 'name' ),
            $verify_url
        );
        
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        
        return wp_mail( $user->user_email, $subject, nl2br( $message ), $headers );
    }

    /**
     * Initialize a free subscription for a dentist
     *
     * @param int $user_id User ID.
     * @return bool Success status
     */
    private function initialize_free_subscription( $user_id ) {
        global $wpdb;
        
        // Insert into subscriptions table
        $result = $wpdb->insert(
            $wpdb->prefix . 'dental_subscriptions',
            array(
                'user_id'     => absint( $user_id ),
                'plan_name'   => 'free',
                'status'      => 'active',
                'date_start'  => current_time( 'mysql' ),
                'date_expiry' => null, // Free plans don't expire
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );
        
        // Create initial message counter for current month
        if ( $result ) {
            $month = intval( date( 'n' ) );
            $year = intval( date( 'Y' ) );
            
            $wpdb->insert(
                $wpdb->prefix . 'dental_message_counters',
                array(
                    'dentist_id'    => absint( $user_id ),
                    'month'         => $month,
                    'year'          => $year,
                    'message_count' => 0,
                ),
                array( '%d', '%d', '%d', '%d' )
            );
        }
        
        return false !== $result;
    }

    /**
     * Custom login redirect based on user role
     *
     * @param string           $redirect_to Default redirect URL.
     * @param string           $request     Requested redirect URL.
     * @param WP_User|WP_Error $user        WP_User on success, WP_Error on failure.
     * @return string Modified redirect URL
     */
    public function custom_login_redirect( $redirect_to, $request, $user ) {
        if ( ! $user instanceof WP_User ) {
            return $redirect_to;
        }
        
        // Get dashboard page IDs
        $dentist_dashboard_id = get_option( 'dental_page_dashboard_dentista' );
        $patient_dashboard_id = get_option( 'dental_page_dashboard_paciente' );
        
        // Redirect based on user role
        if ( $this->permissions->is_dentist( $user->ID ) && $dentist_dashboard_id ) {
            return get_permalink( $dentist_dashboard_id );
        } elseif ( $this->permissions->is_patient( $user->ID ) && $patient_dashboard_id ) {
            return get_permalink( $patient_dashboard_id );
        }
        
        return $redirect_to;
    }

    /**
     * AJAX user registration handler
     *
     * @return void Sends JSON response
     */
    public function ajax_register_user() {
        // Check nonce
        if ( ! check_ajax_referer( 'dental_register_nonce', 'security', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Get and sanitize input
        $user_type = isset( $_POST['user_type'] ) ? sanitize_text_field( $_POST['user_type'] ) : '';
        
        // Validate user type
        if ( ! in_array( $user_type, array( 'dentist', 'patient' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid user type.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Prepare user data
        $user_data = array();
        $fields = array( 'username', 'email', 'password', 'display_name' );
        
        // Add specific fields for dentist
        if ( 'dentist' === $user_type ) {
            $fields = array_merge( $fields, array( 'speciality', 'license' ) );
        }
        
        // Add specific fields for patient
        if ( 'patient' === $user_type ) {
            $fields = array_merge( $fields, array( 'city', 'age' ) );
        }
        
        // Gather data
        foreach ( $fields as $field ) {
            $user_data[ $field ] = isset( $_POST[ $field ] ) ? sanitize_text_field( $_POST[ $field ] ) : '';
        }
        
        // Register user based on type
        if ( 'dentist' === $user_type ) {
            $result = $this->register_dentist( $user_data );
        } else {
            $result = $this->register_patient( $user_data );
        }
        
        // Check result
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        } else {
            wp_send_json_success(
                array(
                    'message' => __( 'Registration successful! Please check your email to verify your account.', 'dental-directory-system' ),
                    'user_id' => $result,
                )
            );
        }
    }

    /**
     * Check if current page requires specific permissions
     *
     * @return void
     */
    public function check_page_access_permissions() {
        global $post;
        
        // Skip if not a singular page
        if ( ! is_singular( 'page' ) || ! $post ) {
            return;
        }
        
        // Dentist-only pages
        $dentist_pages = array(
            get_option( 'dental_page_dashboard_dentista' ),
        );
        
        // Patient-only pages
        $patient_pages = array(
            get_option( 'dental_page_dashboard_paciente' ),
        );
        
        // Check if current page is in restricted pages
        $current_page_id = $post->ID;
        
        // Redirect non-dentists from dentist pages
        if ( in_array( $current_page_id, $dentist_pages, true ) && ! $this->permissions->is_dentist() ) {
            wp_safe_redirect( home_url() );
            exit;
        }
        
        // Redirect non-patients from patient pages
        if ( in_array( $current_page_id, $patient_pages, true ) && ! $this->permissions->is_patient() ) {
            wp_safe_redirect( home_url() );
            exit;
        }
    }

    /**
     * Get role name by user ID
     *
     * @param int $user_id User ID.
     * @return string|bool Role name or false if not found
     */
    public function get_user_role( $user_id ) {
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return false;
        }
        
        if ( $this->permissions->is_dentist( $user_id ) ) {
            return Dental_User_Roles::ROLE_DENTIST;
        }
        
        if ( $this->permissions->is_patient( $user_id ) ) {
            return Dental_User_Roles::ROLE_PATIENT;
        }
        
        return false;
    }
    
    /**
     * Check if user has a specific capability
     *
     * @param string   $capability Capability name.
     * @param int|null $user_id    Optional user ID to check, defaults to current user.
     * @return bool True if user has capability, false otherwise
     */
    public function user_can( $capability, $user_id = null ) {
        $user_id = $this->permissions->get_user_id( $user_id );
        
        return user_can( $user_id, $capability );
    }
}
