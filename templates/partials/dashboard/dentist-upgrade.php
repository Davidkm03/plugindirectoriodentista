<?php
/**
 * Dentist Upgrade Plan Template Part
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get user data and subscription status
$user_id = get_current_user_id();
$subscription_type = dental_get_subscription_type($user_id);
$is_premium = ($subscription_type === 'premium');

// Get message stats
global $wpdb;
$messages_table = $wpdb->prefix . 'dental_messages';
$current_month = date('Y-m');
$monthly_messages = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$messages_table} WHERE dentist_id = %d AND DATE_FORMAT(created_at, '%%Y-%%m') = %s",
        $user_id, $current_month
    )
);

// Already premium?
if ($is_premium) {
    ?>
    <div class="dental-dashboard-header">
        <h1><?php esc_html_e('Tu Plan', 'dental-directory-system'); ?></h1>
    </div>
    
    <div class="dental-upgrade-container">
        <div class="dental-current-plan dental-premium-plan">
            <div class="dental-plan-icon">
                <i class="dashicons dashicons-star-filled"></i>
            </div>
            <h2><?php esc_html_e('Plan Premium Activo', 'dental-directory-system'); ?></h2>
            <p class="dental-plan-description">
                <?php esc_html_e('Ya tienes el plan premium activo. Disfruta de todos los beneficios sin límites.', 'dental-directory-system'); ?>
            </p>
            
            <ul class="dental-plan-features">
                <li>
                    <i class="dashicons dashicons-yes"></i>
                    <?php esc_html_e('Mensajes ilimitados', 'dental-directory-system'); ?>
                </li>
                <li>
                    <i class="dashicons dashicons-yes"></i>
                    <?php esc_html_e('Perfil destacado en el directorio', 'dental-directory-system'); ?>
                </li>
                <li>
                    <i class="dashicons dashicons-yes"></i>
                    <?php esc_html_e('Mejor posicionamiento en búsquedas', 'dental-directory-system'); ?>
                </li>
                <li>
                    <i class="dashicons dashicons-yes"></i>
                    <?php esc_html_e('Acceso a estadísticas avanzadas', 'dental-directory-system'); ?>
                </li>
            </ul>
            
            <?php 
            // Get subscription expiration if available
            $subscription_table = $wpdb->prefix . 'dental_subscriptions';
            $expiration_date = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT expiry_date FROM {$subscription_table} 
                    WHERE user_id = %d AND status = 'active' 
                    ORDER BY id DESC LIMIT 1",
                    $user_id
                )
            );
            
            if ($expiration_date) {
                $expiry_timestamp = strtotime($expiration_date);
                $days_remaining = ceil(($expiry_timestamp - time()) / (60 * 60 * 24));
                ?>
                <div class="dental-subscription-info">
                    <p class="dental-expiry-date">
                        <?php esc_html_e('Tu suscripción expira:', 'dental-directory-system'); ?>
                        <strong><?php echo esc_html(date_i18n(get_option('date_format'), $expiry_timestamp)); ?></strong>
                        <span class="dental-days-remaining">
                            (<?php printf(esc_html(_n('%d día restante', '%d días restantes', $days_remaining, 'dental-directory-system')), $days_remaining); ?>)
                        </span>
                    </p>
                    
                    <?php if ($days_remaining <= 7) : ?>
                    <div class="dental-expiry-alert">
                        <p>
                            <i class="dashicons dashicons-warning"></i>
                            <?php esc_html_e('Tu suscripción está próxima a expirar. Renueva ahora para mantener los beneficios premium.', 'dental-directory-system'); ?>
                        </p>
                        <button id="dental-renew-subscription" class="dental-btn dental-btn-primary" data-nonce="<?php echo esc_attr(wp_create_nonce('dental_renew_subscription')); ?>">
                            <?php esc_html_e('Renovar Suscripción', 'dental-directory-system'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
} else {
    // Upgrade options
    ?>
    <div class="dental-dashboard-header">
        <h1><?php esc_html_e('Actualiza tu Plan', 'dental-directory-system'); ?></h1>
    </div>
    
    <?php if ($monthly_messages >= 5): ?>
    <div class="dental-alert dental-alert-warning">
        <p>
            <strong><?php esc_html_e('Límite mensual alcanzado:', 'dental-directory-system'); ?></strong> 
            <?php esc_html_e('Has alcanzado el límite de 5 mensajes por mes. Actualiza tu plan para enviar mensajes ilimitados.', 'dental-directory-system'); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="dental-plans-comparison">
        <div class="dental-plan-card dental-free-plan">
            <div class="dental-plan-header">
                <h2><?php esc_html_e('Plan Gratuito', 'dental-directory-system'); ?></h2>
                <p class="dental-plan-price"><?php esc_html_e('0€/mes', 'dental-directory-system'); ?></p>
                <div class="dental-plan-badge">
                    <?php esc_html_e('Tu plan actual', 'dental-directory-system'); ?>
                </div>
            </div>
            <div class="dental-plan-features">
                <ul>
                    <li>
                        <i class="dashicons dashicons-yes"></i>
                        <?php esc_html_e('Perfil en el directorio', 'dental-directory-system'); ?>
                    </li>
                    <li>
                        <i class="dashicons dashicons-yes"></i>
                        <?php esc_html_e('Mensajería básica', 'dental-directory-system'); ?>
                    </li>
                    <li>
                        <i class="dashicons dashicons-warning"></i>
                        <strong><?php esc_html_e('Límite: 5 mensajes/mes', 'dental-directory-system'); ?></strong>
                    </li>
                    <li class="dental-feature-disabled">
                        <i class="dashicons dashicons-no-alt"></i>
                        <?php esc_html_e('Perfil destacado', 'dental-directory-system'); ?>
                    </li>
                    <li class="dental-feature-disabled">
                        <i class="dashicons dashicons-no-alt"></i>
                        <?php esc_html_e('Posicionamiento prioritario', 'dental-directory-system'); ?>
                    </li>
                    <li class="dental-feature-disabled">
                        <i class="dashicons dashicons-no-alt"></i>
                        <?php esc_html_e('Estadísticas avanzadas', 'dental-directory-system'); ?>
                    </li>
                </ul>
            </div>
            
            <div class="dental-plan-message">
                <p>
                    <?php printf(
                        esc_html__('Has usado %d de 5 mensajes este mes', 'dental-directory-system'),
                        $monthly_messages
                    ); ?>
                </p>
                <div class="dental-progress-container">
                    <div class="dental-progress-bar">
                        <div class="dental-progress" style="width: <?php echo min(100, ($monthly_messages / 5) * 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dental-plan-card dental-premium-plan dental-featured-plan">
            <div class="dental-plan-ribbon"><?php esc_html_e('Recomendado', 'dental-directory-system'); ?></div>
            <div class="dental-plan-header">
                <h2><?php esc_html_e('Plan Premium', 'dental-directory-system'); ?></h2>
                <p class="dental-plan-price">
                    <span class="dental-amount">29.99€</span>
                    <span class="dental-period">/mes</span>
                </p>
                <p class="dental-annual-option">
                    <?php esc_html_e('o 299€/año', 'dental-directory-system'); ?>
                    <span class="dental-save-badge"><?php esc_html_e('¡Ahorra 17%!', 'dental-directory-system'); ?></span>
                </p>
            </div>
            <div class="dental-plan-features">
                <ul>
                    <li>
                        <i class="dashicons dashicons-yes"></i>
                        <?php esc_html_e('Perfil en el directorio', 'dental-directory-system'); ?>
                    </li>
                    <li>
                        <i class="dashicons dashicons-yes"></i>
                        <strong><?php esc_html_e('Mensajes ILIMITADOS', 'dental-directory-system'); ?></strong>
                    </li>
                    <li>
                        <i class="dashicons dashicons-yes"></i>
                        <strong><?php esc_html_e('Perfil destacado', 'dental-directory-system'); ?></strong>
                    </li>
                    <li>
                        <i class="dashicons dashicons-yes"></i>
                        <?php esc_html_e('Posicionamiento prioritario', 'dental-directory-system'); ?>
                    </li>
                    <li>
                        <i class="dashicons dashicons-yes"></i>
                        <?php esc_html_e('Estadísticas avanzadas', 'dental-directory-system'); ?>
                    </li>
                    <li>
                        <i class="dashicons dashicons-yes"></i>
                        <?php esc_html_e('Soporte premium', 'dental-directory-system'); ?>
                    </li>
                </ul>
            </div>
            
            <div class="dental-plan-actions">
                <button id="dental-subscribe-monthly" class="dental-btn dental-btn-primary dental-btn-lg" data-plan="monthly" data-nonce="<?php echo esc_attr(wp_create_nonce('dental_subscribe_plan')); ?>">
                    <?php esc_html_e('Suscribirse mensual', 'dental-directory-system'); ?>
                </button>
                <button id="dental-subscribe-yearly" class="dental-btn dental-btn-outline dental-btn-lg" data-plan="yearly" data-nonce="<?php echo esc_attr(wp_create_nonce('dental_subscribe_plan')); ?>">
                    <?php esc_html_e('Suscribirse anual (Ahorra 17%)', 'dental-directory-system'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <div class="dental-upgrade-info">
        <h3><?php esc_html_e('¿Por qué actualizar a Premium?', 'dental-directory-system'); ?></h3>
        
        <div class="dental-upgrade-benefits">
            <div class="dental-benefit-item">
                <div class="dental-benefit-icon">
                    <i class="dashicons dashicons-email-alt"></i>
                </div>
                <div class="dental-benefit-content">
                    <h4><?php esc_html_e('Mensajes Ilimitados', 'dental-directory-system'); ?></h4>
                    <p><?php esc_html_e('Comunícate con todos los pacientes sin restricciones. Responde rápidamente y aumenta tus posibilidades de conseguir nuevos pacientes.', 'dental-directory-system'); ?></p>
                </div>
            </div>
            
            <div class="dental-benefit-item">
                <div class="dental-benefit-icon">
                    <i class="dashicons dashicons-star-filled"></i>
                </div>
                <div class="dental-benefit-content">
                    <h4><?php esc_html_e('Perfil Destacado', 'dental-directory-system'); ?></h4>
                    <p><?php esc_html_e('Tu perfil aparecerá destacado en el directorio y en los resultados de búsqueda, aumentando tu visibilidad ante posibles pacientes.', 'dental-directory-system'); ?></p>
                </div>
            </div>
            
            <div class="dental-benefit-item">
                <div class="dental-benefit-icon">
                    <i class="dashicons dashicons-chart-line"></i>
                </div>
                <div class="dental-benefit-content">
                    <h4><?php esc_html_e('Estadísticas Avanzadas', 'dental-directory-system'); ?></h4>
                    <p><?php esc_html_e('Analiza tu rendimiento con datos detallados sobre visitas a tu perfil, mensajes recibidos y conversiones.', 'dental-directory-system'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="dental-satisfaction-guarantee">
            <div class="dental-guarantee-icon">
                <i class="dashicons dashicons-shield"></i>
            </div>
            <div class="dental-guarantee-content">
                <h4><?php esc_html_e('Garantía de Satisfacción', 'dental-directory-system'); ?></h4>
                <p><?php esc_html_e('Prueba el plan Premium durante 30 días. Si no estás satisfecho, te reembolsaremos el 100% del importe.', 'dental-directory-system'); ?></p>
            </div>
        </div>
    </div>
    <?php
}
?>

<script>
jQuery(document).ready(function($) {
    // Handle subscription button click
    $('#dental-subscribe-monthly, #dental-subscribe-yearly').on('click', function() {
        const planType = $(this).data('plan');
        const nonce = $(this).data('nonce');
        
        // Show loading state
        const originalText = $(this).text();
        $(this).prop('disabled', true).html('<span class="dental-spinner"></span> <?php esc_html_e("Procesando...", "dental-directory-system"); ?>');
        
        // Process subscription via AJAX
        $.ajax({
            url: dental_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'dental_subscribe_plan',
                plan: planType,
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to payment page if URL provided
                    if (response.data.payment_url) {
                        window.location.href = response.data.payment_url;
                    } else {
                        alert(response.data.message || '<?php esc_html_e("Suscripción iniciada correctamente.", "dental-directory-system"); ?>');
                        window.location.reload();
                    }
                } else {
                    alert(response.data.message || '<?php esc_html_e("Error al procesar la suscripción. Intente de nuevo.", "dental-directory-system"); ?>');
                    // Reset button
                    $(this).prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('<?php esc_html_e("Error de conexión. Verifique su conexión a internet e intente nuevamente.", "dental-directory-system"); ?>');
                // Reset button
                $(this).prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Handle renewal button click
    $('#dental-renew-subscription').on('click', function() {
        const nonce = $(this).data('nonce');
        
        // Show loading state
        $(this).prop('disabled', true).html('<span class="dental-spinner"></span> <?php esc_html_e("Procesando...", "dental-directory-system"); ?>');
        
        // Process renewal via AJAX
        $.ajax({
            url: dental_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'dental_renew_subscription',
                security: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to payment page if URL provided
                    if (response.data.payment_url) {
                        window.location.href = response.data.payment_url;
                    } else {
                        alert(response.data.message || '<?php esc_html_e("Renovación iniciada correctamente.", "dental-directory-system"); ?>');
                        window.location.reload();
                    }
                } else {
                    alert(response.data.message || '<?php esc_html_e("Error al procesar la renovación. Intente de nuevo.", "dental-directory-system"); ?>');
                    // Reset button
                    $(this).prop('disabled', false).text('<?php esc_html_e("Renovar Suscripción", "dental-directory-system"); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e("Error de conexión. Verifique su conexión a internet e intente nuevamente.", "dental-directory-system"); ?>');
                // Reset button
                $(this).prop('disabled', false).text('<?php esc_html_e("Renovar Suscripción", "dental-directory-system"); ?>');
            }
        });
    });
});
</script>
