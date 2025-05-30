<?php
/**
 * Public-facing functionality of the plugin
 *
 * @package    DentalDirectorySystem
 * @subpackage Public
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the hooks for the public-facing side of the site
 *
 * @since 1.0.0
 */
class Dental_Public {

    /**
     * Template loader instance
     *
     * @var Dental_Template_Loader
     */
    private $template_loader;
    
    /**
     * Router instance
     *
     * @var Dental_Router
     */
    private $router;

    /**
     * Initialize the class
     * 
     * @param Dental_Template_Loader $template_loader Optional. Template loader instance.
     * @param Dental_Router $router Optional. Router instance.
     */
    public function __construct( $template_loader = null, $router = null ) {
        // Set template loader - use provided instance or create new one
        if ( $template_loader instanceof Dental_Template_Loader ) {
            $this->template_loader = $template_loader;
        } else {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/class-dental-template-loader.php';
            $this->template_loader = new Dental_Template_Loader();
        }
        
        // Set router - use provided instance or create new one
        if ( $router instanceof Dental_Router ) {
            $this->router = $router;
        } else {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/class-dental-router.php';
            $this->router = new Dental_Router( $this->template_loader );
        }
        
        // Register hooks
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_action( 'init', array( $this, 'register_ajax_handlers' ) );
    }

