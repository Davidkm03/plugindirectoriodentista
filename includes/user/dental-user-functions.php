<?php
/**
 * User Helper Functions
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Check if a user is a dentist
 *
 * @param int|null $user_id Optional user ID to check, defaults to current user.
 * @return bool True if user is a dentist, false otherwise
 */
function dental_is_dentist( $user_id = null ) {
    global $dental_directory_system;
    
    if ( ! isset( $dental_directory_system->components['user'] ) ) {
        return false;
    }
    
    $user_manager = $dental_directory_system->components['user'];
    return $user_manager->permissions->is_dentist( $user_id );
}

/**
 * Check if a user is a patient
 *
 * @param int|null $user_id Optional user ID to check, defaults to current user.
 * @return bool True if user is a patient, false otherwise
 */
function dental_is_patient( $user_id = null ) {
    global $dental_directory_system;
    
    if ( ! isset( $dental_directory_system->components['user'] ) ) {
        return false;
    }
    
    $user_manager = $dental_directory_system->components['user'];
    return $user_manager->permissions->is_patient( $user_id );
}

/**
 * Check if user has permission to perform an action
 *
 * @param string   $capability Capability name.
 * @param int|null $user_id    User ID to check, defaults to current user.
 * @return bool True if user has permission, false otherwise
 */
function dental_user_can( $capability, $user_id = null ) {
    if ( null === $user_id ) {
        $user_id = get_current_user_id();
    }
    
    return user_can( $user_id, $capability );
}

/**
 * Get current user role in the dental system
 *
 * @param int|null $user_id User ID to check, defaults to current user.
 * @return string|bool Role name or false if not found
 */
function dental_get_user_role( $user_id = null ) {
    global $dental_directory_system;
    
    if ( ! isset( $dental_directory_system->components['user'] ) ) {
        return false;
    }
    
    if ( null === $user_id ) {
        $user_id = get_current_user_id();
    }
    
    $user_manager = $dental_directory_system->components['user'];
    return $user_manager->get_user_role( $user_id );
}

/**
 * Restrict access to a page based on user role
 *
 * @param array $allowed_roles Array of allowed role names.
 * @return void
 */
function dental_restrict_access( $allowed_roles = array() ) {
    if ( ! is_user_logged_in() ) {
        // Redirect to login page
        wp_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
    
    $user_role = dental_get_user_role( get_current_user_id() );
    
    if ( ! in_array( $user_role, $allowed_roles, true ) ) {
        // Redirect based on role
        if ( 'dentist' === $user_role ) {
            $redirect_id = get_option( 'dental_page_dashboard_dentista' );
            $redirect_url = $redirect_id ? get_permalink( $redirect_id ) : home_url();
            wp_redirect( $redirect_url );
            exit;
        } elseif ( 'patient' === $user_role ) {
            $redirect_id = get_option( 'dental_page_dashboard_paciente' );
            $redirect_url = $redirect_id ? get_permalink( $redirect_id ) : home_url();
            wp_redirect( $redirect_url );
            exit;
        } else {
            // Not a valid role, redirect to home
            wp_redirect( home_url() );
            exit;
        }
    }
}

/**
 * Get user verification status
 *
 * @param int|null $user_id User ID to check, defaults to current user.
 * @return bool True if user is verified, false otherwise
 */
function dental_is_user_verified( $user_id = null ) {
    if ( null === $user_id ) {
        $user_id = get_current_user_id();
    }
    
    if ( ! $user_id ) {
        return false;
    }
    
    return (bool) get_user_meta( $user_id, 'dental_verified', true );
}

/**
 * Verify a user using token
 *
 * @param int    $user_id User ID.
 * @param string $token   Verification token.
 * @return bool True on success, false on failure
 */
function dental_verify_user( $user_id, $token ) {
    $stored_token = get_user_meta( $user_id, 'dental_verification_token', true );
    $timestamp = get_user_meta( $user_id, 'dental_verification_timestamp', true );
    
    // Check if token is valid and not expired (48 hours)
    if ( $stored_token === $token && $timestamp && ( time() - $timestamp ) < ( 48 * HOUR_IN_SECONDS ) ) {
        update_user_meta( $user_id, 'dental_verified', 1 );
        delete_user_meta( $user_id, 'dental_verification_token' );
        delete_user_meta( $user_id, 'dental_verification_timestamp' );
        return true;
    }
    
    return false;
}
