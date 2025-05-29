<?php
/**
 * User Roles Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * User Roles Class
 *
 * Handles creation and management of user roles and capabilities
 *
 * @since 1.0.0
 */
class Dental_User_Roles {

    /**
     * Dentist role name
     *
     * @var string
     */
    const ROLE_DENTIST = 'dentist';

    /**
     * Patient role name
     *
     * @var string
     */
    const ROLE_PATIENT = 'patient';

    /**
     * Custom capabilities for our plugin
     *
     * @var array
     */
    private $custom_caps = array(
        // Dentist capabilities
        'dental_manage_profile'    => true,
        'dental_view_messages'     => true,
        'dental_reply_messages'    => true,
        'dental_manage_reviews'    => true,
        'dental_view_statistics'   => true,
        'dental_manage_subscription' => true,
        
        // Patient capabilities
        'dental_send_messages'     => true,
        'dental_write_reviews'     => true,
        'dental_view_dentists'     => true,
        'dental_favorite_dentists' => true,
    );

    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks
        add_action( 'init', array( $this, 'register_capabilities' ) );
    }

    /**
     * Register custom capabilities with WordPress roles
     *
     * @return void
     */
    public function register_capabilities() {
        // Get administrator role
        $admin = get_role( 'administrator' );
        
        // Add all custom capabilities to administrator
        if ( $admin ) {
            foreach ( $this->custom_caps as $cap => $grant ) {
                $admin->add_cap( $cap, $grant );
            }
        }
    }

    /**
     * Create custom user roles
     *
     * @return void
     */
    public function create_roles() {
        // Create dentist role if it doesn't exist
        if ( ! get_role( self::ROLE_DENTIST ) ) {
            add_role(
                self::ROLE_DENTIST,
                __( 'Dentist', 'dental-directory-system' ),
                array(
                    // WordPress core capabilities
                    'read'                   => true,
                    'edit_posts'             => false,
                    'delete_posts'           => false,
                    'publish_posts'          => false,
                    'upload_files'           => true,
                    
                    // Custom capabilities
                    'dental_manage_profile'  => true,
                    'dental_view_messages'   => true,
                    'dental_reply_messages'  => true,
                    'dental_manage_reviews'  => true,
                    'dental_view_statistics' => true,
                    'dental_manage_subscription' => true,
                )
            );
        }
        
        // Create patient role if it doesn't exist
        if ( ! get_role( self::ROLE_PATIENT ) ) {
            add_role(
                self::ROLE_PATIENT,
                __( 'Patient', 'dental-directory-system' ),
                array(
                    // WordPress core capabilities
                    'read'                   => true,
                    'edit_posts'             => false,
                    'delete_posts'           => false,
                    'publish_posts'          => false,
                    'upload_files'           => true,
                    
                    // Custom capabilities
                    'dental_manage_profile'  => true,
                    'dental_send_messages'   => true,
                    'dental_write_reviews'   => true,
                    'dental_view_dentists'   => true,
                    'dental_favorite_dentists' => true,
                )
            );
        }
    }

    /**
     * Remove custom roles
     *
     * @return void
     */
    public function remove_roles() {
        remove_role( self::ROLE_DENTIST );
        remove_role( self::ROLE_PATIENT );
    }

    /**
     * Get all custom capabilities
     *
     * @return array Custom capabilities
     */
    public function get_custom_capabilities() {
        return array_keys( $this->custom_caps );
    }
}
