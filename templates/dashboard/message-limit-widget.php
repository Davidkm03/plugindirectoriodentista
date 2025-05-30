<?php
/**
 * Template for message limit widget in dentist dashboard
 *
 * @package    DentalDirectorySystem
 * @subpackage Templates/Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Get user ID
$user_id = get_current_user_id();

// Get message limits instance
require_once DENTAL_DIRECTORY_PLUGIN_DIR . 'includes/messaging/class-dental-message-limits.php';
$message_limits = new Dental_Message_Limits();
$limit_status = $message_limits->get_dentist_limit_status( $user_id );

// Set CSS class based on status
$status_class = 'message-limits-normal';
$icon = 'fa-check-circle';

if ( $limit_status['is_premium'] ) {
    $status_class = 'message-limits-premium';
    $icon = 'fa-crown';
} elseif ( $limit_status['remaining'] <= 1 ) {
    $status_class = 'message-limits-critical';
    $icon = 'fa-exclamation-circle';
} elseif ( $limit_status['remaining'] <= 2 ) {
    $status_class = 'message-limits-warning';
    $icon = 'fa-exclamation-triangle';
}
?>

<div class="dental-dashboard-widget message-limits-widget <?php echo esc_attr( $status_class ); ?>">
    <div class="widget-header">
        <h3><i class="fas <?php echo esc_attr( $icon ); ?>"></i> <?php esc_html_e( 'Estado de Mensajes', 'dental-directory-system' ); ?></h3>
    </div>
    <div class="widget-content">
        <?php if ( $limit_status['is_premium'] ) : ?>
            <div class="premium-status">
                <span class="plan-badge premium"><?php esc_html_e( 'Premium', 'dental-directory-system' ); ?></span>
                <p><?php esc_html_e( 'Disfrutas de mensajes ilimitados con tu plan Premium.', 'dental-directory-system' ); ?></p>
            </div>
        <?php else : ?>
            <div class="free-status">
                <span class="plan-badge free"><?php esc_html_e( 'Plan Gratuito', 'dental-directory-system' ); ?></span>
                
                <?php if ( $limit_status['limit_reached'] ) : ?>
                    <div class="limit-reached">
                        <p class="limit-alert"><?php esc_html_e( 'Has alcanzado tu límite mensual de mensajes.', 'dental-directory-system' ); ?></p>
                        <p><?php echo sprintf( esc_html__( 'Próximo reinicio: %s', 'dental-directory-system' ), esc_html( $limit_status['next_reset'] ) ); ?></p>
                        <a href="#" class="dental-button upgrade-button" data-action="upgrade-subscription">
                            <?php esc_html_e( 'Actualizar a Premium', 'dental-directory-system' ); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="limit-counter">
                        <div class="counter-stat">
                            <span class="counter-number"><?php echo esc_html( $limit_status['message_count'] ); ?></span>
                            <span class="counter-label"><?php esc_html_e( 'usados', 'dental-directory-system' ); ?></span>
                        </div>
                        <div class="counter-divider">/</div>
                        <div class="counter-stat">
                            <span class="counter-number"><?php echo esc_html( $limit_status['limit'] ); ?></span>
                            <span class="counter-label"><?php esc_html_e( 'total', 'dental-directory-system' ); ?></span>
                        </div>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo esc_attr( ( $limit_status['message_count'] / $limit_status['limit'] ) * 100 ); ?>%"></div>
                    </div>
                    <p class="remaining-text">
                        <?php echo sprintf( 
                            esc_html__( 'Te quedan %d mensajes este mes.', 'dental-directory-system' ), 
                            esc_html( $limit_status['remaining'] ) 
                        ); ?>
                    </p>
                    <?php if ( $limit_status['remaining'] <= 2 ) : ?>
                        <a href="#" class="dental-button upgrade-button" data-action="upgrade-subscription">
                            <?php esc_html_e( 'Actualizar a Premium', 'dental-directory-system' ); ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.message-limits-widget {
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
    background: #fff;
    transition: all 0.3s ease;
}

.message-limits-widget .widget-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.message-limits-widget .widget-header h3 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
}

.message-limits-widget .widget-header i {
    margin-right: 8px;
}

.message-limits-widget .widget-content {
    padding: 20px;
}

.message-limits-widget.message-limits-premium {
    border-left: 4px solid #6c5ce7;
}

.message-limits-widget.message-limits-normal {
    border-left: 4px solid #00b894;
}

.message-limits-widget.message-limits-warning {
    border-left: 4px solid #fdcb6e;
}

.message-limits-widget.message-limits-critical {
    border-left: 4px solid #e74c3c;
}

.plan-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.plan-badge.premium {
    background-color: #6c5ce7;
    color: #fff;
}

.plan-badge.free {
    background-color: #e9f7fe;
    color: #3498db;
}

.premium-status p {
    margin-top: 10px;
    color: #2d3436;
}

.limit-counter {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 15px 0;
    font-size: 18px;
}

.counter-stat {
    text-align: center;
}

.counter-number {
    font-size: 24px;
    font-weight: bold;
    color: #2d3436;
    display: block;
}

.counter-label {
    font-size: 12px;
    color: #7f8c8d;
}

.counter-divider {
    margin: 0 15px;
    font-size: 24px;
    color: #ddd;
}

.progress-bar-container {
    height: 8px;
    background-color: #f1f1f1;
    border-radius: 4px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-bar {
    height: 100%;
    background-color: #00b894;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.message-limits-warning .progress-bar {
    background-color: #fdcb6e;
}

.message-limits-critical .progress-bar {
    background-color: #e74c3c;
}

.remaining-text {
    text-align: center;
    color: #7f8c8d;
    margin: 10px 0;
}

.upgrade-button {
    display: block;
    text-align: center;
    background-color: #3498db;
    color: #fff;
    padding: 10px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: bold;
    margin-top: 15px;
    transition: background-color 0.3s ease;
}

.upgrade-button:hover {
    background-color: #2980b9;
    color: #fff;
}

.limit-reached {
    text-align: center;
}

.limit-alert {
    font-weight: bold;
    color: #e74c3c;
    margin-bottom: 5px;
}
</style>
