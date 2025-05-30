<?php
/**
 * Script para corregir errores de sintaxis en el plugin Dental Directory System
 * 
 * Este script identifica y corrige errores de sintaxis en los archivos del plugin
 * que están impidiendo su activación.
 */

// Definir la ruta base del plugin
define('PLUGIN_BASE_PATH', __DIR__);

// Archivos con errores conocidos que deben ser revisados
$files_to_check = [
    'includes/dashboard/class-dental-dashboard-actions.php',
    'includes/subscription/class-dental-woocommerce-subscription.php'
];

echo "==========================================\n";
echo "CORRECTOR DE ERRORES DE SINTAXIS\n";
echo "==========================================\n\n";

foreach ($files_to_check as $file) {
    $full_path = PLUGIN_BASE_PATH . '/' . $file;
    
    if (!file_exists($full_path)) {
        echo "❌ Archivo no encontrado: {$file}\n";
        continue;
    }
    
    echo "Verificando archivo: {$file}\n";
    
    // Leer el contenido del archivo
    $content = file_get_contents($full_path);
    $original_content = $content;
    $fixed = false;
    
    // Errores específicos a corregir
    
    // 1. Añadir 'return' faltante después de wp_send_json_error
    if (strpos($file, 'class-dental-dashboard-actions.php') !== false) {
        $pattern = '/wp_send_json_error\(\s*array\(\s*\'message\'\s*=>\s*\'User ID is required\'\s*\)\s*\);(\s*)\}/';
        $replacement = 'wp_send_json_error( array( \'message\' => \'User ID is required\' ) );$1    return;$1}';
        $content = preg_replace($pattern, $replacement, $content, -1, $count);
        
        if ($count > 0) {
            $fixed = true;
            echo "  ✓ Corregido: Añadido 'return' faltante después de wp_send_json_error\n";
        }
    }
    
    // 2. Corregir la estructura del método register_webhooks en la clase WooCommerce Subscription
    if (strpos($file, 'class-dental-woocommerce-subscription.php') !== false) {
        // Buscar errores específicos de estructura o sintaxis en register_webhooks
        
        // Verificar que hay un cierre de función correcto
        $pattern = '/public function register_webhooks\(\) \{(.*?)try \{(.*?)}\s*catch/s';
        if (preg_match($pattern, $content)) {
            // La estructura parece correcta, pero puede haber otros errores
            // Verificar si falta algún paréntesis o llave
            $open_braces = substr_count($content, '{');
            $close_braces = substr_count($content, '}');
            
            if ($open_braces !== $close_braces) {
                echo "  ⚠️ Advertencia: Desequilibrio de llaves ({$open_braces} abiertas, {$close_braces} cerradas)\n";
                
                // Intentar añadir llaves faltantes en lugares lógicos
                if ($open_braces > $close_braces) {
                    $content .= str_repeat('}', $open_braces - $close_braces);
                    echo "  ✓ Corregido: Añadidas " . ($open_braces - $close_braces) . " llaves faltantes\n";
                    $fixed = true;
                }
            }
        } else {
            // Podría haber un problema con la declaración del método
            echo "  ⚠️ Posible error en la declaración del método register_webhooks\n";
            
            // Agregar funciones alternativas para dental_is_dentist y dental_is_patient
            $content = preg_replace(
                '/\/\/ Initialize the class\s*new Dental_WooCommerce_Subscription\(\);/',
                "// Funciones alternativas para verificación de roles
if (!function_exists('dental_is_dentist')) {
    function dental_is_dentist(\$user_id = null) {
        if (null === \$user_id) {
            \$user_id = get_current_user_id();
        }
        if (!\$user_id) return false;
        \$user = get_userdata(\$user_id);
        return \$user && in_array('dentist', (array)\$user->roles, true);
    }
}

if (!function_exists('dental_is_patient')) {
    function dental_is_patient(\$user_id = null) {
        if (null === \$user_id) {
            \$user_id = get_current_user_id();
        }
        if (!\$user_id) return false;
        \$user = get_userdata(\$user_id);
        return \$user && in_array('patient', (array)\$user->roles, true);
    }
}

// Initialize the class
new Dental_WooCommerce_Subscription();",
                $content,
                -1,
                $count
            );
            
            if ($count > 0) {
                $fixed = true;
                echo "  ✓ Añadidas funciones alternativas para dental_is_dentist y dental_is_patient\n";
            }
        }
    }
    
    // Guardar cambios si se realizaron correcciones
    if ($fixed) {
        file_put_contents($full_path, $content);
        echo "✅ Se corrigieron errores en el archivo {$file}\n";
        
        // Crear copia de seguridad
        file_put_contents($full_path . '.bak', $original_content);
        echo "  ℹ️ Se creó una copia de seguridad: {$file}.bak\n";
    } else {
        echo "ℹ️ No se encontraron errores específicos para corregir en {$file}\n";
    }
    
    echo "\n";
}

