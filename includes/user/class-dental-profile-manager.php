<?php
/**
 * Profile Manager Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Profile Manager Class
 *
 * Handles profile CRUD operations for both dentists and patients
 *
 * @since 1.0.0
 */
class Dental_Profile_Manager {

    /**
     * Database instance
     *
     * @var Dental_Database
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        // Get database instance
        global $dental_database;
        if ( ! $dental_database ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/class-dental-database.php';
            $dental_database = new Dental_Database();
        }
        $this->db = $dental_database;

        // Register hooks
        add_action( 'wp_ajax_dental_save_dentist_profile', array( $this, 'ajax_save_dentist_profile' ) );
        add_action( 'wp_ajax_dental_save_patient_profile', array( $this, 'ajax_save_patient_profile' ) );
        add_action( 'wp_ajax_dental_upload_profile_image', array( $this, 'ajax_upload_profile_image' ) );
        add_action( 'wp_ajax_dental_delete_profile_image', array( $this, 'ajax_delete_profile_image' ) );
        
        // Add profile image to user avatars
        add_filter( 'get_avatar', array( $this, 'get_custom_avatar' ), 10, 5 );
    }

    /**
     * Get dentist profile data
     *
     * @param int $user_id User ID.
     * @return array Profile data.
     */
    public function get_dentist_profile( $user_id ) {
        $user_id = absint( $user_id );
        
        // Get user data
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return array();
        }
        
        // Start with basic user data
        $profile = array(
            'user_id'      => $user_id,
            'display_name' => $user->display_name,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'email'        => $user->user_email,
            'username'     => $user->user_login,
        );
        
        // Get custom fields from user meta
        $meta_fields = array(
            'speciality', 'license', 'phone', 'bio', 'city', 'state', 'country',
            'website', 'profile_image', 'cover_image', 'gallery_images',
            'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin'
        );
        
        foreach ( $meta_fields as $field ) {
            $meta_key = 'dental_' . $field;
            $profile[$field] = get_user_meta( $user_id, $meta_key, true );
        }
        
        // Get profile data from database
        $db_profile = $this->db->get_dentist_profile( $user_id );
        if ( $db_profile ) {
            // Convert to array and merge with profile data
            $db_profile_array = (array) $db_profile;
            $profile = array_merge( $profile, $db_profile_array );
        }
        
        // Get subscription data
        $profile['subscription'] = dental_get_subscription_type( $user_id );
        