    /**
     * Register stylesheets and scripts
     *
     * @return void
     */
    public function register_assets() {
        // Register frontend styles
        wp_register_style(
            'dental-public-styles',
            DENTAL_DIRECTORY_PLUGIN_URL . 'public/css/dental-public.css',
            array(),
            DENTAL_DIRECTORY_VERSION
        );
        
        // Register frontend scripts
        wp_register_script(
            'dental-public-scripts',
            DENTAL_DIRECTORY_PLUGIN_URL . 'public/js/dental-public.js',
            array( 'jquery' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );
        
        // Register authentication scripts
        wp_register_script(
            'dental-auth-scripts',
            DENTAL_DIRECTORY_PLUGIN_URL . 'public/js/dental-auth.js',
            array( 'jquery' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );
        
        // Register registration styles and scripts
        wp_register_style(
            'dental-registration-styles',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/css/dental-registration.css',
            array(),
            DENTAL_DIRECTORY_VERSION
        );
        
        wp_register_script(
            'dental-registration-scripts',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/js/dental-registration-enhanced.js',
            array( 'jquery' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );
        
        // Localize scripts with Ajax URL and nonce
        $localization = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dental_ajax_nonce' ),
            'logout_url' => wp_logout_url( home_url() ),
            'resend_nonce' => wp_create_nonce( 'dental_resend_verification_nonce' ),
            'texts' => array(
                'login' => __( 'Iniciar sesión', 'dental-directory-system' ),
                'register' => __( 'Registrarse', 'dental-directory-system' ),
                'logging_in' => __( 'Iniciando sesión...', 'dental-directory-system' ),
                'registering' => __( 'Registrando...', 'dental-directory-system' ),
                'sending' => __( 'Enviando...', 'dental-directory-system' ),
                'resetting' => __( 'Restableciendo...', 'dental-directory-system' ),
                'logging_out' => __( 'Cerrando sesión...', 'dental-directory-system' ),
                'send_reset_link' => __( 'Enviar enlace', 'dental-directory-system' ),
                'reset_password' => __( 'Restablecer contraseña', 'dental-directory-system' ),
                'password_mismatch' => __( 'Las contraseñas no coinciden', 'dental-directory-system' ),
                'server_error' => __( 'Error en el servidor. Inténtalo de nuevo.', 'dental-directory-system' ),
                'processing' => __( 'Procesando...', 'dental-directory-system' ),
                'password_empty' => __( 'Introduce una contraseña', 'dental-directory-system' ),
                'password_short' => __( 'La contraseña es demasiado corta (mínimo 8 caracteres)', 'dental-directory-system' ),
                'password_weak' => __( 'Contraseña débil', 'dental-directory-system' ),
                'password_medium' => __( 'Contraseña media', 'dental-directory-system' ),
                'password_strong' => __( 'Contraseña fuerte', 'dental-directory-system' ),
                'password_very_strong' => __( 'Contraseña muy fuerte', 'dental-directory-system' ),
            ),
        );
        
        // Enqueue on auth pages
        if ( $this->is_auth_page() ) {
            wp_enqueue_style( 'dental-public-styles' );
            wp_enqueue_script( 'dental-auth-scripts' );
            wp_localize_script( 'dental-auth-scripts', 'dental_vars', $localization );
            
            // Check if it's a registration page
            global $post;
            if ( $post && is_object( $post ) ) {
                $registration_pages = array(
                    get_option( 'dental_page_registro_dentista' ),
                    get_option( 'dental_page_registro_paciente' )
                );
                
                if ( in_array( $post->ID, $registration_pages ) ) {
                    wp_enqueue_style( 'dental-registration-styles' );
                    wp_enqueue_script( 'dental-registration-scripts' );
                    wp_localize_script( 'dental-registration-scripts', 'dental_vars', $localization );
                }
            }
        } else {
            // Enqueue on all other pages
            wp_enqueue_style( 'dental-public-styles' );
            wp_enqueue_script( 'dental-public-scripts' );
            wp_localize_script( 'dental-public-scripts', 'dental_vars', $localization );
        }
    }
    
    /**
     * Check if current page is an authentication page
     *
     * @return boolean True if current page is an auth page
     */
    public function is_auth_page() {
        // Get auth page IDs
        $login_page_id = get_option( 'dental_page_login' );
        $recover_page_id = get_option( 'dental_page_recuperar_password' );
        $reset_page_id = get_option( 'dental_page_reset_password' );
        $register_dentist_id = get_option( 'dental_page_registro_dentista' );
        $register_patient_id = get_option( 'dental_page_registro_paciente' );
        
        // Get current page ID
        $current_page_id = get_queried_object_id();
        
        // Check if current page is an auth page
        if ( in_array( $current_page_id, array( 
            $login_page_id, 
            $recover_page_id, 
            $reset_page_id,
            $register_dentist_id,
            $register_patient_id
        ) ) ) {
            return true;
        }
        
        return false;
    }

    /**
     * Register AJAX handlers
     *
     * @return void
     */
    public function register_ajax_handlers() {
        // Login handler
        add_action( 'wp_ajax_nopriv_dental_login', array( $this, 'handle_login' ) );
        
        // Password recovery handler
        add_action( 'wp_ajax_nopriv_dental_recover_password', array( $this, 'handle_recover_password' ) );
    }

    /**
     * Handle AJAX login requests
     *
     * @return void Sends JSON response
     */
    public function handle_login() {
        // Check nonce
        if ( ! check_ajax_referer( 'dental_login_nonce', 'security', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Get login credentials
        $username = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : '';
        $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
        $remember = isset( $_POST['remember'] ) ? (bool) $_POST['remember'] : false;
        $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : '';
        
        // Check if username and password are provided
        if ( empty( $username ) || empty( $password ) ) {
            wp_send_json_error( array( 'message' => __( 'Username and password are required.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Attempt to log the user in
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
            wp_send_json_error( array( 'message' => __( 'Invalid username or password.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Determine redirect URL based on user role
        if ( empty( $redirect_to ) ) {
            if ( dental_is_dentist( $user->ID ) ) {
                $dashboard_id = get_option( 'dental_page_dashboard_dentista' );
                if ( $dashboard_id ) {
                    $redirect_to = get_permalink( $dashboard_id );
                }
            } elseif ( dental_is_patient( $user->ID ) ) {
                $dashboard_id = get_option( 'dental_page_dashboard_paciente' );
                if ( $dashboard_id ) {
                    $redirect_to = get_permalink( $dashboard_id );
                }
            }
            
            if ( empty( $redirect_to ) ) {
                $redirect_to = home_url();
            }
        }
        
        // Return success response
        wp_send_json_success(
            array(
                'message'  => __( 'Login successful. Redirecting...', 'dental-directory-system' ),
                'redirect' => $redirect_to,
            )
        );
    }

    /**
     * Handle AJAX password recovery requests
     *
     * @return void Sends JSON response
     */
    public function handle_recover_password() {
        // Check nonce
        if ( ! check_ajax_referer( 'dental_recover_password_nonce', 'security', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Get email address
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        
        // Check if email is provided
        if ( empty( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Email address is required.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Check if user exists
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            wp_send_json_error( array( 'message' => __( 'No user found with this email address.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Generate reset key
        $key = get_password_reset_key( $user );
        if ( is_wp_error( $key ) ) {
            wp_send_json_error( array( 'message' => __( 'Error generating password reset link. Please try again.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Get reset URL
        $reset_url = '';
        $page_id = get_option( 'dental_page_recuperar_password' );
        if ( $page_id ) {
            $reset_url = add_query_arg(
                array(
                    'key'   => $key,
                    'login' => rawurlencode( $user->user_login ),
                ),
                get_permalink( $page_id )
            );
        } else {
            $reset_url = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
        }
        
        // Send email
        $subject = sprintf( __( '[%s] Password Reset', 'dental-directory-system' ), get_bloginfo( 'name' ) );
        
        $message = __( 'Someone has requested a password reset for the following account:', 'dental-directory-system' ) . "\r\n\r\n";
        $message .= sprintf( __( 'Site Name: %s', 'dental-directory-system' ), get_bloginfo( 'name' ) ) . "\r\n";
        $message .= sprintf( __( 'Username: %s', 'dental-directory-system' ), $user->user_login ) . "\r\n\r\n";
        $message .= __( 'If this was a mistake, just ignore this email and nothing will happen.', 'dental-directory-system' ) . "\r\n\r\n";
        $message .= __( 'To reset your password, visit the following address:', 'dental-directory-system' ) . "\r\n\r\n";
        $message .= $reset_url . "\r\n";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $sent = wp_mail( $email, $subject, $message, $headers );
        
        if ( $sent ) {
            wp_send_json_success( array( 'message' => __( 'Password reset link has been sent to your email address.', 'dental-directory-system' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Error sending password reset email. Please try again later.', 'dental-directory-system' ) ) );
        }
    }
}
