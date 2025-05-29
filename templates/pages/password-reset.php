<?php
/**
 * Template for displaying password reset form
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include header template
$template_loader->get_template_part('partials/header');

// Check if user is already logged in
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $user_name = $user->display_name;
    
    // Determine redirect based on user role
    $redirect = '';
    if (dental_is_dentist()) {
        $dashboard_id = get_option('dental_page_dashboard_dentista');
        if ($dashboard_id) {
            $redirect = get_permalink($dashboard_id);
        }
    } elseif (dental_is_patient()) {
        $dashboard_id = get_option('dental_page_dashboard_paciente');
        if ($dashboard_id) {
            $redirect = get_permalink($dashboard_id);
        }
    }
    
    if (empty($redirect)) {
        $redirect = home_url();
    }
?>
    <div class="dental-container">
        <div class="dental-alert dental-alert-info">
            <p><?php echo sprintf(esc_html__('You are already logged in as %s.', 'dental-directory-system'), esc_html($user_name)); ?></p>
            <p><a href="<?php echo esc_url($redirect); ?>" class="dental-btn"><?php esc_html_e('Go to Dashboard', 'dental-directory-system'); ?></a></p>
            <p><a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>"><?php esc_html_e('Log out', 'dental-directory-system'); ?></a></p>
        </div>
    </div>
<?php
} else {
    // Get login page URL
    $login_url = '';
    $login_page_id = get_option('dental_page_login');
    if ($login_page_id) {
        $login_url = get_permalink($login_page_id);
    } else {
        $login_url = wp_login_url();
    }
    
    // Check if there's a reset key in the URL
    $has_reset_key = isset($_GET['key']) && isset($_GET['login']);
?>
    <div class="dental-container">
        <div class="dental-row">
            <div class="dental-col-2" style="margin: 0 auto; float: none;">
                <div class="dental-form">
                    <?php if ($has_reset_key) : ?>
                        <h2><?php esc_html_e('Reset Your Password', 'dental-directory-system'); ?></h2>
                        
                        <div class="dental-alert dental-form-message" style="display: none;"></div>
                        
                        <form id="dental-reset-password-form" method="post">
                            <div class="form-group">
                                <label for="password"><?php esc_html_e('New Password', 'dental-directory-system'); ?></label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password_confirm"><?php esc_html_e('Confirm New Password', 'dental-directory-system'); ?></label>
                                <input type="password" id="password_confirm" name="password_confirm" required>
                            </div>
                            
                            <div class="form-group">
                                <?php wp_nonce_field('dental_reset_password_nonce', 'security'); ?>
                                <input type="hidden" name="key" value="<?php echo esc_attr($_GET['key']); ?>">
                                <input type="hidden" name="login" value="<?php echo esc_attr($_GET['login']); ?>">
                                <button type="submit" class="dental-btn"><?php esc_html_e('Reset Password', 'dental-directory-system'); ?></button>
                            </div>
                        </form>
                    <?php else : ?>
                        <h2><?php esc_html_e('Recover Your Password', 'dental-directory-system'); ?></h2>
                        <p><?php esc_html_e('Enter your email address and we\'ll send you a link to reset your password.', 'dental-directory-system'); ?></p>
                        
                        <div class="dental-alert dental-form-message" style="display: none;"></div>
                        
                        <form id="dental-recovery-form" method="post">
                            <div class="form-group">
                                <label for="email"><?php esc_html_e('Email Address', 'dental-directory-system'); ?></label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <?php wp_nonce_field('dental_recover_password_nonce', 'security'); ?>
                                <button type="submit" class="dental-btn"><?php esc_html_e('Send Reset Link', 'dental-directory-system'); ?></button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="dental-form-footer">
                        <p>
                            <a href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Back to Login', 'dental-directory-system'); ?></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}

// Include footer template
$template_loader->get_template_part('partials/footer');
