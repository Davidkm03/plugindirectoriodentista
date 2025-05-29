<?php
/**
 * Autoloader for Dental Directory System classes
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Autoloader Class
 *
 * Handles dynamically loading classes only when needed.
 *
 * @since 1.0.0
 */
class Dental_Autoloader {

    /**
     * Run the autoloader
     *
     * @return void
     */
    public static function run() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    /**
     * Autoload callback function
     *
     * @param string $class The class being instantiated.
     * @return void
     */
    public static function autoload( $class ) {
        // Check if class has our prefix.
        if ( false === strpos( $class, 'Dental_' ) ) {
            return;
        }

        // Convert class name to filename
        $class_name = strtolower( $class );
        $class_name = str_replace( '_', '-', $class_name );
        $filename   = 'class-' . $class_name . '.php';

        // Build path based on class name
        $file = self::get_file_path( $class, $filename );

        // If the file exists, require it
        if ( $file && file_exists( $file ) ) {
            require_once $file;
        }
    }

    /**
     * Get the path of the class file from its name
     *
     * @param string $class    The class name.
     * @param string $filename The filename for class.
     * @return string|bool     The path if found, false otherwise
     */
    private static function get_file_path( $class, $filename ) {
        // Define possible class directories
        $directories = array(
            'includes'              => DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/',
            'admin'                 => DENTAL_DIRECTORY_PLUGIN_DIR . 'admin/',
            'public'                => DENTAL_DIRECTORY_PLUGIN_DIR . 'public/',
            'elementor'             => DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/elementor/',
            'database'              => DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/',
            'chat'                  => DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/chat/',
            'subscription'          => DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/subscription/',
            'user'                  => DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/',
        );

        // Look for class file in each directory
        foreach ( $directories as $directory ) {
            $file = trailingslashit( $directory ) . $filename;
            if ( file_exists( $file ) ) {
                return $file;
            }
        }

        return false;
    }
}

// Initialize the autoloader
Dental_Autoloader::run();
