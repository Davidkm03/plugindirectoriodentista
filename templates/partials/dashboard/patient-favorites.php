<?php
/**
 * Patient Favorite Dentists Template Part
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get user ID
$user_id = get_current_user_id();

// Get favorite dentists
global $wpdb;
$favorites_table = $wpdb->prefix . 'dental_favorites';
$favorite_dentists_ids = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT dentist_id FROM {$favorites_table} WHERE patient_id = %d ORDER BY created_at DESC",
        $user_id
    )
);

// Get profile manager
$profile_manager = new Dental_Profile_Manager();
?>

<div class="dental-dashboard-header">
    <h1><?php esc_html_e('Mis Dentistas Favoritos', 'dental-directory-system'); ?></h1>
</div>

<div class="dental-favorites-container">
    <?php if (empty($favorite_dentists_ids)) : ?>
        <div class="dental-empty-favorites">
            <div class="dental-empty-icon">
                <i class="dashicons dashicons-heart"></i>
            </div>
            <h3><?php esc_html_e('No tienes dentistas favoritos', 'dental-directory-system'); ?></h3>
            <p><?php esc_html_e('Añade dentistas a tus favoritos para encontrarlos más rápido.', 'dental-directory-system'); ?></p>
            <a href="<?php echo esc_url(add_query_arg('view', 'find-dentist', get_permalink())); ?>" class="dental-btn dental-btn-primary">
                <?php esc_html_e('Buscar Dentistas', 'dental-directory-system'); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="dental-favorites-header">
            <div class="dental-favorites-count">
                <?php printf(
                    esc_html(_n('%d dentista favorito', '%d dentistas favoritos', count($favorite_dentists_ids), 'dental-directory-system')),
                    count($favorite_dentists_ids)
                ); ?>
            </div>
            <div class="dental-favorites-search">
                <input type="text" id="dental-favorites-search" placeholder="<?php esc_attr_e('Buscar en favoritos...', 'dental-directory-system'); ?>" class="dental-search-input">
                <i class="dashicons dashicons-search dental-search-icon"></i>
            </div>
        </div>
        
        <div class="dental-dentists-grid">
            <?php 
            foreach ($favorite_dentists_ids as $dentist_id) {
                $dentist = get_user_by('id', $dentist_id);
                if (!$dentist || !dental_is_dentist($dentist_id)) {
                    continue;
                }
                
                $dentist_profile = $profile_manager->get_dentist_profile($dentist_id);
                $is_featured = get_user_meta($dentist_id, 'dental_is_featured', true);
            ?>
                <div class="dental-dentist-card <?php echo $is_featured ? 'dental-featured-card' : ''; ?>" data-dentist-id="<?php echo esc_attr($dentist_id); ?>">
                    <?php if ($is_featured) : ?>
                        <div class="dental-featured-tag"><?php esc_html_e('Destacado', 'dental-directory-system'); ?></div>
                    <?php endif; ?>
                    
                    <div class="dental-dentist-header">
                        <div class="dental-dentist-avatar">
                            <?php echo get_avatar($dentist_id, 90); ?>
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
                            $rating = get_user_meta($dentist_id, 'dental_rating', true);
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
                            <a href="<?php echo esc_url(get_author_posts_url($dentist_id)); ?>" class="dental-btn dental-btn-sm">
                                <?php esc_html_e('Ver Perfil', 'dental-directory-system'); ?>
                            </a>
                            <a href="<?php echo esc_url(add_query_arg(array('dentist_id' => $dentist_id), get_permalink(get_option('dental_page_chat')))); ?>" class="dental-btn dental-btn-sm dental-btn-primary">
                                <?php esc_html_e('Enviar Mensaje', 'dental-directory-system'); ?>
                            </a>
                            <button class="dental-btn dental-btn-sm dental-btn-icon dental-remove-favorite" data-dentist-id="<?php echo esc_attr($dentist_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('dental_remove_favorite')); ?>">
                                <i class="dashicons dashicons-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle favorite removal
    $('.dental-remove-favorite').on('click', function() {
        const button = $(this);
        const dentistId = button.data('dentist-id');
        const nonce = button.data('nonce');
        const card = button.closest('.dental-dentist-card');
        
        // Add removing state
        card.addClass('dental-removing');
        
        // Confirm removal
        if (confirm('<?php esc_html_e('¿Estás seguro de que deseas eliminar este dentista de tus favoritos?', 'dental-directory-system'); ?>')) {
            // Send AJAX request
            $.ajax({
                url: dental_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'dental_remove_favorite',
                    dentist_id: dentistId,
                    security: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove card with animation
                        card.fadeOut(300, function() {
                            card.remove();
                            
                            // Update counter
                            const remainingCards = $('.dental-dentist-card').length;
                            $('.dental-favorites-count').text(
                                remainingCards === 1 
                                    ? '<?php esc_html_e('1 dentista favorito', 'dental-directory-system'); ?>'
                                    : remainingCards + ' <?php esc_html_e('dentistas favoritos', 'dental-directory-system'); ?>'
                            );
                            
                            // Show empty state if no cards left
                            if (remainingCards === 0) {
                                $('.dental-favorites-header').remove();
                                $('.dental-dentists-grid').remove();
                                $('.dental-favorites-container').html(
                                    '<div class="dental-empty-favorites">' +
                                    '<div class="dental-empty-icon"><i class="dashicons dashicons-heart"></i></div>' +
                                    '<h3><?php esc_html_e('No tienes dentistas favoritos', 'dental-directory-system'); ?></h3>' +
                                    '<p><?php esc_html_e('Añade dentistas a tus favoritos para encontrarlos más rápido.', 'dental-directory-system'); ?></p>' +
                                    '<a href="<?php echo esc_url(add_query_arg('view', 'find-dentist', get_permalink())); ?>" class="dental-btn dental-btn-primary">' +
                                    '<?php esc_html_e('Buscar Dentistas', 'dental-directory-system'); ?>' +
                                    '</a></div>'
                                );
                            }
                        });
                    } else {
                        // Remove removing state
                        card.removeClass('dental-removing');
                        alert(response.data.message || '<?php esc_html_e('Error al eliminar favorito.', 'dental-directory-system'); ?>');
                    }
                },
                error: function() {
                    // Remove removing state
                    card.removeClass('dental-removing');
                    alert('<?php esc_html_e('Error de conexión. Inténtalo de nuevo.', 'dental-directory-system'); ?>');
                }
            });
        } else {
            // Remove removing state
            card.removeClass('dental-removing');
        }
    });
    
    // Search favorites
    $('#dental-favorites-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.dental-dentist-card').each(function() {
            const $card = $(this);
            const name = $card.find('h3').text().toLowerCase();
            const specialty = $card.find('.dental-dentist-specialty').text().toLowerCase();
            const location = $card.find('.dental-dentist-location').text().toLowerCase();
            const bio = $card.find('.dental-dentist-bio').text().toLowerCase();
            
            if (name.includes(searchTerm) || specialty.includes(searchTerm) || 
                location.includes(searchTerm) || bio.includes(searchTerm)) {
                $card.show();
            } else {
                $card.hide();
            }
        });
        
        // Check if any cards are visible
        const visibleCards = $('.dental-dentist-card:visible').length;
        
        // Show/hide no results message
        if (visibleCards === 0) {
            if ($('.dental-no-search-results').length === 0) {
                $('.dental-dentists-grid').append(
                    '<div class="dental-no-search-results">' +
                    '<p><?php esc_html_e('No se encontraron dentistas que coincidan con tu búsqueda.', 'dental-directory-system'); ?></p>' +
                    '</div>'
                );
            }
        } else {
            $('.dental-no-search-results').remove();
        }
    });
});
</script>
