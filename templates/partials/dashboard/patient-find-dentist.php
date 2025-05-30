<?php
/**
 * Patient Find Dentist Template Part
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current filters
$specialty = isset($_GET['specialty']) ? sanitize_text_field($_GET['specialty']) : '';
$location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Prepare query args
$args = array(
    'role'    => 'dentist',
    'number'  => 20,
    'orderby' => 'meta_value',
    'order'   => 'DESC',
    'meta_key' => 'dental_is_featured',
);

// Add filters to query
if (!empty($specialty)) {
    $args['meta_query'][] = array(
        'key'     => 'dental_speciality',
        'value'   => $specialty,
        'compare' => '=',
    );
}

if (!empty($location)) {
    $args['meta_query'][] = array(
        'relation' => 'OR',
        array(
            'key'     => 'dental_city',
            'value'   => $location,
            'compare' => 'LIKE',
        ),
        array(
            'key'     => 'dental_state',
            'value'   => $location,
            'compare' => 'LIKE',
        ),
        array(
            'key'     => 'dental_country',
            'value'   => $location,
            'compare' => 'LIKE',
        ),
    );
}

if (!empty($search)) {
    $args['search'] = '*' . $search . '*';
}

// Get dentists
$dentists = get_users($args);

// Get profile manager
$profile_manager = new Dental_Profile_Manager();
?>

<div class="dental-dashboard-header">
    <h1><?php esc_html_e('Encuentra un Dentista', 'dental-directory-system'); ?></h1>
    <p><?php esc_html_e('Busca el profesional dental adecuado para tus necesidades.', 'dental-directory-system'); ?></p>
</div>

<div class="dental-find-dentist-container">
    <div class="dental-search-filters">
        <form id="dental-find-dentist-form" action="" method="get">
            <input type="hidden" name="view" value="find-dentist">
            
            <div class="dental-search-row">
                <div class="dental-search-input-group">
                    <div class="dental-input-icon">
                        <i class="dashicons dashicons-search"></i>
                        <input type="text" name="search" id="dental-search-term" placeholder="<?php esc_attr_e('Buscar por nombre...', 'dental-directory-system'); ?>" value="<?php echo esc_attr($search); ?>">
                    </div>
                </div>
                
                <div class="dental-search-input-group">
                    <div class="dental-input-icon">
                        <i class="dashicons dashicons-location"></i>
                        <input type="text" name="location" id="dental-search-location" placeholder="<?php esc_attr_e('Ciudad o región...', 'dental-directory-system'); ?>" value="<?php echo esc_attr($location); ?>">
                    </div>
                </div>
                
                <div class="dental-search-input-group">
                    <div class="dental-custom-select">
                        <select name="specialty" id="dental-search-specialty">
                            <option value=""><?php esc_html_e('Todas las especialidades', 'dental-directory-system'); ?></option>
                            <option value="general" <?php selected($specialty, 'general'); ?>><?php esc_html_e('Odontología General', 'dental-directory-system'); ?></option>
                            <option value="orthodontics" <?php selected($specialty, 'orthodontics'); ?>><?php esc_html_e('Ortodoncia', 'dental-directory-system'); ?></option>
                            <option value="pediatric" <?php selected($specialty, 'pediatric'); ?>><?php esc_html_e('Odontopediatría', 'dental-directory-system'); ?></option>
                            <option value="endodontics" <?php selected($specialty, 'endodontics'); ?>><?php esc_html_e('Endodoncia', 'dental-directory-system'); ?></option>
                            <option value="oral_surgery" <?php selected($specialty, 'oral_surgery'); ?>><?php esc_html_e('Cirugía Oral', 'dental-directory-system'); ?></option>
                            <option value="periodontics" <?php selected($specialty, 'periodontics'); ?>><?php esc_html_e('Periodoncia', 'dental-directory-system'); ?></option>
                            <option value="prosthodontics" <?php selected($specialty, 'prosthodontics'); ?>><?php esc_html_e('Prostodoncia', 'dental-directory-system'); ?></option>
                            <option value="other" <?php selected($specialty, 'other'); ?>><?php esc_html_e('Otra', 'dental-directory-system'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="dental-search-input-group">
                    <button type="submit" class="dental-btn dental-btn-primary">
                        <i class="dashicons dashicons-search"></i>
                        <?php esc_html_e('Buscar', 'dental-directory-system'); ?>
                    </button>
                </div>
            </div>
            
            <?php if (!empty($search) || !empty($location) || !empty($specialty)) : ?>
            <div class="dental-active-filters">
                <?php esc_html_e('Filtros activos:', 'dental-directory-system'); ?>
                
                <?php if (!empty($search)) : ?>
                <span class="dental-filter-tag">
                    <?php echo esc_html($search); ?>
                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'find-dentist', 'specialty' => $specialty, 'location' => $location, 'search' => ''))); ?>" class="dental-remove-filter">×</a>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($location)) : ?>
                <span class="dental-filter-tag">
                    <i class="dashicons dashicons-location"></i> <?php echo esc_html($location); ?>
                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'find-dentist', 'specialty' => $specialty, 'location' => '', 'search' => $search))); ?>" class="dental-remove-filter">×</a>
                </span>
                <?php endif; ?>
                
                <?php if (!empty($specialty)) : 
                    $specialties = array(
                        'general' => __('Odontología General', 'dental-directory-system'),
                        'orthodontics' => __('Ortodoncia', 'dental-directory-system'),
                        'pediatric' => __('Odontopediatría', 'dental-directory-system'),
                        'endodontics' => __('Endodoncia', 'dental-directory-system'),
                        'oral_surgery' => __('Cirugía Oral', 'dental-directory-system'),
                        'periodontics' => __('Periodoncia', 'dental-directory-system'),
                        'prosthodontics' => __('Prostodoncia', 'dental-directory-system'),
                        'other' => __('Otra', 'dental-directory-system')
                    );
                ?>
                <span class="dental-filter-tag">
                    <?php echo esc_html($specialties[$specialty]); ?>
                    <a href="<?php echo esc_url(add_query_arg(array('view' => 'find-dentist', 'specialty' => '', 'location' => $location, 'search' => $search))); ?>" class="dental-remove-filter">×</a>
                </span>
                <?php endif; ?>
                
                <a href="<?php echo esc_url(add_query_arg(array('view' => 'find-dentist'))); ?>" class="dental-clear-all-filters">
                    <?php esc_html_e('Limpiar todos', 'dental-directory-system'); ?>
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="dental-search-results">
        <div class="dental-results-header">
            <div class="dental-results-count">
                <?php printf(
                    esc_html(_n('%d dentista encontrado', '%d dentistas encontrados', count($dentists), 'dental-directory-system')),
                    count($dentists)
                ); ?>
            </div>
            <div class="dental-sort-options">
                <label for="dental-sort-select"><?php esc_html_e('Ordenar por:', 'dental-directory-system'); ?></label>
                <select id="dental-sort-select" class="dental-sort-select">
                    <option value="featured"><?php esc_html_e('Destacados', 'dental-directory-system'); ?></option>
                    <option value="name"><?php esc_html_e('Nombre', 'dental-directory-system'); ?></option>
                    <option value="rating"><?php esc_html_e('Valoración', 'dental-directory-system'); ?></option>
                </select>
            </div>
        </div>
        
        <?php if (empty($dentists)) : ?>
        <div class="dental-empty-results">
            <div class="dental-empty-icon">
                <i class="dashicons dashicons-search"></i>
            </div>
            <h3><?php esc_html_e('No se encontraron dentistas', 'dental-directory-system'); ?></h3>
            <p><?php esc_html_e('No hay dentistas que coincidan con tus criterios de búsqueda.', 'dental-directory-system'); ?></p>
            <a href="<?php echo esc_url(add_query_arg(array('view' => 'find-dentist'))); ?>" class="dental-btn dental-btn-primary">
                <?php esc_html_e('Limpiar filtros', 'dental-directory-system'); ?>
            </a>
        </div>
        <?php else : ?>
        <div class="dental-dentists-grid">
            <?php foreach ($dentists as $dentist) : 
                $dentist_profile = $profile_manager->get_dentist_profile($dentist->ID);
                $is_featured = get_user_meta($dentist->ID, 'dental_is_featured', true);
            ?>
                <div class="dental-dentist-card <?php echo $is_featured ? 'dental-featured-card' : ''; ?>" data-dentist-id="<?php echo esc_attr($dentist->ID); ?>">
                    <?php if ($is_featured) : ?>
                        <div class="dental-featured-tag"><?php esc_html_e('Destacado', 'dental-directory-system'); ?></div>
                    <?php endif; ?>
                    
                    <div class="dental-dentist-header">
                        <div class="dental-dentist-avatar">
                            <?php echo get_avatar($dentist->ID, 90); ?>
                        </div>
                        <div class="dental-dentist-info">
                            <h3><?php echo esc_html($dentist_profile['display_name']); ?></h3>
                            <p class="dental-dentist-specialty">
                                <?php 
                                    $specialty_value = isset($dentist_profile['speciality']) ? $dentist_profile['speciality'] : '';
                                    $specialties = array(
                                        'general' => __('Odontología General', 'dental-directory-system'),
                                        'orthodontics' => __('Ortodoncia', 'dental-directory-system'),
                                        'pediatric' => __('Odontopediatría', 'dental-directory-system'),
                                        'endodontics' => __('Endodoncia', 'dental-directory-system'),
                                        'oral_surgery' => __('Cirugía Oral', 'dental-directory-system'),
                                        'periodontics' => __('Periodoncia', 'dental-directory-system'),
                                        'prosthodontics' => __('Prostodoncia', 'dental-directory-system'),
                                        'other' => __('Otra', 'dental-directory-system')
                                    );
                                    echo isset($specialties[$specialty_value]) ? esc_html($specialties[$specialty_value]) : '';
                                ?>
                            </p>
                            
                            <?php 
                            // Get dentist rating
                            $rating = get_user_meta($dentist->ID, 'dental_rating', true);
                            if ($rating) :
                            ?>
                            <div class="dental-dentist-rating">
                                <div class="dental-star-rating" data-rating="<?php echo esc_attr($rating); ?>">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <i class="dashicons <?php echo $i <= $rating ? 'dashicons-star-filled' : 'dashicons-star-empty'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php endif; ?>
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
                        
                        <?php if (!empty($dentist_profile['bio'])) : ?>
                        <div class="dental-dentist-bio">
                            <?php echo wp_trim_words(wp_kses_post($dentist_profile['bio']), 20, '...'); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="dental-dentist-actions">
                            <a href="<?php echo esc_url(get_author_posts_url($dentist->ID)); ?>" class="dental-btn dental-btn-sm">
                                <?php esc_html_e('Ver Perfil', 'dental-directory-system'); ?>
                            </a>
                            <a href="<?php echo esc_url(add_query_arg(array('dentist_id' => $dentist->ID), get_permalink(get_option('dental_page_chat')))); ?>" class="dental-btn dental-btn-sm dental-btn-primary">
                                <?php esc_html_e('Enviar Mensaje', 'dental-directory-system'); ?>
                            </a>
                            <button class="dental-btn dental-btn-sm dental-btn-icon dental-toggle-favorite" data-dentist-id="<?php echo esc_attr($dentist->ID); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('dental_toggle_favorite')); ?>">
                                <i class="dashicons <?php echo is_dentist_favorite($dentist->ID) ? 'dashicons-heart' : 'dashicons-heart-empty'; ?>"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle favorite toggle
    $('.dental-toggle-favorite').on('click', function() {
        const button = $(this);
        const dentistId = button.data('dentist-id');
        const nonce = button.data('nonce');
        const icon = button.find('i.dashicons');
        
        // Toggle icon state immediately for better UX
        icon.toggleClass('dashicons-heart-empty dashicons-heart');
        
        // Send AJAX request
        $.ajax({
            url: dental_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'dental_toggle_favorite',
                dentist_id: dentistId,
                security: nonce
            },
            success: function(response) {
                if (!response.success) {
                    // Restore original state if failed
                    icon.toggleClass('dashicons-heart-empty dashicons-heart');
                    alert(response.data.message || '<?php esc_html_e("Error al actualizar favoritos.", "dental-directory-system"); ?>');
                }
            },
            error: function() {
                // Restore original state if error
                icon.toggleClass('dashicons-heart-empty dashicons-heart');
                alert('<?php esc_html_e("Error de conexión. Inténtalo de nuevo.", "dental-directory-system"); ?>');
            }
        });
    });
    
    // Sort functionality
    $('#dental-sort-select').on('change', function() {
        const sortType = $(this).val();
        const cards = $('.dental-dentist-card').toArray();
        
        cards.sort(function(a, b) {
            const aFeatured = $(a).hasClass('dental-featured-card');
            const bFeatured = $(b).hasClass('dental-featured-card');
            
            if (sortType === 'featured') {
                // Featured first
                if (aFeatured && !bFeatured) return -1;
                if (!aFeatured && bFeatured) return 1;
            }
            
            if (sortType === 'name') {
                // Sort by name
                const aName = $(a).find('h3').text().toLowerCase();
                const bName = $(b).find('h3').text().toLowerCase();
                return aName.localeCompare(bName);
            }
            
            if (sortType === 'rating') {
                // Sort by rating
                const aRating = parseFloat($(a).find('.dental-star-rating').data('rating') || 0);
                const bRating = parseFloat($(b).find('.dental-star-rating').data('rating') || 0);
                
                // Sort by rating first, then by featured status
                if (aRating !== bRating) {
                    return bRating - aRating; // Higher rating first
                } else {
                    // If ratings are equal, featured first
                    if (aFeatured && !bFeatured) return -1;
                    if (!aFeatured && bFeatured) return 1;
                }
            }
            
            return 0;
        });
        
        // Reattach sorted cards
        const container = $('.dental-dentists-grid');
        $.each(cards, function(index, card) {
            container.append(card);
        });
    });
});

/**
 * Check if dentist is in patient's favorites
 * 
 * @param int dentist_id Dentist ID
 * @return bool True if favorite, false otherwise
 */
function is_dentist_favorite($dentist_id) {
    global $wpdb;
    $user_id = get_current_user_id();
    $favorites_table = $wpdb->prefix . 'dental_favorites';
    
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$favorites_table} WHERE patient_id = %d AND dentist_id = %d",
            $user_id, $dentist_id
        )
    );
    
    return $exists > 0;
}
</script>
