<?php
/**
 * Template for the chat button
 *
 * @package    DentalDirectorySystem
 * @subpackage Templates/Elements
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get current user ID and dentist ID
$current_user_id = get_current_user_id();
$dentist_id = isset( $dentist_id ) ? absint( $dentist_id ) : 0;

// Only show the chat button if:
// 1. User is logged in
// 2. Current user is a patient (patients can message dentists)
// 3. Viewing a dentist profile that is not the current user
if ( is_user_logged_in() && dental_is_patient( $current_user_id ) && $dentist_id && $dentist_id !== $current_user_id ) :
    
    // Create nonce for security
    $nonce = wp_create_nonce( 'dental_dashboard_nonce' );
?>
<div class="dental-profile-action">
    <button class="dental-chat-button" data-recipient-id="<?php echo esc_attr( $dentist_id ); ?>">
        <i class="fas fa-comments"></i><?php esc_html_e( 'Hablar con el dentista', 'dental-directory-system' ); ?>
    </button>
</div>
<?php
endif;
?>
