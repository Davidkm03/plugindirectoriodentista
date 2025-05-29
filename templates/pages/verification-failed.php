<?php
/**
 * Template for displaying verification failed page
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include header template
$template_loader->get_template_part('partials/header');
?>

<div class="dental-container">
    <div class="dental-row">
        <div class="dental-col">
            <div class="dental-form">
                <div class="dental-alert dental-alert-error">
                    <h2><?php esc_html_e('Verificación fallida', 'dental-directory-system'); ?></h2>
                    <p><?php esc_html_e('Lo sentimos, el enlace de verificación es inválido o ha expirado.', 'dental-directory-system'); ?></p>
                    
                    <div class="dental-form-footer">
                        <?php 
                        // Get login URL
                        $login_url = '';
                        $login_page_id = get_option('dental_page_login');
                        if ($login_page_id) {
                            $login_url = get_permalink($login_page_id);
                        }
                        ?>
                        
                        <?php if (!empty($login_url)): ?>
                            <p>
                                <?php esc_html_e('Puedes intentar iniciar sesión y solicitar un nuevo enlace de verificación.', 'dental-directory-system'); ?>
                                <a href="<?php echo esc_url($login_url); ?>" class="dental-btn"><?php esc_html_e('Iniciar sesión', 'dental-directory-system'); ?></a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer template
$template_loader->get_template_part('partials/footer');
?>
