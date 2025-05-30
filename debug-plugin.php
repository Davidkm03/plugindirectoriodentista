<?php
/**
 * Diagnóstico del Plugin
 * 
 * Este archivo ayuda a identificar problemas de activación
 */

// Desactivar la salida de errores en el navegador
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Define la constante de WordPress para permitir la carga del plugin
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

// Define la ruta del plugin
define('DENTAL_DIRECTORY_PLUGIN_DIR', dirname(__FILE__) . '/');
define('DENTAL_DIRECTORY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DENTAL_DIRECTORY_VERSION', '1.0.0');

// Función para verificar archivos críticos
function check_critical_files() {
    $critical_files = [
        'dental-directory-system.php',
        'includes/class-dental-directory-system.php',
        'includes/class-dental-assets.php',
        'includes/messaging/class-dental-message-limits.php',
        'includes/messaging/class-dental-message-notifications.php',
        'includes/dashboard/class-dental-dashboard-actions.php',
        'includes/subscription/class-dental-woocommerce-subscription.php',
        'includes/functions/dental-chat-functions.php',
        'includes/functions/dental-subscription-functions.php',
    ];
    
    $missing_files = [];
    foreach ($critical_files as $file) {
        if (!file_exists(DENTAL_DIRECTORY_PLUGIN_DIR . $file)) {
            $missing_files[] = $file;
        }
    }
    
    return $missing_files;
}

// Función para verificar la estructura de directorios
function check_directories() {
    $directories = [
        'includes',
        'includes/messaging',
        'includes/dashboard',
        'includes/subscription',
        'includes/functions',
        'includes/api',
        'includes/shortcodes',
        'templates',
        'templates/chat',
        'templates/elements',
        'assets',
        'assets/css',
        'assets/js',
    ];
    
    $missing_dirs = [];
    foreach ($directories as $dir) {
        if (!is_dir(DENTAL_DIRECTORY_PLUGIN_DIR . $dir)) {
            $missing_dirs[] = $dir;
        }
    }
    
    return $missing_dirs;
}

// Función para verificar errores de sintaxis en un archivo
function check_syntax($file) {
    $output = [];
    $return_var = 0;
    
    // Usar php para verificar sintaxis
    exec('php -l ' . escapeshellarg($file), $output, $return_var);
    
    if ($return_var !== 0) {
        return [
            'file' => $file,
            'error' => implode("\n", $output)
        ];
    }
    
    return null;
}

// Verificar sintaxis de archivos PHP
function check_all_php_files() {
    $files_with_errors = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(DENTAL_DIRECTORY_PLUGIN_DIR));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $syntax_check = check_syntax($file->getPathname());
            if ($syntax_check !== null) {
                $files_with_errors[] = $syntax_check;
            }
        }
    }
    
    return $files_with_errors;
}

// Verificar funciones requeridas
function check_required_functions() {
    $required_functions = [
        'dental_is_dentist',
        'dental_is_patient',
        'dental_get_subscription_type',
    ];
    
    $missing_functions = [];
    foreach ($required_functions as $function) {
        if (!function_exists($function)) {
            $missing_functions[] = $function;
        }
    }
    
    return $missing_functions;
}

// Ejecución del diagnóstico
try {
    echo "<h1>Diagnóstico del Plugin Dental Directory System</h1>";
    
    // Verificar archivos críticos
    $missing_files = check_critical_files();
    echo "<h2>Verificación de Archivos Críticos</h2>";
    if (empty($missing_files)) {
        echo "<p style='color:green'>✓ Todos los archivos críticos están presentes.</p>";
    } else {
        echo "<p style='color:red'>✗ Faltan los siguientes archivos críticos:</p>";
        echo "<ul>";
        foreach ($missing_files as $file) {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
        echo "</ul>";
    }
    
    // Verificar directorios
    $missing_dirs = check_directories();
    echo "<h2>Verificación de Directorios</h2>";
    if (empty($missing_dirs)) {
        echo "<p style='color:green'>✓ Todos los directorios requeridos están presentes.</p>";
    } else {
        echo "<p style='color:red'>✗ Faltan los siguientes directorios:</p>";
        echo "<ul>";
        foreach ($missing_dirs as $dir) {
            echo "<li>" . htmlspecialchars($dir) . "</li>";
        }
        echo "</ul>";
    }
    
    // Verificar sintaxis de archivos PHP
    echo "<h2>Verificación de Sintaxis PHP</h2>";
    $syntax_errors = check_all_php_files();
    if (empty($syntax_errors)) {
        echo "<p style='color:green'>✓ No se encontraron errores de sintaxis en los archivos PHP.</p>";
    } else {
        echo "<p style='color:red'>✗ Se encontraron errores de sintaxis en los siguientes archivos:</p>";
        echo "<ul>";
        foreach ($syntax_errors as $error) {
            echo "<li><strong>" . htmlspecialchars($error['file']) . "</strong>: " . htmlspecialchars($error['error']) . "</li>";
        }
        echo "</ul>";
    }
    
    // Recomendaciones
    echo "<h2>Recomendaciones</h2>";
    echo "<p>Para solucionar el error fatal:</p>";
    echo "<ol>";
    echo "<li>Verificar que todos los archivos críticos estén presentes y en la ubicación correcta.</li>";
    echo "<li>Corregir cualquier error de sintaxis en los archivos PHP.</li>";
    echo "<li>Asegurarse de que todos los directorios requeridos existan.</li>";
    echo "<li>Verificar que las funciones requeridas estén definidas.</li>";
    echo "<li>Revisar el registro de errores de WordPress en <code>wp-content/debug.log</code> para más detalles.</li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "<h1>Error durante el diagnóstico</h1>";
    echo "<p>Se produjo un error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
