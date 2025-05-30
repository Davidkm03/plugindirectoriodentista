<?php
/**
 * Subscription Helper Functions
 *
 * @package    DentalDirectorySystem
 * @subpackage Includes/Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Get user subscription type
 *
 * @param int $user_id User ID.
 * @return string Subscription type: 'free' or 'premium'.
 */
function dental_get_subscription_type( $user_id ) {
    global $wpdb;
    
    if ( ! $user_id ) {
        return 'free';
    }
    
    // Check if user has an active subscription
    $subscription_table = $wpdb->prefix . 'dental_subscriptions';
    $subscription = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$subscription_table} WHERE user_id = %d AND status = 'active'",
            $user_id
        )
    );
    
    // Check WooCommerce subscription for premium features
    $has_premium = false;
    
    if ( $subscription && 'premium' === $subscription->plan_type ) {
        $has_premium = true;
    }
    
    return $has_premium ? 'premium' : 'free';
}

/**
 * Check if user has premium subscription
 *
 * @param int $user_id User ID.
 * @return bool True if premium, false if free.
 */
function dental_is_premium_user( $user_id ) {
    return 'premium' === dental_get_subscription_type( $user_id );
}

/**
 * Get message limit for subscription type
 *
 * @param string $subscription_type Subscription type: 'free' or 'premium'.
 * @return int|bool Message limit or false for unlimited.
 */
function dental_get_message_limit( $subscription_type ) {
    switch ( $subscription_type ) {
        case 'premium':
            return false; // Unlimited
        case 'free':
        default:
            return 5; // Default free tier limit
    }
}
