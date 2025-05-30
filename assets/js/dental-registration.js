/**
 * Registration JavaScript
 * 
 * Handles all registration form functionality including multi-step forms,
 * real-time validations, and email verification UX
 */
(function($) {
    'use strict';
    
    // Variables for field validation
    const validationMessages = {
        username: {
            checking: dental_vars.texts.username_checking || 'Checking username availability...',
            available: dental_vars.texts.username_available || 'Username is available',
            notAvailable: dental_vars.texts.username_not_available || 'Username is already taken',
            invalid: dental_vars.texts.username_invalid || 'Username must be at least 4 characters and contain only letters, numbers, and underscores',
            required: dental_vars.texts.username_required || 'Username is required'
        },
        email: {
            checking: dental_vars.texts.email_checking || 'Checking email...',
            available: dental_vars.texts.email_available || 'Email is valid',
            notAvailable: dental_vars.texts.email_not_available || 'Email is already registered',
            invalid: dental_vars.texts.email_invalid || 'Please enter a valid email address',
            required: dental_vars.texts.email_required || 'Email is required'
        },
        password: {
            weak: dental_vars.texts.password_weak || 'Password is too weak',
            medium: dental_vars.texts.password_medium || 'Password strength: medium',
            strong: dental_vars.texts.password_strong || 'Password strength: strong',
            veryStrong: dental_vars.texts.password_very_strong || 'Password strength: very strong',
            required: dental_vars.texts.password_required || 'Password is required'
        },
        password_confirm: {
            match: dental_vars.texts.password_match || 'Passwords match',
            notMatch: dental_vars.texts.password_not_match || 'Passwords do not match',
            required: dental_vars.texts.password_confirm_required || 'Please confirm your password'
        }
    };
    
    // Field validation delays
    const validationDelays = {
        username: 500,
        email: 500
    };
    
    // Validation timeouts
    let validationTimeouts = {};
    
    // Validation status
    let fieldValidStatus = {};

    // Dentist registration form handler
    function initDentistRegistration() {
        const form = $('#dental-register-dentist-form');
        
        if (!form.length) {
            return;
        }

        form.on('submit', function(e) {
            e.preventDefault();
            
            // Reset form errors
            $('.form-group').removeClass('has-error');
            $('.dental-form-message').hide().removeClass('dental-alert-error dental-alert-success');
            
            // Get form data
            const formData = new FormData(this);
            formData.append('action', 'dental_register_dentist');
            
            // Disable submit button
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.text();
            submitBtn.prop('disabled', true).text(dental_vars.texts.processing);
            
            // Send AJAX request
            $.ajax({
                url: dental_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('.dental-form-message')
                            .addClass('dental-alert-success')
                            .html(response.data.message)
                            .show();
                        
                        // Clear form
                        form[0].reset();
                        
                        // Redirect after delay
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        }
                    } else {
                        // Show error message
                        $('.dental-form-message')
                            .addClass('dental-alert-error')
                            .html(response.data.message)
                            .show();
                        
                        // Highlight field with error if specified
                        if (response.data.field && response.data.field !== 'general') {
                            const fieldElement = $('#' + response.data.field);
                            if (fieldElement.length) {
                                fieldElement.closest('.form-group').addClass('has-error');
                                fieldElement.focus();
                            }
                        }
                    }
                },
                error: function() {
                    // Show generic error message
                    $('.dental-form-message')
                        .addClass('dental-alert-error')
                        .html(dental_vars.texts.server_error)
                        .show();
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });
        
        // Password strength meter
        const passwordField = $('#password');
        const confirmField = $('#password_confirm');
        const strengthMeter = $('<div class="password-strength-meter"></div>');
        
        if (passwordField.length) {
            passwordField.after(strengthMeter);
            
            passwordField.on('input', function() {
                const password = $(this).val();
                const strength = checkPasswordStrength(password);
                
                // Update strength meter
                strengthMeter.attr('data-strength', strength.level);
                strengthMeter.html(strength.message);
            });
            
            // Check passwords match
            confirmField.on('input', function() {
                const password = passwordField.val();
                const confirm = $(this).val();
                
                if (password && confirm) {
                    if (password !== confirm) {
                        $(this).closest('.form-group').addClass('has-error');
                    } else {
                        $(this).closest('.form-group').removeClass('has-error');
                    }
                }
            });
        }
    }
    
    // Patient registration form handler
    function initPatientRegistration() {
        const form = $('#dental-register-patient-form');
        
        if (!form.length) {
            return;
        }
        
        form.on('submit', function(e) {
            e.preventDefault();
            
            // Reset form errors
            $('.form-group').removeClass('has-error');
            $('.dental-form-message').hide().removeClass('dental-alert-error dental-alert-success');
            
            // Get form data
            const formData = new FormData(this);
            formData.append('action', 'dental_register_patient');
            
            // Disable submit button
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.text();
            submitBtn.prop('disabled', true).text(dental_vars.texts.processing);
            
            // Send AJAX request
            $.ajax({
                url: dental_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('.dental-form-message')
                            .addClass('dental-alert-success')
                            .html(response.data.message)
                            .show();
                        
                        // Clear form
                        form[0].reset();
                        
                        // Redirect after delay
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 2000);
                        }
                    } else {
                        // Show error message
                        $('.dental-form-message')
                            .addClass('dental-alert-error')
                            .html(response.data.message)
                            .show();
                        
                        // Highlight field with error if specified
                        if (response.data.field && response.data.field !== 'general') {
                            const fieldElement = $('#' + response.data.field);
                            if (fieldElement.length) {
                                fieldElement.closest('.form-group').addClass('has-error');
                                fieldElement.focus();
                            }
                        }
                    }
                },
                error: function() {
                    // Show generic error message
                    $('.dental-form-message')
                        .addClass('dental-alert-error')
                        .html(dental_vars.texts.server_error)
                        .show();
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });
        
        // Password strength meter
        const passwordField = $('#password');
        const confirmField = $('#password_confirm');
        const strengthMeter = $('<div class="password-strength-meter"></div>');
        
        if (passwordField.length) {
            passwordField.after(strengthMeter);
            
            passwordField.on('input', function() {
                const password = $(this).val();
                const strength = checkPasswordStrength(password);
                
                // Update strength meter
                strengthMeter.attr('data-strength', strength.level);
                strengthMeter.html(strength.message);
            });
            
            // Check passwords match
            confirmField.on('input', function() {
                const password = passwordField.val();
                const confirm = $(this).val();
                
                if (password && confirm) {
                    if (password !== confirm) {
                        $(this).closest('.form-group').addClass('has-error');
                    } else {
                        $(this).closest('.form-group').removeClass('has-error');
                    }
                }
            });
        }
    }
    
    // Resend verification email handler
    function initResendVerification() {
        $(document).on('click', '.dental-resend-verification', function(e) {
            e.preventDefault();
            
            const userId = $(this).data('user');
            const button = $(this);
            const originalText = button.text();
            
            // Disable button
            button.text(dental_vars.texts.processing).addClass('disabled');
            
            // Send AJAX request
            $.ajax({
                url: dental_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'dental_resend_verification',
                    user_id: userId,
                    security: dental_vars.resend_nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        button.after('<p class="dental-alert dental-alert-success">' + response.data.message + '</p>');
                        button.remove();
                    } else {
                        // Show error message
                        button.after('<p class="dental-alert dental-alert-error">' + response.data.message + '</p>');
                        button.text(originalText).removeClass('disabled');
                    }
                },
                error: function() {
                    // Show generic error message
                    button.after('<p class="dental-alert dental-alert-error">' + dental_vars.texts.server_error + '</p>');
                    button.text(originalText).removeClass('disabled');
                }
            });
        });
        
        // Handle verification notification
        if (window.location.search.indexOf('verification_sent=1') !== -1) {
            $('.dental-verification-notice').show();
        }
        
        if (window.location.search.indexOf('verified=1') !== -1) {
            $('.dental-verified-notice').show();
        }
    }
    
    // Check password strength
    function checkPasswordStrength(password) {
        let strength = {
            level: 0,
            message: ''
        };
        
        if (!password) {
            strength.message = dental_vars.texts.password_empty;
            return strength;
        }
        
        // Calculate strength
        let score = 0;
        
        // Length check
        if (password.length < 8) {
            strength.level = 1;
            strength.message = dental_vars.texts.password_short;
            return strength;
        } else {
            score += 1;
        }
        
        // Check for mixed case
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
            score += 1;
        }
        
        // Check for numbers
        if (password.match(/\d/)) {
            score += 1;
        }
        
        // Check for special characters
        if (password.match(/[^a-zA-Z\d]/)) {
            score += 1;
        }
        
        // Determine strength level
        if (score === 1) {
            strength.level = 1; // Weak
            strength.message = dental_vars.texts.password_weak;
        } else if (score === 2) {
            strength.level = 2; // Medium
            strength.message = dental_vars.texts.password_medium;
        } else if (score === 3) {
            strength.level = 3; // Strong
            strength.message = dental_vars.texts.password_strong;
        } else {
            strength.level = 4; // Very strong
            strength.message = dental_vars.texts.password_very_strong;
        }
        
        return strength;
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initDentistRegistration();
        initPatientRegistration();
        initResendVerification();
    });

})(jQuery);
