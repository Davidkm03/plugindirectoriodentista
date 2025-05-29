/**
 * Profile Management JavaScript
 * 
 * Handles profile forms interaction, image uploads and tabs navigation
 */
(function($) {
    'use strict';

    /**
     * Initialize profile form tabs
     */
    function initProfileTabs() {
        $('.dental-tabs-nav a').on('click', function(e) {
            e.preventDefault();
            
            // Get target panel
            const target = $(this).attr('href');
            
            // Update active tab
            $('.dental-tabs-nav li').removeClass('active');
            $(this).parent().addClass('active');
            
            // Show target panel
            $('.dental-tab-panel').removeClass('active');
            $(target).addClass('active');
        });
        
        // Handle next/prev buttons
        $('.dental-btn-next').on('click', function() {
            const currentPanel = $(this).closest('.dental-tab-panel');
            const nextPanel = currentPanel.next('.dental-tab-panel');
            
            if (nextPanel.length) {
                // Move to next panel
                currentPanel.removeClass('active');
                nextPanel.addClass('active');
                
                // Update nav
                const index = $('.dental-tab-panel').index(nextPanel);
                $('.dental-tabs-nav li').removeClass('active');
                $('.dental-tabs-nav li').eq(index).addClass('active');
                
                // Scroll to top
                $('html, body').animate({
                    scrollTop: $('.dental-tabs').offset().top - 50
                }, 300);
            }
        });
        
        $('.dental-btn-prev').on('click', function() {
            const currentPanel = $(this).closest('.dental-tab-panel');
            const prevPanel = currentPanel.prev('.dental-tab-panel');
            
            if (prevPanel.length) {
                // Move to previous panel
                currentPanel.removeClass('active');
                prevPanel.addClass('active');
                
                // Update nav
                const index = $('.dental-tab-panel').index(prevPanel);
                $('.dental-tabs-nav li').removeClass('active');
                $('.dental-tabs-nav li').eq(index).addClass('active');
                
                // Scroll to top
                $('html, body').animate({
                    scrollTop: $('.dental-tabs').offset().top - 50
                }, 300);
            }
        });
    }
    
    /**
     * Initialize dentist profile form
     */
    function initDentistProfileForm() {
        const form = $('#dental-dentist-profile-form');
        
        if (!form.length) {
            return;
        }
        
        form.on('submit', function(e) {
            e.preventDefault();
            
            // Show loading
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.text();
            submitBtn.prop('disabled', true).text(dental_vars.texts.processing);
            
            // Hide any previous messages
            $('.dental-form-message').hide().removeClass('dental-alert-error dental-alert-success');
            
            // Get form data
            const formData = new FormData(this);
            formData.append('action', 'dental_save_dentist_profile');
            formData.append('security', $('#profile_nonce').val());
            
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
                            
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $('.dental-form-message').offset().top - 50
                        }, 300);
                    } else {
                        // Show error message
                        $('.dental-form-message')
                            .addClass('dental-alert-error')
                            .html(response.data.message)
                            .show();
                            
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $('.dental-form-message').offset().top - 50
                        }, 300);
                    }
                },
                error: function() {
                    // Show error message
                    $('.dental-form-message')
                        .addClass('dental-alert-error')
                        .html(dental_vars.texts.server_error)
                        .show();
                        
                    // Scroll to message
                    $('html, body').animate({
                        scrollTop: $('.dental-form-message').offset().top - 50
                    }, 300);
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });
    }
    
    /**
     * Initialize patient profile form
     */
    function initPatientProfileForm() {
        const form = $('#dental-patient-profile-form');
        
        if (!form.length) {
            return;
        }
        
        form.on('submit', function(e) {
            e.preventDefault();
            
            // Show loading
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.text();
            submitBtn.prop('disabled', true).text(dental_vars.texts.processing);
            
            // Hide any previous messages
            $('.dental-form-message').hide().removeClass('dental-alert-error dental-alert-success');
            
            // Get form data
            const formData = new FormData(this);
            formData.append('action', 'dental_save_patient_profile');
            formData.append('security', $('#profile_nonce').val());
            
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
                            
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $('.dental-form-message').offset().top - 50
                        }, 300);
                    } else {
                        // Show error message
                        $('.dental-form-message')
                            .addClass('dental-alert-error')
                            .html(response.data.message)
                            .show();
                            
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $('.dental-form-message').offset().top - 50
                        }, 300);
                    }
                },
                error: function() {
                    // Show error message
                    $('.dental-form-message')
                        .addClass('dental-alert-error')
                        .html(dental_vars.texts.server_error)
                        .show();
                        
                    // Scroll to message
                    $('html, body').animate({
                        scrollTop: $('.dental-form-message').offset().top - 50
                    }, 300);
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });
    }
    
    /**
     * Initialize profile image upload
     */
    function initProfileImageUpload() {
        // Profile image upload button
        $('#profile_image_upload_btn').on('click', function() {
            $('#profile_image_upload').trigger('click');
        });
        
        // Cover image upload button
        $('#cover_image_upload_btn').on('click', function() {
            $('#cover_image_upload').trigger('click');
        });
        
        // Gallery image upload button
        $('#gallery_image_upload_btn').on('click', function() {
            $('#gallery_image_upload').trigger('click');
        });
        
        // Handle profile image upload
        $('#profile_image_upload').on('change', function() {
            if (this.files && this.files[0]) {
                uploadProfileImage(this.files[0], 'profile');
            }
        });
        
        // Handle cover image upload
        $('#cover_image_upload').on('change', function() {
            if (this.files && this.files[0]) {
                uploadProfileImage(this.files[0], 'cover');
            }
        });
        
        // Handle gallery image upload
        $('#gallery_image_upload').on('change', function() {
            if (this.files && this.files[0]) {
                uploadGalleryImage(this.files[0]);
            }
        });
        
        // Handle profile image delete
        $('#profile_image_delete_btn').on('click', function() {
            deleteProfileImage('profile');
        });
        
        // Handle cover image delete
        $('#cover_image_delete_btn').on('click', function() {
            deleteProfileImage('cover');
        });
        
        // Handle gallery image delete
        $(document).on('click', '.dental-gallery-delete', function() {
            const item = $(this).closest('.dental-gallery-item');
            const id = item.data('id');
            
            if (id) {
                deleteGalleryImage(id, item);
            }
        });
    }
    
    /**
     * Upload profile image
     * 
     * @param {File} file Image file
     * @param {string} type Image type (profile, cover)
     */
    function uploadProfileImage(file, type) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'dental_upload_profile_image');
        formData.append('security', dental_vars.upload_nonce);
        formData.append('profile_image', file);
        formData.append('type', type);
        
        // Show loading
        const uploadBtn = type === 'profile' ? $('#profile_image_upload_btn') : $('#cover_image_upload_btn');
        const originalBtnText = uploadBtn.text();
        uploadBtn.prop('disabled', true).text(dental_vars.texts.processing);
        
        // Send AJAX request
        $.ajax({
            url: dental_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Update image preview
                    const container = type === 'profile' ? $('.dental-profile-image') : $('.dental-cover-image');
                    container.html('<img src="' + response.data.url + '" alt="">');
                    
                    // Add delete button if not exists
                    const deleteBtn = type === 'profile' ? $('#profile_image_delete_btn') : $('#cover_image_delete_btn');
                    if (!deleteBtn.length) {
                        const deleteHtml = '<button type="button" id="' + type + '_image_delete_btn" class="dental-btn dental-btn-small dental-btn-danger">' + dental_vars.texts.delete + '</button>';
                        uploadBtn.after(deleteHtml);
                        
                        // Attach event handler
                        $('#' + type + '_image_delete_btn').on('click', function() {
                            deleteProfileImage(type);
                        });
                    }
                    
                    // Show success message
                    $('.dental-form-message')
                        .addClass('dental-alert-success')
                        .html(response.data.message)
                        .show();
                        
                    // Hide message after 3 seconds
                    setTimeout(function() {
                        $('.dental-form-message').fadeOut();
                    }, 3000);
                } else {
                    // Show error message
                    $('.dental-form-message')
                        .addClass('dental-alert-error')
                        .html(response.data.message)
                        .show();
                }
            },
            error: function() {
                // Show error message
                $('.dental-form-message')
                    .addClass('dental-alert-error')
                    .html(dental_vars.texts.server_error)
                    .show();
            },
            complete: function() {
                // Re-enable upload button
                uploadBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    }
    
    /**
     * Upload gallery image
     * 
     * @param {File} file Image file
     */
    function uploadGalleryImage(file) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'dental_upload_gallery_image');
        formData.append('security', dental_vars.upload_nonce);
        formData.append('gallery_image', file);
        
        // Show loading
        const uploadBtn = $('#gallery_image_upload_btn');
        const originalBtnText = uploadBtn.text();
        uploadBtn.prop('disabled', true).text(dental_vars.texts.processing);
        
        // Send AJAX request
        $.ajax({
            url: dental_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Add new image to gallery
                    const galleryHtml = '<div class="dental-gallery-item" data-id="' + response.data.id + '">' +
                        '<div class="dental-gallery-image">' +
                        '<img src="' + response.data.url + '" alt="">' +
                        '</div>' +
                        '<div class="dental-gallery-actions">' +
                        '<button type="button" class="dental-btn dental-btn-small dental-btn-danger dental-gallery-delete">' + dental_vars.texts.delete + '</button>' +
                        '</div>' +
                        '</div>';
                        
                    // Insert before add button
                    $('.dental-gallery-add').before(galleryHtml);
                    
                    // Show success message
                    $('.dental-form-message')
                        .addClass('dental-alert-success')
                        .html(response.data.message)
                        .show();
                        
                    // Hide message after 3 seconds
                    setTimeout(function() {
                        $('.dental-form-message').fadeOut();
                    }, 3000);
                } else {
                    // Show error message
                    $('.dental-form-message')
                        .addClass('dental-alert-error')
                        .html(response.data.message)
                        .show();
                }
            },
            error: function() {
                // Show error message
                $('.dental-form-message')
                    .addClass('dental-alert-error')
                    .html(dental_vars.texts.server_error)
                    .show();
            },
            complete: function() {
                // Re-enable upload button
                uploadBtn.prop('disabled', false).text(originalBtnText);
                
                // Reset file input
                $('#gallery_image_upload').val('');
            }
        });
    }
    
    /**
     * Delete profile image
     * 
     * @param {string} type Image type (profile, cover)
     */
    function deleteProfileImage(type) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'dental_delete_profile_image');
        formData.append('security', dental_vars.upload_nonce);
        formData.append('type', type);
        
        // Show loading
        const deleteBtn = type === 'profile' ? $('#profile_image_delete_btn') : $('#cover_image_delete_btn');
        const originalBtnText = deleteBtn.text();
        deleteBtn.prop('disabled', true).text(dental_vars.texts.processing);
        
        // Send AJAX request
        $.ajax({
            url: dental_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Update image preview
                    const container = type === 'profile' ? $('.dental-profile-image') : $('.dental-cover-image');
                    const placeholder = '<div class="dental-' + type + '-image-placeholder">' +
                        '<i class="dashicons dashicons-' + (type === 'profile' ? 'admin-users' : 'format-image') + '"></i>' +
                        '</div>';
                    container.html(placeholder);
                    
                    // Remove delete button
                    deleteBtn.remove();
                    
                    // Show success message
                    $('.dental-form-message')
                        .addClass('dental-alert-success')
                        .html(response.data.message)
                        .show();
                        
                    // Hide message after 3 seconds
                    setTimeout(function() {
                        $('.dental-form-message').fadeOut();
                    }, 3000);
                } else {
                    // Show error message
                    $('.dental-form-message')
                        .addClass('dental-alert-error')
                        .html(response.data.message)
                        .show();
                        
                    // Re-enable delete button
                    deleteBtn.prop('disabled', false).text(originalBtnText);
                }
            },
            error: function() {
                // Show error message
                $('.dental-form-message')
                    .addClass('dental-alert-error')
                    .html(dental_vars.texts.server_error)
                    .show();
                    
                // Re-enable delete button
                deleteBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    }
    
    /**
     * Delete gallery image
     * 
     * @param {number} id Attachment ID
     * @param {jQuery} item Gallery item element
     */
    function deleteGalleryImage(id, item) {
        // Create form data
        const formData = new FormData();
        formData.append('action', 'dental_delete_gallery_image');
        formData.append('security', dental_vars.upload_nonce);
        formData.append('attachment_id', id);
        
        // Show loading
        const deleteBtn = item.find('.dental-gallery-delete');
        const originalBtnText = deleteBtn.text();
        deleteBtn.prop('disabled', true).text(dental_vars.texts.processing);
        
        // Send AJAX request
        $.ajax({
            url: dental_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Remove gallery item
                    item.fadeOut(300, function() {
                        item.remove();
                    });
                    
                    // Show success message
                    $('.dental-form-message')
                        .addClass('dental-alert-success')
                        .html(response.data.message)
                        .show();
                        
                    // Hide message after 3 seconds
                    setTimeout(function() {
                        $('.dental-form-message').fadeOut();
                    }, 3000);
                } else {
                    // Show error message
                    $('.dental-form-message')
                        .addClass('dental-alert-error')
                        .html(response.data.message)
                        .show();
                        
                    // Re-enable delete button
                    deleteBtn.prop('disabled', false).text(originalBtnText);
                }
            },
            error: function() {
                // Show error message
                $('.dental-form-message')
                    .addClass('dental-alert-error')
                    .html(dental_vars.texts.server_error)
                    .show();
                    
                // Re-enable delete button
                deleteBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initProfileTabs();
        initDentistProfileForm();
        initPatientProfileForm();
        initProfileImageUpload();
    });

})(jQuery);
