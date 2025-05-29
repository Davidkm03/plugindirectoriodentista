/**
 * Dental Directory System - Authentication JavaScript
 */
(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        initLoginForm();
        initRegistrationForms();
        initPasswordRecoveryForms();
        initLogoutLinks();
    });
    
    /**
     * Initialize login form with AJAX submission
     */
    function initLoginForm() {
        const $loginForm = $('#dental-login-form');
        
        if (!$loginForm.length) return;
        
        $loginForm.on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $message = $('.dental-form-message');
            
            // Disable submit button and show loading state
            $submitBtn.prop('disabled', true).addClass('loading');
            $submitBtn.html('<span class="dental-spinner"></span> Iniciando sesión...');
            
            // Clear previous messages
            $message.removeClass('dental-alert-error dental-alert-success').hide();
            
            // Get form data
            const formData = new FormData($form[0]);
            formData.append('action', 'dental_login');
            
            // Send AJAX request
            $.ajax({
                url: dental_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $message.addClass('dental-alert-success').html(response.data.message).fadeIn();
                        
                        // Redirect after short delay
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        // Show error message
                        $message.addClass('dental-alert-error').html(response.data.message).fadeIn();
                        $submitBtn.prop('disabled', false).removeClass('loading');
                        $submitBtn.html('Iniciar sesión');
                    }
                },
                error: function() {
                    // Show generic error message
                    $message.addClass('dental-alert-error').html('Error en el servidor. Inténtalo de nuevo.').fadeIn();
                    $submitBtn.prop('disabled', false).removeClass('loading');
                    $submitBtn.html('Iniciar sesión');
                }
            });
        });
    }
    
    /**
     * Initialize registration forms with AJAX submission
     */
    function initRegistrationForms() {
        const $dentistForm = $('#dental-register-dentist-form');
        const $patientForm = $('#dental-register-patient-form');
        
        // Initialize dentist registration form
        if ($dentistForm.length) {
            initRegistrationForm($dentistForm, 'dental_register_dentist');
        }
        
        // Initialize patient registration form
        if ($patientForm.length) {
            initRegistrationForm($patientForm, 'dental_register_patient');
        }
    }
    
    /**
     * Initialize a single registration form
     * 
     * @param {jQuery} $form Form element
     * @param {string} action AJAX action name
     */
    function initRegistrationForm($form, action) {
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const $submitBtn = $form.find('button[type="submit"]');
            const $message = $form.closest('.dental-form').find('.dental-form-message');
            
            // Validate password match
            const password = $form.find('input[name="password"]').val();
            const confirmPassword = $form.find('input[name="password_confirm"]').val();
            
            if (password !== confirmPassword) {
                $message.addClass('dental-alert-error').html('Las contraseñas no coinciden').fadeIn();
                return;
            }
            
            // Disable submit button and show loading state
            $submitBtn.prop('disabled', true).addClass('loading');
            $submitBtn.html('<span class="dental-spinner"></span> Registrando...');
            
            // Clear previous messages
            $message.removeClass('dental-alert-error dental-alert-success').hide();
            
            // Get form data
            const formData = new FormData($form[0]);
            formData.append('action', action);
            
            // Send AJAX request
            $.ajax({
                url: dental_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $message.addClass('dental-alert-success').html(response.data.message).fadeIn();
                        
                        // Reset form
                        $form[0].reset();
                        
                        // Redirect after short delay
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    } else {
                        // Show error message
                        $message.addClass('dental-alert-error').html(response.data.message).fadeIn();
                        $submitBtn.prop('disabled', false).removeClass('loading');
                        $submitBtn.html('Registrarse');
                    }
                },
                error: function() {
                    // Show generic error message
                    $message.addClass('dental-alert-error').html('Error en el servidor. Inténtalo de nuevo.').fadeIn();
                    $submitBtn.prop('disabled', false).removeClass('loading');
                    $submitBtn.html('Registrarse');
                }
            });
        });
    }
    
    /**
     * Initialize password recovery forms
     */
    function initPasswordRecoveryForms() {
        const $recoveryForm = $('#dental-recovery-form');
        const $resetForm = $('#dental-reset-password-form');
        
        // Initialize recovery form
        if ($recoveryForm.length) {
            $recoveryForm.on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $form.find('button[type="submit"]');
                const $message = $('.dental-form-message');
                
                // Disable submit button and show loading state
                $submitBtn.prop('disabled', true).addClass('loading');
                $submitBtn.html('<span class="dental-spinner"></span> Enviando...');
                
                // Clear previous messages
                $message.removeClass('dental-alert-error dental-alert-success').hide();
                
                // Get form data
                const formData = new FormData($form[0]);
                formData.append('action', 'dental_recover_password');
                
                // Send AJAX request
                $.ajax({
                    url: dental_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            $message.addClass('dental-alert-success').html(response.data.message).fadeIn();
                            
                            // Reset form
                            $form[0].reset();
                        } else {
                            // Show error message
                            $message.addClass('dental-alert-error').html(response.data.message).fadeIn();
                        }
                        
                        $submitBtn.prop('disabled', false).removeClass('loading');
                        $submitBtn.html('Enviar enlace');
                    },
                    error: function() {
                        // Show generic error message
                        $message.addClass('dental-alert-error').html('Error en el servidor. Inténtalo de nuevo.').fadeIn();
                        $submitBtn.prop('disabled', false).removeClass('loading');
                        $submitBtn.html('Enviar enlace');
                    }
                });
            });
        }
        
        // Initialize reset password form
        if ($resetForm.length) {
            $resetForm.on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $form.find('button[type="submit"]');
                const $message = $('.dental-form-message');
                
                // Validate password match
                const password = $form.find('input[name="password"]').val();
                const confirmPassword = $form.find('input[name="password_confirm"]').val();
                
                if (password !== confirmPassword) {
                    $message.addClass('dental-alert-error').html('Las contraseñas no coinciden').fadeIn();
                    return;
                }
                
                // Disable submit button and show loading state
                $submitBtn.prop('disabled', true).addClass('loading');
                $submitBtn.html('<span class="dental-spinner"></span> Restableciendo...');
                
                // Clear previous messages
                $message.removeClass('dental-alert-error dental-alert-success').hide();
                
                // Get form data
                const formData = new FormData($form[0]);
                formData.append('action', 'dental_reset_password');
                
                // Send AJAX request
                $.ajax({
                    url: dental_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            $message.addClass('dental-alert-success').html(response.data.message).fadeIn();
                            
                            // Redirect after short delay
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        } else {
                            // Show error message
                            $message.addClass('dental-alert-error').html(response.data.message).fadeIn();
                            $submitBtn.prop('disabled', false).removeClass('loading');
                            $submitBtn.html('Restablecer contraseña');
                        }
                    },
                    error: function() {
                        // Show generic error message
                        $message.addClass('dental-alert-error').html('Error en el servidor. Inténtalo de nuevo.').fadeIn();
                        $submitBtn.prop('disabled', false).removeClass('loading');
                        $submitBtn.html('Restablecer contraseña');
                    }
                });
            });
        }
    }
    
    /**
     * Initialize logout links
     */
    function initLogoutLinks() {
        $('.dental-logout-link').on('click', function(e) {
            e.preventDefault();
            
            const $link = $(this);
            const redirect = $link.data('redirect') || window.location.href;
            
            // Show loading state
            $link.addClass('loading');
            $link.html('<span class="dental-spinner"></span> Cerrando sesión...');
            
            // Send AJAX request
            $.ajax({
                url: dental_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dental_logout',
                    security: dental_ajax.nonce,
                    redirect: redirect
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to specified URL
                        window.location.href = response.data.redirect;
                    } else {
                        // Fallback to standard logout
                        window.location.href = dental_ajax.logout_url;
                    }
                },
                error: function() {
                    // Fallback to standard logout
                    window.location.href = dental_ajax.logout_url;
                }
            });
        });
    }

})(jQuery);
