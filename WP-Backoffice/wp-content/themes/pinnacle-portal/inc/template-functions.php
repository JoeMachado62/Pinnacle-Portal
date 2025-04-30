<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 */

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function pinnacle_portal_pingback_header() {
    if (is_singular() && pings_open()) {
        printf('<link rel="pingback" href="%s">', esc_url(get_bloginfo('pingback_url')));
    }
}
add_action('wp_head', 'pinnacle_portal_pingback_header');

/**
 * Add custom classes to the body tag
 */
function pinnacle_portal_body_classes($classes) {
    // Add a class if we're viewing the admin dashboard
    if (is_admin()) {
        $classes[] = 'pinnacle-admin';
    }
    
    // Add a class based on the post type
    if (is_singular()) {
        global $post;
        $classes[] = 'singular-' . $post->post_type;
    }
    
    return $classes;
}
add_filter('body_class', 'pinnacle_portal_body_classes');

/**
 * Custom template tags for this theme
 */

/**
 * Display a custom logo
 */
function pinnacle_portal_custom_logo() {
    if (function_exists('the_custom_logo') && has_custom_logo()) {
        the_custom_logo();
    } else {
        echo '<a href="' . esc_url(home_url('/')) . '" class="custom-logo-link" rel="home">';
        echo '<span class="site-title">' . get_bloginfo('name') . '</span>';
        echo '</a>';
    }
}

/**
 * Display post meta information
 */
function pinnacle_portal_post_meta() {
    // Hide category and tag text for pages
    if ('post' === get_post_type()) {
        // Posted on
        $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
        if (get_the_time('U') !== get_the_modified_time('U')) {
            $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
        }

        $time_string = sprintf(
            $time_string,
            esc_attr(get_the_date(DATE_W3C)),
            esc_html(get_the_date()),
            esc_attr(get_the_modified_date(DATE_W3C)),
            esc_html(get_the_modified_date())
        );

        $posted_on = sprintf(
            /* translators: %s: post date. */
            esc_html_x('Posted on %s', 'post date', 'pinnacle-portal'),
            '<a href="' . esc_url(get_permalink()) . '" rel="bookmark">' . $time_string . '</a>'
        );

        // Posted by
        $byline = sprintf(
            /* translators: %s: post author. */
            esc_html_x('by %s', 'post author', 'pinnacle-portal'),
            '<span class="author vcard"><a class="url fn n" href="' . esc_url(get_author_posts_url(get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a></span>'
        );

        echo '<span class="posted-on">' . $posted_on . '</span> <span class="byline"> ' . $byline . '</span>';
    }
}

/**
 * Display post categories
 */
function pinnacle_portal_post_categories() {
    // Hide category and tag text for pages
    if ('post' === get_post_type()) {
        /* translators: used between list items, there is a space after the comma */
        $categories_list = get_the_category_list(esc_html__(', ', 'pinnacle-portal'));
        if ($categories_list) {
            /* translators: 1: list of categories. */
            printf('<span class="cat-links">' . esc_html__('Posted in %1$s', 'pinnacle-portal') . '</span>', $categories_list);
        }
    }
}

/**
 * Display post tags
 */
function pinnacle_portal_post_tags() {
    // Hide category and tag text for pages
    if ('post' === get_post_type()) {
        /* translators: used between list items, there is a space after the comma */
        $tags_list = get_the_tag_list('', esc_html_x(', ', 'list item separator', 'pinnacle-portal'));
        if ($tags_list) {
            /* translators: 1: list of tags. */
            printf('<span class="tags-links">' . esc_html__('Tagged %1$s', 'pinnacle-portal') . '</span>', $tags_list);
        }
    }
}

/**
 * Display deal status
 */
function pinnacle_portal_deal_status() {
    if ('deal' === get_post_type()) {
        $terms = get_the_terms(get_the_ID(), 'deal_status');
        if ($terms && !is_wp_error($terms)) {
            echo '<div class="deal-status">';
            echo '<span class="deal-status-label">' . esc_html__('Status:', 'pinnacle-portal') . '</span> ';
            
            foreach ($terms as $term) {
                $status_class = 'status-' . $term->slug;
                echo '<span class="deal-status-value ' . esc_attr($status_class) . '">' . esc_html($term->name) . '</span>';
            }
            
            echo '</div>';
        }
    }
}

/**
 * Display dealer information
 */
function pinnacle_portal_dealer_info() {
    if ('dealer' === get_post_type()) {
        // Display dealer contact information if ACF is active
        if (function_exists('get_field')) {
            $phone = get_field('dealer_phone');
            $email = get_field('dealer_email');
            $address = get_field('dealer_address');
            
            if ($phone || $email || $address) {
                echo '<div class="dealer-contact-info">';
                echo '<h3>' . esc_html__('Contact Information', 'pinnacle-portal') . '</h3>';
                
                if ($phone) {
                    echo '<p class="dealer-phone"><strong>' . esc_html__('Phone:', 'pinnacle-portal') . '</strong> ' . esc_html($phone) . '</p>';
                }
                
                if ($email) {
                    echo '<p class="dealer-email"><strong>' . esc_html__('Email:', 'pinnacle-portal') . '</strong> <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></p>';
                }
                
                if ($address) {
                    echo '<p class="dealer-address"><strong>' . esc_html__('Address:', 'pinnacle-portal') . '</strong> ' . esc_html($address) . '</p>';
                }
                
                echo '</div>';
            }
        }
    }
}

/**
 * Display related deals for a dealer
 */
function pinnacle_portal_related_deals() {
    if ('dealer' === get_post_type()) {
        $dealer_id = get_the_ID();
        
        // Query related deals if ACF is active
        if (function_exists('get_field')) {
            $args = array(
                'post_type' => 'deal',
                'posts_per_page' => 5,
                'meta_query' => array(
                    array(
                        'key' => 'related_dealer',
                        'value' => $dealer_id,
                        'compare' => '=',
                    ),
                ),
            );
            
            $related_deals = new WP_Query($args);
            
            if ($related_deals->have_posts()) {
                echo '<div class="related-deals">';
                echo '<h3>' . esc_html__('Recent Deals', 'pinnacle-portal') . '</h3>';
                echo '<ul class="deal-list">';
                
                while ($related_deals->have_posts()) {
                    $related_deals->the_post();
                    echo '<li class="deal-item">';
                    echo '<a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a>';
                    
                    // Display deal status
                    $terms = get_the_terms(get_the_ID(), 'deal_status');
                    if ($terms && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            $status_class = 'status-' . $term->slug;
                            echo ' <span class="deal-status-badge ' . esc_attr($status_class) . '">' . esc_html($term->name) . '</span>';
                        }
                    }
                    
                    echo '</li>';
                }
                
                echo '</ul>';
                echo '<p><a href="' . esc_url(admin_url('edit.php?post_type=deal')) . '" class="button">' . esc_html__('View All Deals', 'pinnacle-portal') . '</a></p>';
                echo '</div>';
                
                wp_reset_postdata();
            }
        }
    }
}

/**
 * Display a dashboard card
 */
function pinnacle_portal_dashboard_card($title, $content, $footer = '', $class = '') {
    echo '<div class="dashboard-card ' . esc_attr($class) . '">';
    echo '<div class="dashboard-card-header">';
    echo '<h3 class="dashboard-card-title">' . esc_html($title) . '</h3>';
    echo '</div>';
    echo '<div class="dashboard-card-content">';
    echo $content;
    echo '</div>';
    
    if ($footer) {
        echo '<div class="dashboard-card-footer">';
        echo $footer;
        echo '</div>';
    }
    
    echo '</div>';
}
