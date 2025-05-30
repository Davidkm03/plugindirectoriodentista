<?php
/**
 * Dental Directory System (Modo Seguro)
 *
 * @package           DentalDirectorySystem
 * @author            Dental Team
 * @copyright         2025 Dental Directory
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Dental Directory System (Modo Seguro)
 * Plugin URI:        https://example.com/dental-directory-system
 * Description:       A dental directory system with chat, reviews, and subscription features - Safe Mode.
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

// Si este archivo es llamado directamente, abortar
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access is not permitted.' );
}

// Modo de depuración para ver errores
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', true);
}

// Definir constantes del plugin
define( 'DENTAL_DIRECTORY_VERSION', '1.0.0' );
define( 'DENTAL_DIRECTORY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DENTAL_DIRECTORY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DENTAL_DIRECTORY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'DENTAL_SAFE_MODE', true );

/**
 * Función auxiliar para verificar la existencia de archivos
 */
function dental_safe_require_once($file_path) {
    if (file_exists($file_path)) {
        try {
            require_once $file_path;
            return true;
        } catch (Throwable $e) {
            error_log('Error cargando archivo ' . $file_path . ': ' . $e->getMessage());
            return false;
        }
    } else {
        error_log('Archivo no encontrado: ' . $file_path);
        return false;
    }
}

/**
 * Hook de activación del plugin (modo seguro)
 */
function dental_safe_activate_plugin() {
    global $wpdb;
    
    // Registrar la activación
    error_log('Dental Directory System (Modo Seguro) - Activación iniciada: ' . date('Y-m-d H:i:s'));
    
    // Crear tablas de manera mínima
    $charset_collate = $wpdb->get_charset_collate();
    
    // Crear tabla de mensajes básica
    $table_name = $wpdb->prefix . 'dental_messages';
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            message longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Registrar activación exitosa
    update_option('dental_directory_safe_activated', true);
    update_option('dental_directory_version', DENTAL_DIRECTORY_VERSION);
    
    // Finalizar activación
    error_log('Dental Directory System (Modo Seguro) - Activación completada: ' . date('Y-m-d H:i:s'));
}
register_activation_hook( __FILE__, 'dental_safe_activate_plugin' );

/**
 * Hook de desactivación del plugin
 */
function dental_safe_deactivate_plugin() {
    // Limpiar reglas de reescritura
    flush_rewrite_rules();
    
    // Registrar desactivación
    error_log('Dental Directory System (Modo Seguro) desactivado: ' . date('Y-m-d H:i:s'));
    delete_option('dental_directory_safe_activated');
}
register_deactivation_hook( __FILE__, 'dental_safe_deactivate_plugin' );

/**
 * Cargar dominio de texto
 */
function dental_safe_load_textdomain() {
    load_plugin_textdomain('dental-directory-system', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'dental_safe_load_textdomain');

/**
 * Inicializar el plugin en modo seguro
 */
function dental_safe_init_plugin() {
    // Mostrar aviso en el admin
    add_action('admin_notices', 'dental_safe_admin_notice');
}
add_action('plugins_loaded', 'dental_safe_init_plugin', 20);

/**
 * Mostrar aviso en el panel de administración
 */
function dental_safe_admin_notice() {
    ?>
    <div class="notice notice-warning">
        <p><strong>Dental Directory System - Modo Seguro</strong></p>
        <p>El plugin está funcionando en modo seguro para diagnóstico. Para proceder con el diagnóstico completo, visite <a href="<?php echo esc_url(site_url('/wp-content/plugins/dental-directory-system/diagnostic.php')); ?>">la página de diagnóstico</a>.</p>
    </div>
    <?php
}

// Añadir opción en el menú de herramientas
function dental_safe_add_diagnostics_page() {
    add_management_page(
        'Diagnóstico Dental Directory',
        'Diagnóstico Dental',
        'manage_options',
        'dental-diagnostics',
        'dental_safe_diagnostics_page'
    );
}
add_action('admin_menu', 'dental_safe_add_diagnostics_page');

// Página de diagnóstico
function dental_safe_diagnostics_page() {
    ?>
    <div class="wrap">
        <h1>Diagnóstico de Dental Directory System</h1>
        <p>Esta página muestra información de diagnóstico para ayudar a solucionar problemas con el plugin.</p>
        
        <h2>Información del sistema</h2>
        <table class="widefat" style="margin-bottom: 20px;">
            <tbody>
                <tr>
                    <td><strong>Versión de WordPress:</strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong>Versión de PHP:</strong></td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td><strong>Servidor web:</strong></td>
                    <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                </tr>
                <tr>
                    <td><strong>Tema activo:</strong></td>
                    <td><?php echo wp_get_theme()->get('Name'); ?></td>
                </tr>
                <tr>
                    <td><strong>WooCommerce activo:</strong></td>
                    <td><?php echo class_exists('WooCommerce') ? 'Sí' : 'No'; ?></td>
                </tr>
            </tbody>
        </table>
        
        <h2>Directorios y archivos</h2>
        <table class="widefat" style="margin-bottom: 20px;">
            <tbody>
                <tr>
                    <td><strong>Directorio del plugin:</strong></td>
                    <td><?php echo DENTAL_DIRECTORY_PLUGIN_DIR; ?></td>
                </tr>
                <tr>
                    <td><strong>URL del plugin:</strong></td>
                    <td><?php echo DENTAL_DIRECTORY_PLUGIN_URL; ?></td>
                </tr>
                <tr>
                    <td><strong>Archivo principal existe:</strong></td>
                    <td><?php echo file_exists(DENTAL_DIRECTORY_PLUGIN_DIR . 'dental-directory-system.php') ? 'Sí' : 'No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Archivo de clase principal existe:</strong></td>
                    <td><?php echo file_exists(DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/class-dental-directory-system.php') ? 'Sí' : 'No'; ?></td>
                </tr>
                <tr>
                    <td><strong>Permisos del directorio del plugin:</strong></td>
                    <td><?php echo substr(sprintf('%o', fileperms(DENTAL_DIRECTORY_PLUGIN_DIR)), -4); ?></td>
                </tr>
            </tbody>
        </table>
        
        <h2>Archivos críticos</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Existe</th>
                    <th>Legible</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $critical_files = [
                    'includes/class-dental-directory-system.php',
                    'includes/class-dental-assets.php',
                    'includes/database/class-dental-database.php',
                    'includes/database/class-dental-db-migrator.php',
                    'includes/user/class-dental-user-roles.php',
                    'includes/messaging/class-dental-message-limits.php',
                    'includes/messaging/class-dental-message-notifications.php',
                    'includes/dashboard/class-dental-dashboard-actions.php',
                    'includes/subscription/class-dental-woocommerce-subscription.php',
                    'includes/functions/dental-chat-functions.php',
                    'includes/functions/dental-subscription-functions.php',
                ];
                
                foreach ($critical_files as $file) {
                    $full_path = DENTAL_DIRECTORY_PLUGIN_DIR . $file;
                    $exists = file_exists($full_path);
                    $readable = is_readable($full_path);
                    ?>
                    <tr>
                        <td><?php echo esc_html($file); ?></td>
                        <td><?php echo $exists ? '✅' : '❌'; ?></td>
                        <td><?php echo $readable ? '✅' : '❌'; ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px;">
            <a href="<?php echo esc_url(site_url('/wp-content/plugins/dental-directory-system/diagnostic.php')); ?>" class="button button-primary">Ejecutar diagnóstico completo</a>
        </p>
    </div>
    <?php
}
