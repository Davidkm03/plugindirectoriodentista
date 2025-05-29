<?php
/**
 * Database Handler Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Database
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Database Manager Class
 *
 * @since 1.0.0
 */
class Dental_Database {

    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do here for now
    }

    /**
     * Create all required tables for the plugin
     *
     * @return void
     */
    public function create_tables() {
        $this->create_chat_table();
        $this->create_subscriptions_table();
        $this->create_reviews_table();
        $this->create_message_counters_table();
        $this->create_profiles_table();
    }

    /**
     * Create chat messages table
     *
     * @return void
     */
    private function create_chat_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dental_chat_messages';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            sender_id bigint(20) unsigned NOT NULL,
            receiver_id bigint(20) unsigned NOT NULL,
            message text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'sent',
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_read datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY status (status),
            KEY date_created (date_created)
        ) $charset_collate;";
        
        $this->execute_db_query( $sql );
    }

    /**
     * Create subscriptions table
     *
     * @return void
     */
    private function create_subscriptions_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dental_subscriptions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            user_id bigint(20) unsigned NOT NULL,
            plan_name varchar(50) NOT NULL,
            payment_processor varchar(20) DEFAULT NULL,
            processor_subscription_id varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            date_start datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_expiry datetime DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY date_expiry (date_expiry)
        ) $charset_collate;";
        
        $this->execute_db_query( $sql );
    }

    /**
     * Create reviews table
     *
     * @return void
     */
    private function create_reviews_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dental_reviews';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            dentist_id bigint(20) unsigned NOT NULL,
            author_id bigint(20) unsigned NOT NULL,
            rating tinyint unsigned NOT NULL,
            review_text text DEFAULT NULL,
            dentist_response text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'published',
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY dentist_id (dentist_id),
            KEY author_id (author_id),
            KEY rating (rating),
            KEY status (status),
            KEY date_created (date_created)
        ) $charset_collate;";
        
        $this->execute_db_query( $sql );
    }

    /**
     * Create message counters table
     *
     * @return void
     */
    private function create_message_counters_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dental_message_counters';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            dentist_id bigint(20) unsigned NOT NULL,
            month int NOT NULL,
            year int NOT NULL,
            message_count int unsigned NOT NULL DEFAULT 0,
            date_reset datetime DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY dentist_month_year (dentist_id, month, year),
            KEY dentist_id (dentist_id),
            KEY month_year (month, year)
        ) $charset_collate;";
        
        $this->execute_db_query( $sql );
    }

    /**
     * Create dental profiles table
     *
     * @return void
     */
    private function create_profiles_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dental_profiles';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            user_id bigint(20) unsigned NOT NULL,
            speciality varchar(100) DEFAULT NULL,
            license varchar(50) DEFAULT NULL,
            address text DEFAULT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            working_hours text DEFAULT NULL,
            bio text DEFAULT NULL,
            profile_photo varchar(255) DEFAULT NULL,
            featured tinyint(1) NOT NULL DEFAULT 0,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY speciality (speciality),
            KEY featured (featured)
        ) $charset_collate;";
        
        $this->execute_db_query( $sql );
    }

    /**
     * Execute database query safely
     *
     * @param string $sql SQL query to execute.
     * @return bool True on success, false on error
     */
    private function execute_db_query( $sql ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        
        // Check if the query failed and log error
        global $EZSQL_ERROR;
        if ( ! empty( $EZSQL_ERROR ) ) {
            error_log( 'Dental Directory System DB Error: ' . print_r( $EZSQL_ERROR, true ) );
            return false;
        }
        
        return true;
    }

    /**
     * Get message count for a dentist in the current month
     *
     * @param int $dentist_id Dentist user ID.
     * @return int Current message count
     */
    public function get_dentist_message_count( $dentist_id ) {
        global $wpdb;
        
        $dentist_id = absint( $dentist_id );
        $month = intval( date( 'n' ) );
        $year = intval( date( 'Y' ) );
        
        $table_name = $wpdb->prefix . 'dental_message_counters';
        
        $query = $wpdb->prepare(
            "SELECT message_count FROM $table_name 
            WHERE dentist_id = %d AND month = %d AND year = %d",
            $dentist_id, $month, $year
        );
        
        $count = $wpdb->get_var( $query );
        
        return $count ? intval( $count ) : 0;
    }

    /**
     * Increment message count for a dentist
     *
     * @param int $dentist_id Dentist user ID.
     * @return bool True on success, false on failure
     */
    public function increment_dentist_message_count( $dentist_id ) {
        global $wpdb;
        
        $dentist_id = absint( $dentist_id );
        $month = intval( date( 'n' ) );
        $year = intval( date( 'Y' ) );
        
        $table_name = $wpdb->prefix . 'dental_message_counters';
        
        // Try to update existing record
        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table_name (dentist_id, month, year, message_count) 
                VALUES (%d, %d, %d, 1) 
                ON DUPLICATE KEY UPDATE message_count = message_count + 1",
                $dentist_id, $month, $year
            )
        );
        
        if ( false === $result ) {
            error_log( 'Dental Directory System - Failed to increment message count: ' . $wpdb->last_error );
            return false;
        }
        
        return true;
    }

    /**
     * Reset monthly message counters
     * 
     * This should be called by a cron job at the beginning of each month
     *
     * @return bool True on success, false on failure
     */
    public function reset_monthly_counters() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dental_message_counters';
        $current_month = intval( date( 'n' ) );
        $current_year = intval( date( 'Y' ) );
        
        // Reset counters for all dentists
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name 
                SET message_count = 0, date_reset = %s 
                WHERE month = %d AND year = %d",
                current_time( 'mysql' ), $current_month, $current_year
            )
        );
        
        if ( false === $result ) {
            error_log( 'Dental Directory System - Failed to reset message counters: ' . $wpdb->last_error );
            return false;
        }
        
        return true;
    }
}
