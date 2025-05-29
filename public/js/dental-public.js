/**
 * Dental Directory System - Public JavaScript
 * General functionality for the public-facing part of the plugin
 */
(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        initializeTooltips();
        initializeDropdowns();
        initializeFilters();
        initializeMobileMenu();
        initializeModalHandlers();
    });
    
    /**
     * Initialize tooltips
     */
    function initializeTooltips() {
        $('.dental-tooltip').each(function() {
            const $tooltip = $(this);
            
            $tooltip.on('mouseenter', function() {
                $tooltip.addClass('active');
            }).on('mouseleave', function() {
                $tooltip.removeClass('active');
            });
        });
    }
    
    /**
     * Initialize dropdown menus
     */
    function initializeDropdowns() {
        $('.dental-dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            
            const $toggle = $(this);
            const $dropdown = $toggle.next('.dental-dropdown-menu');
            
            // Close other dropdowns
            $('.dental-dropdown-menu').not($dropdown).removeClass('open');
            
            // Toggle this dropdown
            $dropdown.toggleClass('open');
        });
        
        // Close dropdowns when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dental-dropdown').length) {
                $('.dental-dropdown-menu').removeClass('open');
            }
        });
    }
    
    /**
     * Initialize directory filters
     */
    function initializeFilters() {
        const $filters = $('.dental-directory-filters');
        
        if (!$filters.length) return;
        
        // Filter toggle on mobile
        $('.dental-filter-toggle').on('click', function(e) {
            e.preventDefault();
            $filters.toggleClass('open');
        });
        
        // Apply filters
        $filters.on('submit', 'form', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $results = $('.dental-directory-results');
            
            // Show loading state
            $submitBtn.prop('disabled', true).addClass('loading');
            $results.addClass('loading');
            
            // Get form data
            const formData = new FormData($form[0]);
            formData.append('action', 'dental_filter_directory');
            
            // Send AJAX request
            $.ajax({
                url: dental_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Update results
                        $results.html(response.data.html);
                        
                        // Update URL with filter parameters (for browser history)
                        if (window.history && window.history.pushState) {
                            const url = new URL(window.location);
                            
                            // Add each filter parameter to URL
                            $form.find('[name]').each(function() {
                                const name = $(this).attr('name');
                                const value = $(this).val();
                                
                                if (value) {
                                    url.searchParams.set(name, value);
                                } else {
                                    url.searchParams.delete(name);
                                }
                            });
                            
                            window.history.pushState({}, '', url);
                        }
                    }
                    
                    // Reset UI
                    $submitBtn.prop('disabled', false).removeClass('loading');
                    $results.removeClass('loading');
                    $filters.removeClass('open');  // Close on mobile
                },
                error: function() {
                    // Reset UI
                    $submitBtn.prop('disabled', false).removeClass('loading');
                    $results.removeClass('loading');
                }
            });
        });
        
        // Reset filters
        $filters.on('click', '.dental-filter-reset', function(e) {
            e.preventDefault();
            
            const $form = $(this).closest('form');
            
            // Reset form fields
            $form[0].reset();
            
            // Trigger form submit to refresh results
            $form.submit();
        });
    }
    
    /**
     * Initialize mobile menu
     */
    function initializeMobileMenu() {
        $('.dental-mobile-menu-toggle').on('click', function(e) {
            e.preventDefault();
            
            $('.dental-mobile-menu').toggleClass('open');
            $('body').toggleClass('dental-menu-open');
        });
        
        // Close menu when clicking outside
        $(document).on('click', function(e) {
            if ($('.dental-mobile-menu').hasClass('open') && 
                !$(e.target).closest('.dental-mobile-menu').length && 
                !$(e.target).closest('.dental-mobile-menu-toggle').length) {
                $('.dental-mobile-menu').removeClass('open');
                $('body').removeClass('dental-menu-open');
            }
        });
    }
    
    /**
     * Initialize modal handlers
     */
    function initializeModalHandlers() {
        // Open modal
        $(document).on('click', '[data-dental-modal]', function(e) {
            e.preventDefault();
            
            const modalId = $(this).data('dental-modal');
            const $modal = $('#' + modalId);
            
            if ($modal.length) {
                openModal($modal);
            }
        });
        
        // Close modal
        $(document).on('click', '.dental-modal-close, .dental-modal-backdrop', function(e) {
            e.preventDefault();
            
            const $modal = $(this).closest('.dental-modal');
            closeModal($modal);
        });
        
        // Close modal on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal($('.dental-modal.open'));
            }
        });
    }
    
    /**
     * Open modal
     * 
     * @param {jQuery} $modal Modal element
     */
    function openModal($modal) {
        $modal.addClass('open');
        $('body').addClass('dental-modal-open');
    }
    
    /**
     * Close modal
     * 
     * @param {jQuery} $modal Modal element
     */
    function closeModal($modal) {
        $modal.removeClass('open');
        
        // Only remove body class if no other modals are open
        if (!$('.dental-modal.open').length) {
            $('body').removeClass('dental-modal-open');
        }
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
            });

            // Registration forms
            $('#dental-register-dentist-form').on('submit', function(e) {
                e.preventDefault();
                DentalDirectory.submitForm($(this), 'dental_register_user');
            });

            $('#dental-register-patient-form').on('submit', function(e) {
                e.preventDefault();
                DentalDirectory.submitForm($(this), 'dental_register_user');
            });

            // Password recovery form
            $('#dental-recovery-form').on('submit', function(e) {
                e.preventDefault();
                DentalDirectory.submitForm($(this), 'dental_recover_password');
            });
        },

        /**
         * Handle form submission
         * 
         * @param {jQuery} $form - The form jQuery object
         * @param {string} action - The AJAX action
         */
        submitForm: function($form, action) {
            var formData = $form.serialize();
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $form.find('.dental-form-message');
            
            // Add loading state
            $submitBtn.prop('disabled', true).addClass('loading');
            $submitBtn.data('original-text', $submitBtn.text());
            $submitBtn.text('Processing...');
            
            // Remove previous messages
            $message.removeClass('dental-alert-error dental-alert-success').hide();
            
            // Add the action to form data
            formData += '&action=' + action;
            
            // Make the AJAX request
            $.ajax({
                url: dental_ajax.ajax_url,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $message.addClass('dental-alert-success').html(response.data.message).show();
                        
                        // Redirect if provided
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else if (action.includes('register')) {
                            // Reset form on successful registration
                            $form[0].reset();
                        }
                    } else {
                        $message.addClass('dental-alert-error').html(response.data.message).show();
                    }
                },
                error: function() {
                    $message.addClass('dental-alert-error').html('There was an error processing your request. Please try again.').show();
                },
                complete: function() {
                    // Remove loading state
                    $submitBtn.prop('disabled', false).removeClass('loading');
                    $submitBtn.text($submitBtn.data('original-text'));
                }
            });
        },

        /**
         * Setup toggle elements
         */
        setupToggle: function() {
            $('.dental-toggle-trigger').on('click', function(e) {
                e.preventDefault();
                var $target = $($(this).data('target'));
                
                if ($target.length) {
                    $target.slideToggle(200);
                    $(this).toggleClass('active');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DentalDirectory.init();
    });

})(jQuery);
