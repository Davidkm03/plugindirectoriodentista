<?php
/**
 * Main Plugin Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
class Dental_Directory_System {

    /**
     * Plugin instance
     *
     * @var Dental_Directory_System
     */
    private static $instance = null;

    /**
     * Plugin components
     *
     * @var array
     */
    private $components = array();

    /**
     * Get the single instance
     *
     * @return Dental_Directory_System
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Constructor is empty to ensure single instance pattern.
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function initialize() {
        // Check if we need to run install/update process
        $this->maybe_update();
        
        // Initialize components
        $this->init_components();

        // Register hooks and actions
        $this->register_hooks();
    }

    /**
     * Check if we need to run the installer
     *
     * @return void
     */
    private function maybe_update() {
        $current_version = get_option( 'dental_directory_version', '0' );
        
        // Run install process if this is a new install or update
        if ( version_compare( $current_version, DENTAL_DIRECTORY_VERSION, '<' ) ) {
            // Include the installer class
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/class-dental-installer.php';
            $installer = new Dental_Installer();
            $installer->install();
            
            // Update version
            update_option( 'dental_directory_version', DENTAL_DIRECTORY_VERSION );
        }
    }

    /**
     * Initialize plugin components
     *
     * @return void
     */
    private function init_components() {
        // Core components
        $this->components['database'] = new Dental_Database();
        
        // Only load admin components in admin area
        if ( is_admin() ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'admin/class-dental-admin.php';
            $this->components['admin'] = new Dental_Admin();
        }
        
        // Frontend components
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'public/class-dental-public.php';
        $this->components['public'] = new Dental_Public();
        
        // Check if Elementor is active and load Elementor integration
        if ( did_action( 'elementor/loaded' ) ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/elementor/class-dental-elementor.php';
            $this->components['elementor'] = new Dental_Elementor();
        }
        
        // Initialize user management
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/class-dental-user-manager.php';
        $this->components['user'] = new Dental_User_Manager();
        
        // Initialize chat system
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/chat/class-dental-chat-manager.php';
        $this->components['chat'] = new Dental_Chat_Manager();
        
        // Initialize subscription system
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/subscription/class-dental-subscription-manager.php';
        $this->components['subscription'] = new Dental_Subscription_Manager();
    }

    /**
     * Register plugin hooks and filters
     *
     * @return void
     */
    private function register_hooks() {
        // Register activation and deactivation hooks
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        
        // Register AJAX handlers
        add_action( 'wp_ajax_dental_chat_send_message', array( $this, 'handle_chat_send_message' ) );
        add_action( 'wp_ajax_nopriv_dental_chat_send_message', array( $this, 'handle_chat_send_message' ) );
        
        // Register REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        
        // Register shortcodes
        add_action( 'init', array( $this, 'register_shortcodes' ) );
    }

    /**
     * Register custom post types
     *
     * @return void
     */
    public function register_post_types() {
        // Implementation will go here
    }

    /**
     * Register taxonomies
     *
     * @return void
     */
    public function register_taxonomies() {
        // Implementation will go here
    }

    /**
     * Register REST API endpoints
     *
     * @return void
     */
    public function register_rest_routes() {
        // Implementation will go here
    }

    /**
     * Register shortcodes
     *
     * @return void
     */
    public function register_shortcodes() {
        // Implementation will go here
    }

    /**
     * Handle AJAX chat message sending
     *
     * @return void
     */
    public function handle_chat_send_message() {
        // Implementation will go here
    }
}
