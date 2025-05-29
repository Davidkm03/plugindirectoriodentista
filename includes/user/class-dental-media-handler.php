<?php
/**
 * Media Handler Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Media Handler Class
 *
 * Handles image uploads and management for dentist and patient profiles
 *
 * @since 1.0.0
 */
class Dental_Media_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX handlers
        add_action( 'wp_ajax_dental_upload_profile_image', array( $this, 'ajax_upload_profile_image' ) );
        add_action( 'wp_ajax_dental_delete_profile_image', array( $this, 'ajax_delete_profile_image' ) );
        add_action( 'wp_ajax_dental_upload_gallery_image', array( $this, 'ajax_upload_gallery_image' ) );
        add_action( 'wp_ajax_dental_delete_gallery_image', array( $this, 'ajax_delete_gallery_image' ) );
    }

    /**
     * Upload profile image
     *
     * @param array  $file     File data from $_FILES.
     * @param int    $user_id  User ID.
     * @param string $type     Image type (profile, cover, gallery).
     * @return array|WP_Error Array with image URL or WP_Error on failure.
     */
    public function upload_image( $file, $user_id, $type = 'profile' ) {
        // Check if user has permission to upload images
        if ( ! user_can( $user_id, 'dental_manage_profile' ) ) {
            return new WP_Error( 'permission_denied', __( 'No tienes permiso para subir imágenes.', 'dental-directory-system' ) );
        }
        
        // Check if file is valid
        if ( ! isset( $file['tmp_name'] ) || empty( $file['tmp_name'] ) ) {
            return new WP_Error( 'invalid_file', __( 'Archivo no válido.', 'dental-directory-system' ) );
        }
        
        // Verify file type
        $allowed_types = array( 'image/jpeg', 'image/png', 'image/gif' );
        $file_type = wp_check_filetype( $file['name'] );
        
        if ( ! in_array( $file['type'], $allowed_types ) || ! $file_type['ext'] ) {
            return new WP_Error( 'invalid_type', __( 'Tipo de archivo no permitido. Por favor, sube imágenes JPG, PNG o GIF.', 'dental-directory-system' ) );
        }
        
        // Check file size (limit to 2MB)
        if ( $file['size'] > 2 * 1024 * 1024 ) {
            return new WP_Error( 'file_too_large', __( 'La imagen es demasiado grande. El tamaño máximo es 2MB.', 'dental-directory-system' ) );
        }
        
        // Prepare for upload
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        
        // Upload file
        $upload_overrides = array(
            'test_form' => false,
            'mimes'     => array(
                'jpg|jpeg' => 'image/jpeg',
                'png'      => 'image/png',
                'gif'      => 'image/gif',
            ),
        );
        
        $uploaded_file = wp_handle_upload( $file, $upload_overrides );
        
        if ( isset( $uploaded_file['error'] ) ) {
            return new WP_Error( 'upload_error', $uploaded_file['error'] );
        }
        
        // Add image to WordPress media library
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }
        
        $attachment = array(
            'guid'           => $uploaded_file['url'],
            'post_mime_type' => $uploaded_file['type'],
            'post_title'     => sanitize_file_name( $file['name'] ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment( $attachment, $uploaded_file['file'] );
        
        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }
        
        // Generate metadata and update attachment
        $attachment_data = wp_generate_attachment_metadata( $attachment_id, $uploaded_file['file'] );
        wp_update_attachment_metadata( $attachment_id, $attachment_data );
        
        // Set the attachment to have the user as its author
        wp_update_post( array(
            'ID'          => $attachment_id,
            'post_author' => $user_id
        ) );
        
        // Update user meta with the image URL
        $meta_key = 'dental_' . $type . '_image';
        update_user_meta( $user_id, $meta_key, $uploaded_file['url'] );
        update_user_meta( $user_id, $meta_key . '_id', $attachment_id );
        
        // Return image data
        return array(
            'url'          => $uploaded_file['url'],
            'attachment_id' => $attachment_id,
        );
    }
    
    /**
     * Add image to gallery
     *
     * @param array $file    File data from $_FILES.
     * @param int   $user_id User ID.
     * @return array|WP_Error Array with image data or WP_Error on failure.
     */
    public function add_gallery_image( $file, $user_id ) {
        // Upload the image
        $result = $this->upload_image( $file, $user_id, 'gallery' );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        // Get existing gallery
        $gallery = get_user_meta( $user_id, 'dental_gallery_images', true );
        if ( ! is_array( $gallery ) ) {
            $gallery = array();
        }
        
        // Add new image to gallery
        $gallery[] = array(
            'url'          => $result['url'],
            'attachment_id' => $result['attachment_id'],
            'date_added'   => current_time( 'mysql' ),
        );
        
        // Update gallery
        update_user_meta( $user_id, 'dental_gallery_images', $gallery );
        
        return $result;
    }
    
    /**
     * Delete profile image
     *
     * @param int    $user_id User ID.
     * @param string $type    Image type (profile, cover, gallery).
     * @return bool True on success, false on failure.
     */
    public function delete_image( $user_id, $type = 'profile' ) {
        // Check if user has permission to delete images
        if ( ! user_can( $user_id, 'dental_manage_profile' ) ) {
            return false;
        }
        
        // Get attachment ID
        $meta_key = 'dental_' . $type . '_image_id';
        $attachment_id = get_user_meta( $user_id, $meta_key, true );
        
        if ( $attachment_id ) {
            // Delete attachment
            wp_delete_attachment( $attachment_id, true );
        }
        
        // Delete user meta
        delete_user_meta( $user_id, 'dental_' . $type . '_image' );
        delete_user_meta( $user_id, $meta_key );
        
        return true;
    }
    
    /**
     * Delete gallery image
     *
     * @param int $user_id       User ID.
     * @param int $attachment_id Attachment ID.
     * @return bool True on success, false on failure.
     */
    public function delete_gallery_image( $user_id, $attachment_id ) {
        // Check if user has permission to delete images
        if ( ! user_can( $user_id, 'dental_manage_profile' ) ) {
            return false;
        }
        
        // Get existing gallery
        $gallery = get_user_meta( $user_id, 'dental_gallery_images', true );
        if ( ! is_array( $gallery ) ) {
            return false;
        }
        
        // Find and remove the image
        $updated_gallery = array();
        $found = false;
        
        foreach ( $gallery as $image ) {
            if ( isset( $image['attachment_id'] ) && $image['attachment_id'] == $attachment_id ) {
                // Delete attachment
                wp_delete_attachment( $attachment_id, true );
                $found = true;
            } else {
                $updated_gallery[] = $image;
            }
        }
        
        if ( $found ) {
            // Update gallery
            update_user_meta( $user_id, 'dental_gallery_images', $updated_gallery );
            return true;
        }
        
        return false;
    }
    
    /**
     * AJAX handler for profile image upload
     */
    public function ajax_upload_profile_image() {
        // Check nonce
        check_ajax_referer( 'dental_upload_image_nonce', 'security' );
        
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Debes iniciar sesión para subir imágenes.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check if file was uploaded
        if ( ! isset( $_FILES['profile_image'] ) ) {
            wp_send_json_error( array(
                'message' => __( 'No se ha subido ningún archivo.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Get image type
        $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'profile';
        if ( ! in_array( $type, array( 'profile', 'cover' ) ) ) {
            $type = 'profile';
        }
        
        // Upload image
        $result = $this->upload_image( $_FILES['profile_image'], $user_id, $type );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
            ) );
            return;
        }
        
        wp_send_json_success( array(
            'message' => __( 'Imagen subida correctamente.', 'dental-directory-system' ),
            'url'     => $result['url'],
        ) );
    }
    
    /**
     * AJAX handler for profile image deletion
     */
    public function ajax_delete_profile_image() {
        // Check nonce
        check_ajax_referer( 'dental_upload_image_nonce', 'security' );
        
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Debes iniciar sesión para eliminar imágenes.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get image type
        $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'profile';
        if ( ! in_array( $type, array( 'profile', 'cover' ) ) ) {
            $type = 'profile';
        }
        
        // Delete image
        $result = $this->delete_image( $user_id, $type );
        
        if ( ! $result ) {
            wp_send_json_error( array(
                'message' => __( 'Error al eliminar la imagen.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        wp_send_json_success( array(
            'message' => __( 'Imagen eliminada correctamente.', 'dental-directory-system' ),
        ) );
    }
    
    /**
     * AJAX handler for gallery image upload
     */
    public function ajax_upload_gallery_image() {
        // Check nonce
        check_ajax_referer( 'dental_upload_image_nonce', 'security' );
        
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Debes iniciar sesión para subir imágenes.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check if file was uploaded
        if ( ! isset( $_FILES['gallery_image'] ) ) {
            wp_send_json_error( array(
                'message' => __( 'No se ha subido ningún archivo.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Upload image
        $result = $this->add_gallery_image( $_FILES['gallery_image'], $user_id );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
            ) );
            return;
        }
        
        wp_send_json_success( array(
            'message' => __( 'Imagen subida correctamente.', 'dental-directory-system' ),
            'url'     => $result['url'],
            'id'      => $result['attachment_id'],
        ) );
    }
    
    /**
     * AJAX handler for gallery image deletion
     */
    public function ajax_delete_gallery_image() {
        // Check nonce
        check_ajax_referer( 'dental_upload_image_nonce', 'security' );
        
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Debes iniciar sesión para eliminar imágenes.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get attachment ID
        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
        
        if ( ! $attachment_id ) {
            wp_send_json_error( array(
                'message' => __( 'ID de imagen no válido.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        // Delete image
        $result = $this->delete_gallery_image( $user_id, $attachment_id );
        
        if ( ! $result ) {
            wp_send_json_error( array(
                'message' => __( 'Error al eliminar la imagen.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        wp_send_json_success( array(
            'message' => __( 'Imagen eliminada correctamente.', 'dental-directory-system' ),
        ) );
    }
}

// Initialize the class
new Dental_Media_Handler();
