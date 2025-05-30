<?php
/**
 * Template for displaying patient registration form
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
    // Get login page URL
    $login_url = '';
    $login_page_id = get_option('dental_page_login');
    if ($login_page_id) {
        $login_url = get_permalink($login_page_id);
    } else {
        $login_url = wp_login_url();
    }
    
    // Get dentist registration URL
    $dentist_url = '';
    $dentist_page_id = get_option('dental_page_registro_dentista');
    if ($dentist_page_id) {
        $dentist_url = get_permalink($dentist_page_id);
    }
?>
    <div class="dental-container">
        <div class="dental-row">
            <div class="dental-col">
                <div class="dental-form">
                    <h2><?php esc_html_e('Patient Registration', 'dental-directory-system'); ?></h2>
                    <p><?php esc_html_e('Create your patient account to connect with dentists.', 'dental-directory-system'); ?></p>
                    
                    <div class="dental-alert dental-form-message" style="display: none;"></div>
                    
                    <form id="dental-register-patient-form" method="post">
                        <div class="dental-row">
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="display_name"><?php esc_html_e('Full Name *', 'dental-directory-system'); ?></label>
                                    <input type="text" id="display_name" name="display_name" required>
                                </div>
                            </div>
                            
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="username"><?php esc_html_e('Username *', 'dental-directory-system'); ?></label>
                                    <input type="text" id="username" name="username" data-validate="username" required>
                                    <div class="field-validation"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><?php esc_html_e('Email *', 'dental-directory-system'); ?></label>
                            <input type="email" id="email" name="email" data-validate="email" required>
                            <div class="field-validation"></div>
                        </div>
                        
                        <div class="dental-row">
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="password"><?php esc_html_e('Password *', 'dental-directory-system'); ?></label>
                                    <input type="password" id="password" name="password" data-validate="password" required>
                                    <div class="password-strength-meter" data-strength="0" data-text=""></div>
                                    <div class="field-validation"></div>
                                </div>
                            </div>
                            
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="password_confirm"><?php esc_html_e('Confirm Password *', 'dental-directory-system'); ?></label>
                                    <input type="password" id="password_confirm" name="password_confirm" data-validate="password_confirm" required>
                                    <div class="field-validation"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dental-row">
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="city"><?php esc_html_e('City', 'dental-directory-system'); ?></label>
                                    <input type="text" id="city" name="city">
                                </div>
                            </div>
                            
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="age"><?php esc_html_e('Age', 'dental-directory-system'); ?></label>
                                    <input type="number" id="age" name="age" min="1" max="120">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="terms" value="1" required>
                                <?php 
                                    echo wp_kses(
                                        __('I agree to the <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Privacy Policy</a>', 'dental-directory-system'),
                                        array(
                                            'a' => array(
                                                'href' => array(),
                                                'target' => array(),
                                            ),
                                        )
                                    ); 
                                ?>
                            </label>
                            <div class="field-validation"></div>
                        </div>
                        
                        <div class="form-group">
                            <?php wp_nonce_field('dental_register_nonce', 'security'); ?>
                            <input type="hidden" name="user_type" value="patient">
                            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>">
                        </div>
                        
                        <div class="form-actions">
                            <div>
                                <?php if (!empty($dentist_url)) : ?>
                                    <a href="<?php echo esc_url($dentist_url); ?>" class="dental-link"><?php esc_html_e('¿Eres dentista? Regístrate aquí', 'dental-directory-system'); ?></a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button type="submit" class="dental-btn dental-btn-primary" id="patient-submit-btn"><?php esc_html_e('Register', 'dental-directory-system'); ?></button>
                            </div>
                        </div>
                        
                        <div class="dental-form-message" style="display: none;"></div>
                    </form>
                    
                    <div class="dental-form-footer">
                        <p>
                            <?php esc_html_e('Already have an account?', 'dental-directory-system'); ?>
                            <a href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Log in', 'dental-directory-system'); ?></a>
                        </p>
                        <?php if (!empty($dentist_url)) : ?>
                            <p>
                                <?php esc_html_e('Are you a dentist?', 'dental-directory-system'); ?>
                                <a href="<?php echo esc_url($dentist_url); ?>"><?php esc_html_e('Register as a dentist', 'dental-directory-system'); ?></a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="dental-col">
                <div class="dental-benefits-box">
                    <h3><?php esc_html_e('Benefits of Joining as a Patient', 'dental-directory-system'); ?></h3>
                    <ul>
                        <li><?php esc_html_e('Access the complete dentist directory', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Search dentists by specialty, location, and rating', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Send messages directly to dentists', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Save favorite dentists for quick access', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Leave reviews for dentists you\'ve consulted', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Manage all your dental communications in one place', 'dental-directory-system'); ?></li>
                    </ul>
                    
                    <div class="dental-cta">
                        <p><?php esc_html_e('Find the right dental professional for your needs today!', 'dental-directory-system'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        // Display verification UI if query parameter is present
        $verification_sent = isset($_GET['verification_sent']) && $_GET['verification_sent'] == '1';
        $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
        $verified = isset($_GET['verified']) && $_GET['verified'] == '1';
        
        if ($verification_sent) :
        ?>
        <div class="dental-verification-container" style="display: none;" id="verification-ui">
            <div class="dental-verification-icon"><i class="dashicons dashicons-email-alt"></i></div>
            <h2 class="dental-verification-title"><?php esc_html_e('Check Your Email', 'dental-directory-system'); ?></h2>
            <p class="dental-verification-message">
                <?php esc_html_e('We\'ve sent a verification link to your email address. Please check your inbox and click the link to activate your account.', 'dental-directory-system'); ?>
            </p>
            <div class="dental-verification-actions">
                <p>
                    <?php esc_html_e('Didn\'t receive the email?', 'dental-directory-system'); ?> 
                    <a href="#" class="dental-resend-verification" data-user="<?php echo esc_attr($user_id); ?>">
                        <?php esc_html_e('Resend verification email', 'dental-directory-system'); ?>
                    </a>
                </p>
                <div class="dental-verification-timer" style="display: none;">
                    <?php esc_html_e('You can request another email in', 'dental-directory-system'); ?> 
                    <span class="dental-countdown">60</span> 
                    <?php esc_html_e('seconds', 'dental-directory-system'); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($verified) : ?>
        <div class="dental-verification-container dental-verified-container" id="verified-ui">
            <div class="dental-verification-icon" style="color: #4CAF50;"><i class="dashicons dashicons-yes-alt"></i></div>
            <h2 class="dental-verification-title"><?php esc_html_e('Email Verified!', 'dental-directory-system'); ?></h2>
            <p class="dental-verification-message">
                <?php esc_html_e('Your email has been successfully verified. You can now log in to your account.', 'dental-directory-system'); ?>
            </p>
            <div class="dental-verification-actions">
                <a href="<?php echo esc_url($login_url); ?>" class="dental-btn dental-btn-primary">
                    <?php esc_html_e('Log In Now', 'dental-directory-system'); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php
}

// Script para mostrar interfaz de verificación
if (isset($_GET['verification_sent']) && $_GET['verification_sent'] == '1') :
?>
<script>
    jQuery(document).ready(function($) {
        // Ocultar formulario y mostrar UI de verificación
        $('.dental-form').hide();
        $('#verification-ui').show();
    });
</script>
<?php endif; ?>

// Include footer template
$template_loader->get_template_part('partials/footer');
