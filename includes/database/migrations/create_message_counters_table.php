<?php
/**
 * Migration: Create message counters table
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Database/Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Create message counters table
 *
 * @return bool True on success, false on failure
 */
function dental_create_message_counters_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dental_message_counters';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        dentist_id bigint(20) UNSIGNED NOT NULL,
        month varchar(7) NOT NULL,
        count int(11) NOT NULL DEFAULT 0,
        updated_at datetime NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY dentist_month (dentist_id, month),
        KEY dentist_id (dentist_id),
        KEY month (month)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $result = dbDelta( $sql );

    return ! empty( $result );
}
