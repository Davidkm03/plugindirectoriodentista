<?php
/**
 * Template for displaying login form
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include header template
$template_loader->get_template_part('partials/header');

// Get redirect URL
$redirect_url = !empty($redirect_url) ? $redirect_url : '';

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
    // Get registration page URLs
    $register_dentist_url = '';
    $register_patient_url = '';
    
    $dentist_page_id = get_option('dental_page_registro_dentista');
    if ($dentist_page_id) {
        $register_dentist_url = get_permalink($dentist_page_id);
    }
    
    $patient_page_id = get_option('dental_page_registro_paciente');
    if ($patient_page_id) {
        $register_patient_url = get_permalink($patient_page_id);
    }
    
    // Get password recovery URL
    $recovery_url = '';
    $recovery_page_id = get_option('dental_page_recuperar_password');
    if ($recovery_page_id) {
        $recovery_url = get_permalink($recovery_page_id);
    }
?>
    <div class="dental-container">
        <div class="dental-row">
            <div class="dental-col-2" style="margin: 0 auto; float: none;">
                <div class="dental-form">
                    <h2><?php esc_html_e('Login', 'dental-directory-system'); ?></h2>
                    
                    <div class="dental-alert dental-form-message" style="display: none;"></div>
                    
                    <form id="dental-login-form" method="post">
                        <div class="form-group">
                            <label for="username"><?php esc_html_e('Username or Email', 'dental-directory-system'); ?></label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password"><?php esc_html_e('Password', 'dental-directory-system'); ?></label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="remember" value="1">
                                <?php esc_html_e('Remember me', 'dental-directory-system'); ?>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <?php wp_nonce_field('dental_login_nonce', 'security'); ?>
                            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>">
                            <button type="submit" class="dental-btn"><?php esc_html_e('Log In', 'dental-directory-system'); ?></button>
                        </div>
                    </form>
                    
                    <div class="dental-form-footer">
                        <?php if (!empty($recovery_url)) : ?>
                            <p><a href="<?php echo esc_url($recovery_url); ?>"><?php esc_html_e('Forgot your password?', 'dental-directory-system'); ?></a></p>
                        <?php endif; ?>
                        
                        <p>
                            <?php esc_html_e('Don\'t have an account yet?', 'dental-directory-system'); ?><br>
                            <?php if (!empty($register_dentist_url)) : ?>
                                <a href="<?php echo esc_url($register_dentist_url); ?>" class="dental-btn dental-btn-secondary"><?php esc_html_e('Register as Dentist', 'dental-directory-system'); ?></a>
                            <?php endif; ?>
                            
                            <?php if (!empty($register_patient_url)) : ?>
                                <a href="<?php echo esc_url($register_patient_url); ?>" class="dental-btn dental-btn-secondary"><?php esc_html_e('Register as Patient', 'dental-directory-system'); ?></a>
                            <?php endif; ?>
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
