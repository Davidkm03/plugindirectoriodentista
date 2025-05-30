<?php
/**
 * Diagnóstico del Plugin Dental Directory System
 * 
 * Este archivo carga componentes del plugin uno por uno para identificar cuál causa el error fatal.
 * 
 * INSTRUCCIONES:
 * 1. Coloca este archivo en la raíz del plugin
 * 2. Accede a él desde el navegador: http://tudominio.com/wp-content/plugins/dental-directory-system/diagnostic.php
 * 3. Observa qué componente causa el error
 */

// Desactiva la limitación de tiempo de ejecución
set_time_limit(300);

// Muestra todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define constantes de WordPress básicas si no están definidas
if (!defined('ABSPATH')) {
    // Ruta relativa para intentar cargar WordPress
    require_once('../../../wp-load.php');
}

echo "<h1>Diagnóstico del Plugin Dental Directory System</h1>";
echo "<p>Este script cargará los componentes del plugin uno por uno para identificar cuál causa el error fatal.</p>";

// Define constantes del plugin
if (!defined('DENTAL_DIRECTORY_PLUGIN_DIR')) {
    define('DENTAL_DIRECTORY_PLUGIN_DIR', dirname(__FILE__) . '/');
}
if (!defined('DENTAL_DIRECTORY_PLUGIN_URL')) {
    define('DENTAL_DIRECTORY_PLUGIN_URL', plugins_url('', __FILE__) . '/');
}
if (!defined('DENTAL_DIRECTORY_VERSION')) {
    define('DENTAL_DIRECTORY_VERSION', '1.0.0');
}

/**
 * Función para intentar cargar un archivo PHP y capturar cualquier error
 */
function try_load_file($file_path, $description) {
    echo "<hr><h3>Probando: $description</h3>";
    echo "<p>Archivo: " . basename($file_path) . "</p>";
    
    try {
        if (!file_exists($file_path)) {
            echo "<p style='color:red'>❌ ARCHIVO NO ENCONTRADO</p>";
            return false;
        }
        
        // Capturar la salida y errores
        ob_start();
        $result = include_once $file_path;
        $output = ob_get_clean();
        
        if ($result === false) {
            echo "<p style='color:red'>❌ ERROR AL CARGAR ARCHIVO</p>";
            if (!empty($output)) {
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            }
            return false;
        }
        
        echo "<p style='color:green'>✅ CARGADO CORRECTAMENTE</p>";
        return true;
    } catch (Throwable $e) {
        echo "<p style='color:red'>❌ EXCEPCIÓN: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        return false;
    }
}

// Lista de archivos críticos a probar en orden
$files_to_test = [
    // Funciones de ayuda primero
    'includes/functions/dental-subscription-functions.php' => 'Funciones de Suscripción',
    
    // Clases básicas
    'includes/class-dental-assets.php' => 'Clase de Assets',
    'includes/database/class-dental-database.php' => 'Clase de Base de Datos',
    'includes/database/class-dental-db-migrator.php' => 'Clase de Migración de BD',
    
    // Clases de usuarios
    'includes/user/class-dental-user-roles.php' => 'Clase de Roles de Usuario',
    'includes/user/class-dental-user-permissions.php' => 'Clase de Permisos de Usuario',
    'includes/user/class-dental-user-manager.php' => 'Clase de Gestión de Usuarios',
    
    // Sistema de mensajería
    'includes/messaging/class-dental-message-limits.php' => 'Clase de Límites de Mensajes',
    'includes/messaging/class-dental-message-notifications.php' => 'Clase de Notificaciones de Mensajes',
    
    // APIs
    'includes/api/class-dental-api.php' => 'Clase de API Base',
    'includes/api/class-dental-messaging-api.php' => 'Clase de API de Mensajería',
    
    // Funciones de chat
    'includes/functions/dental-chat-functions.php' => 'Funciones de Chat',
    
    // Dashboard
    'includes/dashboard/class-dental-dashboard-actions.php' => 'Clase de Acciones del Dashboard',
    
    // Shortcodes
    'includes/shortcodes/class-dental-dashboard-shortcode.php' => 'Shortcode de Dashboard',
    'includes/shortcodes/class-dental-directory-shortcode.php' => 'Shortcode de Directorio',
    'includes/shortcodes/class-dental-chat-shortcode.php' => 'Shortcode de Chat',
    
    // Suscripciones
    'includes/subscription/class-dental-woocommerce-subscription.php' => 'Integración con WooCommerce',
    
    // Clase principal al final
    'includes/class-dental-directory-system.php' => 'Clase Principal del Sistema',
];

