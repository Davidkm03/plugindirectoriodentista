<?php
/**
 * Dashboard Shortcode Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Dashboard Shortcode Class
 *
 * Handles the shortcode for displaying the dentist dashboard
 *
 * @since 1.0.0
 */
class Dental_Dashboard_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode( 'dental_dashboard_dentist', array( $this, 'render_dentist_dashboard' ) );
        add_shortcode( 'dental_dashboard_patient', array( $this, 'render_patient_dashboard' ) );
    }

    /**
     * Render the dentist dashboard
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_dentist_dashboard( $atts ) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'default_tab' => 'profile',
            ),
            $atts,
            'dental_dashboard_dentist'
        );

        // If user is not logged in, show login message
        if ( ! is_user_logged_in() ) {
            return $this->get_login_message();
        }

        // Check if user is a dentist
        if ( ! function_exists('dental_is_dentist') || ! dental_is_dentist() ) {
            return '<div class="dental-notice dental-error">' . 
                   esc_html__( 'No tienes permiso para acceder a esta área.', 'dental-directory-system' ) . 
                   '</div>';
        }

        // Get current view
        $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : $atts['default_tab'];

        // Start output buffering
        ob_start();

        // Include dashboard template
        $template_path = DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/dashboard/dashboard-dentist.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="dental-notice dental-error">' . 
                 esc_html__( 'Error: No se encontró la plantilla del dashboard.', 'dental-directory-system' ) . 
                 '</div>';
        }

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Render the patient dashboard
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_patient_dashboard( $atts ) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'default_tab' => 'profile',
            ),
            $atts,
            'dental_dashboard_patient'
        );

        // If user is not logged in, show login message
        if ( ! is_user_logged_in() ) {
            return $this->get_login_message();
        }

        // Check if user is a patient
        if ( ! function_exists('dental_is_patient') || ! dental_is_patient() ) {
            return '<div class="dental-notice dental-error">' . 
                   esc_html__( 'No tienes permiso para acceder a esta área.', 'dental-directory-system' ) . 
                   '</div>';
        }

        // Get current view
        $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : $atts['default_tab'];

        // Start output buffering
        ob_start();

        // Include dashboard template
        $template_path = DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/dashboard/dashboard-patient.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="dental-notice dental-error">' . 
                 esc_html__( 'Error: No se encontró la plantilla del dashboard.', 'dental-directory-system' ) . 
                 '</div>';
        }

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Get login message for non-logged in users
     *
     * @return string HTML output.
     */
    private function get_login_message() {
        $login_url = wp_login_url( get_permalink() );
        
        $output  = '<div class="dental-login-required">';
        $output .= '<h3>' . esc_html__( 'Acceso requerido', 'dental-directory-system' ) . '</h3>';
        $output .= '<p>' . esc_html__( 'Debes iniciar sesión para acceder a tu dashboard.', 'dental-directory-system' ) . '</p>';
        $output .= '<a href="' . esc_url( $login_url ) . '" class="dental-button">' . esc_html__( 'Iniciar Sesión', 'dental-directory-system' ) . '</a>';
        $output .= '</div>';
        
        return $output;
    }
}

// Initialize the shortcode
new Dental_Dashboard_Shortcode();
