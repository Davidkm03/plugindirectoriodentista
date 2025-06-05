<?php
/**
 * Assets Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Assets Class
 *
 * Handles registration and enqueuing of all CSS and JS assets
 *
 * @since 1.0.0
 */
class Dental_Assets {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Register all scripts and styles
     */
    public function register_assets() {
        // Register styles
        wp_register_style(
            'dental-dashboard',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/css/dashboard.css',
            array(),
            DENTAL_DIRECTORY_VERSION
        );

        wp_register_style(
            'dental-chat',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/css/dental-chat.css',
            array(),
            DENTAL_DIRECTORY_VERSION
        );

        wp_register_style(
            'dental-main',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/css/main.css',
            array(),
            DENTAL_DIRECTORY_VERSION
        );

        // Register scripts
        wp_register_script(
            'dental-dashboard-actions',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/js/dashboard-actions.js',
            array( 'jquery' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );

        wp_register_script(
            'dental-registration-enhanced',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/js/dental-registration-enhanced.js',
            array( 'jquery' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );

        wp_register_script(
            'dental-chat-modal',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/js/dental-chat-modal.js',
            array( 'jquery', 'wp-util' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );

        wp_register_script(
            'dental-chat',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/js/dental-chat.js',
            array( 'jquery', 'wp-util' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );
        
        // Register alert styles and scripts
        wp_register_style(
            'dental-alerts',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/css/dental-alerts.css',
            array(),
            DENTAL_DIRECTORY_VERSION
        );
        
        wp_register_script(
            'dental-alerts',
            DENTAL_DIRECTORY_PLUGIN_URL . 'assets/js/dental-alerts.js',
            array( 'jquery' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        global $post;

        // Always load the main stylesheet
        wp_enqueue_style( 'dental-main' );
        
        // Enqueue chat modal assets on dentist profiles and directory pages
        if ( is_a( $post, 'WP_Post' ) && 
             (is_singular('dentist') || 
             has_shortcode( $post->post_content, 'dental_directory' ) ||
             strpos( $post->post_content, '<!-- wp:dental-directory/directory' ) !== false)
        ) {
            // Only for logged in patients
            if ( is_user_logged_in() && dental_is_patient( get_current_user_id() ) ) {
                wp_enqueue_style( 'dental-chat' );
                wp_enqueue_script( 'dental-chat-modal' );
                
                // Add Font Awesome if not already loaded
                if ( ! wp_script_is( 'font-awesome', 'registered' ) ) {
                    wp_enqueue_style(
                        'font-awesome',
                        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css',
                        array(),
                        '5.15.3'
                    );
                }
            }
        }
        
        // Enqueue dashboard assets on dashboard pages
        if ( is_a( $post, 'WP_Post' ) && ( 
            has_shortcode( $post->post_content, 'dental_dashboard_dentist' ) || 
            has_shortcode( $post->post_content, 'dental_dashboard_patient' ) ||
            strpos( $post->post_content, '<!-- wp:dental-directory/dashboard' ) !== false
        ) ) {
            // Load dashicons for the dashboard
            wp_enqueue_style( 'dashicons' );
            
            // Load dashboard styles and scripts
            wp_enqueue_style( 'dental-dashboard' );
            wp_enqueue_script( 'dental-dashboard-actions' );
            
            // Load chat interface if on chat tab
            if ( isset( $_GET['view'] ) && $_GET['view'] === 'chat' ) {
                wp_enqueue_style( 'dental-chat' );
                wp_enqueue_script( 'dental-chat' );
                
                // Load alerts system for chat (needed for limit warnings)
                if ( dental_is_dentist() ) {
                    wp_enqueue_style( 'dental-alerts' );
                    wp_enqueue_script( 'dental-alerts' );
                }
            }
            
            // Load alerts system on subscription tab (for upgrade modals)
            if ( isset( $_GET['view'] ) && $_GET['view'] === 'subscription' && dental_is_dentist() ) {
                wp_enqueue_style( 'dental-alerts' );
                wp_enqueue_script( 'dental-alerts' );
            }
            
            // Add localization data for dashboard
            wp_localize_script(
                'dental-dashboard-actions',
                'dental_ajax_object',
                array(
                    'ajax_url'     => admin_url( 'admin-ajax.php' ),
                    'nonce'        => wp_create_nonce( 'dental_ajax_nonce' ),
                    'is_dentist'   => dental_is_dentist(),
                    'is_patient'   => dental_is_patient(),
                    'user_id'      => get_current_user_id(),
                    'translations' => array(
                        'loading'            => __( 'Cargando...', 'dental-directory-system' ),
                        'sending'            => __( 'Enviando...', 'dental-directory-system' ),
                        'error'              => __( 'Error al cargar la conversación.', 'dental-directory-system' ),
                        'ajax_error'         => __( 'Error de conexión. Inténtalo más tarde.', 'dental-directory-system' ),
                        'send'               => __( 'Enviar', 'dental-directory-system' ),
                        'send_error'         => __( 'Error al enviar el mensaje.', 'dental-directory-system' ),
                        'free_plan'          => __( 'Plan gratuito', 'dental-directory-system' ),
                        'of_messages'        => __( 'de 5 mensajes enviados este mes.', 'dental-directory-system' ),
                        'upgrade'            => __( 'Actualizar', 'dental-directory-system' ),
                        'add_favorite'       => __( 'Añadir a favoritos', 'dental-directory-system' ),
                        'remove_favorite'    => __( 'Quitar de favoritos', 'dental-directory-system' ),
                        'no_favorites'       => __( 'No tienes dentistas en favoritos aún.', 'dental-directory-system' ),
                        'find_dentists'      => __( 'Buscar Dentistas', 'dental-directory-system' ),
                        'find_dentists_url'  => add_query_arg( 'view', 'find-dentist', get_permalink() ),
                        'select_plan'        => __( 'Seleccionar Plan', 'dental-directory-system' ),
                        'confirm_subscription' => __( '¿Estás seguro de que deseas suscribirte a este plan? Serás redirigido al portal de pago.', 'dental-directory-system' ),
                        'confirm_renewal'    => __( '¿Estás seguro de que deseas renovar tu suscripción? Serás redirigido al portal de pago.', 'dental-directory-system' ),
                        'processing'         => __( 'Procesando...', 'dental-directory-system' ),
                        'renew'              => __( 'Renovar', 'dental-directory-system' ),
                    ),
                )
            );
        }

        // Enqueue registration scripts on register pages
        if ( is_a( $post, 'WP_Post' ) && (
            has_shortcode( $post->post_content, 'dental_register_dentist' ) ||
            has_shortcode( $post->post_content, 'dental_register_patient' ) ||
            strpos( $post->post_content, '<!-- wp:dental-directory/register' ) !== false
        ) ) {
            wp_enqueue_script( 'dental-registration-enhanced' );
            
            // Add localization data for registration
            wp_localize_script(
                'dental-registration-enhanced',
                'dental_reg_object',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'dental_registration_nonce' ),
                )
            );
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on plugin admin pages
        if ( strpos( $hook, 'dental-directory' ) !== false ) {
            wp_enqueue_style( 'dental-dashboard' );
        }
    }
}

// Initialize the class
new Dental_Assets();
