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
     * Database migrator instance
     *
     * @var Dental_DB_Migrator
     */
    private $migrator;

    /**
     * Create all required tables for the plugin
     *
     * @return bool True on success, false on failure
     */
    public function create_tables() {
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/class-dental-db-migrator.php';
        
        $this->migrator = new Dental_DB_Migrator();
        return $this->migrator->migrate();
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
    
    /**
     * Get database schema version
     *
     * @return string Current schema version
     */
    public function get_schema_version() {
        if ( isset( $this->migrator ) ) {
            return $this->migrator->get_current_version();
        }
        
        return get_option( 'dental_db_version', '0.0.0' );
    }
    
    /**
     * Check if database needs to be updated
     *
     * @return bool True if update needed, false otherwise
     */
    public function needs_update() {
        if ( ! isset( $this->migrator ) ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/class-dental-db-migrator.php';
            $this->migrator = new Dental_DB_Migrator();
        }
        
        return ! $this->migrator->is_up_to_date();
    }
    
    /**
     * Rollback database to a specific version
     *
     * @param string $version Target version.
     * @return bool True on success, false on failure
     */
    public function rollback( $version ) {
        if ( ! isset( $this->migrator ) ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/class-dental-db-migrator.php';
            $this->migrator = new Dental_DB_Migrator();
        }
        
        return $this->migrator->rollback( $version );
    }
    
    /**
     * Get dentist profile by user ID
     *
     * @param int $user_id User ID.
     * @return object|null Profile object or null if not found
     */
    public function get_dentist_profile( $user_id ) {
        global $wpdb;
        
        $user_id = absint( $user_id );
        $table_name = $wpdb->prefix . 'dental_profiles';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d",
                $user_id
            )
        );
    }
    
    /**
     * Get dentist subscription by user ID
     *
     * @param int $user_id User ID.
     * @return object|null Subscription object or null if not found
     */
    public function get_dentist_subscription( $user_id ) {
        global $wpdb;
        
        $user_id = absint( $user_id );
        $table_name = $wpdb->prefix . 'dental_subscriptions';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND status = 'active'",
                $user_id
            )
        );
    }
    
    /**
     * Check if a dentist has a premium subscription
     *
     * @param int $user_id Dentist user ID.
     * @return bool True if premium, false if free or no subscription
     */
    public function is_premium_dentist( $user_id ) {
        $subscription = $this->get_dentist_subscription( $user_id );
        
        if ( ! $subscription ) {
            return false;
        }
        
        return 'premium' === $subscription->plan_name;
    }
    
    /**
     * Get reviews for a dentist
     *
     * @param int   $dentist_id Dentist user ID.
     * @param array $args       Query arguments.
     * @return array Array of review objects
     */
    public function get_dentist_reviews( $dentist_id, $args = array() ) {
        global $wpdb;
        
        $defaults = array(
            'status'      => 'published',
            'per_page'    => 10,
            'offset'      => 0,
            'orderby'     => 'date_created',
            'order'       => 'DESC',
            'min_rating'  => null,
            'max_rating'  => null,
            'count_only'  => false,
            'with_author' => false,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $dentist_id = absint( $dentist_id );
        $reviews_table = $wpdb->prefix . 'dental_reviews';
        
        // Build WHERE clause
        $where = $wpdb->prepare( "WHERE dentist_id = %d", $dentist_id );
        
        if ( $args['status'] ) {
            $where .= $wpdb->prepare( " AND status = %s", $args['status'] );
        }
        
        if ( null !== $args['min_rating'] ) {
            $where .= $wpdb->prepare( " AND rating >= %d", absint( $args['min_rating'] ) );
        }
        
        if ( null !== $args['max_rating'] ) {
            $where .= $wpdb->prepare( " AND rating <= %d", absint( $args['max_rating'] ) );
        }
        
        // Count only query
        if ( $args['count_only'] ) {
            $query = "SELECT COUNT(*) FROM $reviews_table $where";
            return $wpdb->get_var( $query );
        }
        
        // Regular query
        $orderby = sanitize_sql_orderby( $args['orderby'] );
        if ( ! $orderby ) {
            $orderby = 'date_created';
        }
        
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
        
        $limit = '';
        if ( $args['per_page'] > 0 ) {
            $limit = $wpdb->prepare( "LIMIT %d OFFSET %d", $args['per_page'], $args['offset'] );
        }
        
        if ( $args['with_author'] ) {
            // Join with users table to get author information
            $query = "SELECT r.*, u.display_name as author_name, u.user_email as author_email, u.user_registered as author_registered 
                    FROM $reviews_table r 
                    LEFT JOIN {$wpdb->users} u ON r.author_id = u.ID 
                    $where 
                    ORDER BY r.$orderby $order 
                    $limit";
        } else {
            $query = "SELECT * FROM $reviews_table $where ORDER BY $orderby $order $limit";
        }
        
        return $wpdb->get_results( $query );
    }
    
    /**
     * Get conversations for a user
     *
     * @param int   $user_id User ID.
     * @param array $args    Query arguments.
     * @return array Array of conversation objects
     */
    public function get_user_conversations( $user_id, $args = array() ) {
        global $wpdb;
        
        $defaults = array(
            'role'        => null, // 'dentist' or 'patient'
            'status'      => 'active',
            'per_page'    => 10,
            'offset'      => 0,
            'orderby'     => 'date_modified',
            'order'       => 'DESC',
            'count_only'  => false,
            'with_participants' => false,
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        $user_id = absint( $user_id );
        $conversations_table = $wpdb->prefix . 'dental_conversations';
        
        // Build WHERE clause based on role
        $where = '';
        if ( 'dentist' === $args['role'] ) {
            $where = $wpdb->prepare( "WHERE dentist_id = %d", $user_id );
        } elseif ( 'patient' === $args['role'] ) {
            $where = $wpdb->prepare( "WHERE patient_id = %d", $user_id );
        } else {
            $where = $wpdb->prepare( "WHERE dentist_id = %d OR patient_id = %d", $user_id, $user_id );
        }
        
        if ( $args['status'] ) {
            $where .= $wpdb->prepare( " AND status = %s", $args['status'] );
        }
        
        // Add archived filter for role
        if ( 'dentist' === $args['role'] ) {
            $where .= " AND dentist_archived = 0";
        } elseif ( 'patient' === $args['role'] ) {
            $where .= " AND patient_archived = 0";
        }
        
        // Count only query
        if ( $args['count_only'] ) {
            $query = "SELECT COUNT(*) FROM $conversations_table $where";
            return $wpdb->get_var( $query );
        }
        
        // Regular query
        $orderby = sanitize_sql_orderby( $args['orderby'] );
        if ( ! $orderby ) {
            $orderby = 'date_modified';
        }
        
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
        
        $limit = '';
        if ( $args['per_page'] > 0 ) {
            $limit = $wpdb->prepare( "LIMIT %d OFFSET %d", $args['per_page'], $args['offset'] );
        }
        
        if ( $args['with_participants'] ) {
            // Join with users table to get participant information
            $query = "SELECT c.*, 
                      d.display_name as dentist_name, 
                      p.display_name as patient_name, 
                      dp.profile_photo as dentist_photo,
                      COUNT(m.id) as unread_count
                    FROM $conversations_table c 
                    LEFT JOIN {$wpdb->users} d ON c.dentist_id = d.ID 
                    LEFT JOIN {$wpdb->users} p ON c.patient_id = p.ID 
                    LEFT JOIN {$wpdb->prefix}dental_profiles dp ON c.dentist_id = dp.user_id
                    LEFT JOIN {$wpdb->prefix}dental_chat_messages m ON c.id = m.conversation_id AND m.is_read = 0 AND m.receiver_id = %d
                    $where 
                    GROUP BY c.id
                    ORDER BY c.$orderby $order 
                    $limit";
            
            return $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
        } else {
            $query = "SELECT * FROM $conversations_table $where ORDER BY $orderby $order $limit";
            return $wpdb->get_results( $query );
        }
    }
    
    /**
     * Save profile data for a dentist
     *
     * @param int   $user_id      User ID.
     * @param array $profile_data Profile data.
     * @return int|false ID of inserted/updated profile or false on failure
     */
    public function save_dentist_profile( $user_id, $profile_data ) {
        global $wpdb;
        
        $user_id = absint( $user_id );
        $table_name = $wpdb->prefix . 'dental_profiles';
        
        // Check if profile already exists
        $existing_profile = $this->get_dentist_profile( $user_id );
        
        // Sanitize profile data
        $data = $this->sanitize_profile_data( $profile_data );
        
        // Add user ID
        $data['user_id'] = $user_id;
        
        if ( $existing_profile ) {
            // Update existing profile
            $data['date_modified'] = current_time( 'mysql' );
            
            $result = $wpdb->update(
                $table_name,
                $data,
                array( 'user_id' => $user_id )
            );
            
            if ( false === $result ) {
                error_log( 'Dental Directory System - Failed to update profile: ' . $wpdb->last_error );
                return false;
            }
            
            return $existing_profile->id;
        } else {
            // Insert new profile
            $data['date_created'] = current_time( 'mysql' );
            $data['date_modified'] = current_time( 'mysql' );
            
            $result = $wpdb->insert(
                $table_name,
                $data
            );
            
            if ( false === $result ) {
                error_log( 'Dental Directory System - Failed to insert profile: ' . $wpdb->last_error );
                return false;
            }
            
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Sanitize profile data for database insertion
     *
     * @param array $data Raw profile data.
     * @return array Sanitized data
     */
    private function sanitize_profile_data( $data ) {
        $sanitized = array();
        
        // Text fields
        $text_fields = array(
            'speciality', 'license', 'clinic_name', 'address', 'address_line_1',
            'address_line_2', 'city', 'state', 'postal_code', 'country',
            'phone', 'website', 'working_hours', 'education', 'experience',
            'bio', 'services', 'languages', 'profile_photo', 'cover_photo',
            'gallery', 'social_facebook', 'social_twitter', 'social_instagram',
            'social_linkedin', 'visibility', 'profile_status'
        );
        
        foreach ( $text_fields as $field ) {
            if ( isset( $data[$field] ) ) {
                $sanitized[$field] = sanitize_text_field( $data[$field] );
            }
        }
        
        // Number fields
        if ( isset( $data['latitude'] ) ) {
            $sanitized['latitude'] = floatval( $data['latitude'] );
        }
        if ( isset( $data['longitude'] ) ) {
            $sanitized['longitude'] = floatval( $data['longitude'] );
        }
        if ( isset( $data['featured'] ) ) {
            $sanitized['featured'] = absint( $data['featured'] ) ? 1 : 0;
        }
        
        return $sanitized;
    }
    
    /**
     * Check if database schema exists
     *
     * @return bool True if schema exists, false otherwise
     */
    public function schema_exists() {
        global $wpdb;
        
        // Check if one of our tables exists as a proxy for full schema check
        $table_name = $wpdb->prefix . 'dental_profiles';
        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
        
        return $wpdb->get_var( $query ) === $table_name;
    }
}
