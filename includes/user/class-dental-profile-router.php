<?php
/**
 * Profile Router Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Profile Router Class
 *
 * Handles loading profile templates and routing based on URL parameters
 *
 * @since 1.0.0
 */
class Dental_Profile_Router {

    /**
     * Template loader instance
     *
     * @var Dental_Template_Loader
     */
    private $template_loader;

    /**
     * Constructor
     */
    public function __construct() {
        global $dental_template_loader;
        $this->template_loader = $dental_template_loader;

        // Register rewrite rules
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        
        // Handle template loading
        add_filter( 'template_include', array( $this, 'template_loader' ), 99 );
        
        // Register scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
    }

    /**
     * Add rewrite rules for profile pages
     */
    public function add_rewrite_rules() {
        // Dashboard pages
        add_rewrite_rule( 
            '^dashboard-dentist/?$', 
            'index.php?dental_page=dashboard&dental_user_type=dentist', 
            'top' 
        );
        
        add_rewrite_rule( 
            '^dashboard-patient/?$', 
            'index.php?dental_page=dashboard&dental_user_type=patient', 
            'top' 
        );

        // Profile edit pages
        add_rewrite_rule( 
            '^edit-profile/dentist/?$', 
            'index.php?dental_page=edit_profile&dental_user_type=dentist', 
            'top' 
        );
        
        add_rewrite_rule( 
            '^edit-profile/patient/?$', 
            'index.php?dental_page=edit_profile&dental_user_type=patient', 
            'top' 
        );

        // Add query vars
        add_filter( 'query_vars', function( $vars ) {
            $vars[] = 'dental_page';
            $vars[] = 'dental_user_type';
            return $vars;
        });

        // Flush rewrite rules only when needed
        if ( get_option( 'dental_flush_rewrite_rules', false ) ) {
            flush_rewrite_rules();
            update_option( 'dental_flush_rewrite_rules', false );
        }
    }

    /**
     * Load appropriate template based on URL
     *
     * @param string $template Default template path.
     * @return string Modified template path.
     */
    public function template_loader( $template ) {
        $dental_page = get_query_var( 'dental_page', '' );
        $user_type = get_query_var( 'dental_user_type', '' );

        // Only load custom templates if this is a dental page
        if ( empty( $dental_page ) ) {
            return $template;
        }

        // Make sure user is logged in for all these pages
        if ( ! is_user_logged_in() ) {
            // Redirect to login page with redirect parameter
            $login_page_id = get_option( 'dental_page_login' );
            $login_url = $login_page_id ? get_permalink( $login_page_id ) : wp_login_url();
            $redirect_url = add_query_arg(
                'redirect_to',
                urlencode( $_SERVER['REQUEST_URI'] ),
                $login_url
            );
            wp_redirect( $redirect_url );
            exit;
        }

        // Load appropriate template based on page and user type
        if ( 'edit_profile' === $dental_page ) {
            if ( 'dentist' === $user_type && current_user_can( 'dentist' ) ) {
                // Enqueue profile scripts and styles
                $this->enqueue_profile_assets();
                
                // Load dentist profile edit template
                return DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/pages/profile-edit-dentist.php';
            } elseif ( 'patient' === $user_type && current_user_can( 'patient' ) ) {
                // Enqueue profile scripts and styles
                $this->enqueue_profile_assets();
                
                // Load patient profile edit template
                return DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/pages/profile-edit-patient.php';
            } else {
                // Redirect to appropriate profile edit page based on user role
                if ( current_user_can( 'dentist' ) ) {
                    wp_redirect( home_url( '/edit-profile/dentist/' ) );
                    exit;
                } elseif ( current_user_can( 'patient' ) ) {
                    wp_redirect( home_url( '/edit-profile/patient/' ) );
                    exit;
                } else {
                    // Fallback for other roles
                    wp_redirect( home_url() );
                    exit;
                }
            }
        } elseif ( 'dashboard' === $dental_page ) {
            if ( 'dentist' === $user_type && current_user_can( 'dentist' ) ) {
                // Load dentist dashboard template
                return DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/pages/dashboard-dentist.php';
            } elseif ( 'patient' === $user_type && current_user_can( 'patient' ) ) {
                // Load patient dashboard template
                return DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/pages/dashboard-patient.php';
            } else {
                // Redirect to appropriate dashboard based on user role
                if ( current_user_can( 'dentist' ) ) {
                    wp_redirect( home_url( '/dashboard-dentist/' ) );
                    exit;
                } elseif ( current_user_can( 'patient' ) ) {
                    wp_redirect( home_url( '/dashboard-patient/' ) );
                    exit;
                } else {
                    // Fallback for other roles
                    wp_redirect( home_url() );
                    exit;
                }
            }
        }

        // Return default template if no matching template found
        return $template;
    }

    /**
     * Register scripts and styles
     */
    public function register_scripts() {
        // Profile scripts and styles (only register, not enqueue)
        wp_register_style(
            'dental-profile-styles',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/css/dental-profile.css',
            array(),
            DENTAL_DIRECTORY_VERSION
        );

        wp_register_script(
            'dental-profile-script',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/js/dental-profile.js',
            array( 'jquery' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );

        // Localize script with necessary data
        wp_localize_script(
            'dental-profile-script',
            'dental_vars',
            array(
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'upload_nonce' => wp_create_nonce( 'dental_upload_image_nonce' ),
                'texts'        => array(
                    'processing'   => __( 'Procesando...', 'dental-directory-system' ),
                    'delete'       => __( 'Eliminar', 'dental-directory-system' ),
                    'server_error' => __( 'Error en el servidor. Por favor, intenta de nuevo.', 'dental-directory-system' ),
                ),
            )
        );
    }

    /**
     * Enqueue profile assets
     */
    private function enqueue_profile_assets() {
        wp_enqueue_style( 'dental-profile-styles' );
        wp_enqueue_script( 'dental-profile-script' );
    }
}

// Initialize the class
new Dental_Profile_Router();
