<?php
/**
 * Template part for displaying the footer
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="dental-container">
    <footer class="dental-footer">
        <div class="dental-footer-content">
            <p><?php echo esc_html( sprintf( __( '© %s Dental Directory System. All rights reserved.', 'dental-directory-system' ), date( 'Y' ) ) ); ?></p>
        </div>
    </footer>
</div>
