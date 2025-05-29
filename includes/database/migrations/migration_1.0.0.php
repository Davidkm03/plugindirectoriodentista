<?php
/**
 * Database Migration: Version 1.0.0
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Database/Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Database Migration Class for Version 1.0.0
 *
 * Creates initial database tables for the plugin
 *
 * @since 1.0.0
 */
class Dental_DB_Migration_1_0_0 {

    /**
     * Apply the migration - create tables
     *
     * @return bool True on success, false on failure
     */
    public function up() {
        global $wpdb;
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $success = true;
        
        // Create chat messages table
        $table_name = $wpdb->prefix . 'dental_chat_messages';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            sender_id bigint(20) unsigned NOT NULL,
            receiver_id bigint(20) unsigned NOT NULL,
            message text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'sent',
            is_read tinyint(1) NOT NULL DEFAULT 0,
            attachment_url varchar(255) DEFAULT NULL,
            attachment_type varchar(50) DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_read datetime DEFAULT NULL,
            conversation_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY status (status),
            KEY is_read (is_read),
            KEY conversation_id (conversation_id),
            KEY date_created (date_created)
        ) $charset_collate;";
        
        dbDelta( $sql );
        if ( $this->check_for_errors() ) {
            $success = false;
        }
        
        // Create conversations table (for grouping messages)
        $table_name = $wpdb->prefix . 'dental_conversations';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            dentist_id bigint(20) unsigned NOT NULL,
            patient_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            last_message_id bigint(20) unsigned DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            patient_archived tinyint(1) NOT NULL DEFAULT 0,
            dentist_archived tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY dentist_patient (dentist_id,patient_id),
            KEY dentist_id (dentist_id),
            KEY patient_id (patient_id),
            KEY status (status),
            KEY date_modified (date_modified)
        ) $charset_collate;";
        
        dbDelta( $sql );
        if ( $this->check_for_errors() ) {
            $success = false;
        }
        
        // Create subscriptions table
        $table_name = $wpdb->prefix . 'dental_subscriptions';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            user_id bigint(20) unsigned NOT NULL,
            plan_id varchar(50) NOT NULL,
            plan_name varchar(50) NOT NULL,
            amount decimal(10,2) DEFAULT NULL,
            currency varchar(3) DEFAULT 'USD',
            interval varchar(20) DEFAULT 'month',
            payment_processor varchar(20) DEFAULT NULL,
            processor_subscription_id varchar(100) DEFAULT NULL,
            processor_customer_id varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            date_start datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_expiry datetime DEFAULT NULL,
            date_cancelled datetime DEFAULT NULL,
            cancel_reason text DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            payment_method varchar(50) DEFAULT NULL,
            payment_method_details text DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id_active (user_id, status),
            KEY user_id (user_id),
            KEY status (status),
            KEY plan_id (plan_id),
            KEY date_expiry (date_expiry),
            KEY processor_subscription_id (processor_subscription_id)
        ) $charset_collate;";
        
        dbDelta( $sql );
        if ( $this->check_for_errors() ) {
            $success = false;
        }
        
        // Create subscription payments table
        $table_name = $wpdb->prefix . 'dental_subscription_payments';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            subscription_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            status varchar(20) NOT NULL DEFAULT 'completed',
            transaction_id varchar(100) DEFAULT NULL,
            processor_fee decimal(10,2) DEFAULT NULL,
            payment_method varchar(50) DEFAULT NULL,
            payment_details text DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY subscription_id (subscription_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY transaction_id (transaction_id),
            KEY date_created (date_created)
        ) $charset_collate;";
        
        dbDelta( $sql );
        if ( $this->check_for_errors() ) {
            $success = false;
        }
        
        // Create reviews table
        $table_name = $wpdb->prefix . 'dental_reviews';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            dentist_id bigint(20) unsigned NOT NULL,
            author_id bigint(20) unsigned NOT NULL,
            rating tinyint unsigned NOT NULL,
            review_text text DEFAULT NULL,
            dentist_response text DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'published',
            is_featured tinyint(1) NOT NULL DEFAULT 0,
            is_verified tinyint(1) NOT NULL DEFAULT 0,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_response datetime DEFAULT NULL,
            helpful_count int unsigned NOT NULL DEFAULT 0,
            unhelpful_count int unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY dentist_id (dentist_id),
            KEY author_id (author_id),
            KEY rating (rating),
            KEY status (status),
            KEY is_featured (is_featured),
            KEY is_verified (is_verified),
            KEY date_created (date_created)
        ) $charset_collate;";
        
        dbDelta( $sql );
        if ( $this->check_for_errors() ) {
            $success = false;
        }
        
        // Create review votes table
        $table_name = $wpdb->prefix . 'dental_review_votes';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            review_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            vote tinyint NOT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY review_user (review_id,user_id),
            KEY review_id (review_id),
            KEY user_id (user_id),
            KEY vote (vote)
        ) $charset_collate;";
        
        dbDelta( $sql );
        if ( $this->check_for_errors() ) {
            $success = false;
        }
        
        // Create message counters table
        $table_name = $wpdb->prefix . 'dental_message_counters';
        $sql = "CREATE TABLE $table_name (
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
        
        dbDelta( $sql );
        if ( $this->check_for_errors() ) {
            $success = false;
        }
        
        // Create dental profiles table
        $table_name = $wpdb->prefix . 'dental_profiles';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            user_id bigint(20) unsigned NOT NULL,
            speciality varchar(100) DEFAULT NULL,
            license varchar(50) DEFAULT NULL,
            clinic_name varchar(100) DEFAULT NULL,
            address text DEFAULT NULL,
            address_line_1 varchar(255) DEFAULT NULL,
            address_line_2 varchar(255) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            state varchar(100) DEFAULT NULL,
            postal_code varchar(20) DEFAULT NULL,
            country varchar(2) DEFAULT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            phone varchar(30) DEFAULT NULL,
            website varchar(255) DEFAULT NULL,
            working_hours text DEFAULT NULL,
            education text DEFAULT NULL,
            experience text DEFAULT NULL,
            bio text DEFAULT NULL,
            services text DEFAULT NULL,
            languages varchar(255) DEFAULT NULL,
            profile_photo varchar(255) DEFAULT NULL,
            cover_photo varchar(255) DEFAULT NULL,
            gallery text DEFAULT NULL,
            social_facebook varchar(255) DEFAULT NULL,
            social_twitter varchar(255) DEFAULT NULL,
            social_instagram varchar(255) DEFAULT NULL,
            social_linkedin varchar(255) DEFAULT NULL,
            featured tinyint(1) NOT NULL DEFAULT 0,
            visibility varchar(20) NOT NULL DEFAULT 'public',
            profile_status varchar(20) NOT NULL DEFAULT 'active',
            rating_average decimal(3,2) DEFAULT NULL,
            rating_count int unsigned DEFAULT 0,
            views_count int unsigned DEFAULT 0,
            last_active datetime DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY speciality (speciality),
            KEY city (city),
            KEY state (state),
            KEY country (country),
            KEY featured (featured),
            KEY visibility (visibility),
            KEY profile_status (profile_status),
            KEY rating_average (rating_average)
        ) $charset_collate;";
        
        dbDelta( $sql );
        if ( $this->check_for_errors() ) {
            $success = false;
        }
        
        // Create patient favorites table
        $table_name = $wpdb->prefix . 'dental_favorites';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL auto_increment,
            patient_id bigint(20) unsigned NOT NULL,
            dentist_id bigint(20) unsigned NOT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY patient_dentist (patient_id,dentist_id),
            KEY patient_id (patient_id),
            KEY dentist_id (dentist_id),
            KEY date_created (date_created)
        ) $charset_collate;";
        
        dbDelta( $sql );
        if ( $this->check_for_errors() ) {
            $success = false;
        }

        return $success;
    }

    /**
     * Rollback the migration - drop tables
     *
     * @return bool True on success, false on failure
     */
    public function down() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'dental_chat_messages',
            $wpdb->prefix . 'dental_conversations',
            $wpdb->prefix . 'dental_subscriptions',
            $wpdb->prefix . 'dental_subscription_payments',
            $wpdb->prefix . 'dental_reviews',
            $wpdb->prefix . 'dental_review_votes',
            $wpdb->prefix . 'dental_message_counters',
            $wpdb->prefix . 'dental_profiles',
            $wpdb->prefix . 'dental_favorites',
        );
        
        $success = true;
        
        foreach ( $tables as $table ) {
            $result = $wpdb->query( "DROP TABLE IF EXISTS $table" );
            
            if ( false === $result ) {
                error_log( "Dental Directory System - Failed to drop table: $table" );
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Check for MySQL errors
     *
     * @return bool True if errors found, false otherwise
     */
    private function check_for_errors() {
        global $wpdb, $EZSQL_ERROR;
        
        if ( ! empty( $wpdb->last_error ) ) {
            error_log( 'Dental Directory System DB Error: ' . $wpdb->last_error );
            return true;
        }
        
        if ( ! empty( $EZSQL_ERROR ) ) {
            $error = end( $EZSQL_ERROR );
            if ( ! empty( $error ) ) {
                error_log( 'Dental Directory System DB Error: ' . print_r( $error, true ) );
                return true;
            }
        }
        
        return false;
    }
}
