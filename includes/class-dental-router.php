<?php
/**
 * Frontend Router for Dental Directory
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Frontend Router Class
 *
 * Handles frontend routing and page display
 *
 * @since 1.0.0
 */
class Dental_Router {

    /**
     * Template loader instance
     *
     * @var Dental_Template_Loader
     */
    private $template_loader;
    
    /**
     * Route map for shortcodes to templates
     *
     * @var array
     */
    private $route_map = array(
        'dental_login_form' => 'pages/login',
        'dental_registration_form' => 'pages/registration',
        'dental_password_reset_form' => 'pages/password-reset',
        'dental_dashboard' => 'pages/dashboard',
        'dental_directory' => 'pages/directory',
        'dental_chat' => 'pages/chat',
        'dental_edit_profile' => 'pages/profile-edit',
        'dental_reviews' => 'pages/reviews',
        'dental_subscription_plans' => 'pages/subscription',
    );
    
    /**
     * Initialize the router
     *
     * @param Dental_Template_Loader $template_loader Template loader instance.
     */
    public function __construct( $template_loader ) {
        $this->template_loader = $template_loader;
        
        // Define core pages
        $this->core_pages = array(
            'login' => array(
                'title' => __('Login', 'dental-directory-system'),
                'content' => '[dental_login]',
                'option_name' => 'dental_page_login'
            ),
            'register_dentist' => array(
                'title' => __('Dentist Registration', 'dental-directory-system'),
                'content' => '[dental_register_dentist]',
                'option_name' => 'dental_page_registro_dentista'
            ),
            'register_patient' => array(
                'title' => __('Patient Registration', 'dental-directory-system'),
                'content' => '[dental_register_patient]',
                'option_name' => 'dental_page_registro_paciente'
            ),
            'password_reset' => array(
                'title' => __('Password Recovery', 'dental-directory-system'),
                'content' => '[dental_password_reset]',
                'option_name' => 'dental_page_recuperar_password'
            ),
            'dashboard_dentist' => array(
                'title' => __('Dentist Dashboard', 'dental-directory-system'),
                'content' => '[dental_dashboard_dentist]',
                'option_name' => 'dental_page_dashboard_dentista'
            ),
            'dashboard_patient' => array(
                'title' => __('Patient Dashboard', 'dental-directory-system'),
                'content' => '[dental_dashboard_patient]',
                'option_name' => 'dental_page_dashboard_paciente'
            ),
            'chat' => array(
                'title' => __('Chat', 'dental-directory-system'),
                'content' => '[dental_chat]',
                'option_name' => 'dental_page_chat'
            ),
            'dentist_directory' => array(
                'title' => __('Dentist Directory', 'dental-directory-system'),
                'content' => '[dental_directory]',
                'option_name' => 'dental_page_directorio'
            ),
        );
        
        // Register hooks
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'init', array( $this, 'register_endpoints' ) );
        add_action( 'dental_plugin_activated', array( $this, 'create_required_pages' ) );
    }

    /**
     * Register REST API endpoints
     * 
     * @return void
     */
    public function register_endpoints() {
        // Registration for dental chat endpoints
        register_rest_route('dental/v1', '/chat/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_messages'),
            'permission_callback' => array($this, 'api_chat_permissions'),
            'args' => array(
                'conversation_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && absint($param) > 0;
                    }
                ),
                'last_id' => array(
                    'required' => false,
                    'default' => 0,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && absint($param) >= 0;
                    }
                ),
            ),
        ));

        register_rest_route('dental/v1', '/chat/send', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_send_message'),
            'permission_callback' => array($this, 'api_chat_permissions'),
            'args' => array(
                'conversation_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && absint($param) > 0;
                    }
                ),
                'message' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
            ),
        ));

        register_rest_route('dental/v1', '/chat/start', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_start_conversation'),
            'permission_callback' => array($this, 'api_patient_permissions'),
            'args' => array(
                'dentist_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && absint($param) > 0;
                    }
                ),
                'message' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
            ),
        ));

        // Registration for dentist directory endpoints
        register_rest_route('dental/v1', '/directory', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_directory'),
            'args' => array(
                'page' => array(
                    'required' => false,
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && absint($param) > 0;
                    }
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 10,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && absint($param) > 0 && absint($param) <= 50;
                    }
                ),
                'specialty' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'location' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'search' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
            ),
        ));
    }

    /**
     * Register shortcodes
     *
     * @return void
     */
    public function register_shortcodes() {
        // Login shortcode
        add_shortcode( 'dental_login', array( $this, 'login_shortcode' ) );
        
        // Registration shortcodes
        add_shortcode( 'dental_register_dentist', array( $this, 'register_dentist_shortcode' ) );
        add_shortcode( 'dental_register_patient', array( $this, 'register_patient_shortcode' ) );
        
        // Password reset shortcode
        add_shortcode( 'dental_password_reset', array( $this, 'password_reset_shortcode' ) );
        
        // Dashboard shortcodes
        add_shortcode( 'dental_dashboard_dentist', array( $this, 'dashboard_dentist_shortcode' ) );
        add_shortcode( 'dental_dashboard_patient', array( $this, 'dashboard_patient_shortcode' ) );
        
        // Chat shortcode
        add_shortcode( 'dental_chat', array( $this, 'chat_shortcode' ) );
        
        // Profile edit shortcode
        add_shortcode( 'dental_edit_profile', array( $this, 'profile_edit_shortcode' ) );
        
        // Reviews shortcode
        add_shortcode( 'dental_reviews', array( $this, 'reviews_shortcode' ) );
        
        // Subscription plans shortcode
        add_shortcode( 'dental_subscription_plans', array( $this, 'subscription_shortcode' ) );
        
        // Role selection shortcode (already registered in class-dental-existing-users.php)
    }

    /**
     * Login form shortcode handler
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function login_shortcode( $atts ) {
        // Enqueue required assets
        $this->template_loader->enqueue_assets();
        
        // Set default attributes
        $atts = shortcode_atts(
            array(
                'redirect' => '',
            ),
            $atts
        );
        
        // Get template
        return $this->template_loader->get_template_part(
            'pages/login',
            array(
                'redirect_url' => esc_url( $atts['redirect'] ),
                'template_loader' => $this->template_loader,
            ),
            false
        );
    }

    /**
     * Registration form shortcode handler
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function registration_shortcode( $atts ) {
        // Enqueue required assets
        $this->template_loader->enqueue_assets();
        
        // Set default attributes
        $atts = shortcode_atts(
            array(
                'type' => 'dentist', // dentist or patient
                'redirect' => '',
            ),
            $atts
        );
        
        // Get template based on registration type
        $template = 'dentist' === $atts['type'] ? 'pages/register-dentist' : 'pages/register-patient';
        
        return $this->template_loader->get_template_part(
            $template,
            array(
                'redirect_url' => esc_url( $atts['redirect'] ),
                'template_loader' => $this->template_loader,
            ),
            false
        );
    }

    /**
     * Password reset shortcode handler
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function password_reset_shortcode( $atts ) {
        // Enqueue required assets
        $this->template_loader->enqueue_assets();
        
        return $this->template_loader->get_template_part(
            'pages/password-reset',
            array(
                'template_loader' => $this->template_loader,
            ),
            false
        );
    }

    /**
     * Dashboard shortcode handler
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function dashboard_shortcode( $atts ) {
        // Enqueue required assets
        $this->template_loader->enqueue_assets();
        
        // Set default attributes
        $atts = shortcode_atts(
            array(
                'type' => '', // dentist or patient
            ),
            $atts
        );
        
        // Determine user type if not specified
        if ( empty( $atts['type'] ) ) {
            if ( dental_is_dentist() ) {
                $atts['type'] = 'dentist';
            } elseif ( dental_is_patient() ) {
                $atts['type'] = 'patient';
            } else {
                // Not logged in or not a dental user
                return $this->not_authorized_message( 'dashboard' );
            }
        } else {
            // Check if user has access to specified dashboard type
            if ( 'dentist' === $atts['type'] && ! dental_is_dentist() ) {
                return $this->not_authorized_message( 'dentist-dashboard' );
            } elseif ( 'patient' === $atts['type'] && ! dental_is_patient() ) {
                return $this->not_authorized_message( 'patient-dashboard' );
            }
        }
        
        // Get template based on dashboard type
        $template = 'dentist' === $atts['type'] ? 'pages/dashboard-dentist' : 'pages/dashboard-patient';
        
        return $this->template_loader->get_template_part(
            $template,
            array(
                'template_loader' => $this->template_loader,
                'user_id' => get_current_user_id(),
            ),
            false
        );
    }

    /**
     * Directory shortcode handler
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function directory_shortcode( $atts ) {
        // Enqueue required assets
        $this->template_loader->enqueue_assets();
        
        // Set default attributes
        $atts = shortcode_atts(
            array(
                'per_page' => 12,
                'speciality' => '',
                'city' => '',
                'featured' => false,
            ),
            $atts
        );
        
        return $this->template_loader->get_template_part(
            'pages/directory',
            array(
                'atts' => $atts,
                'template_loader' => $this->template_loader,
            ),
            false
        );
    }

    /**
     * Chat shortcode handler
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function chat_shortcode( $atts ) {
        // Enqueue required assets
        $this->template_loader->enqueue_assets();
        
        // Must be logged in
        if ( ! is_user_logged_in() ) {
            return $this->not_authorized_message( 'chat' );
        }
        
        // Set default attributes
        $atts = shortcode_atts(
            array(
                'dentist_id' => 0,
                'conversation_id' => 0,
            ),
            $atts
        );
        
        // Parse attributes
        $dentist_id = absint( $atts['dentist_id'] );
        $conversation_id = absint( $atts['conversation_id'] );
        $current_user_id = get_current_user_id();
        $is_dentist = dental_is_dentist();
        $is_patient = dental_is_patient();
        
        // Validate input and permissions
        if ($dentist_id > 0 && !$is_patient) {
            // Only patients can initiate chats with dentists
            return $this->not_authorized_message('patient_only');
        }
        
        if ($conversation_id > 0) {
            // Check if user has permission to view this conversation
            global $wpdb;
            
            $table_conversations = $wpdb->prefix . 'dental_conversations';
            $conversation = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_conversations} WHERE id = %d LIMIT 1",
                    $conversation_id
                )
            );
            
            if (!$conversation) {
                return '<div class="dental-alert dental-alert-error">' . 
                    esc_html__('Conversation not found.', 'dental-directory-system') . 
                    '</div>';
            }
            
            // Ensure user is part of this conversation
            if ($conversation->dentist_id !== $current_user_id && $conversation->patient_id !== $current_user_id) {
                return $this->not_authorized_message('unauthorized');
            }
            
            // Set dentist_id from conversation
            $dentist_id = $conversation->dentist_id;
        }
        
        // Buffer output
        ob_start();
        
        // Load template based on whether user is dentist or patient
        if ($is_dentist) {
            $template_file = 'pages/chat-dentist.php';
        } else {
            $template_file = 'pages/chat-patient.php';
        }
        
        $this->template_loader->get_template($template_file, array(
            'dentist_id' => $dentist_id,
            'conversation_id' => $conversation_id,
            'template_loader' => $this->template_loader,
        ));
        
        return ob_get_clean();
    }

    /**
     * Profile edit shortcode handler
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function profile_edit_shortcode( $atts ) {
        // Enqueue required assets
        $this->template_loader->enqueue_assets();
        
        // Must be logged in
        if ( ! is_user_logged_in() ) {
            return $this->not_authorized_message( 'profile' );
        }
        
        // Set default attributes
        $atts = shortcode_atts(
            array(),
            $atts
        );
        
        // Determine template based on user role
        if ( dental_is_dentist() ) {
            $template = 'pages/profile-edit-dentist';
        } elseif ( dental_is_patient() ) {
            $template = 'pages/profile-edit-patient';
        } else {
            return $this->not_authorized_message( 'profile' );
        }
        
        return $this->template_loader->get_template_part(
            $template,
            array(
                'user_id' => get_current_user_id(),
                'template_loader' => $this->template_loader,
            ),
            false
        );
    }

    /**
     * Reviews shortcode handler
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function reviews_shortcode( $atts ) {
        // Enqueue required assets
        $this->template_loader->enqueue_assets();
        
        // Set default attributes
        $atts = shortcode_atts(
            array(
                'dentist_id' => 0,
            ),
            $atts
        );
        
        $dentist_id = absint( $atts['dentist_id'] );
        
        // If no dentist specified and the current user is a dentist, show their reviews
        if ( 0 === $dentist_id && dental_is_dentist() ) {
            $dentist_id = get_current_user_id();
        }
        
        // Determine template based on context
        if ( $dentist_id > 0 ) {
            // Viewing a specific dentist's reviews
            $template = 'pages/reviews-dentist';
        } else {
            // Viewing the logged-in user's reviews (as a patient)
            if ( ! is_user_logged_in() || ! dental_is_patient() ) {
                return $this->not_authorized_message( 'reviews' );
            }
            $template = 'pages/reviews-patient';
        }
        
        return $this->template_loader->get_template_part(
            $template,
            array(
                'dentist_id' => $dentist_id,
                'template_loader' => $this->template_loader,
            ),
            false
        );
    }

    /**
     * Subscription plans shortcode handler
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function subscription_shortcode( $atts ) {
        // Enqueue required assets
        $this->template_loader->enqueue_assets();
        
        // Set default attributes
        $atts = shortcode_atts(
            array(
                'plan' => '', // specific plan to display
            ),
            $atts
        );
        
        return $this->template_loader->get_template_part(
            'pages/subscription',
            array(
                'plan' => $atts['plan'],
                'template_loader' => $this->template_loader,
            ),
            false
        );
    }

    /**
     * Permission callback for chat API endpoints
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error True if permission granted, WP_Error otherwise.
     */
    public function api_chat_permissions( $request ) {
        // User must be logged in
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'dental_rest_not_logged_in',
                __( 'You must be logged in to use the chat system.', 'dental-directory-system' ),
                array( 'status' => 401 )
            );
        }
        
        // Get conversation ID from request
        $conversation_id = absint( $request->get_param( 'conversation_id' ) );
        
        // Get current user
        $current_user_id = get_current_user_id();
        
        // Verify the user has access to this conversation
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'dental_conversations';
        
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_conversations} WHERE id = %d LIMIT 1",
                $conversation_id
            )
        );
        
        if ( ! $conversation ) {
            return new WP_Error(
                'dental_rest_conversation_not_found',
                __( 'Conversation not found.', 'dental-directory-system' ),
                array( 'status' => 404 )
            );
        }
        
        // Verify user is part of this conversation
        if ( $conversation->dentist_id !== $current_user_id && $conversation->patient_id !== $current_user_id ) {
            return new WP_Error(
                'dental_rest_forbidden',
                __( 'You do not have permission to access this conversation.', 'dental-directory-system' ),
                array( 'status' => 403 )
            );
        }
        
        return true;
    }
    
    /**
     * Permission callback for patient-only API endpoints
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error True if permission granted, WP_Error otherwise.
     */
    public function api_patient_permissions( $request ) {
        // User must be logged in
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'dental_rest_not_logged_in',
                __( 'You must be logged in to use this feature.', 'dental-directory-system' ),
                array( 'status' => 401 )
            );
        }
        
        // User must be a patient
        if ( ! dental_is_patient() ) {
            return new WP_Error(
                'dental_rest_forbidden',
                __( 'Only patients can initiate chats with dentists.', 'dental-directory-system' ),
                array( 'status' => 403 )
            );
        }
        
        return true;
    }

    /**
     * API endpoint for getting messages in a conversation
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function api_get_messages( $request ) {
        // Get parameters
        $conversation_id = absint( $request->get_param( 'conversation_id' ) );
        $last_id = absint( $request->get_param( 'last_id' ) );
        
        // Get current user
        $current_user_id = get_current_user_id();
        
        // Get messages from database
        global $wpdb;
        $table_messages = $wpdb->prefix . 'dental_chat_messages';
        
        // Get conversation to check if we're dentist or patient
        $table_conversations = $wpdb->prefix . 'dental_conversations';
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_conversations} WHERE id = %d LIMIT 1",
                $conversation_id
            )
        );
        
        // Query for messages, if last_id is provided, only get messages newer than that
        $query = "SELECT * FROM {$table_messages} WHERE conversation_id = %d";
        $params = array( $conversation_id );
        
        if ( $last_id > 0 ) {
            $query .= " AND id > %d";
            $params[] = $last_id;
        }
        
        $query .= " ORDER BY created_at ASC LIMIT 50";
        
        $messages = $wpdb->get_results(
            $wpdb->prepare( $query, $params )
        );
        
        // Format messages for response
        $response_messages = array();
        foreach ( $messages as $message ) {
            $is_mine = (int) $message->sender_id === $current_user_id;
            
            $response_messages[] = array(
                'id' => (int) $message->id,
                'conversation_id' => (int) $message->conversation_id,
                'sender_id' => (int) $message->sender_id,
                'message' => esc_html( $message->message ),
                'is_mine' => $is_mine,
                'is_read' => (bool) $message->is_read,
                'created_at' => $message->created_at,
            );
            
            // Mark messages as read if they were sent to current user
            if ( ! $is_mine && ! $message->is_read ) {
                $wpdb->update(
                    $table_messages,
                    array( 'is_read' => 1 ),
                    array( 'id' => $message->id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }
        
        // Return response
        return rest_ensure_response( array(
            'status' => 'success',
            'messages' => $response_messages,
            'conversation' => array(
                'id' => (int) $conversation->id,
                'dentist_id' => (int) $conversation->dentist_id,
                'patient_id' => (int) $conversation->patient_id,
                'created_at' => $conversation->created_at,
            ),
        ) );
    }
    
    /**
     * API endpoint for sending a message
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function api_send_message( $request ) {
        // Get parameters
        $conversation_id = absint( $request->get_param( 'conversation_id' ) );
        $message_text = sanitize_text_field( $request->get_param( 'message' ) );
        
        // Get current user
        $current_user_id = get_current_user_id();
        
        // Get the conversation to determine if user is dentist or patient
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'dental_conversations';
        
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_conversations} WHERE id = %d LIMIT 1",
                $conversation_id
            )
        );
        
        // If current user is the dentist, check if they've reached their message limit
        if ( $current_user_id == $conversation->dentist_id && dental_is_dentist() ) {
            // Check if dentist is on free plan and has reached their message limit
            $subscription_type = dental_get_subscription_type( $current_user_id );
            
            if ( $subscription_type === 'free' ) {
                // Check message counter
                $table_counters = $wpdb->prefix . 'dental_message_counters';
                
                $counter = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$table_counters} WHERE dentist_id = %d AND month = %s AND year = %s LIMIT 1",
                        $current_user_id,
                        date('m'),
                        date('Y')
                    )
                );
                
                $message_limit = apply_filters( 'dental_free_message_limit', 5 );
                
                if ( $counter && $counter->message_count >= $message_limit ) {
                    return new WP_Error(
                        'dental_message_limit_reached',
                        __( 'You have reached your monthly message limit. Please upgrade your subscription to continue the conversation.', 'dental-directory-system' ),
                        array( 'status' => 403 )
                    );
                }
            }
        }
        
        // Insert message into database
        $table_messages = $wpdb->prefix . 'dental_chat_messages';
        
        $result = $wpdb->insert(
            $table_messages,
            array(
                'conversation_id' => $conversation_id,
                'sender_id' => $current_user_id,
                'message' => $message_text,
                'is_read' => 0,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%d', '%s' )
        );
        
        if ( false === $result ) {
            return new WP_Error(
                'dental_message_send_failed',
                __( 'Failed to send message.', 'dental-directory-system' ),
                array( 'status' => 500 )
            );
        }
        
        $message_id = $wpdb->insert_id;
        
        // If current user is dentist, increment their message counter
        if ( $current_user_id == $conversation->dentist_id && dental_is_dentist() ) {
            $subscription_type = dental_get_subscription_type( $current_user_id );
            
            if ( $subscription_type === 'free' ) {
                $table_counters = $wpdb->prefix . 'dental_message_counters';
                
                $counter = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$table_counters} WHERE dentist_id = %d AND month = %s AND year = %s LIMIT 1",
                        $current_user_id,
                        date('m'),
                        date('Y')
                    )
                );
                
                if ( $counter ) {
                    // Update existing counter
                    $wpdb->update(
                        $table_counters,
                        array( 'message_count' => $counter->message_count + 1 ),
                        array( 'id' => $counter->id ),
                        array( '%d' ),
                        array( '%d' )
                    );
                } else {
                    // Create new counter
                    $wpdb->insert(
                        $table_counters,
                        array(
                            'dentist_id' => $current_user_id,
                            'month' => date('m'),
                            'year' => date('Y'),
                            'message_count' => 1,
                        ),
                        array( '%d', '%s', '%s', '%d' )
                    );
                }
            }
        }
        
        // Return response
        return rest_ensure_response( array(
            'status' => 'success',
            'message_id' => $message_id,
            'conversation_id' => $conversation_id,
            'sender_id' => $current_user_id,
            'created_at' => current_time( 'mysql' ),
        ) );
    }
    
    /**
     * API endpoint for starting a new conversation
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function api_start_conversation( $request ) {
        // Get parameters
        $dentist_id = absint( $request->get_param( 'dentist_id' ) );
        $message_text = sanitize_text_field( $request->get_param( 'message' ) );
        
        // Get current patient user
        $patient_id = get_current_user_id();
        
        // Verify that the dentist exists and is a dentist
        $dentist = get_user_by( 'id', $dentist_id );
        if ( ! $dentist || ! dental_is_dentist( $dentist_id ) ) {
            return new WP_Error(
                'dental_dentist_not_found',
                __( 'Dentist not found.', 'dental-directory-system' ),
                array( 'status' => 404 )
            );
        }
        
        // Check if conversation already exists
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'dental_conversations';
        
        $existing_conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_conversations} WHERE dentist_id = %d AND patient_id = %d LIMIT 1",
                $dentist_id,
                $patient_id
            )
        );
        
        if ( $existing_conversation ) {
            // Conversation already exists, use it
            $conversation_id = $existing_conversation->id;
        } else {
            // Create new conversation
            $result = $wpdb->insert(
                $table_conversations,
                array(
                    'dentist_id' => $dentist_id,
                    'patient_id' => $patient_id,
                    'created_at' => current_time( 'mysql' ),
                    'updated_at' => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%s', '%s' )
            );
            
            if ( false === $result ) {
                return new WP_Error(
                    'dental_conversation_start_failed',
                    __( 'Failed to start conversation.', 'dental-directory-system' ),
                    array( 'status' => 500 )
                );
            }
            
            $conversation_id = $wpdb->insert_id;
        }
        
        // Insert message into database
        $table_messages = $wpdb->prefix . 'dental_chat_messages';
        
        $result = $wpdb->insert(
            $table_messages,
            array(
                'conversation_id' => $conversation_id,
                'sender_id' => $patient_id,
                'message' => $message_text,
                'is_read' => 0,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%d', '%s' )
        );
        
        if ( false === $result ) {
            return new WP_Error(
                'dental_message_send_failed',
                __( 'Failed to send message.', 'dental-directory-system' ),
                array( 'status' => 500 )
            );
        }
        
        $message_id = $wpdb->insert_id;
        
        // Update conversation's updated_at timestamp
        $wpdb->update(
            $table_conversations,
            array( 'updated_at' => current_time( 'mysql' ) ),
            array( 'id' => $conversation_id ),
            array( '%s' ),
            array( '%d' )
        );
        
        // Return response
        return rest_ensure_response( array(
            'status' => 'success',
            'conversation_id' => $conversation_id,
            'message_id' => $message_id,
            'dentist_id' => $dentist_id,
            'patient_id' => $patient_id,
        ) );
    }
    
    /**
     * API endpoint for getting dentist directory
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function api_get_directory( $request ) {
        // Get parameters
        $page = absint( $request->get_param( 'page' ) );
        $per_page = absint( $request->get_param( 'per_page' ) );
        $specialty = sanitize_text_field( $request->get_param( 'specialty' ) );
        $location = sanitize_text_field( $request->get_param( 'location' ) );
        $search = sanitize_text_field( $request->get_param( 'search' ) );
        
        // Calculate offset
        $offset = ( $page - 1 ) * $per_page;
        
        // Get dentists from database
        global $wpdb;
        
        // Build query
        $query = "SELECT u.ID, u.display_name, um_speciality.meta_value as specialty, um_location.meta_value as location, um_bio.meta_value as bio
                  FROM {$wpdb->users} u
                  INNER JOIN {$wpdb->usermeta} um_role ON u.ID = um_role.user_id AND um_role.meta_key = '{$wpdb->prefix}capabilities'
                  LEFT JOIN {$wpdb->usermeta} um_speciality ON u.ID = um_speciality.user_id AND um_speciality.meta_key = 'dental_specialty'
                  LEFT JOIN {$wpdb->usermeta} um_location ON u.ID = um_location.user_id AND um_location.meta_key = 'dental_location'
                  LEFT JOIN {$wpdb->usermeta} um_bio ON u.ID = um_bio.user_id AND um_bio.meta_key = 'dental_bio'
                  WHERE um_role.meta_value LIKE %s";
        
        $params = array( '%"dentist"%' );
        
        // Add filters
        if ( ! empty( $specialty ) ) {
            $query .= " AND um_speciality.meta_value = %s";
            $params[] = $specialty;
        }
        
        if ( ! empty( $location ) ) {
            $query .= " AND um_location.meta_value = %s";
            $params[] = $location;
        }
        
        if ( ! empty( $search ) ) {
            $query .= " AND (u.display_name LIKE %s OR um_bio.meta_value LIKE %s)";
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        // Count total dentists for pagination
        $count_query = str_replace( "SELECT u.ID, u.display_name, um_speciality.meta_value as specialty, um_location.meta_value as location, um_bio.meta_value as bio", "SELECT COUNT(DISTINCT u.ID)", $query );
        $total_dentists = $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );
        
        // Add pagination
        $query .= " ORDER BY u.display_name ASC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        
        // Execute query
        $dentists = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
        
        // Format results
        $response_dentists = array();
        foreach ( $dentists as $dentist ) {
            // Get profile image
            $profile_image = get_user_meta( $dentist->ID, 'dental_profile_image', true );
            if ( empty( $profile_image ) ) {
                $profile_image = get_avatar_url( $dentist->ID );
            }
            
            // Get average rating
            $avg_rating = dental_get_dentist_rating( $dentist->ID );
            
            $response_dentists[] = array(
                'id' => $dentist->ID,
                'display_name' => $dentist->display_name,
                'specialty' => $dentist->specialty,
                'location' => $dentist->location,
                'bio' => $dentist->bio ? wp_trim_words( $dentist->bio, 30 ) : '',
                'profile_image' => $profile_image,
                'rating' => $avg_rating,
            );
        }
        
        // Return response
        return rest_ensure_response( array(
            'status' => 'success',
            'dentists' => $response_dentists,
            'total' => (int) $total_dentists,
            'total_pages' => ceil( $total_dentists / $per_page ),
            'current_page' => $page,
        ) );
    }
        
    /**
     * Create required pages for the plugin
     * 
     * @return void
     */
    public function create_required_pages() {
        foreach ( $this->core_pages as $slug => $page ) {
            // Check if page exists by option
            $page_id = get_option( $page['option_name'] );
            $page_exists = false;
            
            if ( $page_id ) {
                // Check if the page actually exists
                $page_exists = get_post( $page_id ) instanceof WP_Post;
            }
            
            if ( ! $page_exists ) {
                // Create page
                $new_page_id = wp_insert_post( array(
                    'post_title'     => $page['title'],
                    'post_content'   => $page['content'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed'
                ) );
                
                if ( ! is_wp_error( $new_page_id ) ) {
                    // Save page ID in options
                    update_option( $page['option_name'], $new_page_id );
                }
            }
        }
    }
    
    /**
     * Get unauthorized access message
     *
     * @param string $page Page type.
     * @return string Error message
     */
    private function not_authorized_message( $page ) {
        $login_url = '';
        $login_page_id = get_option('dental_page_login');
        
        if ($login_page_id) {
            $login_url = get_permalink($login_page_id);
        } else {
            $login_url = wp_login_url( get_permalink() );
        }
        
        $message = '<div class="dental-container">';
        $message .= '<div class="dental-alert dental-alert-warning">';
        
        switch ( $page ) {
            case 'dentist_dashboard':
                $message .= '<p>' . __( 'You must be logged in as a dentist to access this page.', 'dental-directory-system' ) . '</p>';
                break;
            case 'patient_dashboard':
                $message .= '<p>' . __( 'You must be logged in as a patient to access this page.', 'dental-directory-system' ) . '</p>';
                break;
            case 'patient_only':
                $message .= '<p>' . __( 'Only patients can initiate chats with dentists.', 'dental-directory-system' ) . '</p>';
                break;
            case 'unauthorized':
                $message .= '<p>' . __( 'You are not authorized to view this conversation.', 'dental-directory-system' ) . '</p>';
                break;
            case 'chat':
                $message .= '<p>' . __( 'You must be logged in to access the chat system.', 'dental-directory-system' ) . '</p>';
                break;
            default:
                $message .= '<p>' . __( 'You must be logged in to access this page.', 'dental-directory-system' ) . '</p>';
        }
        
        // Only show login button if the user is not logged in or it's not an authorization issue for logged-in users
        if (!is_user_logged_in() || ($page !== 'patient_only' && $page !== 'unauthorized')) {
            $message .= '<p><a href="' . esc_url( $login_url ) . '" class="dental-btn">' . __( 'Log In', 'dental-directory-system' ) . '</a></p>';
        }
        
        // Add back link for unauthorized errors
        if ($page === 'patient_only' || $page === 'unauthorized') {
            $message .= '<p><a href="javascript:history.back()" class="dental-btn dental-btn-secondary">' . __( 'Go Back', 'dental-directory-system' ) . '</a></p>';
        }
        
        $message .= '</div>';
        $message .= '</div>';
        
        return $message;
    }
}
