<?php
/**
 * Dental Directory System
 *
 * @package           DentalDirectorySystem
 * @author            Dental Team
 * @copyright         2025 Dental Directory
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Dental Directory System
 * Plugin URI:        https://example.com/dental-directory-system
 * Description:       A dental directory system with chat, reviews, and subscription features.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Dental Team
 * Author URI:        https://example.com
 * Text Domain:       dental-directory-system
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access is not permitted.' );
}

// Define plugin constants
define( 'DENTAL_DIRECTORY_VERSION', '1.0.0' );
define( 'DENTAL_DIRECTORY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DENTAL_DIRECTORY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DENTAL_DIRECTORY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin activation hook
 *
 * @return void
 */
function dental_activate_plugin() {
    // Include required files for activation
    require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/class-dental-user-roles.php';
    
    // Create necessary database tables
    dental_create_required_tables();
    
    // Create custom user roles
    dental_create_user_roles();
    
    // Create necessary pages
    dental_create_required_pages();
    
    // Set plugin version
    update_option( 'dental_directory_version', DENTAL_DIRECTORY_VERSION );
    
    // Clear rewrite rules
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dental_activate_plugin' );

/**
 * Plugin deactivation hook
 *
 * @return void
 */
function dental_deactivate_plugin() {
    // Clear rewrite rules
    flush_rewrite_rules();
    
    // Optional: Log deactivation for debugging
    error_log( 'Dental Directory System deactivated: ' . date( 'Y-m-d H:i:s' ) );
}
register_deactivation_hook( __FILE__, 'dental_deactivate_plugin' );

/**
 * Load plugin text domain
 *
 * @return void
 */
function dental_load_textdomain() {
    load_plugin_textdomain( 'dental-directory-system', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'dental_load_textdomain' );

/**
 * Load required files
 */
require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/class-dental-autoloader.php';
require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/class-dental-directory-system.php';
require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/class-dental-database.php';
require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/dental-user-functions.php';

// Load fallback functions (solo se definirán si no existen aún)
if (file_exists(DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/dental-user-functions-fallback.php')) {
    require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/dental-user-functions-fallback.php';
}

// Verificar que las funciones críticas existen, si no, definirlas
if (!function_exists('dental_is_dentist')) {
    function dental_is_dentist($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        return in_array('dentist', (array) $user->roles, true);
    }
}

if (!function_exists('dental_is_patient')) {
    function dental_is_patient($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        return in_array('patient', (array) $user->roles, true);
    }
}

/**
 * Initialize the plugin
 */
function dental_init_plugin() {
    // Initialize the main plugin class
    $plugin = new Dental_Directory_System();
    $plugin->initialize();
}
add_action( 'plugins_loaded', 'dental_init_plugin', 20 );
