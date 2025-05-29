<?php
/**
 * Authentication handler class
 * 
 * @package    DentalDirectorySystem
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Authentication handler
 * 
 * Handles login, registration, and password recovery
 * 
 * @since 1.0.0
 */
class Dental_Auth {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register AJAX handlers
        add_action( 'wp_ajax_nopriv_dental_login', array( $this, 'ajax_login' ) );
        add_action( 'wp_ajax_nopriv_dental_register_dentist', array( $this, 'ajax_register_dentist' ) );
        add_action( 'wp_ajax_nopriv_dental_register_patient', array( $this, 'ajax_register_patient' ) );
        add_action( 'wp_ajax_nopriv_dental_recover_password', array( $this, 'ajax_recover_password' ) );
        add_action( 'wp_ajax_nopriv_dental_reset_password', array( $this, 'ajax_reset_password' ) );
        
        // Logout handler (for both logged in and not logged in users)
        add_action( 'wp_ajax_dental_logout', array( $this, 'ajax_logout' ) );
        add_action( 'wp_ajax_nopriv_dental_logout', array( $this, 'ajax_logout' ) );
        
        // Filter to redirect users after login based on role
        add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
        
        // Add custom login/logout links to menus
        add_filter( 'wp_nav_menu_items', array( $this, 'add_login_logout_links' ), 10, 2 );
        
