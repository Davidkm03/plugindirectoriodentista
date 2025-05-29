<?php
/**
 * Patient Profile Edit Template
 *
 * @package DentalDirectorySystem
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get user profile data
$profile_manager = new Dental_Profile_Manager();
$user_id = get_current_user_id();
$profile = $profile_manager->get_patient_profile( $user_id );

// Include header template
$template_loader->get_template_part('partials/header');
?>

<div class="dental-container dental-profile-edit">
    <h1><?php esc_html_e('Editar Perfil', 'dental-directory-system'); ?></h1>
    
    <div class="dental-alert dental-form-message" style="display: none;"></div>
    
    <div class="dental-tabs">
        <ul class="dental-tabs-nav">
            <li class="active"><a href="#basic-info"><?php esc_html_e('Información Básica', 'dental-directory-system'); ?></a></li>
            <li><a href="#contact-info"><?php esc_html_e('Contacto', 'dental-directory-system'); ?></a></li>
            <li><a href="#photos"><?php esc_html_e('Foto de Perfil', 'dental-directory-system'); ?></a></li>
            <li><a href="#preferences"><?php esc_html_e('Preferencias', 'dental-directory-system'); ?></a></li>
        </ul>
        
        <div class="dental-tabs-content">
            <form id="dental-patient-profile-form" method="post">
                <!-- Basic Information Tab -->
                <div id="basic-info" class="dental-tab-panel active">
                    <h2><?php esc_html_e('Información Básica', 'dental-directory-system'); ?></h2>
                    
                    <div class="dental-row">
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="display_name"><?php esc_html_e('Nombre Completo', 'dental-directory-system'); ?> *</label>
                                <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr( $profile['display_name'] ); ?>" required>
                            </div>
                        </div>
                        
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="email"><?php esc_html_e('Correo Electrónico', 'dental-directory-system'); ?></label>
                                <input type="email" id="email" value="<?php echo esc_attr( $profile['email'] ); ?>" disabled>
                                <p class="dental-field-desc"><?php esc_html_e('No puedes cambiar tu correo electrónico.', 'dental-directory-system'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dental-row">
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="first_name"><?php esc_html_e('Nombre', 'dental-directory-system'); ?></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $profile['first_name'] ); ?>">
                            </div>
                        </div>
                        
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="last_name"><?php esc_html_e('Apellido', 'dental-directory-system'); ?></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $profile['last_name'] ); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio"><?php esc_html_e('Acerca de mí', 'dental-directory-system'); ?></label>
                        <textarea id="bio" name="bio" rows="3"><?php echo esc_textarea( isset($profile['bio']) ? $profile['bio'] : '' ); ?></textarea>
                        <p class="dental-field-desc"><?php esc_html_e('Una breve descripción sobre ti. Esta información no se mostrará públicamente.', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="dental-btn dental-btn-next"><?php esc_html_e('Siguiente', 'dental-directory-system'); ?></button>
                    </div>
                </div>
                
                <!-- Contact Information Tab -->
                <div id="contact-info" class="dental-tab-panel">
                    <h2><?php esc_html_e('Información de Contacto', 'dental-directory-system'); ?></h2>
                    
                    <div class="dental-row">
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="phone"><?php esc_html_e('Teléfono', 'dental-directory-system'); ?></label>
                                <input type="tel" id="phone" name="phone" value="<?php echo esc_attr( isset($profile['phone']) ? $profile['phone'] : '' ); ?>">
                            </div>
                        </div>
                        
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="preferred_contact_method"><?php esc_html_e('Método de contacto preferido', 'dental-directory-system'); ?></label>
                                <select id="preferred_contact_method" name="preferred_contact_method">
                                    <option value="email" <?php selected( isset($profile['preferred_contact_method']) ? $profile['preferred_contact_method'] : '', 'email' ); ?>><?php esc_html_e('Correo Electrónico', 'dental-directory-system'); ?></option>
                                    <option value="phone" <?php selected( isset($profile['preferred_contact_method']) ? $profile['preferred_contact_method'] : '', 'phone' ); ?>><?php esc_html_e('Teléfono', 'dental-directory-system'); ?></option>
                                    <option value="whatsapp" <?php selected( isset($profile['preferred_contact_method']) ? $profile['preferred_contact_method'] : '', 'whatsapp' ); ?>><?php esc_html_e('WhatsApp', 'dental-directory-system'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dental-row">
                        <div class="dental-col-3">
                            <div class="form-group">
                                <label for="city"><?php esc_html_e('Ciudad', 'dental-directory-system'); ?></label>
                                <input type="text" id="city" name="city" value="<?php echo esc_attr( isset($profile['city']) ? $profile['city'] : '' ); ?>">
                            </div>
                        </div>
                        
                        <div class="dental-col-3">
                            <div class="form-group">
                                <label for="state"><?php esc_html_e('Estado/Provincia', 'dental-directory-system'); ?></label>
                                <input type="text" id="state" name="state" value="<?php echo esc_attr( isset($profile['state']) ? $profile['state'] : '' ); ?>">
                            </div>
                        </div>
                        
                        <div class="dental-col-3">
                            <div class="form-group">
                                <label for="country"><?php esc_html_e('País', 'dental-directory-system'); ?></label>
                                <input type="text" id="country" name="country" value="<?php echo esc_attr( isset($profile['country']) ? $profile['country'] : '' ); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="dental-btn dental-btn-prev"><?php esc_html_e('Anterior', 'dental-directory-system'); ?></button>
                        <button type="button" class="dental-btn dental-btn-next"><?php esc_html_e('Siguiente', 'dental-directory-system'); ?></button>
                    </div>
                </div>
                
                <!-- Profile Photo Tab -->
                <div id="photos" class="dental-tab-panel">
                    <h2><?php esc_html_e('Foto de Perfil', 'dental-directory-system'); ?></h2>
                    
                    <div class="form-group">
                        <label><?php esc_html_e('Foto de Perfil', 'dental-directory-system'); ?></label>
                        <div class="dental-profile-image-container">
                            <div class="dental-profile-image">
                                <?php if ( isset($profile['profile_image']) && !empty($profile['profile_image']) ) : ?>
                                    <img src="<?php echo esc_url( $profile['profile_image'] ); ?>" alt="<?php esc_attr_e('Foto de Perfil', 'dental-directory-system'); ?>">
                                <?php else : ?>
                                    <div class="dental-profile-image-placeholder">
                                        <i class="dashicons dashicons-admin-users"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="dental-profile-image-actions">
                                <input type="file" id="profile_image_upload" name="profile_image" accept="image/*" style="display: none;">
                                <button type="button" id="profile_image_upload_btn" class="dental-btn dental-btn-small"><?php esc_html_e('Subir Foto', 'dental-directory-system'); ?></button>
                                <?php if ( isset($profile['profile_image']) && !empty($profile['profile_image']) ) : ?>
                                    <button type="button" id="profile_image_delete_btn" class="dental-btn dental-btn-small dental-btn-danger"><?php esc_html_e('Eliminar', 'dental-directory-system'); ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="dental-field-desc"><?php esc_html_e('Tamaño recomendado: 300x300 píxeles. Esta imagen solo será visible para los dentistas con los que te comuniques.', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="dental-btn dental-btn-prev"><?php esc_html_e('Anterior', 'dental-directory-system'); ?></button>
                        <button type="button" class="dental-btn dental-btn-next"><?php esc_html_e('Siguiente', 'dental-directory-system'); ?></button>
                    </div>
                </div>
                
                <!-- Preferences Tab -->
                <div id="preferences" class="dental-tab-panel">
                    <h2><?php esc_html_e('Preferencias', 'dental-directory-system'); ?></h2>
                    
                    <div class="form-group">
                        <label for="dental_concerns"><?php esc_html_e('Motivos de consulta frecuentes', 'dental-directory-system'); ?></label>
                        <textarea id="dental_concerns" name="dental_concerns" rows="3"><?php echo esc_textarea( isset($profile['dental_concerns']) ? $profile['dental_concerns'] : '' ); ?></textarea>
                        <p class="dental-field-desc"><?php esc_html_e('Describe tus preocupaciones dentales habituales, como sensibilidad, dolor, estética, etc.', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="dental-btn dental-btn-prev"><?php esc_html_e('Anterior', 'dental-directory-system'); ?></button>
                        <?php wp_nonce_field('dental_profile_nonce', 'profile_nonce'); ?>
                        <button type="submit" class="dental-btn dental-btn-primary"><?php esc_html_e('Guardar Perfil', 'dental-directory-system'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer template
$template_loader->get_template_part('partials/footer');
?>
