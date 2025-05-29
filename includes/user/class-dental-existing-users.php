<?php
/**
 * Existing Users Handler Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/User
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class for handling existing WordPress users
 *
 * @since 1.0.0
 */
class Dental_Existing_Users {

    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks
        add_action( 'init', array( $this, 'register_hooks' ) );
    }

    /**
     * Register hooks
     *
     * @return void
     */
    public function register_hooks() {
        // Add role selection form for users without dental roles
        add_action( 'wp_ajax_dental_assign_role', array( $this, 'ajax_assign_role' ) );
        
        // Check if user needs to select a role
        add_action( 'template_redirect', array( $this, 'check_user_role_status' ) );
        
        // Add shortcode for role selection form
        add_shortcode( 'dental_role_selection', array( $this, 'role_selection_shortcode' ) );
    }

    /**
     * Check if current user needs to select a role
     *
     * @return void
     */
    public function check_user_role_status() {
        // Only check logged-in users
        if ( ! is_user_logged_in() ) {
            return;
        }
        
        // Skip admin users
        $user = wp_get_current_user();
        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return;
        }
        
        // Check if user already has a dental role
        if ( dental_is_dentist() || dental_is_patient() ) {
            return;
        }
        
        // Get role selection page
        $role_selection_page = get_option( 'dental_page_role_selection' );
        
        // Skip redirect if we're already on the role selection page
        if ( $role_selection_page && is_page( $role_selection_page ) ) {
            return;
        }
        
        // Skip certain pages (like admin pages)
        if ( is_admin() ) {
            return;
        }
        
        // Create role selection page if it doesn't exist
        if ( ! $role_selection_page ) {
            $page_id = wp_insert_post(
                array(
                    'post_title'     => __( 'Select Your Role', 'dental-directory-system' ),
                    'post_content'   => '<!-- wp:shortcode -->[dental_role_selection]<!-- /wp:shortcode -->',
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'post_name'      => 'select-role',
                    'comment_status' => 'closed',
                )
            );
            
            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'dental_page_role_selection', $page_id );
                $role_selection_page = $page_id;
            }
        }
        
        // Redirect to role selection page
        if ( $role_selection_page ) {
            wp_redirect( get_permalink( $role_selection_page ) );
            exit;
        }
    }

    /**
     * Role selection form shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered shortcode content
     */
    public function role_selection_shortcode( $atts ) {
        // Not logged in
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to select your role.', 'dental-directory-system' ) . '</p>';
        }
        
        // Already has a dental role
        if ( dental_is_dentist() || dental_is_patient() ) {
            return '<p>' . esc_html__( 'You already have a role in our dental directory system.', 'dental-directory-system' ) . '</p>';
        }
        
        // Get current user
        $user = wp_get_current_user();
        
        // Build form
        ob_start();
        ?>
        <div class="dental-role-selection">
            <h2><?php esc_html_e( 'Welcome to Dental Directory System', 'dental-directory-system' ); ?></h2>
            
            <p><?php esc_html_e( 'Please select your role in our dental directory system:', 'dental-directory-system' ); ?></p>
            
            <form id="dental-role-form">
                <div class="role-options">
                    <div class="role-option">
                        <input type="radio" name="dental_role" id="role-dentist" value="dentist" />
                        <label for="role-dentist">
                            <strong><?php esc_html_e( 'I am a Dentist', 'dental-directory-system' ); ?></strong>
                            <p><?php esc_html_e( 'Select this if you provide dental services and want to be listed in our directory.', 'dental-directory-system' ); ?></p>
                        </label>
                    </div>
                    
                    <div class="role-option">
                        <input type="radio" name="dental_role" id="role-patient" value="patient" />
                        <label for="role-patient">
                            <strong><?php esc_html_e( 'I am a Patient', 'dental-directory-system' ); ?></strong>
                            <p><?php esc_html_e( 'Select this if you are looking for dental services and want to connect with dentists.', 'dental-directory-system' ); ?></p>
                        </label>
                    </div>
                </div>
                
                <div class="form-footer">
                    <?php wp_nonce_field( 'dental_assign_role_nonce', 'security' ); ?>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Continue', 'dental-directory-system' ); ?></button>
                    <div class="dental-message" style="display: none;"></div>
                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#dental-role-form').on('submit', function(e) {
                e.preventDefault();
                
                const role = $('input[name="dental_role"]:checked').val();
                const security = $('#security').val();
                
                if (!role) {
                    $('.dental-message').text('<?php esc_html_e( 'Please select a role.', 'dental-directory-system' ); ?>').show();
                    return;
                }
                
                // Display loading state
                $(this).find('button').prop('disabled', true).text('<?php esc_html_e( 'Please wait...', 'dental-directory-system' ); ?>');
                
                // Make AJAX request
                $.ajax({
                    url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                    type: 'POST',
                    data: {
                        action: 'dental_assign_role',
                        role: role,
                        security: security
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.dental-message').html(response.data.message).show();
                            
                            // Redirect if provided
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                            }
                        } else {
                            $('.dental-message').text(response.data.message).show();
                            $('#dental-role-form button').prop('disabled', false).text('<?php esc_html_e( 'Continue', 'dental-directory-system' ); ?>');
                        }
                    },
                    error: function() {
                        $('.dental-message').text('<?php esc_html_e( 'An error occurred. Please try again.', 'dental-directory-system' ); ?>').show();
                        $('#dental-role-form button').prop('disabled', false).text('<?php esc_html_e( 'Continue', 'dental-directory-system' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }

    /**
     * AJAX handler for assigning role
     *
     * @return void Sends JSON response
     */
    public function ajax_assign_role() {
        // Check nonce
        if ( ! check_ajax_referer( 'dental_assign_role_nonce', 'security', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Check user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to perform this action.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Get role
        $role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
        
        // Validate role
        if ( ! in_array( $role, array( 'dentist', 'patient' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid role selection.', 'dental-directory-system' ) ) );
            return;
        }
        
        // Get current user
        $user_id = get_current_user_id();
        $user = get_user_by( 'id', $user_id );
        
        // Remove existing roles except administrator
        $roles_to_keep = array();
        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            $roles_to_keep[] = 'administrator';
        }
        
        // Reset roles
        $user->set_role( '' );
        
        // Re-add preserved roles
        foreach ( $roles_to_keep as $preserved_role ) {
            $user->add_role( $preserved_role );
        }
        
        // Add the new role
        $user->add_role( $role );
        
        // Initialize subscription if dentist
        if ( 'dentist' === $role ) {
            // Get user manager to initialize subscription
            global $dental_directory_system;
            if ( isset( $dental_directory_system->components['user'] ) ) {
                $user_manager = $dental_directory_system->components['user'];
                $user_manager->initialize_free_subscription( $user_id );
            }
        }
        
        // Determine redirect URL
        $redirect_url = '';
        if ( 'dentist' === $role ) {
            $dentist_dashboard_id = get_option( 'dental_page_dashboard_dentista' );
            if ( $dentist_dashboard_id ) {
                $redirect_url = get_permalink( $dentist_dashboard_id );
            }
        } else {
            $patient_dashboard_id = get_option( 'dental_page_dashboard_paciente' );
            if ( $patient_dashboard_id ) {
                $redirect_url = get_permalink( $patient_dashboard_id );
            }
        }
        
        // Send success response
        wp_send_json_success(
            array(
                'message' => __( 'Role assigned successfully! Redirecting to your dashboard...', 'dental-directory-system' ),
                'redirect' => $redirect_url,
            )
        );
    }
}
