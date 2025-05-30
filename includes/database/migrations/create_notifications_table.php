<?php
/**
 * Migration: Create notifications table
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Database/Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Create notifications table
 *
 * @return bool True on success, false on failure
 */
function dental_create_notifications_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dental_notifications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        type varchar(50) NOT NULL,
        reference_id varchar(50) DEFAULT NULL,
        message text NOT NULL,
        read tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY type (type),
        KEY reference_id (reference_id),
        KEY read (read)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $result = dbDelta( $sql );

    return ! empty( $result );
}
