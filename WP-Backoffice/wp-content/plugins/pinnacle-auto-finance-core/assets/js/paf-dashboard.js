/**
 * Pinnacle Auto Finance - Dashboard JavaScript
 * Handles dashboard interactions and functionality
 */

jQuery(document).ready(function($) {
    
    // Profile accordion toggle
    $('#pafEditProfileBtn').on('click', function(e) {
        e.preventDefault();
        var $content = $('#pafProfileAccordionContent');
        if ($content.is(':visible')) {
            $content.slideUp();
            $(this).text('Edit Profile Details');
        } else {
            $content.slideDown();
            $(this).text('Close Profile Details');
        }
    });

    // Profile edit modal functionality
    $('.paf-profile-edit-link').on('click', function(e) {
        e.preventDefault();
        
        var fieldGroup = $(this).data('fieldgroup');
        var dealerId = $(this).data('dealer-id');
        
        // Populate modal
        openProfileEditModal(fieldGroup, dealerId);
    });

    // Modal close functionality
    $('.paf-modal-close, .paf-modal').on('click', function(e) {
        if (e.target === this) {
            closeProfileEditModal();
        }
    });

    // Prevent modal close when clicking inside modal content
    $('.paf-modal-content').on('click', function(e) {
        e.stopPropagation();
    });

    // Profile edit form submission
    $('#pafProfileEditForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'paf_update_profile_field');
        
        // Show loading state
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        $submitBtn.text('Saving...').prop('disabled', true);
        
        $.ajax({
            url: paf_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showModalMessage('Profile updated successfully!', 'success');
                    // Reload page after a short delay to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showModalMessage(response.data || 'Update failed. Please try again.', 'error');
                    $submitBtn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showModalMessage('Network error. Please try again.', 'error');
                $submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });

    // Table sorting functionality
    $('.paf-table th[data-sort]').on('click', function() {
        var $table = $(this).closest('.paf-table');
        var column = $(this).data('sort');
        var $tbody = $table.find('tbody');
        var rows = $tbody.find('tr').toArray();
        var isAsc = $(this).hasClass('sort-asc');
        
        // Remove sort classes from all headers
        $table.find('th').removeClass('sort-asc sort-desc');
        
        // Add appropriate sort class
        if (isAsc) {
            $(this).addClass('sort-desc');
        } else {
            $(this).addClass('sort-asc');
        }
        
        // Sort rows
        rows.sort(function(a, b) {
            var aText = $(a).find('td').eq($(this).index()).text().trim();
            var bText = $(b).find('td').eq($(this).index()).text().trim();
            
            // Try to parse as numbers for numeric sorting
            var aNum = parseFloat(aText);
            var bNum = parseFloat(bText);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAsc ? bNum - aNum : aNum - bNum;
            } else {
                // String comparison
                if (isAsc) {
                    return bText.localeCompare(aText);
                } else {
                    return aText.localeCompare(bText);
                }
            }
        }.bind(this));
        
        // Re-append sorted rows
        $tbody.empty().append(rows);
    });

    /**
     * Open the profile edit modal
     */
    function openProfileEditModal(fieldGroup, dealerId) {
        var $modal = $('#pafProfileEditModal');
        var $title = $('#pafModalTitle');
        var $instructions = $('#pafModalInstructions');
        var $fieldsContainer = $('#pafModalFieldsContainer');
        var $dealerIdInput = $('#pafModalDealerId');
        
        // Set basic modal data
        $title.text('Edit ' + fieldGroup.label);
        $instructions.text(fieldGroup.instructions || '');
        $dealerIdInput.val(dealerId);
        
        // Clear previous fields
        $fieldsContainer.empty();
        
        // Generate form fields based on field group configuration
        if (fieldGroup.meta_key === 'full_address_group') {
            // Special handling for address group
            var addressHtml = '<div class="paf-modal-address-group">';
            
            fieldGroup.fields.forEach(function(field) {
                var currentValue = ''; // You'd need to pass current values or fetch via AJAX
                addressHtml += '<div class="paf-modal-field-group">';
                addressHtml += '<label for="modal_' + field._meta_key + '">' + field.label + '</label>';
                addressHtml += '<input type="' + (field.type || 'text') + '" ';
                addressHtml += 'id="modal_' + field._meta_key + '" ';
                addressHtml += 'name="' + field._meta_key + '" ';
                addressHtml += 'placeholder="' + field.placeholder + '" ';
                addressHtml += 'value="' + currentValue + '" ';
                if (field.maxlength) addressHtml += 'maxlength="' + field.maxlength + '" ';
                if (field.required) addressHtml += 'required ';
                addressHtml += '>';
                addressHtml += '</div>';
            });
            
            addressHtml += '</div>';
            $fieldsContainer.html(addressHtml);
        } else {
            // Single field
            var fieldHtml = '<div class="paf-modal-field-group">';
            fieldHtml += '<label for="modal_' + fieldGroup.meta_key + '">' + fieldGroup.label + '</label>';
            fieldHtml += '<input type="' + (fieldGroup.type || 'text') + '" ';
            fieldHtml += 'id="modal_' + fieldGroup.meta_key + '" ';
            fieldHtml += 'name="' + fieldGroup.meta_key + '" ';
            fieldHtml += 'value="" '; // Current value would be populated here
            if (fieldGroup.required) fieldHtml += 'required ';
            fieldHtml += '>';
            fieldHtml += '</div>';
            $fieldsContainer.html(fieldHtml);
        }
        
        // Show modal
        $modal.show();
    }

    /**
     * Close the profile edit modal
     */
    function closeProfileEditModal() {
        $('#pafProfileEditModal').hide();
        $('#pafModalMessage').hide();
    }

    /**
     * Show message in modal
     */
    function showModalMessage(message, type) {
        var $messageDiv = $('#pafModalMessage');
        $messageDiv.removeClass('success error').addClass(type);
        $messageDiv.text(message).show();
    }

    // Initialize any tooltips or other interactive elements
    initializeDashboardInteractions();

    function initializeDashboardInteractions() {
        // Add hover effects for cards
        $('.paf-ad-card').hover(
            function() {
                $(this).addClass('paf-card-hover');
            },
            function() {
                $(this).removeClass('paf-card-hover');
            }
        );

        // Initialize any other dashboard-specific interactions
        console.log('Pinnacle Auto Finance Dashboard initialized');
    }
});

// Global functions that might be called from other scripts
window.PAF_Dashboard = {
    refreshPipeline: function() {
        // Function to refresh the pipeline section via AJAX
        jQuery.ajax({
            url: paf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'paf_refresh_pipeline',
                nonce: paf_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    jQuery('.paf-dashboard-pipeline-section').html(response.data.html);
                }
            }
        });
    },

    showNotification: function(message, type) {
        // Function to show notifications
        var notificationHtml = '<div class="paf-notification paf-notification-' + type + '">' + message + '</div>';
        jQuery('body').append(notificationHtml);
        
        setTimeout(function() {
            jQuery('.paf-notification').fadeOut(function() {
                jQuery(this).remove();
            });
        }, 5000);
    }
};