        return $profile;
    }
    
    /**
     * Get patient profile data
     *
     * @param int $user_id User ID.
     * @return array Profile data.
     */
    public function get_patient_profile( $user_id ) {
        $user_id = absint( $user_id );
        
        // Get user data
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return array();
        }
        
        // Start with basic user data
        $profile = array(
            'user_id'      => $user_id,
            'display_name' => $user->display_name,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'email'        => $user->user_email,
            'username'     => $user->user_login,
        );
        
        // Get custom fields from user meta
        $meta_fields = array(
            'phone', 'city', 'state', 'country', 'profile_image', 
            'bio', 'preferred_contact_method', 'dental_concerns'
        );
        
        foreach ( $meta_fields as $field ) {
            $meta_key = 'dental_' . $field;
            $profile[$field] = get_user_meta( $user_id, $meta_key, true );
        }
        
        return $profile;
    }

    /**
     * Save dentist profile
     *
     * @param int   $user_id      User ID.
     * @param array $profile_data Profile data.
     * @return bool|int ID of inserted/updated profile or false on failure.
     */
    public function save_dentist_profile( $user_id, $profile_data ) {
        $user_id = absint( $user_id );
        
        // Check if user exists
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }
        
        // Update basic WordPress user data
        if ( isset( $profile_data['display_name'] ) ) {
            wp_update_user( array(
                'ID'           => $user_id,
                'display_name' => sanitize_text_field( $profile_data['display_name'] ),
            ) );
        }
        
        if ( isset( $profile_data['first_name'] ) ) {
            update_user_meta( $user_id, 'first_name', sanitize_text_field( $profile_data['first_name'] ) );
        }
        
        if ( isset( $profile_data['last_name'] ) ) {
            update_user_meta( $user_id, 'last_name', sanitize_text_field( $profile_data['last_name'] ) );
        }
        
        // Update meta fields
        $meta_fields = array(
            'speciality', 'license', 'phone', 'bio', 'city', 'state', 'country',
            'website', 'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin'
        );
        
        foreach ( $meta_fields as $field ) {
            if ( isset( $profile_data[$field] ) ) {
                $meta_key = 'dental_' . $field;
                update_user_meta( $user_id, $meta_key, sanitize_text_field( $profile_data[$field] ) );
            }
        }
        
        // Prepare data for dental_profiles table
        $db_profile_data = array();
        
        $db_fields = array(
            'speciality', 'license', 'clinic_name', 'address', 'address_line_1',
            'address_line_2', 'city', 'state', 'postal_code', 'country',
            'phone', 'website', 'working_hours', 'education', 'experience',
            'bio', 'services', 'languages', 'social_facebook', 'social_twitter', 
            'social_instagram', 'social_linkedin', 'latitude', 'longitude', 'featured'
        );
        
        foreach ( $db_fields as $field ) {
            if ( isset( $profile_data[$field] ) ) {
                $db_profile_data[$field] = $profile_data[$field];
            }
        }
        
        // Save to database
        if ( ! empty( $db_profile_data ) ) {
            return $this->db->save_dentist_profile( $user_id, $db_profile_data );
        }
        
        return true;
    }
    
    /**
     * Save patient profile
     *
     * @param int   $user_id      User ID.
     * @param array $profile_data Profile data.
     * @return bool True on success, false on failure.
     */
    public function save_patient_profile( $user_id, $profile_data ) {
        $user_id = absint( $user_id );
        
        // Check if user exists
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }
        
        // Update basic WordPress user data
        if ( isset( $profile_data['display_name'] ) ) {
            wp_update_user( array(
                'ID'           => $user_id,
                'display_name' => sanitize_text_field( $profile_data['display_name'] ),
            ) );
        }
        
        if ( isset( $profile_data['first_name'] ) ) {
            update_user_meta( $user_id, 'first_name', sanitize_text_field( $profile_data['first_name'] ) );
        }
        
        if ( isset( $profile_data['last_name'] ) ) {
            update_user_meta( $user_id, 'last_name', sanitize_text_field( $profile_data['last_name'] ) );
        }
        
        // Update meta fields
        $meta_fields = array(
            'phone', 'city', 'state', 'country', 'bio', 
            'preferred_contact_method', 'dental_concerns'
        );
        
        foreach ( $meta_fields as $field ) {
            if ( isset( $profile_data[$field] ) ) {
                $meta_key = 'dental_' . $field;
                update_user_meta( $user_id, $meta_key, sanitize_text_field( $profile_data[$field] ) );
            }
        }
        
        return true;
    }

    /**
     * AJAX handler for saving dentist profile
     */
    public function ajax_save_dentist_profile() {
        // Check nonce
        check_ajax_referer( 'dental_profile_nonce', 'security' );
        
        // Check if user is logged in and is a dentist
        if ( ! is_user_logged_in() || ! dental_is_dentist() ) {
            wp_send_json_error( array(
                'message' => __( 'Acceso no autorizado.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get and sanitize profile data
        $profile_data = array();
        $fields = array(
            'display_name', 'first_name', 'last_name', 'speciality', 'license',
            'phone', 'bio', 'city', 'state', 'country', 'website', 'working_hours',
            'education', 'experience', 'services', 'languages',
            'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin'
        );
        
        foreach ( $fields as $field ) {
            if ( isset( $_POST[$field] ) ) {
                $profile_data[$field] = sanitize_text_field( wp_unslash( $_POST[$field] ) );
            }
        }
        
        // Handle special fields
        if ( isset( $_POST['bio'] ) ) {
            $profile_data['bio'] = wp_kses_post( wp_unslash( $_POST['bio'] ) );
        }
        
        if ( isset( $_POST['services'] ) ) {
            $profile_data['services'] = wp_kses_post( wp_unslash( $_POST['services'] ) );
        }
        
        // Save profile
        $result = $this->save_dentist_profile( $user_id, $profile_data );
        
        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Perfil actualizado correctamente.', 'dental-directory-system' ),
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Error al actualizar el perfil. Por favor, intenta de nuevo.', 'dental-directory-system' ),
            ) );
        }
    }
    
    /**
     * AJAX handler for saving patient profile
     */
    public function ajax_save_patient_profile() {
        // Check nonce
        check_ajax_referer( 'dental_profile_nonce', 'security' );
        
        // Check if user is logged in and is a patient
        if ( ! is_user_logged_in() || ! dental_is_patient() ) {
            wp_send_json_error( array(
                'message' => __( 'Acceso no autorizado.', 'dental-directory-system' ),
            ) );
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get and sanitize profile data
        $profile_data = array();
        $fields = array(
            'display_name', 'first_name', 'last_name', 'phone', 'bio',
            'city', 'state', 'country', 'preferred_contact_method', 'dental_concerns'
        );
        
        foreach ( $fields as $field ) {
            if ( isset( $_POST[$field] ) ) {
                $profile_data[$field] = sanitize_text_field( wp_unslash( $_POST[$field] ) );
            }
        }
        
        // Handle special fields
        if ( isset( $_POST['bio'] ) ) {
            $profile_data['bio'] = wp_kses_post( wp_unslash( $_POST['bio'] ) );
        }
        
        // Save profile
        $result = $this->save_patient_profile( $user_id, $profile_data );
        
        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Perfil actualizado correctamente.', 'dental-directory-system' ),
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Error al actualizar el perfil. Por favor, intenta de nuevo.', 'dental-directory-system' ),
            ) );
        }
    }

    /**
     * Get custom avatar
     *
     * @param string $avatar      HTML for the user's avatar.
     * @param mixed  $id_or_email User ID or email.
     * @param int    $size        Avatar size in pixels.
     * @param string $default     URL to the default image.
     * @param string $alt         Alternative text.
     * @return string HTML for the user's avatar.
     */
    public function get_custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
        // Get user ID
        $user_id = 0;
        if ( is_numeric( $id_or_email ) ) {
            $user_id = absint( $id_or_email );
        } elseif ( is_string( $id_or_email ) ) {
            $user = get_user_by( 'email', $id_or_email );
            if ( $user ) {
                $user_id = $user->ID;
            }
        } elseif ( is_object( $id_or_email ) ) {
            if ( ! empty( $id_or_email->user_id ) ) {
                $user_id = $id_or_email->user_id;
            } elseif ( ! empty( $id_or_email->ID ) ) {
                $user_id = $id_or_email->ID;
            }
        }
        
        // Return default avatar if user ID is not found
        if ( ! $user_id ) {
            return $avatar;
        }
        
        // Get custom avatar URL
        $custom_avatar = get_user_meta( $user_id, 'dental_profile_image', true );
        
        // Return default avatar if custom avatar is not set
        if ( empty( $custom_avatar ) ) {
            return $avatar;
        }
        
        // Build custom avatar HTML
        $avatar_url = esc_url( $custom_avatar );
        $avatar_markup = sprintf(
            '<img alt="%s" src="%s" class="avatar avatar-%d photo" height="%d" width="%d" loading="lazy" decoding="async"/>',
            esc_attr( $alt ),
            $avatar_url,
            esc_attr( $size ),
            esc_attr( $size ),
            esc_attr( $size )
        );
        
        return $avatar_markup;
    }
}

// Initialize the class
new Dental_Profile_Manager();
