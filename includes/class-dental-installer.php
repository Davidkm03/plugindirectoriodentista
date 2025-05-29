<?php
/**
 * Installer Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Plugin Installer Class
 *
 * @since 1.0.0
 */
class Dental_Installer {

    /**
     * Run the installation process
     *
     * @return void
     */
    public function install() {
        // Create database tables
        $this->create_required_tables();
        
        // Create user roles
        $this->create_user_roles();
        
        // Create required pages
        $this->create_required_pages();
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
    }

    /**
     * Create required database tables
     *
     * @return bool True on success, false on failure
     */
    private function create_required_tables() {
        // Create database tables using migration system
        $db = new Dental_Database();
        $success = $db->create_tables();
        
        if ( ! $success ) {
            error_log( 'Dental Directory System - Failed to create database tables' );
            return false;
        }
        
        return true;
    }

    /**
     * Create custom user roles
     *
     * @return void
     */
    private function create_user_roles() {
        // Create dentist role
        add_role(
            'dentist',
            __( 'Dentist', 'dental-directory-system' ),
            array(
                'read'                   => true,
                'edit_posts'             => false,
                'delete_posts'           => false,
                'publish_posts'          => false,
                'upload_files'           => true,
                'dental_manage_profile'  => true,
                'dental_view_messages'   => true,
                'dental_reply_messages'  => true,
                'dental_manage_reviews'  => true,
            )
        );
        
        // Create patient role
        add_role(
            'patient',
            __( 'Patient', 'dental-directory-system' ),
            array(
                'read'                   => true,
                'edit_posts'             => false,
                'delete_posts'           => false,
                'publish_posts'          => false,
                'upload_files'           => true,
                'dental_manage_profile'  => true,
                'dental_send_messages'   => true,
                'dental_write_reviews'   => true,
            )
        );
    }

    /**
     * Create required pages for the plugin
     *
     * @return void
     */
    private function create_required_pages() {
        $pages = array(
            'registro-dentista' => array(
                'title' => __( 'Registro de Dentista', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_registration_form type="dentist"]<!-- /wp:shortcode -->',
            ),
            'registro-paciente' => array(
                'title' => __( 'Registro de Paciente', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_registration_form type="patient"]<!-- /wp:shortcode -->',
            ),
            'login' => array(
                'title' => __( 'Iniciar Sesión', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_login_form]<!-- /wp:shortcode -->',
            ),
            'recuperar-password' => array(
                'title' => __( 'Recuperar Contraseña', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_password_reset_form]<!-- /wp:shortcode -->',
            ),
            'dashboard-dentista' => array(
                'title' => __( 'Panel de Control - Dentista', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_dashboard type="dentist"]<!-- /wp:shortcode -->',
            ),
            'dashboard-paciente' => array(
                'title' => __( 'Panel de Control - Paciente', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_dashboard type="patient"]<!-- /wp:shortcode -->',
            ),
            'directorio' => array(
                'title' => __( 'Directorio de Dentistas', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_directory]<!-- /wp:shortcode -->',
            ),
            'mis-chats' => array(
                'title' => __( 'Mis Conversaciones', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_chats]<!-- /wp:shortcode -->',
            ),
            'suscripciones' => array(
                'title' => __( 'Planes de Suscripción', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_subscription_plans]<!-- /wp:shortcode -->',
            ),
            'mis-reviews' => array(
                'title' => __( 'Mis Reseñas', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_reviews]<!-- /wp:shortcode -->',
            ),
            'editar-perfil' => array(
                'title' => __( 'Editar Perfil', 'dental-directory-system' ),
                'content' => '<!-- wp:shortcode -->[dental_edit_profile]<!-- /wp:shortcode -->',
            ),
        );
        
        foreach ( $pages as $slug => $page_data ) {
            // Check if the page already exists by slug
            $existing_page = get_page_by_path( $slug );
            
            if ( ! $existing_page ) {
                // Create the page
                $page_id = wp_insert_post(
                    array(
                        'post_title'     => sanitize_text_field( $page_data['title'] ),
                        'post_content'   => wp_kses_post( $page_data['content'] ),
                        'post_status'    => 'publish',
                        'post_type'      => 'page',
                        'post_name'      => $slug,
                        'comment_status' => 'closed',
                    )
                );
                
                // Store page ID in options for later reference
                if ( $page_id && ! is_wp_error( $page_id ) ) {
                    update_option( 'dental_page_' . str_replace( '-', '_', $slug ), $page_id );
                }
            } else {
                // Store existing page ID
                update_option( 'dental_page_' . str_replace( '-', '_', $slug ), $existing_page->ID );
            }
        }
    }

    /**
     * Schedule cron jobs
     *
     * @return void
     */
    private function schedule_cron_jobs() {
        // Schedule monthly counter reset
        if ( ! wp_next_scheduled( 'dental_reset_monthly_counters' ) ) {
            // Schedule for the first day of each month
            $next_month_start = strtotime( 'first day of next month midnight' );
            wp_schedule_event( $next_month_start, 'monthly', 'dental_reset_monthly_counters' );
        }
    }
}

/**
 * Create required database tables
 *
 * @return void
 */
function dental_create_required_tables() {
    $installer = new Dental_Installer();
    $installer->install();
}

/**
 * Create custom user roles
 *
 * @return void
 */
function dental_create_user_roles() {
    // Load the user roles class and create roles
    if ( ! class_exists( 'Dental_User_Roles' ) ) {
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/class-dental-user-roles.php';
    }
    
    $user_roles = new Dental_User_Roles();
    $user_roles->create_roles();
}

/**
 * Create required pages
 *
 * @return void
 */
function dental_create_required_pages() {
    // This function is intentionally left empty
    // since the Dental_Installer::create_required_pages() will be called during activation
}
