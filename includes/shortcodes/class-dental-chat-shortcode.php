<?php
/**
 * Chat Shortcode Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Chat Shortcode Class
 *
 * Handles the shortcode for displaying the chat interface
 *
 * @since 1.0.0
 */
class Dental_Chat_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcode
        add_shortcode( 'dental_chat', array( $this, 'render_chat' ) );
    }

    /**
     * Render the chat interface
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_chat( $atts ) {
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'recipient_id' => 0,
                'height'       => '600px',
            ),
            $atts,
            'dental_chat'
        );

        // If user is not logged in, show login message
        if ( ! is_user_logged_in() ) {
            return $this->get_login_message();
        }

        // Check if user is a dentist or patient
        $current_user_id = get_current_user_id();
        if ( ! dental_is_dentist( $current_user_id ) && ! dental_is_patient( $current_user_id ) ) {
            return '<div class="dental-notice dental-error">' . 
                   esc_html__( 'Solo los dentistas y pacientes pueden acceder al chat.', 'dental-directory-system' ) . 
                   '</div>';
        }

        // Enqueue required scripts and styles
        wp_enqueue_style( 'dental-chat' );
        wp_enqueue_script( 'dental-chat' );

        // Start output buffering
        ob_start();

        // Include chat template
        $template_path = DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/chat/chat-interface.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="dental-notice dental-error">' . 
                 esc_html__( 'Error: No se encontró la plantilla del chat.', 'dental-directory-system' ) . 
                 '</div>';
        }

        // Custom height if specified
        if ( ! empty( $atts['height'] ) ) {
            echo '<style>.dental-chat-container { height: ' . esc_attr( $atts['height'] ) . '; }</style>';
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
        $output .= '<p>' . esc_html__( 'Debes iniciar sesión para acceder al sistema de chat.', 'dental-directory-system' ) . '</p>';
        $output .= '<a href="' . esc_url( $login_url ) . '" class="dental-button">' . esc_html__( 'Iniciar Sesión', 'dental-directory-system' ) . '</a>';
        $output .= '</div>';
        
        return $output;
    }
}

// Initialize the shortcode
new Dental_Chat_Shortcode();
