<?php
/**
 * Messaging API Class
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/API
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class for REST API endpoints related to messaging
 *
 * @since 1.0.0
 */
class Dental_Messaging_API {

    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'dental-directory/v1';

    /**
     * Route base
     *
     * @var string
     */
    private $rest_base = 'messaging';

    /**
     * Database instance
     *
     * @var Dental_Database
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        // Get database instance
        global $dental_database;
        if ( ! $dental_database ) {
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/database/class-dental-database.php';
            $dental_database = new Dental_Database();
        }
        $this->db = $dental_database;

        // Register REST API routes
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Endpoint for sending messages
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/send',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'send_message' ),
                'permission_callback' => array( $this, 'check_send_permission' ),
                'args'                => $this->get_send_message_args(),
            )
        );

        // Endpoint for retrieving conversations
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/conversations',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_conversations' ),
                'permission_callback' => array( $this, 'check_user_permission' ),
                'args'                => $this->get_conversations_args(),
            )
        );

        // Endpoint for retrieving a specific conversation
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/conversations/(?P<id>[a-zA-Z0-9-]+)',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_conversation' ),
                'permission_callback' => array( $this, 'check_conversation_permission' ),
                'args'                => $this->get_conversation_args(),
            )
        );

        // Endpoint for marking messages as read
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/mark-read',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'mark_messages_read' ),
                'permission_callback' => array( $this, 'check_user_permission' ),
                'args'                => $this->get_mark_read_args(),
            )
        );

        // Endpoint for getting notifications
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/notifications',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_notifications' ),
                'permission_callback' => array( $this, 'check_user_permission' ),
            )
        );
    }

    /**
     * Check if user has permission to send messages
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error True if user has permission, WP_Error otherwise.
     */
    public function check_send_permission( $request ) {
        // Must be logged in
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Necesitas iniciar sesión para enviar mensajes.', 'dental-directory-system' ),
                array( 'status' => 401 )
            );
        }

        // Get recipient ID
        $recipient_id = $request->get_param( 'recipient_id' );
        if ( ! $recipient_id ) {
            return new WP_Error(
                'rest_invalid_param',
                __( 'Destinatario no válido.', 'dental-directory-system' ),
                array( 'status' => 400 )
            );
        }

        $current_user_id = get_current_user_id();

        // Check if user is a patient or dentist
        $is_patient = dental_is_patient( $current_user_id );
        $is_dentist = dental_is_dentist( $current_user_id );

        // Check if recipient is valid
        if ( $is_patient && ! dental_is_dentist( $recipient_id ) ) {
            return new WP_Error(
                'rest_invalid_param',
                __( 'El destinatario debe ser un dentista.', 'dental-directory-system' ),
                array( 'status' => 400 )
            );
        }

        if ( $is_dentist && ! dental_is_patient( $recipient_id ) ) {
            return new WP_Error(
                'rest_invalid_param',
                __( 'El destinatario debe ser un paciente.', 'dental-directory-system' ),
                array( 'status' => 400 )
            );
        }

        // If user is dentist, check message limit
        if ( $is_dentist ) {
            // Use the message limits system to check if limit is reached
            require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
            $message_limits = new Dental_Message_Limits();
            
            if ( $message_limits->has_reached_limit( $current_user_id ) ) {
                // Get subscription status for better error message
                $limit_status = $message_limits->get_dentist_limit_status( $current_user_id );
                
                return new WP_Error(
                    'rest_message_limit',
                    sprintf(
                        __( 'Has alcanzado el límite de %d mensajes gratuitos para este mes. Actualiza a premium para enviar mensajes ilimitados. Tu próximo reinicio será el %s.', 'dental-directory-system' ),
                        $limit_status['limit'],
                        $limit_status['next_reset']
                    ),
                    array( 'status' => 403 )
                );
            }
        }

        return true;
    }

    /**
     * Check if user has permission to access their conversations
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error True if user has permission, WP_Error otherwise.
     */
    public function check_user_permission( $request ) {
        // Must be logged in
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Necesitas iniciar sesión para acceder a este recurso.', 'dental-directory-system' ),
                array( 'status' => 401 )
            );
        }

        // Must be a dentist or patient
        $current_user_id = get_current_user_id();
        if ( ! dental_is_dentist( $current_user_id ) && ! dental_is_patient( $current_user_id ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'No tienes permiso para acceder a este recurso.', 'dental-directory-system' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * Check if user has permission to access a specific conversation
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error True if user has permission, WP_Error otherwise.
     */
    public function check_conversation_permission( $request ) {
        // First check basic user permission
        $user_permission = $this->check_user_permission( $request );
        if ( is_wp_error( $user_permission ) ) {
            return $user_permission;
        }

        // Get conversation ID
        $conversation_id = $request->get_param( 'id' );
        
        // Check if conversation exists and belongs to the user
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'dental_conversations';
        $current_user_id = get_current_user_id();
        
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$conversations_table} WHERE id = %s",
                $conversation_id
            )
        );
        
        if ( ! $conversation ) {
            return new WP_Error(
                'rest_not_found',
                __( 'Conversación no encontrada.', 'dental-directory-system' ),
                array( 'status' => 404 )
            );
        }
        
        // Check if user is a participant in the conversation
        if ( $conversation->dentist_id !== $current_user_id && $conversation->patient_id !== $current_user_id ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'No tienes permiso para acceder a esta conversación.', 'dental-directory-system' ),
                array( 'status' => 403 )
            );
        }
        
        return true;
    }

    /**
     * Get arguments for the send message endpoint
     *
     * @return array Arguments.
     */
    public function get_send_message_args() {
        return array(
            'recipient_id' => array(
                'required'          => true,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) {
                    return is_numeric( $param ) && $param > 0;
                },
            ),
            'message' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function( $param ) {
                    return is_string( $param ) && ! empty( $param );
                },
            ),
        );
    }

    /**
     * Get arguments for the conversations endpoint
     *
     * @return array Arguments.
     */
    public function get_conversations_args() {
        return array(
            'page' => array(
                'required'          => false,
                'default'           => 1,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) {
                    return is_numeric( $param ) && $param > 0;
                },
            ),
            'per_page' => array(
                'required'          => false,
                'default'           => 10,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) {
                    return is_numeric( $param ) && $param > 0 && $param <= 50;
                },
            ),
        );
    }

    /**
     * Get arguments for the conversation endpoint
     *
     * @return array Arguments.
     */
    public function get_conversation_args() {
        return array(
            'page' => array(
                'required'          => false,
                'default'           => 1,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) {
                    return is_numeric( $param ) && $param > 0;
                },
            ),
            'per_page' => array(
                'required'          => false,
                'default'           => 20,
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) {
                    return is_numeric( $param ) && $param > 0 && $param <= 100;
                },
            ),
        );
    }

    /**
     * Get arguments for marking messages as read
     *
     * @return array Arguments.
     */
    public function get_mark_read_args() {
        return array(
            'conversation_id' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function( $param ) {
                    return ! empty( $param );
                },
            ),
        );
    }

    /**
     * Send a message
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function send_message( $request ) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'dental_messages';
        $conversations_table = $wpdb->prefix . 'dental_conversations';
        $notifications_table = $wpdb->prefix . 'dental_notifications';
        
        // Get parameters
        $recipient_id = absint( $request->get_param( 'recipient_id' ) );
        $message_content = sanitize_text_field( $request->get_param( 'message' ) );
        $sender_id = get_current_user_id();
        
        // Check if sender is dentist or patient
        $is_dentist = dental_is_dentist( $sender_id );
        $is_patient = dental_is_patient( $sender_id );
        
        // Set dentist and patient IDs based on sender type
        $dentist_id = $is_dentist ? $sender_id : $recipient_id;
        $patient_id = $is_patient ? $sender_id : $recipient_id;
        
        // Begin transaction
        $wpdb->query( 'START TRANSACTION' );
        
        try {
            // Check if conversation exists
            $conversation_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$conversations_table} WHERE (dentist_id = %d AND patient_id = %d)",
                    $dentist_id,
                    $patient_id
                )
            );
            
            // If conversation doesn't exist, create it
            if ( ! $conversation_id ) {
                // Generate UUID for conversation
                require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/utils/class-dental-uuid.php';
                $conversation_id = Dental_UUID::generate();
                
                // Insert conversation
                $wpdb->insert(
                    $conversations_table,
                    array(
                        'id'         => $conversation_id,
                        'dentist_id' => $dentist_id,
                        'patient_id' => $patient_id,
                        'created_at' => current_time( 'mysql' ),
                        'updated_at' => current_time( 'mysql' ),
                    ),
                    array( '%s', '%d', '%d', '%s', '%s' )
                );
                
                if ( $wpdb->last_error ) {
                    throw new Exception( $wpdb->last_error );
                }
            } else {
                // Update conversation timestamp
                $wpdb->update(
                    $conversations_table,
                    array( 'updated_at' => current_time( 'mysql' ) ),
                    array( 'id' => $conversation_id ),
                    array( '%s' ),
                    array( '%s' )
                );
                
                if ( $wpdb->last_error ) {
                    throw new Exception( $wpdb->last_error );
                }
            }
            
            // Insert message
            $message_id = $wpdb->insert(
                $messages_table,
                array(
                    'conversation_id' => $conversation_id,
                    'sender_id'      => $sender_id,
                    'recipient_id'   => $recipient_id,
                    'dentist_id'     => $dentist_id,
                    'message'        => $message_content,
                    'read'           => 0,
                    'created_at'     => current_time( 'mysql' ),
                ),
                array( '%s', '%d', '%d', '%d', '%s', '%d', '%s' )
            );
            
            if ( $wpdb->last_error ) {
                throw new Exception( $wpdb->last_error );
            }
            
            // Create notification for recipient
            $notification_data = array(
                'user_id'    => $recipient_id,
                'type'       => 'message',
                'reference_id' => $conversation_id,
                'message'    => sprintf(
                    __( 'Tienes un nuevo mensaje de %s', 'dental-directory-system' ),
                    get_the_author_meta( 'display_name', $sender_id )
                ),
                'read'       => 0,
                'created_at' => current_time( 'mysql' ),
            );
            
            $wpdb->insert(
                $notifications_table,
                $notification_data,
                array( '%d', '%s', '%s', '%s', '%d', '%s' )
            );
            
            if ( $wpdb->last_error ) {
                throw new Exception( $wpdb->last_error );
            }
            
            // If dentist sending a message, increment message count
            if ( $is_dentist ) {
                require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
                $message_limits = new Dental_Message_Limits();
                $message_limits->increment_dentist_message_count( $sender_id );
                
                // Log this action for debugging
                error_log( sprintf( 'Dental Directory: Dentist ID %d sent a message. Counter incremented.', $sender_id ) );
            }
            
            // Commit transaction
            $wpdb->query( 'COMMIT' );
            
            // Get the inserted message
            $message = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$messages_table} WHERE id = %d",
                    $wpdb->insert_id
                ),
                ARRAY_A
            );
            
            // Format the response
            $response = array(
                'success'   => true,
                'message'   => $message,
                'conversation_id' => $conversation_id,
            );
            
            return new WP_REST_Response( $response, 201 );
            
        } catch ( Exception $e ) {
            // Rollback transaction on error
            $wpdb->query( 'ROLLBACK' );
            
            return new WP_Error(
                'rest_message_error',
                $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }
    
    /**
     * Get all conversations for current user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function get_conversations( $request ) {
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'dental_conversations';
        $messages_table = $wpdb->prefix . 'dental_messages';
        
        // Get current user ID
        $current_user_id = get_current_user_id();
        
        // Get pagination parameters
        $page = absint( $request->get_param( 'page' ) );
        $per_page = absint( $request->get_param( 'per_page' ) );
        $offset = ( $page - 1 ) * $per_page;
        
        // Get conversations
        $conversations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.*, 
                (SELECT COUNT(*) FROM {$messages_table} m WHERE m.conversation_id = c.id AND m.recipient_id = %d AND m.read = 0) as unread_count,
                (SELECT message FROM {$messages_table} m WHERE m.conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM {$messages_table} m WHERE m.conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_date
                FROM {$conversations_table} c
                WHERE c.dentist_id = %d OR c.patient_id = %d
                ORDER BY (SELECT created_at FROM {$messages_table} m WHERE m.conversation_id = c.id ORDER BY created_at DESC LIMIT 1) DESC
                LIMIT %d OFFSET %d",
                $current_user_id,
                $current_user_id,
                $current_user_id,
                $per_page,
                $offset
            ),
            ARRAY_A
        );
        
        // Get total conversations count for pagination
        $total_conversations = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$conversations_table} WHERE dentist_id = %d OR patient_id = %d",
                $current_user_id,
                $current_user_id
            )
        );
        
        // Format conversations with participant details
        $formatted_conversations = array();
        foreach ( $conversations as $conversation ) {
            // Get other participant ID
            $other_id = ( $conversation['dentist_id'] == $current_user_id ) 
                ? $conversation['patient_id'] 
                : $conversation['dentist_id'];
            
            // Get participant details
            $participant = get_userdata( $other_id );
            
            if ( $participant ) {
                $conversation['participant'] = array(
                    'id'           => $other_id,
                    'display_name' => $participant->display_name,
                    'avatar'       => get_avatar_url( $other_id ),
                    'role'         => dental_is_dentist( $other_id ) ? 'dentist' : 'patient',
                );
                
                $formatted_conversations[] = $conversation;
            }
        }
        
        // Set pagination headers
        $total_pages = ceil( $total_conversations / $per_page );
        
        $response = new WP_REST_Response( $formatted_conversations, 200 );
        $response->header( 'X-WP-Total', $total_conversations );
        $response->header( 'X-WP-TotalPages', $total_pages );
        
        return $response;
    }
    
    /**
     * Get messages for a specific conversation
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function get_conversation( $request ) {
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'dental_conversations';
        $messages_table = $wpdb->prefix . 'dental_messages';
        
        // Get parameters
        $conversation_id = $request->get_param( 'id' );
        $page = absint( $request->get_param( 'page' ) );
        $per_page = absint( $request->get_param( 'per_page' ) );
        $offset = ( $page - 1 ) * $per_page;
        
        // Get conversation details
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$conversations_table} WHERE id = %s",
                $conversation_id
            ),
            ARRAY_A
        );
        
        if ( ! $conversation ) {
            return new WP_Error(
                'rest_not_found',
                __( 'Conversación no encontrada.', 'dental-directory-system' ),
                array( 'status' => 404 )
            );
        }
        
        // Get messages
        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$messages_table} 
                WHERE conversation_id = %s 
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d",
                $conversation_id,
                $per_page,
                $offset
            ),
            ARRAY_A
        );
        
        // Get total messages count for pagination
        $total_messages = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$messages_table} WHERE conversation_id = %s",
                $conversation_id
            )
        );
        
        // Get participant details
        $current_user_id = get_current_user_id();
        $other_id = ( $conversation['dentist_id'] == $current_user_id ) 
            ? $conversation['patient_id'] 
            : $conversation['dentist_id'];
        
        $participant = get_userdata( $other_id );
        
        if ( $participant ) {
            $conversation['participant'] = array(
                'id'           => $other_id,
                'display_name' => $participant->display_name,
                'avatar'       => get_avatar_url( $other_id ),
                'role'         => dental_is_dentist( $other_id ) ? 'dentist' : 'patient',
            );
        }
        
        // Format response
        $response_data = array(
            'conversation' => $conversation,
            'messages'     => $messages,
        );
        
        // Set pagination headers
        $total_pages = ceil( $total_messages / $per_page );
        
        $response = new WP_REST_Response( $response_data, 200 );
        $response->header( 'X-WP-Total', $total_messages );
        $response->header( 'X-WP-TotalPages', $total_pages );
        
        return $response;
    }
    
    /**
     * Mark messages as read
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function mark_messages_read( $request ) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'dental_messages';
        
        // Get parameters
        $conversation_id = $request->get_param( 'conversation_id' );
        $current_user_id = get_current_user_id();
        
        // Mark messages as read
        $result = $wpdb->update(
            $messages_table,
            array( 'read' => 1 ),
            array(
                'conversation_id' => $conversation_id,
                'recipient_id'    => $current_user_id,
                'read'            => 0,
            ),
            array( '%d' ),
            array( '%s', '%d', '%d' )
        );
        
        if ( false === $result ) {
            return new WP_Error(
                'rest_mark_read_error',
                __( 'Error al marcar los mensajes como leídos.', 'dental-directory-system' ),
                array( 'status' => 500 )
            );
        }
        
        return new WP_REST_Response(
            array(
                'success' => true,
                'marked_count' => $result,
            ),
            200
        );
    }
    
    /**
     * Get notifications for current user
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function get_notifications( $request ) {
        global $wpdb;
        $notifications_table = $wpdb->prefix . 'dental_notifications';
        
        // Get current user ID
        $current_user_id = get_current_user_id();
        
        // Get notifications
        $notifications = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$notifications_table} 
                WHERE user_id = %d 
                ORDER BY created_at DESC
                LIMIT 50",
                $current_user_id
            ),
            ARRAY_A
        );
        
        // Get unread count
        $unread_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$notifications_table} 
                WHERE user_id = %d AND read = 0",
                $current_user_id
            )
        );
        
        $response = array(
            'notifications' => $notifications,
            'unread_count'  => intval( $unread_count ),
        );
        
        return new WP_REST_Response( $response, 200 );
    }
}
