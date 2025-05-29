<?php
/**
 * Dentist Profile Edit Template
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
$profile = $profile_manager->get_dentist_profile( $user_id );

// Include header template
$template_loader->get_template_part('partials/header');
?>

<div class="dental-container dental-profile-edit">
    <h1><?php esc_html_e('Editar Perfil', 'dental-directory-system'); ?></h1>
    
    <div class="dental-alert dental-form-message" style="display: none;"></div>
    
    <div class="dental-tabs">
        <ul class="dental-tabs-nav">
            <li class="active"><a href="#basic-info"><?php esc_html_e('Información Básica', 'dental-directory-system'); ?></a></li>
            <li><a href="#professional-info"><?php esc_html_e('Información Profesional', 'dental-directory-system'); ?></a></li>
            <li><a href="#contact-info"><?php esc_html_e('Contacto', 'dental-directory-system'); ?></a></li>
            <li><a href="#photos"><?php esc_html_e('Fotos', 'dental-directory-system'); ?></a></li>
            <li><a href="#social-media"><?php esc_html_e('Redes Sociales', 'dental-directory-system'); ?></a></li>
        </ul>
        
        <div class="dental-tabs-content">
            <form id="dental-dentist-profile-form" method="post">
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
                        <label for="bio"><?php esc_html_e('Biografía', 'dental-directory-system'); ?></label>
                        <textarea id="bio" name="bio" rows="5"><?php echo esc_textarea( isset($profile['bio']) ? $profile['bio'] : '' ); ?></textarea>
                        <p class="dental-field-desc"><?php esc_html_e('Una breve descripción sobre ti que se mostrará en tu perfil público.', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="dental-btn dental-btn-next"><?php esc_html_e('Siguiente', 'dental-directory-system'); ?></button>
                    </div>
                </div>
                
                <!-- Professional Information Tab -->
                <div id="professional-info" class="dental-tab-panel">
                    <h2><?php esc_html_e('Información Profesional', 'dental-directory-system'); ?></h2>
                    
                    <div class="dental-row">
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="speciality"><?php esc_html_e('Especialidad', 'dental-directory-system'); ?> *</label>
                                <select id="speciality" name="speciality" required>
                                    <option value=""><?php esc_html_e('Selecciona Especialidad', 'dental-directory-system'); ?></option>
                                    <option value="general" <?php selected( isset($profile['speciality']) ? $profile['speciality'] : '', 'general' ); ?>><?php esc_html_e('Odontología General', 'dental-directory-system'); ?></option>
                                    <option value="orthodontics" <?php selected( isset($profile['speciality']) ? $profile['speciality'] : '', 'orthodontics' ); ?>><?php esc_html_e('Ortodoncia', 'dental-directory-system'); ?></option>
                                    <option value="pediatric" <?php selected( isset($profile['speciality']) ? $profile['speciality'] : '', 'pediatric' ); ?>><?php esc_html_e('Odontopediatría', 'dental-directory-system'); ?></option>
                                    <option value="periodontics" <?php selected( isset($profile['speciality']) ? $profile['speciality'] : '', 'periodontics' ); ?>><?php esc_html_e('Periodoncia', 'dental-directory-system'); ?></option>
                                    <option value="endodontics" <?php selected( isset($profile['speciality']) ? $profile['speciality'] : '', 'endodontics' ); ?>><?php esc_html_e('Endodoncia', 'dental-directory-system'); ?></option>
                                    <option value="oral_surgery" <?php selected( isset($profile['speciality']) ? $profile['speciality'] : '', 'oral_surgery' ); ?>><?php esc_html_e('Cirugía Oral', 'dental-directory-system'); ?></option>
                                    <option value="prosthodontics" <?php selected( isset($profile['speciality']) ? $profile['speciality'] : '', 'prosthodontics' ); ?>><?php esc_html_e('Prostodoncia', 'dental-directory-system'); ?></option>
                                    <option value="implantology" <?php selected( isset($profile['speciality']) ? $profile['speciality'] : '', 'implantology' ); ?>><?php esc_html_e('Implantología', 'dental-directory-system'); ?></option>
                                    <option value="other" <?php selected( isset($profile['speciality']) ? $profile['speciality'] : '', 'other' ); ?>><?php esc_html_e('Otra', 'dental-directory-system'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="license"><?php esc_html_e('Número de Licencia', 'dental-directory-system'); ?> *</label>
                                <input type="text" id="license" name="license" value="<?php echo esc_attr( isset($profile['license']) ? $profile['license'] : '' ); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="education"><?php esc_html_e('Educación', 'dental-directory-system'); ?></label>
                        <textarea id="education" name="education" rows="3"><?php echo esc_textarea( isset($profile['education']) ? $profile['education'] : '' ); ?></textarea>
                        <p class="dental-field-desc"><?php esc_html_e('Incluye tu educación y certificaciones.', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="experience"><?php esc_html_e('Experiencia', 'dental-directory-system'); ?></label>
                        <textarea id="experience" name="experience" rows="3"><?php echo esc_textarea( isset($profile['experience']) ? $profile['experience'] : '' ); ?></textarea>
                        <p class="dental-field-desc"><?php esc_html_e('Describe tu experiencia profesional.', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="services"><?php esc_html_e('Servicios', 'dental-directory-system'); ?></label>
                        <textarea id="services" name="services" rows="3"><?php echo esc_textarea( isset($profile['services']) ? $profile['services'] : '' ); ?></textarea>
                        <p class="dental-field-desc"><?php esc_html_e('Lista los servicios que ofreces.', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="languages"><?php esc_html_e('Idiomas', 'dental-directory-system'); ?></label>
                        <input type="text" id="languages" name="languages" value="<?php echo esc_attr( isset($profile['languages']) ? $profile['languages'] : '' ); ?>">
                        <p class="dental-field-desc"><?php esc_html_e('Idiomas que hablas, separados por comas.', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="dental-btn dental-btn-prev"><?php esc_html_e('Anterior', 'dental-directory-system'); ?></button>
                        <button type="button" class="dental-btn dental-btn-next"><?php esc_html_e('Siguiente', 'dental-directory-system'); ?></button>
                    </div>
                </div>
                
                <!-- Contact Information Tab -->
                <div id="contact-info" class="dental-tab-panel">
                    <h2><?php esc_html_e('Información de Contacto', 'dental-directory-system'); ?></h2>
                    
                    <div class="form-group">
                        <label for="clinic_name"><?php esc_html_e('Nombre de la Clínica', 'dental-directory-system'); ?></label>
                        <input type="text" id="clinic_name" name="clinic_name" value="<?php echo esc_attr( isset($profile['clinic_name']) ? $profile['clinic_name'] : '' ); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address"><?php esc_html_e('Dirección', 'dental-directory-system'); ?></label>
                        <input type="text" id="address" name="address" value="<?php echo esc_attr( isset($profile['address']) ? $profile['address'] : '' ); ?>">
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
                    
                    <div class="dental-row">
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="phone"><?php esc_html_e('Teléfono', 'dental-directory-system'); ?></label>
                                <input type="tel" id="phone" name="phone" value="<?php echo esc_attr( isset($profile['phone']) ? $profile['phone'] : '' ); ?>">
                            </div>
                        </div>
                        
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label for="website"><?php esc_html_e('Sitio Web', 'dental-directory-system'); ?></label>
                                <input type="url" id="website" name="website" value="<?php echo esc_attr( isset($profile['website']) ? $profile['website'] : '' ); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="working_hours"><?php esc_html_e('Horario de Atención', 'dental-directory-system'); ?></label>
                        <textarea id="working_hours" name="working_hours" rows="3"><?php echo esc_textarea( isset($profile['working_hours']) ? $profile['working_hours'] : '' ); ?></textarea>
                        <p class="dental-field-desc"><?php esc_html_e('Ejemplo: Lunes a Viernes 9:00 - 18:00, Sábados 9:00 - 13:00', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="dental-btn dental-btn-prev"><?php esc_html_e('Anterior', 'dental-directory-system'); ?></button>
                        <button type="button" class="dental-btn dental-btn-next"><?php esc_html_e('Siguiente', 'dental-directory-system'); ?></button>
                    </div>
                </div>
                
                <!-- Photos Tab -->
                <div id="photos" class="dental-tab-panel">
                    <h2><?php esc_html_e('Fotos', 'dental-directory-system'); ?></h2>
                    
                    <div class="dental-row">
                        <div class="dental-col-2">
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
                                <p class="dental-field-desc"><?php esc_html_e('Tamaño recomendado: 300x300 píxeles.', 'dental-directory-system'); ?></p>
                            </div>
                        </div>
                        
                        <div class="dental-col-2">
                            <div class="form-group">
                                <label><?php esc_html_e('Imagen de Portada', 'dental-directory-system'); ?></label>
                                <div class="dental-cover-image-container">
                                    <div class="dental-cover-image">
                                        <?php if ( isset($profile['cover_image']) && !empty($profile['cover_image']) ) : ?>
                                            <img src="<?php echo esc_url( $profile['cover_image'] ); ?>" alt="<?php esc_attr_e('Imagen de Portada', 'dental-directory-system'); ?>">
                                        <?php else : ?>
                                            <div class="dental-cover-image-placeholder">
                                                <i class="dashicons dashicons-format-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dental-cover-image-actions">
                                        <input type="file" id="cover_image_upload" name="cover_image" accept="image/*" style="display: none;">
                                        <button type="button" id="cover_image_upload_btn" class="dental-btn dental-btn-small"><?php esc_html_e('Subir Imagen', 'dental-directory-system'); ?></button>
                                        <?php if ( isset($profile['cover_image']) && !empty($profile['cover_image']) ) : ?>
                                            <button type="button" id="cover_image_delete_btn" class="dental-btn dental-btn-small dental-btn-danger"><?php esc_html_e('Eliminar', 'dental-directory-system'); ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="dental-field-desc"><?php esc_html_e('Tamaño recomendado: 1200x300 píxeles.', 'dental-directory-system'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?php esc_html_e('Galería de Imágenes', 'dental-directory-system'); ?></label>
                        <div class="dental-gallery-container">
                            <div class="dental-gallery-images">
                                <?php 
                                $gallery = isset($profile['gallery_images']) ? $profile['gallery_images'] : array();
                                if ( !empty($gallery) && is_array($gallery) ) :
                                    foreach ( $gallery as $image ) : 
                                ?>
                                    <div class="dental-gallery-item" data-id="<?php echo esc_attr($image['attachment_id']); ?>">
                                        <div class="dental-gallery-image">
                                            <img src="<?php echo esc_url($image['url']); ?>" alt="">
                                        </div>
                                        <div class="dental-gallery-actions">
                                            <button type="button" class="dental-btn dental-btn-small dental-btn-danger dental-gallery-delete"><?php esc_html_e('Eliminar', 'dental-directory-system'); ?></button>
                                        </div>
                                    </div>
                                <?php 
                                    endforeach; 
                                endif; 
                                ?>
                                <div class="dental-gallery-add">
                                    <input type="file" id="gallery_image_upload" name="gallery_image" accept="image/*" style="display: none;">
                                    <button type="button" id="gallery_image_upload_btn" class="dental-btn">
                                        <i class="dashicons dashicons-plus"></i>
                                        <?php esc_html_e('Añadir Imagen', 'dental-directory-system'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p class="dental-field-desc"><?php esc_html_e('Sube imágenes de tu clínica, tratamientos, etc.', 'dental-directory-system'); ?></p>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="dental-btn dental-btn-prev"><?php esc_html_e('Anterior', 'dental-directory-system'); ?></button>
                        <button type="button" class="dental-btn dental-btn-next"><?php esc_html_e('Siguiente', 'dental-directory-system'); ?></button>
                    </div>
                </div>
                
                <!-- Social Media Tab -->
                <div id="social-media" class="dental-tab-panel">
                    <h2><?php esc_html_e('Redes Sociales', 'dental-directory-system'); ?></h2>
                    
                    <div class="form-group">
                        <label for="social_facebook">
                            <i class="dashicons dashicons-facebook"></i>
                            <?php esc_html_e('Facebook', 'dental-directory-system'); ?>
                        </label>
                        <input type="url" id="social_facebook" name="social_facebook" value="<?php echo esc_attr( isset($profile['social_facebook']) ? $profile['social_facebook'] : '' ); ?>" placeholder="https://facebook.com/tuclínica">
                    </div>
                    
                    <div class="form-group">
                        <label for="social_twitter">
                            <i class="dashicons dashicons-twitter"></i>
                            <?php esc_html_e('Twitter', 'dental-directory-system'); ?>
                        </label>
                        <input type="url" id="social_twitter" name="social_twitter" value="<?php echo esc_attr( isset($profile['social_twitter']) ? $profile['social_twitter'] : '' ); ?>" placeholder="https://twitter.com/tuclínica">
                    </div>
                    
                    <div class="form-group">
                        <label for="social_instagram">
                            <i class="dashicons dashicons-instagram"></i>
                            <?php esc_html_e('Instagram', 'dental-directory-system'); ?>
                        </label>
                        <input type="url" id="social_instagram" name="social_instagram" value="<?php echo esc_attr( isset($profile['social_instagram']) ? $profile['social_instagram'] : '' ); ?>" placeholder="https://instagram.com/tuclínica">
                    </div>
                    
                    <div class="form-group">
                        <label for="social_linkedin">
                            <i class="dashicons dashicons-linkedin"></i>
                            <?php esc_html_e('LinkedIn', 'dental-directory-system'); ?>
                        </label>
                        <input type="url" id="social_linkedin" name="social_linkedin" value="<?php echo esc_attr( isset($profile['social_linkedin']) ? $profile['social_linkedin'] : '' ); ?>" placeholder="https://linkedin.com/in/tunombre">
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
