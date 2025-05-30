<?php
/**
 * Dentist Dashboard Template
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if user is logged in and is a dentist
if ( ! is_user_logged_in() || ! dental_is_dentist() ) {
    wp_redirect( home_url() );
    exit;
}

// Get user profile data
$profile_manager = new Dental_Profile_Manager();
$user_id = get_current_user_id();
$profile = $profile_manager->get_dentist_profile( $user_id );

// Get subscription data
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

$total_messages = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$messages_table} WHERE dentist_id = %d",
        $user_id
    )
);

// Include header template
$template_loader->get_template_part('partials/header');
?>

<div class="dental-dashboard-container">
    <!-- Dashboard sidebar -->
    <div class="dental-dashboard-sidebar">
        <div class="dental-sidebar-profile">
            <div class="dental-sidebar-avatar">
                <?php echo get_avatar($user_id, 80); ?>
            </div>
            <div class="dental-sidebar-user-info">
                <h3><?php echo esc_html($profile['display_name']); ?></h3>
                <p class="dental-user-role"><?php esc_html_e('Dentista', 'dental-directory-system'); ?></p>
                <?php if ($is_premium): ?>
                    <span class="dental-premium-badge"><?php esc_html_e('Premium', 'dental-directory-system'); ?></span>
                <?php else: ?>
                    <span class="dental-free-badge"><?php esc_html_e('Plan Gratuito', 'dental-directory-system'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <nav class="dental-dashboard-nav">
            <ul>
                <li class="active">
                    <a href="<?php echo esc_url(get_permalink()); ?>">
                        <i class="dashicons dashicons-dashboard"></i>
                        <?php esc_html_e('Dashboard', 'dental-directory-system'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('view', 'messages', get_permalink())); ?>">
                        <i class="dashicons dashicons-email"></i>
                        <?php esc_html_e('Mensajes', 'dental-directory-system'); ?>
                        <?php if (!$is_premium && $monthly_messages >= 5): ?>
                            <span class="dental-nav-badge dental-badge-error"><?php esc_html_e('Límite', 'dental-directory-system'); ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('view', 'profile', get_permalink())); ?>">
                        <i class="dashicons dashicons-id"></i>
                        <?php esc_html_e('Mi Perfil', 'dental-directory-system'); ?>
                    </a>
                </li>
                <?php if (!$is_premium): ?>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('view', 'upgrade', get_permalink())); ?>">
                        <i class="dashicons dashicons-star-filled"></i>
                        <?php esc_html_e('Actualizar Plan', 'dental-directory-system'); ?>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">
                        <i class="dashicons dashicons-exit"></i>
                        <?php esc_html_e('Cerrar Sesión', 'dental-directory-system'); ?>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Dashboard content -->
    <div class="dental-dashboard-content">
        <?php
        // Get current view
        $current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'dashboard';
        
        // Display appropriate content based on view
        switch ($current_view) {
            case 'messages':
                // Messages section will be implemented separately
                $template_loader->get_template_part('partials/dashboard/dentist-messages');
                break;
                
            case 'profile':
                // Load profile edit template
                include_once DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/pages/profile-edit-dentist.php';
                break;
                
            case 'upgrade':
                // Upgrade section will be implemented separately
                $template_loader->get_template_part('partials/dashboard/dentist-upgrade');
                break;
                
            default:
                // Default dashboard view
                ?>
                <div class="dental-dashboard-header">
                    <h1><?php esc_html_e('Dashboard', 'dental-directory-system'); ?></h1>
                    <p class="dental-welcome-text">
                        <?php printf(esc_html__('Bienvenido, %s', 'dental-directory-system'), esc_html($profile['display_name'])); ?>
                    </p>
                </div>
                
                <div class="dental-dashboard-stats">
                    <div class="dental-stat-card">
                        <div class="dental-stat-icon">
                            <i class="dashicons dashicons-email"></i>
                        </div>
                        <div class="dental-stat-content">
                            <h3><?php echo esc_html($monthly_messages); ?></h3>
                            <p><?php esc_html_e('Mensajes este mes', 'dental-directory-system'); ?></p>
                            <?php if (!$is_premium): ?>
                                <div class="dental-progress-container">
                                    <div class="dental-progress-bar">
                                        <div class="dental-progress" style="width: <?php echo min(100, ($monthly_messages / 5) * 100); ?>%"></div>
                                    </div>
                                    <div class="dental-progress-text">
                                        <?php printf(esc_html__('%d de 5 mensajes', 'dental-directory-system'), min(5, $monthly_messages)); ?>
                                    </div>
                                </div>
                                <?php if ($monthly_messages >= 5): ?>
                                    <div class="dental-limit-reached">
                                        <?php esc_html_e('Has alcanzado el límite mensual.', 'dental-directory-system'); ?>
                                        <a href="<?php echo esc_url(add_query_arg('view', 'upgrade', get_permalink())); ?>" class="dental-upgrade-link">
                                            <?php esc_html_e('Actualizar ahora', 'dental-directory-system'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="dental-stat-card">
                        <div class="dental-stat-icon">
                            <i class="dashicons dashicons-admin-users"></i>
                        </div>
                        <div class="dental-stat-content">
                            <h3><?php echo esc_html($total_messages); ?></h3>
                            <p><?php esc_html_e('Total de mensajes', 'dental-directory-system'); ?></p>
                        </div>
                    </div>
                    
                    <div class="dental-stat-card">
                        <div class="dental-stat-icon <?php echo $is_premium ? 'dental-premium-icon' : ''; ?>">
                            <i class="dashicons <?php echo $is_premium ? 'dashicons-star-filled' : 'dashicons-star-empty'; ?>"></i>
                        </div>
                        <div class="dental-stat-content">
                            <h3><?php echo $is_premium ? esc_html__('Premium', 'dental-directory-system') : esc_html__('Plan Gratuito', 'dental-directory-system'); ?></h3>
                            <p>
                                <?php 
                                if ($is_premium) {
                                    esc_html_e('Sin límites de mensajes', 'dental-directory-system');
                                } else {
                                    esc_html_e('Límite: 5 mensajes/mes', 'dental-directory-system');
                                }
                                ?>
                            </p>
                            <?php if (!$is_premium): ?>
                                <a href="<?php echo esc_url(add_query_arg('view', 'upgrade', get_permalink())); ?>" class="dental-btn dental-btn-sm dental-btn-primary">
                                    <?php esc_html_e('Actualizar a Premium', 'dental-directory-system'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="dental-dashboard-section">
                    <h2><?php esc_html_e('Acciones Rápidas', 'dental-directory-system'); ?></h2>
                    <div class="dental-quick-actions">
                        <a href="<?php echo esc_url(add_query_arg('view', 'profile', get_permalink())); ?>" class="dental-quick-action-card">
                            <div class="dental-action-icon">
                                <i class="dashicons dashicons-id"></i>
                            </div>
                            <h3><?php esc_html_e('Actualizar Perfil', 'dental-directory-system'); ?></h3>
                            <p><?php esc_html_e('Mantén tu información actualizada para atraer más pacientes', 'dental-directory-system'); ?></p>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('view', 'messages', get_permalink())); ?>" class="dental-quick-action-card">
                            <div class="dental-action-icon">
                                <i class="dashicons dashicons-email-alt"></i>
                            </div>
                            <h3><?php esc_html_e('Ver Mensajes', 'dental-directory-system'); ?></h3>
                            <p><?php esc_html_e('Responde a los pacientes interesados en tus servicios', 'dental-directory-system'); ?></p>
                        </a>
                        
                        <?php if (!$is_premium): ?>
                        <a href="<?php echo esc_url(add_query_arg('view', 'upgrade', get_permalink())); ?>" class="dental-quick-action-card dental-premium-card">
                            <div class="dental-action-icon">
                                <i class="dashicons dashicons-star-filled"></i>
                            </div>
                            <h3><?php esc_html_e('Actualiza a Premium', 'dental-directory-system'); ?></h3>
                            <p><?php esc_html_e('Recibe mensajes ilimitados y aumenta tu visibilidad', 'dental-directory-system'); ?></p>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                break;
        }
        ?>
    </div>
</div>

<?php
// Include footer template
$template_loader->get_template_part('partials/footer');
?>
