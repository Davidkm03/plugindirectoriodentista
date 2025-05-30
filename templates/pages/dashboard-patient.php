<?php
/**
 * Patient Dashboard Template
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if user is logged in and is a patient
if ( ! is_user_logged_in() || ! dental_is_patient() ) {
    wp_redirect( home_url() );
    exit;
}

// Get user profile data
$profile_manager = new Dental_Profile_Manager();
$user_id = get_current_user_id();
$profile = $profile_manager->get_patient_profile( $user_id );

// Get messages stats
global $wpdb;
$messages_table = $wpdb->prefix . 'dental_messages';
$total_messages = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$messages_table} WHERE patient_id = %d",
        $user_id
    )
);

// Get favorite dentists
$favorites_table = $wpdb->prefix . 'dental_favorites';
$favorite_dentists = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$favorites_table} WHERE patient_id = %d",
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
                <p class="dental-user-role"><?php esc_html_e('Paciente', 'dental-directory-system'); ?></p>
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
                        <?php esc_html_e('Mis Mensajes', 'dental-directory-system'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('view', 'favorites', get_permalink())); ?>">
                        <i class="dashicons dashicons-heart"></i>
                        <?php esc_html_e('Dentistas Favoritos', 'dental-directory-system'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('view', 'profile', get_permalink())); ?>">
                        <i class="dashicons dashicons-id"></i>
                        <?php esc_html_e('Mi Perfil', 'dental-directory-system'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('view', 'find-dentist', get_permalink())); ?>">
                        <i class="dashicons dashicons-search"></i>
                        <?php esc_html_e('Buscar Dentistas', 'dental-directory-system'); ?>
                    </a>
                </li>
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
                $template_loader->get_template_part('partials/dashboard/patient-messages');
                break;
                
            case 'favorites':
                // Favorites section will be implemented separately
                $template_loader->get_template_part('partials/dashboard/patient-favorites');
                break;
                
            case 'profile':
                // Load profile edit template
                include_once DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/pages/profile-edit-patient.php';
                break;
                
            case 'find-dentist':
                // Find dentist section will be implemented separately
                $template_loader->get_template_part('partials/dashboard/patient-find-dentist');
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
                            <h3><?php echo esc_html($total_messages); ?></h3>
                            <p><?php esc_html_e('Mensajes enviados', 'dental-directory-system'); ?></p>
                        </div>
                    </div>
                    
                    <div class="dental-stat-card">
                        <div class="dental-stat-icon">
                            <i class="dashicons dashicons-heart"></i>
                        </div>
                        <div class="dental-stat-content">
                            <h3><?php echo esc_html($favorite_dentists); ?></h3>
                            <p><?php esc_html_e('Dentistas favoritos', 'dental-directory-system'); ?></p>
                        </div>
                    </div>
                    
                    <div class="dental-stat-card">
                        <div class="dental-stat-icon">
                            <i class="dashicons dashicons-calendar"></i>
                        </div>
                        <div class="dental-stat-content">
                            <h3><?php echo esc_html(human_time_diff(strtotime($profile['user_registered']), current_time('timestamp'))); ?></h3>
                            <p><?php esc_html_e('Tiempo como miembro', 'dental-directory-system'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="dental-dashboard-section">
                    <h2><?php esc_html_e('Acciones Rápidas', 'dental-directory-system'); ?></h2>
                    <div class="dental-quick-actions">
                        <a href="<?php echo esc_url(add_query_arg('view', 'find-dentist', get_permalink())); ?>" class="dental-quick-action-card dental-primary-card">
                            <div class="dental-action-icon">
                                <i class="dashicons dashicons-search"></i>
                            </div>
                            <h3><?php esc_html_e('Buscar Dentistas', 'dental-directory-system'); ?></h3>
                            <p><?php esc_html_e('Encuentra profesionales dentales para tus necesidades', 'dental-directory-system'); ?></p>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('view', 'messages', get_permalink())); ?>" class="dental-quick-action-card">
                            <div class="dental-action-icon">
                                <i class="dashicons dashicons-email-alt"></i>
                            </div>
                            <h3><?php esc_html_e('Ver Mensajes', 'dental-directory-system'); ?></h3>
                            <p><?php esc_html_e('Revisa tus conversaciones con dentistas', 'dental-directory-system'); ?></p>
                        </a>
                        
                        <a href="<?php echo esc_url(add_query_arg('view', 'profile', get_permalink())); ?>" class="dental-quick-action-card">
                            <div class="dental-action-icon">
                                <i class="dashicons dashicons-id"></i>
                            </div>
                            <h3><?php esc_html_e('Actualizar Perfil', 'dental-directory-system'); ?></h3>
                            <p><?php esc_html_e('Mantén tu información personal actualizada', 'dental-directory-system'); ?></p>
                        </a>
                    </div>
                </div>
                
                <div class="dental-dashboard-section">
                    <h2><?php esc_html_e('Dentistas Recomendados', 'dental-directory-system'); ?></h2>
                    <?php
                    // Get featured dentists
                    $args = array(
                        'role'    => 'dentist',
                        'meta_key' => 'dental_is_featured',
                        'meta_value' => '1',
                        'number'  => 3,
                        'orderby' => 'rand',
                    );
                    $featured_dentists = get_users($args);
                    
                    if (!empty($featured_dentists)) {
                        ?>
                        <div class="dental-dentists-grid">
                            <?php foreach ($featured_dentists as $dentist) { 
                                $dentist_profile = $profile_manager->get_dentist_profile($dentist->ID);
                            ?>
                                <div class="dental-dentist-card">
                                    <div class="dental-dentist-header">
                                        <div class="dental-dentist-avatar">
                                            <?php echo get_avatar($dentist->ID, 60); ?>
                                        </div>
                                        <div class="dental-dentist-info">
                                            <h3><?php echo esc_html($dentist_profile['display_name']); ?></h3>
                                            <p class="dental-dentist-specialty">
                                                <?php 
                                                    $specialty_value = isset($dentist_profile['speciality']) ? $dentist_profile['speciality'] : '';
                                                    $specialities = array(
                                                        'general' => __('Odontología General', 'dental-directory-system'),
                                                        'orthodontics' => __('Ortodoncia', 'dental-directory-system'),
                                                        'pediatric' => __('Odontopediatría', 'dental-directory-system'),
                                                        'endodontics' => __('Endodoncia', 'dental-directory-system'),
                                                        'oral_surgery' => __('Cirugía Oral', 'dental-directory-system'),
                                                        'periodontics' => __('Periodoncia', 'dental-directory-system'),
                                                        'prosthodontics' => __('Prostodoncia', 'dental-directory-system'),
                                                        'other' => __('Otra', 'dental-directory-system')
                                                    );
                                                    echo isset($specialities[$specialty_value]) ? esc_html($specialities[$specialty_value]) : '';
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="dental-dentist-body">
                                        <p class="dental-dentist-location">
                                            <i class="dashicons dashicons-location"></i>
                                            <?php 
                                                $location = array();
                                                if (!empty($dentist_profile['city'])) $location[] = $dentist_profile['city'];
                                                if (!empty($dentist_profile['state'])) $location[] = $dentist_profile['state'];
                                                if (!empty($dentist_profile['country'])) $location[] = $dentist_profile['country'];
                                                echo !empty($location) ? esc_html(implode(', ', $location)) : esc_html__('Ubicación no disponible', 'dental-directory-system');
                                            ?>
                                        </p>
                                        <div class="dental-dentist-actions">
                                            <a href="<?php echo esc_url(get_author_posts_url($dentist->ID)); ?>" class="dental-btn dental-btn-sm">
                                                <?php esc_html_e('Ver Perfil', 'dental-directory-system'); ?>
                                            </a>
                                            <a href="<?php echo esc_url(add_query_arg(array('dentist_id' => $dentist->ID), get_permalink(get_option('dental_page_chat')))); ?>" class="dental-btn dental-btn-sm dental-btn-primary">
                                                <?php esc_html_e('Enviar Mensaje', 'dental-directory-system'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="dental-view-all">
                            <a href="<?php echo esc_url(add_query_arg('view', 'find-dentist', get_permalink())); ?>" class="dental-link">
                                <?php esc_html_e('Ver todos los dentistas', 'dental-directory-system'); ?> →
                            </a>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="dental-info-box">
                            <p><?php esc_html_e('No hay dentistas destacados en este momento.', 'dental-directory-system'); ?></p>
                            <a href="<?php echo esc_url(add_query_arg('view', 'find-dentist', get_permalink())); ?>" class="dental-btn dental-btn-primary">
                                <?php esc_html_e('Buscar Dentistas', 'dental-directory-system'); ?>
                            </a>
                        </div>
                        <?php
                    }
                    ?>
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
