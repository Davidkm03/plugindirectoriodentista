<?php
/**
 * Database Migration Interface
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Database
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Interface for database migrations
 *
 * @since 1.0.0
 */
interface Dental_DB_Migration {

    /**
     * Apply the migration
     *
     * @return bool True on success, false on failure
     */
    public function up();

    /**
     * Rollback the migration
     *
     * @return bool True on success, false on failure
     */
    public function down();
}
