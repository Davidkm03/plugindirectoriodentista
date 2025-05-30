<?php
/**
 * Chat Functions
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add chat button to dentist profile
 *
 * @param int $dentist_id The dentist ID.
 * @return void
 */
function dental_add_chat_button( $dentist_id ) {
    if ( ! $dentist_id ) {
        return;
    }
    
    // Include the chat button template
    include_once DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/elements/chat-button.php';
}

/**
 * Load chat modal in footer
 *
 * @return void
 */
function dental_load_chat_modal() {
    // Only for logged in users
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Check if modal template exists
    $template_path = DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/chat/chat-modal.php';
    if ( file_exists( $template_path ) ) {
        include_once $template_path;
    }
}
add_action( 'wp_footer', 'dental_load_chat_modal' );

/**
 * Enqueue chat modal scripts and styles
 *
 * @return void
 */
function dental_enqueue_chat_modal_assets() {
    global $post;
    
    // Check if we're on a single dentist profile or directory page
    $is_dentist_profile = is_singular( 'dentist' ) || 
                          ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'dental_directory' ) );
    
    if ( ! $is_dentist_profile ) {
        return;
    }
    
    // Only load for patients who can send messages
    if ( ! is_user_logged_in() || ! dental_is_patient( get_current_user_id() ) ) {
        return;
    }
    
    // Enqueue required scripts and styles
    wp_enqueue_style( 'dental-chat' );
    wp_enqueue_script( 'dental-chat-modal' );
    
    // Font Awesome for icons if not already loaded
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css',
        array(),
        '5.15.3'
    );
    
    // Localize script
    wp_localize_script(
        'dental-chat-modal',
        'DENTAL_CHAT_MODAL',
        array(
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'rest_url'     => esc_url_raw( rest_url( 'dental-directory/v1' ) ),
            'nonce'        => wp_create_nonce( 'dental_dashboard_nonce' ),
            'current_user_id' => get_current_user_id(),
            'user_type'    => 'patient',
            'recipient_id' => 0,
            'strings'      => array(
                'sending'       => esc_html__( 'Enviando...', 'dental-directory-system' ),
                'empty_message' => esc_html__( 'Por favor escribe un mensaje.', 'dental-directory-system' ),
                'error_sending' => esc_html__( 'Error al enviar el mensaje. Inténtalo de nuevo.', 'dental-directory-system' ),
                'typing'        => esc_html__( 'está escribiendo...', 'dental-directory-system' ),
            ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'dental_enqueue_chat_modal_assets' );

/**
 * Get AJAX handler for chat user info
 * Used by the chat modal to get recipient info
 */
function dental_ajax_get_user_info() {
    // Check nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dental_dashboard_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Invalid security token' ) );
    }

    // Check if user ID was provided
    if ( ! isset( $_POST['user_id'] ) || empty( $_POST['user_id'] ) ) {
        wp_send_json_error( array( 'message' => 'User ID is required' ) );
    }

    // Get user ID
    $user_id = absint( $_POST['user_id'] );
    
    // Get user data
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        wp_send_json_error( array( 'message' => 'User not found' ) );
    }
    
    // Get user avatar
    $avatar_url = get_avatar_url( $user_id, array( 'size' => 96 ) );
    
    // Get user display name
    $display_name = $user->display_name;
    
    // Get user role
    $is_dentist = dental_is_dentist( $user_id );
    $is_patient = dental_is_patient( $user_id );
    
    // Prepare user info
    $user_info = array(
        'id'           => $user_id,
        'display_name' => $display_name,
        'avatar'       => $avatar_url,
        'is_dentist'   => $is_dentist,
        'is_patient'   => $is_patient,
    );
    
    wp_send_json_success( $user_info );
}
add_action( 'wp_ajax_dental_get_user_info', 'dental_ajax_get_user_info' );

/**
 * Add the chat button to the dentist profile template
 *
 * @param int $dentist_id The dentist ID (optional, will detect automatically if not provided).
 * @return void
 */
function dental_add_chat_button_to_profile( $dentist_id = 0 ) {
    // Only show for patients
    if ( ! is_user_logged_in() || ! dental_is_patient( get_current_user_id() ) ) {
        return;
    }
    
    // If dentist ID is not provided, try to detect it automatically
    if ( ! $dentist_id ) {
        // Try to get from global post if we're on a single dentist profile
        if ( is_singular( 'dentist' ) ) {
            global $post;
            $dentist_id = $post->post_author;
        } 
        // Or from query var if set (for custom templates)
        elseif ( get_query_var( 'dentist_id' ) ) {
            $dentist_id = absint( get_query_var( 'dentist_id' ) );
        }
        // Or from URL parameter if present
        elseif ( isset( $_GET['dentist_id'] ) ) {
            $dentist_id = absint( $_GET['dentist_id'] );
        }
        // Last resort - try to get from current post meta if this is a related post
        else {
            global $post;
            if ( $post && is_a( $post, 'WP_Post' ) ) {
                $post_dentist_id = get_post_meta( $post->ID, '_dentist_id', true );
                if ( $post_dentist_id ) {
                    $dentist_id = absint( $post_dentist_id );
                }
            }
        }
    }
    
    // If we found a dentist ID, show the chat button
    if ( $dentist_id ) {
        dental_add_chat_button( $dentist_id );
    }
}
add_action( 'dental_after_dentist_contact_info', 'dental_add_chat_button_to_profile', 10, 1 );
