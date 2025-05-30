<?php
/**
 * Database Migration Manager
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Database
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Database Migration Manager Class
 *
 * Handles database migrations, schema versions, and rollbacks
 *
 * @since 1.0.0
 */
class Dental_DB_Migrator {

    /**
     * Current database schema version
     *
     * @var string
     */
    private $current_schema_version;

    /**
     * Latest database schema version
     *
     * @var string
     */
    private $latest_schema_version = '1.0.0';

    /**
     * Migration scripts directory
     *
     * @var string
     */
    private $migrations_dir;

    /**
     * Constructor
     */
    public function __construct() {
        $this->migrations_dir = DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/migrations/';
        $this->current_schema_version = get_option( 'dental_db_version', '0.0.0' );
    }

    /**
     * Run all pending migrations
     *
     * @return bool True on success, false on failure
     */
    public function migrate() {
        // If already at latest version, do nothing
        if ( version_compare( $this->current_schema_version, $this->latest_schema_version, '>=' ) ) {
            return true;
        }

        // Run migrations in order for version 1.0.0
        if ( version_compare( $this->current_schema_version, '1.0.0', '<' ) ) {
            // Create tables
            require_once $this->migrations_dir . 'create_chat_tables.php';
            require_once $this->migrations_dir . 'create_favorites_table.php';
            require_once $this->migrations_dir . 'create_subscriptions_table.php';
            require_once $this->migrations_dir . 'create_notifications_table.php';
            require_once $this->migrations_dir . 'create_message_counters_table.php';
            
            $chat_result = dental_create_chat_tables();
            $favorites_result = dental_create_favorites_table();
            $subscriptions_result = dental_create_subscriptions_table();
            $notifications_result = dental_create_notifications_table();
            $counters_result = dental_create_message_counters_table();
            
            if ( $chat_result && $favorites_result && $subscriptions_result && $notifications_result && $counters_result ) {
                // Update schema version
                update_option( 'dental_db_version', $this->latest_schema_version );
                return true;
            }
            
            return false;
        }

        // For future migrations, we'll use the migration system below
        // Get all migrations in ascending version order
        $migrations = $this->get_migrations();
        $success = true;
        
        // Start transaction for all migrations
        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            foreach ( $migrations as $version => $migration_file ) {
                // Skip migrations that are older than or equal to current version
                if ( version_compare( $version, $this->current_schema_version, '<=' ) ) {
                    continue;
                }
                
                // Load and execute migration
                require_once $migration_file;
                
                // Get migration class name based on version
                $class_name = 'Dental_DB_Migration_' . str_replace( '.', '_', $version );
                
                if ( class_exists( $class_name ) ) {
                    $migration = new $class_name();
                    
                    // Run the migration
                    if ( ! $migration->up() ) {
                        $success = false;
                        $wpdb->query( 'ROLLBACK' );
                        error_log( "Dental Directory System - Migration to version $version failed" );
                        break;
                    }
                    
                    // Update current version
                    $this->current_schema_version = $version;
                    update_option( 'dental_db_version', $version );
                    
                    // Log successful migration
                    error_log( "Dental Directory System - Successfully migrated to version $version" );
                }
            }
            
            if ( $success ) {
                $wpdb->query( 'COMMIT' );
            }
            
        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            error_log( 'Dental Directory System - Migration error: ' . $e->getMessage() );
            $success = false;
        }
        
        return $success;
    }

    /**
     * Rollback to a specific version
     *
     * @param string $target_version Version to rollback to.
     * @return bool True on success, false on failure
     */
    public function rollback( $target_version ) {
        // If already at or below target version, do nothing
        if ( version_compare( $this->current_schema_version, $target_version, '<=' ) ) {
            return true;
        }
        
        // Get all migrations in descending version order
        $migrations = array_reverse( $this->get_migrations() );
        $success = true;
        
        // Start transaction for rollback
        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            foreach ( $migrations as $version => $migration_file ) {
                // Skip versions that are below or equal to target, or above current
                if ( version_compare( $version, $target_version, '<=' ) || 
                     version_compare( $version, $this->current_schema_version, '>' ) ) {
                    continue;
                }
                
                // Load rollback file
                require_once $migration_file;
                
                // Get migration class name based on version
                $class_name = 'Dental_DB_Migration_' . str_replace( '.', '_', $version );
                
                if ( class_exists( $class_name ) ) {
                    $migration = new $class_name();
                    
                    // Run the rollback
                    if ( ! $migration->down() ) {
                        $success = false;
                        $wpdb->query( 'ROLLBACK' );
                        error_log( "Dental Directory System - Rollback from version $version failed" );
                        break;
                    }
                    
                    // Log successful rollback
                    error_log( "Dental Directory System - Successfully rolled back from version $version" );
                }
            }
            
            if ( $success ) {
                // Update current version
                $this->current_schema_version = $target_version;
                update_option( 'dental_db_version', $target_version );
                $wpdb->query( 'COMMIT' );
            }
            
        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            error_log( 'Dental Directory System - Rollback error: ' . $e->getMessage() );
            $success = false;
        }
        
        return $success;
    }

    /**
     * Get list of available migrations
     *
     * @return array Array of migration files, keyed by version
     */
    private function get_migrations() {
        $migrations = array();
        
        // Check if migrations directory exists
        if ( ! file_exists( $this->migrations_dir ) ) {
            return $migrations;
        }
        
        // Get all PHP files in the migrations directory
        $files = scandir( $this->migrations_dir );
        
        foreach ( $files as $file ) {
            // Skip non-PHP files
            if ( ! preg_match( '/^migration_([0-9]+\.[0-9]+\.[0-9]+)\.php$/', $file, $matches ) ) {
                continue;
            }
            
            $version = $matches[1];
            $migrations[ $version ] = $this->migrations_dir . $file;
        }
        
        // Sort by version
        uksort( $migrations, 'version_compare' );
        
        return $migrations;
    }
    
    /**
     * Get current schema version
     *
     * @return string Current schema version
     */
    public function get_current_version() {
        return $this->current_schema_version;
    }
    
    /**
     * Get latest schema version
     *
     * @return string Latest schema version
     */
    public function get_latest_version() {
        return $this->latest_schema_version;
    }

    /**
     * Check if database is up to date
     *
     * @return bool True if up to date, false otherwise
     */
    public function is_up_to_date() {
        return version_compare( $this->current_schema_version, $this->latest_schema_version, '==' );
    }
}