// Añadir funciones esenciales al principio del plugin
$main_plugin_file = PLUGIN_BASE_PATH . '/dental-directory-system.php';
if (file_exists($main_plugin_file)) {
    $plugin_content = file_get_contents($main_plugin_file);
    
    // Añadir definiciones de funciones críticas si no están presentes
    if (strpos($plugin_content, 'if (!function_exists(\'dental_is_dentist\'))') === false) {
        $plugin_content = preg_replace(
            '/(\/\/ Define plugin constants.*?;)/s',
            '$1

// Definir funciones críticas en caso de que no estén disponibles
if (!function_exists(\'dental_is_dentist\')) {
    function dental_is_dentist($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) return false;
        $user = get_userdata($user_id);
        return $user && in_array(\'dentist\', (array)$user->roles, true);
    }
}

if (!function_exists(\'dental_is_patient\')) {
    function dental_is_patient($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) return false;
        $user = get_userdata($user_id);
        return $user && in_array(\'patient\', (array)$user->roles, true);
    }
}',
            $plugin_content
        );
        
        file_put_contents($main_plugin_file, $plugin_content);
        echo "✅ Se añadieron funciones críticas al archivo principal del plugin\n";
    }
}

// Crear un archivo de diagnóstico más avanzado
$diagnostic_script = PLUGIN_BASE_PATH . '/diagnostic_advanced.php';
file_put_contents($diagnostic_script, '<?php
/**
 * Diagnóstico Avanzado para Dental Directory System
 * 
 * Este script realiza un diagnóstico completo del plugin y muestra errores detallados
 * incluyendo análisis de errores de sintaxis y dependencias.
 */

if (!defined("ABSPATH")) {
    define("ABSPATH", __DIR__ . "/../../../");
}

// Establecer el modo de depuración
ini_set("display_errors", 1);
error_reporting(E_ALL);

echo "<html><head><title>Diagnóstico Avanzado - Dental Directory</title>";
echo "<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
    h1, h2 { color: #2c3e50; }
    .section { margin-bottom: 30px; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
    .success { color: #27ae60; }
    .error { color: #e74c3c; }
    .warning { color: #f39c12; }
    .code { font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>";
echo "</head><body>";

echo "<h1>Diagnóstico Avanzado - Dental Directory System</h1>";

// Información del sistema
echo "<div class=\'section\'>";
echo "<h2>Información del Sistema</h2>";
echo "<table>";
echo "<tr><th>WordPress Version</th><td>" . (defined("ABSPATH") ? include(ABSPATH . "wp-includes/version.php") && isset($wp_version) ? $wp_version : "No detectado") . "</td></tr>";
echo "<tr><th>PHP Version</th><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><th>Server Software</th><td>" . (isset($_SERVER["SERVER_SOFTWARE"]) ? $_SERVER["SERVER_SOFTWARE"] : "Desconocido") . "</td></tr>";
echo "<tr><th>Memory Limit</th><td>" . ini_get("memory_limit") . "</td></tr>";
echo "<tr><th>Time Limit</th><td>" . ini_get("max_execution_time") . " segundos</td></tr>";
echo "</table>";
echo "</div>";

// Verificar archivos críticos
echo "<div class=\'section\'>";
echo "<h2>Verificación de Archivos Críticos</h2>";

$plugin_dir = __DIR__;
$critical_files = [
    "dental-directory-system.php",
    "includes/class-dental-directory-system.php",
    "includes/class-dental-autoloader.php",
    "includes/database/class-dental-database.php",
    "includes/user/dental-user-functions.php",
    "includes/user/class-dental-user-roles.php",
    "includes/messaging/class-dental-message-limits.php",
    "includes/messaging/class-dental-message-notifications.php",
    "includes/api/class-dental-api.php",
    "includes/dashboard/class-dental-dashboard-actions.php",
    "includes/subscription/class-dental-woocommerce-subscription.php"
];

echo "<table>";
echo "<tr><th>Archivo</th><th>Estado</th><th>Permisos</th><th>Tamaño</th></tr>";

foreach ($critical_files as $file) {
    $full_path = $plugin_dir . "/" . $file;
    $exists = file_exists($full_path);
    $readable = is_readable($full_path);
    $size = $exists ? filesize($full_path) : 0;
    $perms = $exists ? substr(sprintf("%o", fileperms($full_path)), -4) : "N/A";
    
    echo "<tr>";
    echo "<td>{$file}</td>";
    if ($exists && $readable) {
        echo "<td class=\'success\'>✓ OK</td>";
    } elseif ($exists && !$readable) {
        echo "<td class=\'error\'>❌ No legible</td>";
    } else {
        echo "<td class=\'error\'>❌ No existe</td>";
    }
    echo "<td>{$perms}</td>";
    echo "<td>" . ($size > 0 ? round($size / 1024, 2) . " KB" : "N/A") . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Verificar funciones críticas
echo "<div class=\'section\'>";
echo "<h2>Verificación de Funciones Críticas</h2>";

$critical_functions = [
    "dental_is_dentist",
    "dental_is_patient",
    "dental_get_subscription_type",
    "dental_create_required_tables",
    "dental_create_user_roles",
    "dental_load_textdomain",
    "dental_init_plugin"
];

echo "<table>";
echo "<tr><th>Función</th><th>Estado</th></tr>";

foreach ($critical_functions as $function) {
    echo "<tr>";
    echo "<td>{$function}</td>";
    if (function_exists($function)) {
        echo "<td class=\'success\'>✓ Existe</td>";
    } else {
        echo "<td class=\'error\'>❌ No existe</td>";
    }
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Verificar estructura del plugin
echo "<div class=\'section\'>";
echo "<h2>Estructura del Plugin</h2>";

// Obtener estructura de directorios
function scan_dir_tree($dir, $relative_path = "") {
    $result = [];
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file == "." || $file == ".." || $file == ".git") continue;
        
        $path = $dir . "/" . $file;
        $rel_path = $relative_path ? $relative_path . "/" . $file : $file;
        
        if (is_dir($path)) {
            $result[$file] = [
                "type" => "directory",
                "children" => scan_dir_tree($path, $rel_path)
            ];
        } else {
            $result[$file] = [
                "type" => "file",
                "size" => filesize($path),
                "modified" => date("Y-m-d H:i:s", filemtime($path))
            ];
        }
    }
    
    return $result;
}

$structure = scan_dir_tree($plugin_dir);

// Mostrar estructura como JSON para facilitar la exploración
echo "<pre class=\'code\'>" . json_encode($structure, JSON_PRETTY_PRINT) . "</pre>";
echo "</div>";

// Analizar archivos PHP en busca de errores de sintaxis
echo "<div class=\'section\'>";
echo "<h2>Análisis de Sintaxis PHP</h2>";

function check_php_syntax($file) {
    $output = null;
    $return_var = null;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
    return [
        "file" => $file,
        "valid" => $return_var === 0,
        "message" => implode("\n", $output)
    ];
}

function scan_php_files($dir, &$results) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file == "." || $file == "..") continue;
        
        $path = $dir . "/" . $file;
        
        if (is_dir($path)) {
            scan_php_files($path, $results);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === "php") {
            $results[] = check_php_syntax($path);
        }
    }
}

$syntax_results = [];
scan_php_files($plugin_dir, $syntax_results);

echo "<table>";
echo "<tr><th>Archivo</th><th>Estado</th><th>Mensaje</th></tr>";

foreach ($syntax_results as $result) {
    $relative_path = str_replace($plugin_dir . "/", "", $result["file"]);
    echo "<tr>";
    echo "<td>{$relative_path}</td>";
    if ($result["valid"]) {
        echo "<td class=\'success\'>✓ Válido</td>";
        echo "<td></td>";
    } else {
        echo "<td class=\'error\'>❌ Error</td>";
        echo "<td>" . htmlspecialchars($result["message"]) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Sugerir soluciones
echo "<div class=\'section\'>";
echo "<h2>Soluciones Recomendadas</h2>";

echo "<ol>";
echo "<li>Utiliza el script <strong>fixsyntax.php</strong> para corregir automáticamente errores de sintaxis conocidos</li>";
echo "<li>Si el plugin aún no funciona, intenta activar la versión segura <strong>dental-directory-system-safe.php</strong></li>";
echo "<li>Verifica que las tablas de la base de datos se hayan creado correctamente</li>";
echo "<li>Asegúrate de que las funciones críticas como <code>dental_is_dentist()</code> y <code>dental_is_patient()</code> estén disponibles</li>";
echo "<li>Revisa los archivos con errores de sintaxis y corrige manualmente los problemas</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href=\'#\' onclick=\'window.location.reload()\'>Volver a ejecutar diagnóstico</a></p>";

echo "</body></html>";
');

echo "\n==========================================\n";
echo "✅ SCRIPTS DE CORRECCIÓN CREADOS CON ÉXITO\n";
echo "==========================================\n\n";
echo "Para corregir los errores de sintaxis, ejecuta este script desde la línea de comandos:\n";
echo "php " . PLUGIN_BASE_PATH . "/fixsyntax.php\n\n";
echo "Para un diagnóstico avanzado, accede a:\n";
echo "http://tu-sitio-web.com/wp-content/plugins/dental-directory-system/diagnostic_advanced.php\n\n";
echo "Estos scripts te ayudarán a identificar y corregir los problemas que impiden la activación del plugin.\n";