// Contador de éxitos y fallos
$success_count = 0;
$failure_count = 0;
$failure_files = [];

// Probar cada archivo
foreach ($files_to_test as $file => $description) {
    $full_path = DENTAL_DIRECTORY_PLUGIN_DIR . $file;
    $result = try_load_file($full_path, $description);
    
    if ($result) {
        $success_count++;
    } else {
        $failure_count++;
        $failure_files[] = $file;
    }
    
    // Dar tiempo al navegador para mostrar resultados
    flush();
    ob_flush();
}

// Mostrar resumen
echo "<hr><h2>Resumen del Diagnóstico</h2>";
echo "<p>Archivos cargados correctamente: <strong>$success_count</strong></p>";
echo "<p>Archivos con problemas: <strong>$failure_count</strong></p>";

if ($failure_count > 0) {
    echo "<h3>Archivos que fallaron:</h3>";
    echo "<ul>";
    foreach ($failure_files as $file) {
        echo "<li>" . htmlspecialchars($file) . "</li>";
    }
    echo "</ul>";
    
    echo "<h3>Pasos recomendados:</h3>";
    echo "<ol>";
    echo "<li>Revisar los archivos mencionados arriba en busca de errores de sintaxis o referencias a funciones/clases inexistentes.</li>";
    echo "<li>Asegurarse de que todos los archivos requeridos estén presentes.</li>";
    echo "<li>Verificar que las dependencias entre archivos sean correctas.</li>";
    echo "<li>Comprobar la compatibilidad con la versión de PHP y WordPress.</li>";
    echo "</ol>";
} else {
    echo "<p style='color:green'>Todos los archivos se cargaron correctamente. El problema puede estar en las interacciones entre componentes.</p>";
}

// Verificar funciones críticas
echo "<hr><h2>Verificación de Funciones Críticas</h2>";
$critical_functions = [
    'dental_is_dentist',
    'dental_is_patient',
    'dental_get_subscription_type',
    'wp_create_nonce',
    'wp_verify_nonce',
    'wp_send_json_error',
    'wp_send_json_success'
];

foreach ($critical_functions as $function) {
    if (function_exists($function)) {
        echo "<p style='color:green'>✅ Función <code>$function</code> existe.</p>";
    } else {
        echo "<p style='color:red'>❌ Función <code>$function</code> NO existe.</p>";
    }
}

// Verificar clases críticas
echo "<hr><h2>Verificación de Clases Críticas</h2>";
$critical_classes = [
    'Dental_Directory_System',
    'Dental_Assets',
    'Dental_Database',
    'Dental_User_Roles',
    'Dental_Message_Limits',
    'Dental_Message_Notifications',
    'Dental_Dashboard_Actions',
    'Dental_Messaging_API',
    'Dental_WooCommerce_Subscription'
];

foreach ($critical_classes as $class) {
    if (class_exists($class)) {
        echo "<p style='color:green'>✅ Clase <code>$class</code> existe.</p>";
    } else {
        echo "<p style='color:red'>❌ Clase <code>$class</code> NO existe.</p>";
    }
}

echo "<hr><p>Diagnóstico completado. Utiliza esta información para identificar y corregir el problema.</p>";
?>
