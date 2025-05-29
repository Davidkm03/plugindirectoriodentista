<?php
/**
 * Template Loader for Dental Directory
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Template Loader Class
 *
 * Handles loading and rendering templates for the frontend
 *
 * @since 1.0.0
 */
class Dental_Template_Loader {

    /**
     * Template directory path
     *
     * @var string
     */
    private $template_dir;

    /**
     * Template directory URL
     *
     * @var string
     */
    private $template_url;

    /**
     * Constructor
     */
    public function __construct() {
        $this->template_dir = DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/';
        $this->template_url = DENTAL_DIRECTORY_PLUGIN_URL . 'templates/';
        
        // Register hooks
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
    }

    /**
     * Load a template part
     *
     * @param string $template Template name/path.
     * @param array  $args     Arguments to pass to the template.
     * @param bool   $echo     Whether to echo or return the template.
     * @return string|void Template content if $echo is false
     */
    public function get_template_part( $template, $args = array(), $echo = true ) {
        // Ensure template has .php extension
        if ( ! preg_match( '/\.php$/', $template ) ) {
            $template .= '.php';
        }
        
        $located = $this->locate_template( $template );
        
        if ( ! file_exists( $located ) ) {
            /* translators: %s template */
            _doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'dental-directory-system' ), '<code>' . $located . '</code>' ), '1.0.0' );
            return;
        }
        
        // Make args available to the template
        if ( ! empty( $args ) && is_array( $args ) ) {
            extract( $args );
        }
        
        // Start output buffering if we're returning the template
        if ( ! $echo ) {
            ob_start();
        }
        
        include $located;
        
        // Return the output buffer content
        if ( ! $echo ) {
            return ob_get_clean();
        }
    }

    /**
     * Locate a template and return the path for inclusion
     *
     * @param string $template_name Template to locate.
     * @return string Path to template file
     */
    private function locate_template( $template_name ) {
        $default_path = $this->template_dir;
        
        // Look for template in theme first
        $template = locate_template(
            array(
                'dental-directory/' . $template_name,
            )
        );
        
        // Get default template if not found in theme
        if ( ! $template ) {
            $template = $default_path . $template_name;
        }
        
        return apply_filters( 'dental_locate_template', $template, $template_name, $default_path );
    }

    /**
     * Register stylesheets and scripts
     *
     * @return void
     */
    public function register_assets() {
        // Register and enqueue CSS
        wp_register_style(
            'dental-directory-styles',
            DENTAL_DIRECTORY_PLUGIN_URL . 'public/css/dental-public.css',
            array(),
            DENTAL_DIRECTORY_VERSION
        );
        
        // Register scripts
        wp_register_script(
            'dental-directory-scripts',
            DENTAL_DIRECTORY_PLUGIN_URL . 'public/js/dental-public.js',
            array( 'jquery' ),
            DENTAL_DIRECTORY_VERSION,
            true
        );
        
        // Localize script with Ajax URL and nonce
        wp_localize_script(
            'dental-directory-scripts',
            'dental_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'dental_ajax_nonce' ),
            )
        );
    }

    /**
     * Enqueue assets when needed
     *
     * @return void
     */
    public function enqueue_assets() {
        wp_enqueue_style( 'dental-directory-styles' );
        wp_enqueue_script( 'dental-directory-scripts' );
    }
}
