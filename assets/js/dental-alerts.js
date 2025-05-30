/**
 * Dental Directory - Alerts and Blocks JavaScript
 * 
 * Handles interactions for message limit alerts, warnings, and blocking elements
 */

(function($) {
    'use strict';

    // Dental Alerts Component
    var DentalAlerts = {
        init: function() {
            this.cacheDom();
            this.bindEvents();
            this.initUpgradeModal();
        },

        cacheDom: function() {
            this.$dismissButtons = $('.dental-dismiss-warning');
            this.$upgradeModal = $('#dental-upgrade-modal');
            this.$modalClose = $('.dental-modal-close');
            this.$upgradeButtons = $('.dental-button-premium, .dental-button-warning');
            this.$progressBars = $('.message-limit-progress-bar');
        },

        bindEvents: function() {
            // Dismiss warning button
            this.$dismissButtons.on('click', this.dismissWarning.bind(this));
            
            // Modal close button
            this.$modalClose.on('click', this.closeModal.bind(this));
            
            // Close modal when clicking outside
            this.$upgradeModal.on('click', function(e) {
                if (e.target === this) {
                    DentalAlerts.closeModal();
                }
            });
            
            // Close modal on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && DentalAlerts.$upgradeModal.is(':visible')) {
                    DentalAlerts.closeModal();
                }
            });
            
            // Update progress bars on load
            this.updateProgressBars();
        },

        dismissWarning: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var nonce = $button.data('nonce');
            var $banner = $button.closest('.dental-limit-warning-banner');
            
            // Show loading state
            $button.html('<i class="fas fa-spinner fa-spin"></i>');
            $button.prop('disabled', true);
            
            // Send AJAX request to dismiss
            $.ajax({
                url: dental_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'dental_dismiss_limit_warning',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Hide the banner with animation
                        $banner.slideUp(300, function() {
                            $banner.remove();
                        });
                    } else {
                        // Reset button on error
                        $button.html('<i class="fas fa-times"></i>');
                        $button.prop('disabled', false);
                        console.error('Error dismissing warning:', response.data.message);
                    }
                },
                error: function() {
                    // Reset button on error
                    $button.html('<i class="fas fa-times"></i>');
                    $button.prop('disabled', false);
                    console.error('AJAX error when dismissing warning');
                }
            });
        },

        openUpgradeModal: function() {
            this.$upgradeModal.css('display', 'block');
            
            // Prevent scrolling on body
            $('body').addClass('modal-open');
        },

        closeModal: function() {
            this.$upgradeModal.css('display', 'none');
            
            // Re-enable scrolling
            $('body').removeClass('modal-open');
        },

        initUpgradeModal: function() {
            // Check if we should auto-open the modal (e.g., after limit reached)
            if (window.location.hash === '#show-upgrade-modal') {
                this.openUpgradeModal();
                
                // Clean the URL
                if (history.replaceState) {
                    history.replaceState(null, null, window.location.pathname + window.location.search);
                }
            }
        },

        updateProgressBars: function() {
            this.$progressBars.each(function() {
                var $bar = $(this);
                var count = parseInt($bar.data('count'), 10);
                var limit = parseInt($bar.data('limit'), 10);
                var percentage = (count / limit) * 100;
                
                // Set width based on percentage
                $bar.css('width', percentage + '%');
                
                // Set color class based on percentage
                if (percentage < 60) {
                    $bar.removeClass('progress-warning progress-danger').addClass('progress-good');
                } else if (percentage < 90) {
                    $bar.removeClass('progress-good progress-danger').addClass('progress-warning');
                } else {
                    $bar.removeClass('progress-good progress-warning').addClass('progress-danger');
                }
            });
        },

        // Public method to show the upgrade modal
        showUpgradeModal: function() {
            this.openUpgradeModal();
            return false; // Prevent default link behavior
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DentalAlerts.init();
        
        // Add global reference for external calls
        window.DentalAlerts = DentalAlerts;
    });

})(jQuery);
