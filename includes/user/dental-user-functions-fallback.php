<?php
/**
 * User Helper Functions - Fallback Version
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Solo definir estas funciones si no existen aún
if ( ! function_exists( 'dental_is_dentist' ) ) {
    /**
     * Verifica si un usuario es dentista (versión de respaldo)
     *
     * @param int|null $user_id ID de usuario opcional, por defecto el usuario actual.
     * @return bool True si el usuario es dentista, false en caso contrario
     */
    function dental_is_dentist( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }
        
        if ( ! $user_id ) {
            return false;
        }
        
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }
        
        // Verificar si el usuario tiene el rol de dentista
        return in_array( 'dentist', (array) $user->roles, true );
    }
}

if ( ! function_exists( 'dental_is_patient' ) ) {
    /**
     * Verifica si un usuario es paciente (versión de respaldo)
     *
     * @param int|null $user_id ID de usuario opcional, por defecto el usuario actual.
     * @return bool True si el usuario es paciente, false en caso contrario
     */
    function dental_is_patient( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }
        
        if ( ! $user_id ) {
            return false;
        }
        
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }
        
        // Verificar si el usuario tiene el rol de paciente
        return in_array( 'patient', (array) $user->roles, true );
    }
}
