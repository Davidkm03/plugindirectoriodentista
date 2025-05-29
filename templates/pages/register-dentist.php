<?php
/**
 * Template for displaying dentist registration form
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
    
    // Get patient registration URL
    $patient_url = '';
    $patient_page_id = get_option('dental_page_registro_paciente');
    if ($patient_page_id) {
        $patient_url = get_permalink($patient_page_id);
    }
?>
    <div class="dental-container">
        <div class="dental-row">
            <div class="dental-col">
                <div class="dental-form">
                    <h2><?php esc_html_e('Dentist Registration', 'dental-directory-system'); ?></h2>
                    <p><?php esc_html_e('Create your dentist account and start receiving patient inquiries.', 'dental-directory-system'); ?></p>
                    
                    <div class="dental-alert dental-form-message" style="display: none;"></div>
                    
                    <form id="dental-register-dentist-form" method="post">
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
                                    <input type="text" id="username" name="username" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><?php esc_html_e('Email *', 'dental-directory-system'); ?></label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="dental-row">
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="password"><?php esc_html_e('Password *', 'dental-directory-system'); ?></label>
                                    <input type="password" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="password_confirm"><?php esc_html_e('Confirm Password *', 'dental-directory-system'); ?></label>
                                    <input type="password" id="password_confirm" name="password_confirm" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="dental-row">
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="speciality"><?php esc_html_e('Speciality *', 'dental-directory-system'); ?></label>
                                    <select id="speciality" name="speciality" required>
                                        <option value=""><?php esc_html_e('Select Speciality', 'dental-directory-system'); ?></option>
                                        <option value="general"><?php esc_html_e('General Dentist', 'dental-directory-system'); ?></option>
                                        <option value="orthodontist"><?php esc_html_e('Orthodontist', 'dental-directory-system'); ?></option>
                                        <option value="periodontist"><?php esc_html_e('Periodontist', 'dental-directory-system'); ?></option>
                                        <option value="endodontist"><?php esc_html_e('Endodontist', 'dental-directory-system'); ?></option>
                                        <option value="oral-surgeon"><?php esc_html_e('Oral Surgeon', 'dental-directory-system'); ?></option>
                                        <option value="prosthodontist"><?php esc_html_e('Prosthodontist', 'dental-directory-system'); ?></option>
                                        <option value="pediatric"><?php esc_html_e('Pediatric Dentist', 'dental-directory-system'); ?></option>
                                        <option value="other"><?php esc_html_e('Other', 'dental-directory-system'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="dental-col-2">
                                <div class="form-group">
                                    <label for="license"><?php esc_html_e('License Number *', 'dental-directory-system'); ?></label>
                                    <input type="text" id="license" name="license" required>
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
                        </div>
                        
                        <div class="form-group">
                            <?php wp_nonce_field('dental_register_nonce', 'security'); ?>
                            <input type="hidden" name="user_type" value="dentist">
                            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>">
                            <button type="submit" class="dental-btn"><?php esc_html_e('Register', 'dental-directory-system'); ?></button>
                        </div>
                    </form>
                    
                    <div class="dental-form-footer">
                        <p>
                            <?php esc_html_e('Already have an account?', 'dental-directory-system'); ?>
                            <a href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Log in', 'dental-directory-system'); ?></a>
                        </p>
                        <?php if (!empty($patient_url)) : ?>
                            <p>
                                <?php esc_html_e('Are you a patient?', 'dental-directory-system'); ?>
                                <a href="<?php echo esc_url($patient_url); ?>"><?php esc_html_e('Register as a patient', 'dental-directory-system'); ?></a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="dental-col">
                <div class="dental-benefits-box">
                    <h3><?php esc_html_e('Benefits of Joining as a Dentist', 'dental-directory-system'); ?></h3>
                    <ul>
                        <li><?php esc_html_e('Free Plan: Receive up to 5 patient messages per month', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Premium Plan: Unlimited patient messages', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Create your professional profile', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Get listed in our public directory', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Receive and manage patient reviews', 'dental-directory-system'); ?></li>
                        <li><?php esc_html_e('Track profile analytics and views', 'dental-directory-system'); ?></li>
                    </ul>
                    
                    <div class="dental-cta">
                        <p><?php esc_html_e('Join our growing network of dental professionals today!', 'dental-directory-system'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}

// Include footer template
$template_loader->get_template_part('partials/footer');
