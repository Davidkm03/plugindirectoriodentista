<?php
/**
 * Directory Shortcode Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Directory Shortcode Class
 *
 * Handles the shortcode for displaying the dental directory
 *
 * @since 1.0.0
 */
class Dental_Directory_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode( 'dental_directory', array( $this, 'render_directory' ) );
        add_shortcode( 'dental_search', array( $this, 'render_search_form' ) );
    }

    /**
     * Render the dental directory
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_directory( $atts ) {
        global $wpdb;
        
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'per_page'   => 12,
                'speciality' => '',
                'location'   => '',
                'orderby'    => 'name',
                'order'      => 'ASC',
            ),
            $atts,
            'dental_directory'
        );
        
        // Sanitize attributes
        $per_page   = absint( $atts['per_page'] );
        $speciality = sanitize_text_field( $atts['speciality'] );
        $location   = sanitize_text_field( $atts['location'] );
        $orderby    = in_array( $atts['orderby'], array( 'name', 'rating', 'date' ), true ) ? $atts['orderby'] : 'name';
        $order      = in_array( strtoupper( $atts['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $atts['order'] ) : 'ASC';
        
        // Get current page
        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
        
        // Query parameters from GET
        $search     = isset( $_GET['dental_search'] ) ? sanitize_text_field( wp_unslash( $_GET['dental_search'] ) ) : '';
        $s_location = isset( $_GET['dental_location'] ) ? sanitize_text_field( wp_unslash( $_GET['dental_location'] ) ) : $location;
        $s_speciality = isset( $_GET['dental_speciality'] ) ? sanitize_text_field( wp_unslash( $_GET['dental_speciality'] ) ) : $speciality;
        
        // Build query to get dentists
        $query_args = array(
            'role'    => 'dentist',
            'number'  => $per_page,
            'paged'   => $paged,
            'orderby' => 'display_name',
            'order'   => $order,
        );
        
        // Add search parameter if provided
        if ( ! empty( $search ) ) {
            $query_args['search'] = '*' . $search . '*';
        }
        
        // Add meta query for filtering
        $meta_query = array();
        
        if ( ! empty( $s_speciality ) ) {
            $meta_query[] = array(
                'key'     => 'dental_speciality',
                'value'   => $s_speciality,
                'compare' => 'LIKE',
            );
        }
        
        if ( ! empty( $s_location ) ) {
            $meta_query[] = array(
                'key'     => 'dental_city',
                'value'   => $s_location,
                'compare' => 'LIKE',
            );
        }
        
        if ( ! empty( $meta_query ) ) {
            $query_args['meta_query'] = $meta_query;
        }
        
        // Get dentists
        $dentists_query = new WP_User_Query( $query_args );
        $dentists = $dentists_query->get_results();
        
        // Start output buffering
        ob_start();
        
        // Include the search form
        echo $this->render_search_form( array(
            'speciality' => $s_speciality,
            'location'   => $s_location,
            'search'     => $search,
        ) );
        
        // Check if we have dentists
        if ( ! empty( $dentists ) ) {
            // Include directory template
            $template_path = DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/directory/directory-list.php';
            if ( file_exists( $template_path ) ) {
                include $template_path;
            } else {
                echo '<div class="dental-notice dental-error">' . 
                     esc_html__( 'Error: No se encontró la plantilla del directorio.', 'dental-directory-system' ) . 
                     '</div>';
            }
            
            // Pagination
            $total_dentists = $dentists_query->get_total();
            $total_pages = ceil( $total_dentists / $per_page );
            
            if ( $total_pages > 1 ) {
                echo '<div class="dental-pagination">';
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $total_pages,
                    'current'   => $paged,
                ) );
                echo '</div>';
            }
        } else {
            echo '<div class="dental-no-results">';
            echo '<h3>' . esc_html__( 'No se encontraron dentistas', 'dental-directory-system' ) . '</h3>';
            echo '<p>' . esc_html__( 'No hay dentistas que coincidan con los criterios de búsqueda.', 'dental-directory-system' ) . '</p>';
            echo '</div>';
        }
        
        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Render the search form
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_search_form( $atts ) {
        global $wpdb;
        
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'speciality' => '',
                'location'   => '',
                'search'     => '',
            ),
            $atts,
            'dental_search'
        );
        
        // Get all available specialities from user meta
        $specialities_query = $wpdb->prepare(
            "SELECT DISTINCT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value != '' ORDER BY meta_value ASC",
            'dental_speciality'
        );
        $specialities = $wpdb->get_col( $specialities_query );
        
        // Get all available locations from user meta
        $locations_query = $wpdb->prepare(
            "SELECT DISTINCT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value != '' ORDER BY meta_value ASC",
            'dental_city'
        );
        $locations = $wpdb->get_col( $locations_query );
        
        // Start output buffering
        ob_start();
        
        // Include search form template
        $template_path = DENTAL_DIRECTORY_PLUGIN_DIR . 'templates/directory/search-form.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Default search form if template doesn't exist
            ?>
            <div class="dental-search-form">
                <form method="get" action="<?php echo esc_url( get_permalink() ); ?>">
                    <div class="dental-search-fields">
                        <div class="dental-search-field">
                            <input type="text" name="dental_search" placeholder="<?php esc_attr_e( 'Buscar dentista...', 'dental-directory-system' ); ?>" value="<?php echo esc_attr( $atts['search'] ); ?>">
                        </div>
                        
                        <div class="dental-search-field">
                            <select name="dental_speciality">
                                <option value=""><?php esc_html_e( 'Todas las especialidades', 'dental-directory-system' ); ?></option>
                                <?php foreach ( $specialities as $speciality ) : ?>
                                    <option value="<?php echo esc_attr( $speciality ); ?>" <?php selected( $atts['speciality'], $speciality ); ?>>
                                        <?php echo esc_html( $speciality ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="dental-search-field">
                            <select name="dental_location">
                                <option value=""><?php esc_html_e( 'Todas las ubicaciones', 'dental-directory-system' ); ?></option>
                                <?php foreach ( $locations as $location ) : ?>
                                    <option value="<?php echo esc_attr( $location ); ?>" <?php selected( $atts['location'], $location ); ?>>
                                        <?php echo esc_html( $location ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="dental-search-field">
                            <button type="submit" class="dental-button">
                                <?php esc_html_e( 'Buscar', 'dental-directory-system' ); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php
        }
        
        // Return the buffered content
        return ob_get_clean();
    }
}

// Initialize the shortcode
new Dental_Directory_Shortcode();
