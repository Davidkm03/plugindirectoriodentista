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
        
        // Initialize user management (load this first as other components depend on it)
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/class-dental-user-roles.php';
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/class-dental-user-permissions.php';
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/class-dental-user-manager.php';
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/user/class-dental-existing-users.php';
        $this->components['user'] = new Dental_User_Manager();
        $this->components['existing_users'] = new Dental_Existing_Users();
        
        // Only load admin components in admin area
        if ( is_admin() ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'admin/class-dental-admin.php';
            $this->components['admin'] = new Dental_Admin();
        }
        
        // Frontend components - Load in correct order (dependency chain)
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/class-dental-template-loader.php';
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/class-dental-router.php';
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/class-dental-auth.php';
        require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'public/class-dental-public.php';
        
        // Initialize template loader first
        $template_loader = new Dental_Template_Loader();
        $this->components['template_loader'] = $template_loader;
        
        // Initialize router with template loader
        $router = new Dental_Router( $template_loader );
        $this->components['router'] = $router;
        
        // Initialize authentication system
        $this->components['auth'] = new Dental_Auth();
        
        // Initialize public facing functionality with required dependencies
        $this->components['public'] = new Dental_Public();
        
        // Check if Elementor is active and load Elementor integration
        if ( did_action( 'elementor/loaded' ) ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/elementor/class-dental-elementor.php';
            $this->components['elementor'] = new Dental_Elementor();
        }
        
        // Initialize chat system
        if (file_exists(DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/chat/class-dental-chat-manager.php')) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/chat/class-dental-chat-manager.php';
            $this->components['chat'] = new Dental_Chat_Manager();
        }
        
        // Initialize subscription system
        if (file_exists(DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/subscription/class-dental-subscription-manager.php')) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/subscription/class-dental-subscription-manager.php';
            $this->components['subscription'] = new Dental_Subscription_Manager();
        }
    }

    /**
     * Register hooks and actions
     */
    private function define_hooks() {
        // Load public facing functionality
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        
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