        // Check verification on login
        add_filter( 'authenticate', array( $this, 'check_email_verification' ), 30, 3 );
    }
    
    /**
     * Handle AJAX login requests
     * 
     * @return void
     */
    public function ajax_login() {
        // Check nonce
        check_ajax_referer( 'dental_login_nonce', 'security' );
        
        // Get form data
        $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
        $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
        $remember = isset( $_POST['remember'] ) && $_POST['remember'] === 'on';
        
        // Validate required fields
        if ( empty( $username ) || empty( $password ) ) {
            wp_send_json_error( array(
                'message' => __( 'Por favor, introduce tu nombre de usuario y contraseña.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Attempt user login
        $user = wp_signon(
            array(
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember,
            ),
            is_ssl()
        );
        
        // Check if login was successful
        if ( is_wp_error( $user ) ) {
            wp_send_json_error( array(
                'message' => __( 'Usuario o contraseña incorrectos.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Get redirect URL based on user role
        $redirect = $this->get_role_redirect_url( $user );
        
        // Return success
        wp_send_json_success( array(
            'message'  => __( 'Inicio de sesión exitoso. Redirigiendo...', 'dental-directory-system' ),
            'redirect' => $redirect,
        ) );
    }
    
    /**
     * Handle AJAX dentist registration
     * 
     * @return void
     */
    public function ajax_register_dentist() {
        // Check nonce
        check_ajax_referer( 'dental_register_nonce', 'security' );
        
        // Get form data
        $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
        $password_confirm = isset( $_POST['password_confirm'] ) ? $_POST['password_confirm'] : '';
        $display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
        $speciality = isset( $_POST['speciality'] ) ? sanitize_text_field( wp_unslash( $_POST['speciality'] ) ) : '';
        $license = isset( $_POST['license'] ) ? sanitize_text_field( wp_unslash( $_POST['license'] ) ) : '';
        
        // Validate required fields
        if ( empty( $username ) || empty( $email ) || empty( $password ) || empty( $display_name ) ) {
            wp_send_json_error( array(
                'message' => __( 'Por favor, completa todos los campos obligatorios.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Validate email
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Por favor, introduce una dirección de correo electrónico válida.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check if username exists
        if ( username_exists( $username ) ) {
            wp_send_json_error( array(
                'message' => __( 'Este nombre de usuario ya está en uso. Por favor, elige otro.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check if email exists
        if ( email_exists( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Esta dirección de correo electrónico ya está registrada.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Create user
        $user_id = wp_create_user( $username, $password, $email );
        
        // Check if user was created successfully
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array(
                'message' => $user_id->get_error_message(),
            ) );
            return;
        }
        
        // Update user meta
        wp_update_user(
            array(
                'ID'           => $user_id,
                'display_name' => $display_name,
                'nickname'     => $display_name,
                'first_name'   => $display_name,
            )
        );
        
        // Set user role to dentist
        $user = new WP_User( $user_id );
        $user->set_role( 'dentist' );
        
        // Add custom user meta
        update_user_meta( $user_id, 'dental_speciality', $speciality );
        update_user_meta( $user_id, 'dental_license', $license );
        update_user_meta( $user_id, 'dental_registration_date', current_time( 'mysql' ) );
        
        // Set up free subscription by default
        $subscription_data = array(
            'user_id'       => $user_id,
            'plan_id'       => 'free',
            'status'        => 'active',
            'start_date'    => current_time( 'mysql' ),
            'next_payment'  => null,
            'trial_end'     => null,
        );
        
        // Add free subscription by default
        $this->add_subscription( array(
            'user_id'   => $user_id,
            'plan_name' => 'free',
            'status'    => 'active',
        ) );
        
        // Send verification email
        $verification = new Dental_Email_Verification();
        $sent = $verification->send_verification_email( $user_id, $email );
        
        if ( ! $sent ) {
            // Log error but continue with registration
            error_log( 'Failed to send verification email to user ' . $user_id );
        }
        
        // Get login page URL for redirect
        $login_page_id = get_option( 'dental_page_login' );
        $redirect = $login_page_id ? get_permalink( $login_page_id ) : home_url();
        $redirect = add_query_arg( 'verification_sent', '1', $redirect );
        
        // Return success
        wp_send_json_success( array(
            'message'  => __( 'Registro exitoso. Por favor, verifica tu correo electrónico para activar tu cuenta.', 'dental-directory-system' ),
            'redirect' => $redirect,
            'user_id'  => $user_id,
        ) );
    }
    
    /**
     * Handle AJAX patient registration
     * 
     * @return void
     */
    public function ajax_register_patient() {
        // Check nonce
        check_ajax_referer( 'dental_register_nonce', 'security' );
        
        // Get form data
        $username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
        $password_confirm = isset( $_POST['password_confirm'] ) ? $_POST['password_confirm'] : '';
        $display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
        $city = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        
        // Validate required fields
        if ( empty( $username ) || empty( $email ) || empty( $password ) || empty( $display_name ) ) {
            wp_send_json_error( array(
                'message' => __( 'Por favor, completa todos los campos obligatorios.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Validate email
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Por favor, introduce una dirección de correo electrónico válida.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check if username exists
        if ( username_exists( $username ) ) {
            wp_send_json_error( array(
                'message' => __( 'Este nombre de usuario ya está en uso. Por favor, elige otro.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check if email exists
        if ( email_exists( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Esta dirección de correo electrónico ya está registrada.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Create user
        $user_id = wp_create_user( $username, $password, $email );
        
        // Check if user was created successfully
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array(
                'message' => $user_id->get_error_message(),
            ) );
            return;
        }
        
        // Update user meta
        wp_update_user(
            array(
                'ID'           => $user_id,
                'display_name' => $display_name,
                'nickname'     => $display_name,
                'first_name'   => $display_name,
            )
        );
        
        // Set user role to patient
        $user = new WP_User( $user_id );
        $user->set_role( 'patient' );
        
        // Add city to user meta if provided
        if ( ! empty( $city ) ) {
            update_user_meta( $user_id, 'dental_city', $city );
        }
        
        // Auto login user
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );
        
        // Get redirect URL
        $redirect = $this->get_role_redirect_url( $user );
        
        // Return success
        wp_send_json_success( array(
            'message'  => __( 'Registro exitoso. Redirigiendo a tu panel...', 'dental-directory-system' ),
            'redirect' => $redirect,
        ) );
    }
    
    /**
     * Handle AJAX password recovery requests
     * 
     * @return void
     */
    public function ajax_recover_password() {
        // Check nonce
        check_ajax_referer( 'dental_recover_password_nonce', 'security' );
        
        // Get email address
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        
        // Validate email
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Por favor, introduce una dirección de correo electrónico válida.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check if user exists
        $user = get_user_by( 'email', $email );
        
        if ( ! $user ) {
            // Don't reveal whether email exists or not for security reasons
            wp_send_json_success( array(
                'message' => __( 'Si tu dirección de correo electrónico está registrada, recibirás un enlace para restablecer tu contraseña.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Get password reset key
        $key = get_password_reset_key( $user );
        
        if ( is_wp_error( $key ) ) {
            wp_send_json_error( array(
                'message' => __( 'Error al generar el enlace de restablecimiento. Por favor, inténtalo de nuevo más tarde.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Get reset URL
        $reset_url = '';
        $reset_page_id = get_option( 'dental_page_recuperar_password' );
        
        if ( $reset_page_id ) {
            $reset_url = add_query_arg(
                array(
                    'key'   => $key,
                    'login' => rawurlencode( $user->user_login ),
                ),
                get_permalink( $reset_page_id )
            );
        } else {
            $reset_url = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
        }
        
        // Send email
        $subject = sprintf( __( '[%s] Restablecimiento de contraseña', 'dental-directory-system' ), get_bloginfo( 'name' ) );
        
        $message = __( 'Se ha solicitado un restablecimiento de contraseña para la siguiente cuenta:', 'dental-directory-system' ) . "\r\n\r\n";
        $message .= sprintf( __( 'Sitio: %s', 'dental-directory-system' ), get_bloginfo( 'name' ) ) . "\r\n";
        $message .= sprintf( __( 'Usuario: %s', 'dental-directory-system' ), $user->user_login ) . "\r\n\r\n";
        $message .= __( 'Si no solicitaste este cambio, ignora este correo y no pasará nada.', 'dental-directory-system' ) . "\r\n\r\n";
        $message .= __( 'Para restablecer tu contraseña, visita el siguiente enlace:', 'dental-directory-system' ) . "\r\n\r\n";
        $message .= $reset_url . "\r\n";
        
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        
        $sent = wp_mail( $email, $subject, $message, $headers );
        
        if ( $sent ) {
            wp_send_json_success( array(
                'message' => __( 'Se ha enviado un enlace para restablecer tu contraseña a tu dirección de correo electrónico.', 'dental-directory-system' ),
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Error al enviar el correo electrónico. Por favor, inténtalo de nuevo más tarde.', 'dental-directory-system' ),
            ) );
        }
    }
    
    /**
     * Handle AJAX password reset
     * 
     * @return void
     */
    public function ajax_reset_password() {
        // Check nonce
        check_ajax_referer( 'dental_reset_password_nonce', 'security' );
        
        // Get form data
        $key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
        $login = isset( $_POST['login'] ) ? sanitize_user( wp_unslash( $_POST['login'] ) ) : '';
        $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
        
        // Validate required fields
        if ( empty( $key ) || empty( $login ) || empty( $password ) ) {
            wp_send_json_error( array(
                'message' => __( 'Por favor, completa todos los campos.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Get user by login
        $user = get_user_by( 'login', $login );
        
        if ( ! $user ) {
            wp_send_json_error( array(
                'message' => __( 'Usuario no válido.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Check password reset key
        $check = check_password_reset_key( $key, $login );
        
        if ( is_wp_error( $check ) ) {
            wp_send_json_error( array(
                'message' => __( 'La clave de restablecimiento no es válida o ha expirado. Por favor, solicita un nuevo enlace.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Reset password
        reset_password( $user, $password );
        
        // Get login URL
        $login_url = '';
        $login_page_id = get_option( 'dental_page_login' );
        
        if ( $login_page_id ) {
            $login_url = get_permalink( $login_page_id );
        } else {
            $login_url = wp_login_url();
        }
        
        // Return success
        wp_send_json_success( array(
            'message'  => __( 'Tu contraseña ha sido restablecida. Redirigiendo a la página de inicio de sesión...', 'dental-directory-system' ),
            'redirect' => $login_url,
        ) );
    }
    
    /**
     * Handle AJAX logout
     * 
     * @return void
     */
    public function ajax_logout() {
        // Check nonce
        check_ajax_referer( 'dental_ajax_nonce', 'security' );
        
        // Get redirect URL
        $redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : home_url();
        
        // Check if we should redirect to a specific page
        $login_page_id = get_option( 'dental_page_login' );
        
        if ( $login_page_id ) {
            $redirect = get_permalink( $login_page_id );
        }
        
        // Logout user
        wp_logout();
        
        // Return success
        wp_send_json_success( array(
            'message'  => __( 'Sesión cerrada. Redirigiendo...', 'dental-directory-system' ),
            'redirect' => $redirect,
        ) );
    }
    
    /**
     * Redirect users after login based on their role
     * 
     * @param string $redirect_to  The redirect destination URL.
     * @param string $request      The requested redirect destination URL passed as a parameter.
     * @param WP_User $user        WP_User object if login was successful, WP_Error object otherwise.
     * @return string Redirect URL
     */
    public function login_redirect( $redirect_to, $request, $user ) {
        // Not logged in, return default redirect
        if ( ! is_object( $user ) || is_wp_error( $user ) ) {
            return $redirect_to;
        }
        
        // Get role-based redirect URL
        $redirect = $this->get_role_redirect_url( $user );
        
        if ( ! empty( $redirect ) ) {
            return $redirect;
        }
        
        // Default to requested redirect
        return $redirect_to;
    }
    
    /**
     * Get role-based redirect URL
     * 
     * @param WP_User $user User object
     * @return string Redirect URL
     */
    private function get_role_redirect_url( $user ) {
        // If user is dentist, redirect to dentist dashboard
        if ( dental_is_dentist( $user->ID ) ) {
            $dashboard_id = get_option( 'dental_page_dashboard_dentista' );
            
            if ( $dashboard_id ) {
                return get_permalink( $dashboard_id );
            }
        }
        
        // If user is patient, redirect to patient dashboard
        if ( dental_is_patient( $user->ID ) ) {
            $dashboard_id = get_option( 'dental_page_dashboard_paciente' );
            
            if ( $dashboard_id ) {
                return get_permalink( $dashboard_id );
            }
        }
        
        // Default to home
        return home_url();
    }
    
    /**
     * Add subscription for user
     * 
     * @param array $data Subscription data
     * @return int|false Subscription ID or false on failure
     */
    private function add_subscription( $data ) {
        global $wpdb;
        
        // Get subscription table name
        $table = $wpdb->prefix . 'dental_subscriptions';
        
        // Insert subscription
        $result = $wpdb->insert(
            $table,
            array(
                'user_id'       => $data['user_id'],
                'plan_id'       => $data['plan_id'],
                'status'        => $data['status'],
                'start_date'    => $data['start_date'],
                'next_payment'  => $data['next_payment'],
                'trial_end'     => $data['trial_end'],
                'created_at'    => current_time( 'mysql' ),
                'updated_at'    => current_time( 'mysql' ),
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
        
        if ( $result ) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Check email verification before login
     *
     * @param WP_User|WP_Error $user     WP_User or WP_Error object from a previous callback
     * @param string           $username Username for authentication
     * @param string           $password Password for authentication
     * @return WP_User|WP_Error WP_User on success, WP_Error on failure
     */
    public function check_email_verification( $user, $username, $password ) {
        // If there's already an error, just return it
        if ( is_wp_error( $user ) ) {
            return $user;
        }

        // Check if user is verified
        if ( ! empty( $user->ID ) ) {
            $verified = get_user_meta( $user->ID, 'dental_email_verified', true );
            
            // Skip verification check for administrators
            if ( user_can( $user->ID, 'administrator' ) ) {
                return $user;
            }
            
            // If user is not verified, return an error
            if ( empty( $verified ) ) {
                // Get user email
                $email = $user->user_email;
                
                // Create error with resend verification link
                $error = new WP_Error(
                    'dental_unverified_email',
                    sprintf(
                        __( 'Tu cuenta no ha sido verificada. Por favor, verifica tu correo electrónico o <a href="#" class="dental-resend-verification" data-user="%d">haz clic aquí para reenviar el correo de verificación</a>.', 'dental-directory-system' ),
                        $user->ID
                    )
                );
                
                return $error;
            }
        }
        
        return $user;
    }
    
    /**
     * Add login/logout links to menus
     * 
     * @param string $items Menu items HTML
     * @param object $args  Menu arguments
     * @return string Modified menu items HTML
     */
    public function add_login_logout_links( $items, $args ) {
        // Only add to primary menu
        if ( $args->theme_location !== 'primary' ) {
            return $items;
        }
        
        // Check if user is logged in
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            $username = $user->display_name;
            
            // Get dashboard URL based on user role
            $dashboard_url = $this->get_role_redirect_url( $user );
            
            // Add dashboard and logout links
            $items .= '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-dashboard">';
            $items .= '<a href="' . esc_url( $dashboard_url ) . '">' . esc_html__( 'Mi Panel', 'dental-directory-system' ) . '</a>';
            $items .= '</li>';
            
            $items .= '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-logout">';
            $items .= '<a href="#" class="dental-logout-link" data-redirect="' . esc_url( home_url() ) . '">' . esc_html__( 'Cerrar sesión', 'dental-directory-system' ) . '</a>';
            $items .= '</li>';
        } else {
            // Get login page URL
            $login_url = '';
            $login_page_id = get_option( 'dental_page_login' );
            
            if ( $login_page_id ) {
                $login_url = get_permalink( $login_page_id );
            } else {
                $login_url = wp_login_url();
            }
            
            // Get register page URLs
            $dentist_reg_url = '';
            $dentist_reg_id = get_option( 'dental_page_registro_dentista' );
            
            if ( $dentist_reg_id ) {
                $dentist_reg_url = get_permalink( $dentist_reg_id );
            }
            
            $patient_reg_url = '';
            $patient_reg_id = get_option( 'dental_page_registro_paciente' );
            
            if ( $patient_reg_id ) {
                $patient_reg_url = get_permalink( $patient_reg_id );
            }
            
            // Add login and register links
            $items .= '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-login">';
            $items .= '<a href="' . esc_url( $login_url ) . '">' . esc_html__( 'Iniciar sesión', 'dental-directory-system' ) . '</a>';
            $items .= '</li>';
            
            if ( ! empty( $dentist_reg_url ) && ! empty( $patient_reg_url ) ) {
                $items .= '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-register menu-item-has-children">';
                $items .= '<a href="#">' . esc_html__( 'Registrarse', 'dental-directory-system' ) . '</a>';
                $items .= '<ul class="sub-menu">';
                $items .= '<li class="menu-item menu-item-type-custom menu-item-object-custom">';
                $items .= '<a href="' . esc_url( $dentist_reg_url ) . '">' . esc_html__( 'Como dentista', 'dental-directory-system' ) . '</a>';
                $items .= '</li>';
                $items .= '<li class="menu-item menu-item-type-custom menu-item-object-custom">';
                $items .= '<a href="' . esc_url( $patient_reg_url ) . '">' . esc_html__( 'Como paciente', 'dental-directory-system' ) . '</a>';
                $items .= '</li>';
                $items .= '</ul>';
                $items .= '</li>';
            }
        }
        
        return $items;
    }
}

// Helper functions

/**
 * Check if a user is a dentist
 * 
 * @param int $user_id User ID (optional, defaults to current user)
 * @return bool True if user is a dentist, false otherwise
 */
function dental_is_dentist( $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata( $user_id );
    
    return $user && in_array( 'dentist', (array) $user->roles, true );
}

/**
 * Check if a user is a patient
 * 
 * @param int $user_id User ID (optional, defaults to current user)
 * @return bool True if user is a patient, false otherwise
 */
function dental_is_patient( $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata( $user_id );
    
    return $user && in_array( 'patient', (array) $user->roles, true );
}

/**
 * Get user subscription type
 * 
 * @param int $user_id User ID (optional, defaults to current user)
 * @return string|null Subscription type or null if none
 */
function dental_get_subscription_type( $user_id = 0 ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    global $wpdb;
    
    // Get subscription table name
    $table = $wpdb->prefix . 'dental_subscriptions';
    
    // Get active subscription for user
    $subscription = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
            $user_id
        )
    );
    
    if ( $subscription ) {
        return $subscription->plan_id;
    }
    
    return null;
}
