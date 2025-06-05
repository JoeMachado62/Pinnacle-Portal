/**
 * Custom JavaScript for Pinnacle Portal
 */

(function($) {
    'use strict';
    
    // Document ready
    $(document).ready(function() {
        // Initialize any custom functionality here
        initDashboardCards();
        initDealStatusFilters();
        initBackToTop();
    });
    
    /**
     * Initialize dashboard cards
     */
    function initDashboardCards() {
        $('.dashboard-card').each(function() {
            var card = $(this);
            
            // Add hover effect
            card.hover(
                function() {
                    $(this).css('box-shadow', '0 4px 8px rgba(0, 0, 0, 0.1)');
                },
                function() {
                    $(this).css('box-shadow', '0 2px 4px rgba(0, 0, 0, 0.1)');
                }
            );
        });
    }
    
    /**
     * Initialize deal status filters
     */
    function initDealStatusFilters() {
        $('.deal-status-filter').on('click', function(e) {
            e.preventDefault();
            
            var status = $(this).data('status');
            
            // Add active class to clicked filter
            $('.deal-status-filter').removeClass('active');
            $(this).addClass('active');
            
            // Show/hide deals based on status
            if (status === 'all') {
                $('.deal-item').show();
            } else {
                $('.deal-item').hide();
                $('.deal-item.status-' + status).show();
            }
        });
    }
    
    /**
     * Initialize back to top button
     */
    function initBackToTop() {
        // Hide the back-to-top button initially
        var backToTopButton = $('#back-to-top');
        backToTopButton.hide();
        
        // Show/hide the button based on scroll position
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                backToTopButton.fadeIn();
            } else {
                backToTopButton.fadeOut();
            }
        });
        
        // Smooth scroll to top when button is clicked
        backToTopButton.on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, 800);
            return false;
        });
    }
    
})(jQuery);
