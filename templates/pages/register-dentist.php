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
                    
                    <div class="dental-progress-container">
                        <div class="dental-progress-bar">
                            <div class="dental-progress" style="width: 33%;"></div>
                        </div>
                        <div class="dental-progress-steps">
                            <div class="dental-progress-step active" data-step="1">1</div>
                            <div class="dental-progress-step" data-step="2">2</div>
                            <div class="dental-progress-step" data-step="3">3</div>
                        </div>
                    </div>
                    
                    <form id="dental-register-dentist-form" method="post">
                        <!-- Step 1: Account Information -->
                        <div class="dental-form-step active" data-step="1">
                            <h3><?php esc_html_e('Account Information', 'dental-directory-system'); ?></h3>
                            
                            <div class="dental-row">
                                <div class="dental-col-2">
                                    <div class="form-group">
                                        <label for="username"><?php esc_html_e('Username *', 'dental-directory-system'); ?></label>
                                        <input type="text" id="username" name="username" data-validate="username" required>
                                        <div class="field-validation"></div>
                                    </div>
                                </div>
                                
                                <div class="dental-col-2">
                                    <div class="form-group">
                                        <label for="email"><?php esc_html_e('Email *', 'dental-directory-system'); ?></label>
                                        <input type="email" id="email" name="email" data-validate="email" required>
                                        <div class="field-validation"></div>
                                    </div>
                                </div>
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
                            
                            <div class="form-actions">
                                <div></div> <!-- Spacer -->
                                <button type="button" class="dental-btn dental-btn-next" data-step="1"><?php esc_html_e('Next', 'dental-directory-system'); ?></button>
                            </div>
                        </div>
                        
                        <!-- Step 2: Personal Information -->
                        <div class="dental-form-step" data-step="2">
                            <h3><?php esc_html_e('Personal Information', 'dental-directory-system'); ?></h3>
                            
                            <div class="dental-row">
                                <div class="dental-col-2">
                                    <div class="form-group">
                                        <label for="first_name"><?php esc_html_e('First Name *', 'dental-directory-system'); ?></label>
                                        <input type="text" id="first_name" name="first_name" required>
                                        <div class="field-validation"></div>
                                    </div>
                                </div>
                                
                                <div class="dental-col-2">
                                    <div class="form-group">
                                        <label for="last_name"><?php esc_html_e('Last Name *', 'dental-directory-system'); ?></label>
                                        <input type="text" id="last_name" name="last_name" required>
                                        <div class="field-validation"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="display_name"><?php esc_html_e('Display Name *', 'dental-directory-system'); ?></label>
                                <input type="text" id="display_name" name="display_name" required>
                                <div class="field-validation"></div>
                                <p class="dental-field-desc"><?php esc_html_e('This is how your name will appear publicly in the directory', 'dental-directory-system'); ?></p>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone"><?php esc_html_e('Phone Number', 'dental-directory-system'); ?></label>
                                <input type="tel" id="phone" name="phone">
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="dental-btn dental-btn-prev" data-step="2"><?php esc_html_e('Previous', 'dental-directory-system'); ?></button>
                                <button type="button" class="dental-btn dental-btn-next" data-step="2"><?php esc_html_e('Next', 'dental-directory-system'); ?></button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Professional Information -->
                        <div class="dental-form-step" data-step="3">
                            <h3><?php esc_html_e('Professional Information', 'dental-directory-system'); ?></h3>
                            
                            <div class="dental-row">
                                <div class="dental-col-2">
                                    <div class="form-group">
                                        <label for="speciality"><?php esc_html_e('Speciality *', 'dental-directory-system'); ?></label>
                                        <select id="speciality" name="speciality" required>
                                            <option value=""><?php esc_html_e('Select Speciality', 'dental-directory-system'); ?></option>
                                            <option value="general"><?php esc_html_e('General Dentistry', 'dental-directory-system'); ?></option>
                                            <option value="orthodontics"><?php esc_html_e('Orthodontics', 'dental-directory-system'); ?></option>
                                            <option value="pediatric"><?php esc_html_e('Pediatric Dentistry', 'dental-directory-system'); ?></option>
                                            <option value="periodontics"><?php esc_html_e('Periodontics', 'dental-directory-system'); ?></option>
                                            <option value="endodontics"><?php esc_html_e('Endodontics', 'dental-directory-system'); ?></option>
                                            <option value="oral_surgery"><?php esc_html_e('Oral Surgery', 'dental-directory-system'); ?></option>
                                            <option value="prosthodontics"><?php esc_html_e('Prosthodontics', 'dental-directory-system'); ?></option>
                                            <option value="other"><?php esc_html_e('Other', 'dental-directory-system'); ?></option>
                                        </select>
                                        <div class="field-validation"></div>
                                    </div>
                                </div>
                                
                                <div class="dental-col-2">
                                    <div class="form-group">
                                        <label for="license"><?php esc_html_e('License Number *', 'dental-directory-system'); ?></label>
                                        <input type="text" id="license" name="license" required>
                                        <div class="field-validation"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="experience"><?php esc_html_e('Years of Experience', 'dental-directory-system'); ?></label>
                                <input type="number" id="experience" name="experience" min="0" max="70">
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
                            
                            <div class="form-actions">
                                <button type="button" class="dental-btn dental-btn-prev" data-step="3"><?php esc_html_e('Previous', 'dental-directory-system'); ?></button>
                                <?php wp_nonce_field('dental_register_nonce', 'security'); ?>
                                <input type="hidden" name="user_type" value="dentist">
                                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>">
                                <button type="submit" class="dental-btn dental-btn-primary"><?php esc_html_e('Register', 'dental-directory-system'); ?></button>
                            </div>
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
