/**
 * Registration JavaScript (Enhanced)
 * 
 * Handles all registration form functionality including multi-step forms,
 * real-time validations, and email verification UX
 */
(function($) {
    'use strict';
    
    // Variables for field validation
    const validationMessages = {
        username: {
            checking: 'Checking username availability...',
            available: 'Username is available',
            notAvailable: 'Username is already taken',
            invalid: 'Username must be at least 4 characters and contain only letters, numbers, and underscores',
            required: 'Username is required'
        },
        email: {
            checking: 'Checking email...',
            available: 'Email is valid',
            notAvailable: 'Email is already registered',
            invalid: 'Please enter a valid email address',
            required: 'Email is required'
        },
        password: {
            weak: 'Password is too weak',
            medium: 'Password strength: medium',
            strong: 'Password strength: strong',
            veryStrong: 'Password strength: very strong',
            required: 'Password is required'
        },
        password_confirm: {
            match: 'Passwords match',
            notMatch: 'Passwords do not match',
            required: 'Please confirm your password'
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
            
            // Validate all required fields before submission
            const currentStep = $('.dental-form-step.active');
            if (!validateStepFields(currentStep)) {
                return false;
            }
            
            // Reset form errors
            $('.form-group').removeClass('has-error');
            $('.dental-form-message').hide().removeClass('dental-alert-error dental-alert-success');
            
            // Get form data
            const formData = new FormData(this);
            formData.append('action', 'dental_register_dentist');
            
            // Disable submit button
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Processing...');
            
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
                        .html('Server error. Please try again later.')
                        .show();
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });
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
            submitBtn.prop('disabled', true).text('Processing...');
            
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
                        .html('Server error. Please try again later.')
                        .show();
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });
    }
    
    // Multi-step form navigation handler
    function initMultiStepForm() {
        // Handle next button clicks
        $('.dental-btn-next').on('click', function() {
            const currentStep = parseInt($(this).data('step'));
            const nextStep = currentStep + 1;
            const currentStepElem = $('.dental-form-step[data-step="' + currentStep + '"]');
            
            // Validate current step fields before proceeding
            if (!validateStepFields(currentStepElem)) {
                return false;
            }
            
            // Update progress bar and indicators
            updateProgressBar(nextStep);
            
            // Hide current step and show next step
            $('.dental-form-step').removeClass('active');
            $('.dental-form-step[data-step="' + nextStep + '"]').addClass('active');
            
            // Scroll to top of form
            scrollToForm();
            
            return false;
        });
        
        // Handle previous button clicks
        $('.dental-btn-prev').on('click', function() {
            const currentStep = parseInt($(this).data('step'));
            const prevStep = currentStep - 1;
            
            // Update progress bar and indicators
            updateProgressBar(prevStep);
            
            // Hide current step and show previous step
            $('.dental-form-step').removeClass('active');
            $('.dental-form-step[data-step="' + prevStep + '"]').addClass('active');
            
            // Scroll to top of form
            scrollToForm();
            
            return false;
        });
    }
    
    // Update progress bar and step indicators
    function updateProgressBar(activeStep) {
        const totalSteps = $('.dental-progress-step').length;
        const progressPercentage = ((activeStep - 1) / (totalSteps - 1)) * 100;
        
        // Update progress bar width
        $('.dental-progress').css('width', progressPercentage + '%');
        
        // Update step indicators
        $('.dental-progress-step').removeClass('active completed');
        
        // Mark completed steps
        for (let i = 1; i < activeStep; i++) {
            $('.dental-progress-step[data-step="' + i + '"]').addClass('completed');
        }
        
        // Mark active step
        $('.dental-progress-step[data-step="' + activeStep + '"]').addClass('active');
    }
    
    // Validate all fields in a step
    function validateStepFields(stepElement) {
        let isValid = true;
        
        // Check all required fields in this step
        stepElement.find('input[required], select[required], textarea[required]').each(function() {
            const field = $(this);
            const fieldId = field.attr('id');
            const validationElement = field.siblings('.field-validation');
            
            if (!field.val()) {
                isValid = false;
                validationElement.text(field.attr('placeholder') || 'This field is required').removeClass('valid').addClass('invalid');
                field.closest('.form-group').addClass('has-error');
            } else if (field.data('validate') && !fieldValidStatus[fieldId]) {
                // If field has validation and hasn't passed it yet
                isValid = false;
                field.closest('.form-group').addClass('has-error');
            }
        });
        
        // Special handling for checkbox validation
        stepElement.find('input[type="checkbox"][required]').each(function() {
            const field = $(this);
            const validationElement = field.closest('.form-group').find('.field-validation');
            
            if (!field.is(':checked')) {
                isValid = false;
                validationElement.text('You must agree to continue').removeClass('valid').addClass('invalid');
                field.closest('.form-group').addClass('has-error');
            }
        });
        
        return isValid;
    }
    
    // Handle field validations
    function initFieldValidations() {
        // Username validation
        $('input[data-validate="username"]').on('input', function() {
            const field = $(this);
            const fieldId = field.attr('id');
            const username = field.val();
            const validationElement = field.siblings('.field-validation');
            
            // Clear previous validation status
            fieldValidStatus[fieldId] = false;
            
            // Clear any existing timeout
            if (validationTimeouts[fieldId]) {
                clearTimeout(validationTimeouts[fieldId]);
            }
            
            // Basic format validation
            if (!username) {
                validationElement.text(validationMessages.username.required).removeClass('valid').addClass('invalid');
                return;
            }
            
            const usernamePattern = /^[a-zA-Z0-9_]{4,}$/;
            if (!usernamePattern.test(username)) {
                validationElement.text(validationMessages.username.invalid).removeClass('valid').addClass('invalid');
                return;
            }
            
            // Show checking message
            validationElement.text(validationMessages.username.checking).removeClass('valid invalid');
            
            // Set timeout for server validation
            validationTimeouts[fieldId] = setTimeout(function() {
                // Check username availability via AJAX
                $.ajax({
                    url: dental_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dental_check_username',
                        username: username,
                        security: dental_vars.validation_nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            validationElement.text(validationMessages.username.available).removeClass('invalid').addClass('valid');
                            fieldValidStatus[fieldId] = true;
                            field.closest('.form-group').removeClass('has-error');
                        } else {
                            validationElement.text(validationMessages.username.notAvailable).removeClass('valid').addClass('invalid');
                            field.closest('.form-group').addClass('has-error');
                        }
                    },
                    error: function() {
                        validationElement.text('Error checking username').removeClass('valid').addClass('invalid');
                    }
                });
            }, validationDelays.username);
        });
        
        // Email validation
        $('input[data-validate="email"]').on('input', function() {
            const field = $(this);
            const fieldId = field.attr('id');
            const email = field.val();
            const validationElement = field.siblings('.field-validation');
            
            // Clear previous validation status
            fieldValidStatus[fieldId] = false;
            
            // Clear any existing timeout
            if (validationTimeouts[fieldId]) {
                clearTimeout(validationTimeouts[fieldId]);
            }
            
            // Basic format validation
            if (!email) {
                validationElement.text(validationMessages.email.required).removeClass('valid').addClass('invalid');
                return;
            }
            
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                validationElement.text(validationMessages.email.invalid).removeClass('valid').addClass('invalid');
                return;
            }
            
            // Show checking message
            validationElement.text(validationMessages.email.checking).removeClass('valid invalid');
            
            // Set timeout for server validation
            validationTimeouts[fieldId] = setTimeout(function() {
                // Check email availability via AJAX
                $.ajax({
                    url: dental_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dental_check_email',
                        email: email,
                        security: dental_vars.validation_nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            validationElement.text(validationMessages.email.available).removeClass('invalid').addClass('valid');
                            fieldValidStatus[fieldId] = true;
                            field.closest('.form-group').removeClass('has-error');
                        } else {
                            validationElement.text(validationMessages.email.notAvailable).removeClass('valid').addClass('invalid');
                            field.closest('.form-group').addClass('has-error');
                        }
                    },
                    error: function() {
                        validationElement.text('Error checking email').removeClass('valid').addClass('invalid');
                    }
                });
            }, validationDelays.email);
        });
        
        // Password validation
        $('input[data-validate="password"]').on('input', function() {
            const field = $(this);
            const fieldId = field.attr('id');
            const password = field.val();
            const validationElement = field.siblings('.field-validation');
            const strengthMeter = field.siblings('.password-strength-meter');
            
            // Clear previous validation status
            fieldValidStatus[fieldId] = false;
            
            // Basic validation
            if (!password) {
                validationElement.text(validationMessages.password.required).removeClass('valid').addClass('invalid');
                strengthMeter.attr('data-strength', '0').attr('data-text', '');
                return;
            }
            
            // Check password strength
            const strength = checkPasswordStrength(password);
            strengthMeter.attr('data-strength', strength.level).attr('data-text', strength.message);
            
            // Update validation message
            if (strength.level >= 3) {
                validationElement.text('Strong password').removeClass('invalid').addClass('valid');
                fieldValidStatus[fieldId] = true;
                field.closest('.form-group').removeClass('has-error');
            } else if (strength.level === 2) {
                validationElement.text('Password could be stronger').removeClass('invalid valid');
                fieldValidStatus[fieldId] = true;
                field.closest('.form-group').removeClass('has-error');
            } else {
                validationElement.text(validationMessages.password.weak).removeClass('valid').addClass('invalid');
                field.closest('.form-group').addClass('has-error');
            }
            
            // Check password confirmation if exists
            const confirmField = $('#password_confirm');
            if (confirmField.length && confirmField.val()) {
                confirmField.trigger('input');
            }
        });
        
        // Password confirmation validation
        $('input[data-validate="password_confirm"]').on('input', function() {
            const field = $(this);
            const fieldId = field.attr('id');
            const confirmPassword = field.val();
            const passwordField = $('#password');
            const password = passwordField.val();
            const validationElement = field.siblings('.field-validation');
            
            // Clear previous validation status
            fieldValidStatus[fieldId] = false;
            
            // Basic validation
            if (!confirmPassword) {
                validationElement.text(validationMessages.password_confirm.required).removeClass('valid').addClass('invalid');
                return;
            }
            
            // Check if passwords match
            if (confirmPassword === password) {
                validationElement.text(validationMessages.password_confirm.match).removeClass('invalid').addClass('valid');
                fieldValidStatus[fieldId] = true;
                field.closest('.form-group').removeClass('has-error');
            } else {
                validationElement.text(validationMessages.password_confirm.notMatch).removeClass('valid').addClass('invalid');
                field.closest('.form-group').addClass('has-error');
            }
        });
    }
    
    // Check password strength
    function checkPasswordStrength(password) {
        let strength = {
            level: 0,
            message: ''
        };
        
        if (!password) {
            strength.message = 'Password is required';
            return strength;
        }
        
        // Calculate strength
        let score = 0;
        
        // Length check
        if (password.length < 8) {
            strength.level = 1;
            strength.message = 'Password is too short (minimum 8 characters)';
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
            strength.message = validationMessages.password.weak;
        } else if (score === 2) {
            strength.level = 2; // Medium
            strength.message = validationMessages.password.medium;
        } else if (score === 3) {
            strength.level = 3; // Strong
            strength.message = validationMessages.password.strong;
        } else {
            strength.level = 4; // Very strong
            strength.message = validationMessages.password.veryStrong;
        }
        
        return strength;
    }
    
    // Helper function to scroll to form
    function scrollToForm() {
        $('html, body').animate({
            scrollTop: $('.dental-progress-container').offset().top - 50
        }, 300);
    }
    
    // Initialize email verification UI enhancement
    function initVerificationUI() {
        // Handle verification notification display
        if (window.location.search.indexOf('verification_sent=1') !== -1) {
            // Show verification sent UI
            $('.dental-form').hide();
            
            // Create verification UI if it doesn't exist
            if (!$('.dental-verification-container').length) {
                const verificationUI = $(
                    '<div class="dental-verification-container">' +
                    '   <div class="dental-verification-icon"><i class="dashicons dashicons-email-alt"></i></div>' +
                    '   <h2 class="dental-verification-title">Check Your Email</h2>' +
                    '   <p class="dental-verification-message">We\'ve sent a verification link to your email address. Please check your inbox and click the link to activate your account.</p>' +
                    '   <div class="dental-verification-actions">' +
                    '       <p>Didn\'t receive the email? <a href="#" class="dental-resend-verification" data-user="' + (new URLSearchParams(window.location.search).get('user_id') || '') + '">Resend verification email</a></p>' +
                    '       <div class="dental-verification-timer" style="display: none;">You can request another email in <span class="dental-countdown">60</span> seconds</div>' +
                    '   </div>' +
                    '</div>'
                );
                
                $('.dental-container').prepend(verificationUI);
            }
        }
        
        // Handle verified notification
        if (window.location.search.indexOf('verified=1') !== -1) {
            // Show verified UI
            if (!$('.dental-verified-container').length) {
                const verifiedUI = $(
                    '<div class="dental-verification-container dental-verified-container">' +
                    '   <div class="dental-verification-icon" style="color: #4CAF50;"><i class="dashicons dashicons-yes-alt"></i></div>' +
                    '   <h2 class="dental-verification-title">Email Verified!</h2>' +
                    '   <p class="dental-verification-message">Your email has been successfully verified. You can now log in to your account.</p>' +
                    '   <div class="dental-verification-actions">' +
                    '       <a href="' + (dental_vars.login_url || '#') + '" class="dental-btn dental-btn-primary">Log In Now</a>' +
                    '   </div>' +
                    '</div>'
                );
                
                $('.dental-container').prepend(verifiedUI);
            }
        }
    }
    
    // Enhanced resend verification email handler
    function enhancedResendVerification() {
        $(document).on('click', '.dental-resend-verification', function(e) {
            e.preventDefault();
            
            const userId = $(this).data('user');
            const button = $(this);
            const timerElement = $('.dental-verification-timer');
            const countdownElement = $('.dental-countdown');
            
            // Don't do anything if already waiting
            if (timerElement.is(':visible')) {
                return false;
            }
            
            // Disable button and show waiting
            button.addClass('disabled');
            
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
                        // Show success message and start countdown
                        $('.dental-verification-message').text(response.data.message || 'Verification email sent again. Please check your inbox.');
                        
                        // Show and start countdown
                        startResendCountdown(button, timerElement, countdownElement);
                    } else {
                        // Show error message
                        $('.dental-verification-message').text(response.data.message || 'There was an error sending the verification email. Please try again.');
                        button.removeClass('disabled');
                    }
                },
                error: function() {
                    // Show error message
                    $('.dental-verification-message').text('Server error. Please try again later.');
                    button.removeClass('disabled');
                }
            });
            
            return false;
        });
    }
    
    // Start countdown for resending verification email
    function startResendCountdown(button, timerElement, countdownElement) {
        let secondsLeft = 60;
        
        // Show timer and hide button
        timerElement.show();
        button.hide();
        
        // Update countdown
        countdownElement.text(secondsLeft);
        
        // Start countdown
        const countdownInterval = setInterval(function() {
            secondsLeft--;
            countdownElement.text(secondsLeft);
            
            if (secondsLeft <= 0) {
                // Stop countdown
                clearInterval(countdownInterval);
                
                // Hide timer and show button
                timerElement.hide();
                button.show().removeClass('disabled');
            }
        }, 1000);
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initDentistRegistration();
        initPatientRegistration();
        initMultiStepForm();
        initFieldValidations();
        initVerificationUI();
        enhancedResendVerification();
    });
})(jQuery);
