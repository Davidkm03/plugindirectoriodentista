<?php
/**
 * User Permissions Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * User Permissions Class
 *
 * Handles permission checking and role verification
 *
 * @since 1.0.0
 */
class Dental_User_Permissions {

    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to initialize
    }

    /**
     * Check if the current user is a dentist
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user is a dentist, false otherwise
     */
    public function is_dentist( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        // Return false if no user
        if ( ! $user_id ) {
            return false;
        }
        
        $user = get_userdata( $user_id );
        
        return $user && in_array( Dental_User_Roles::ROLE_DENTIST, (array) $user->roles, true );
    }

    /**
     * Check if the current user is a patient
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user is a patient, false otherwise
     */
    public function is_patient( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        // Return false if no user
        if ( ! $user_id ) {
            return false;
        }
        
        $user = get_userdata( $user_id );
        
        return $user && in_array( Dental_User_Roles::ROLE_PATIENT, (array) $user->roles, true );
    }

    /**
     * Check if current user can manage dentist profile
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user can manage dentist profile, false otherwise
     */
    public function can_manage_profile( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        return user_can( $user_id, 'dental_manage_profile' );
    }

    /**
     * Check if current user can view messages
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user can view messages, false otherwise
     */
    public function can_view_messages( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        return user_can( $user_id, 'dental_view_messages' );
    }

    /**
     * Check if current user can reply to messages
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user can reply to messages, false otherwise
     */
    public function can_reply_messages( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        return user_can( $user_id, 'dental_reply_messages' );
    }

    /**
     * Check if current user can manage reviews
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user can manage reviews, false otherwise
     */
    public function can_manage_reviews( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        return user_can( $user_id, 'dental_manage_reviews' );
    }

    /**
     * Check if current user can send messages
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user can send messages, false otherwise
     */
    public function can_send_messages( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        return user_can( $user_id, 'dental_send_messages' );
    }

    /**
     * Check if current user can write reviews
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user can write reviews, false otherwise
     */
    public function can_write_reviews( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        return user_can( $user_id, 'dental_write_reviews' );
    }

    /**
     * Check if current user can view dentists
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user can view dentists, false otherwise
     */
    public function can_view_dentists( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        // Any logged-in user or visitor can view dentists
        return true;
    }

    /**
     * Check if current user can manage their subscription
     *
     * @param int|null $user_id Optional user ID to check, defaults to current user.
     * @return bool True if user can manage subscription, false otherwise
     */
    public function can_manage_subscription( $user_id = null ) {
        $user_id = $this->get_user_id( $user_id );
        
        return user_can( $user_id, 'dental_manage_subscription' );
    }

    /**
     * Get a specific user ID, defaulting to current user
     *
     * @param int|null $user_id User ID or null for current user.
     * @return int|false User ID or false if no user
     */
    private function get_user_id( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }
        
        return $user_id;
    }
}
